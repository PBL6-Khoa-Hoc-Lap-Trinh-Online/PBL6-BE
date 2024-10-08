<?php
namespace App\Services;

use App\Http\Requests\RequestUserBuyProduct;
use App\Http\Requests\RequestUserCheckoutCart;
use App\Jobs\SendMailNotify;
use App\Models\Cart;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Repositories\OrderInterface;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Throwable;

class OrderService{
    use APIResponse;
    protected OrderInterface $orderRepository;
    public function __construct(OrderInterface $orderRepository){
        $this->orderRepository = $orderRepository;
    }
    public function buyNow(RequestUserBuyProduct $request){
        DB::beginTransaction();
        try{
            $product = Product::find($request->product_id);
            $user = auth('user_api')->user();
            if(empty($product)){
                return $this->responseError('Product not found!',404);
            }
            if($product->product_quantity < $request->quantity){
                return $this->responseError('Số lượng sản phẩm trong kho không đủ!',400);
            }
            $total_amount=$product->product_price*$request->quantity;
            $data = [
                'user_id' => $user->user_id,
                'receiver_address_id' => $request->receiver_address_id,
                'payment_id' => $request->payment_id,
                'delivery_id' => $request->delivery_id,
                'order_total_amount' => $total_amount,
            ];
            $order = $this->orderRepository->create($data);
            $detail = [
                'order_id' => $order->order_id,
                'product_id' => $product->product_id,
                'order_quantity' => $request->quantity,
                'order_price' => $product->product_price,
                'order_total_price' => $product->product_price*$request->quantity,
            ];
            $order_detail = OrderDetail::create($detail);
            $product->update([
                'product_quantity' => $product->product_quantity - $request->quantity,
                'product_sold' => $product->product_sold + $request->quantity,
            ]);
            $data = [
                'order' => $order,
                'order_detail' => $order_detail,
            ];
            $email_user = $user->email;
            //Send email notify
            Log::info("Thêm jobs vào hàng đợi, Email:$email_user");
            $content = '
            <p>Đặt hàng thành công! Đơn hàng của bạn là:</p>
            <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <tr>
                    <th colspan="2">Thông tin đơn hàng</th>
                </tr>
                <tr>
                    <td>Mã đơn hàng</td>
                    <td>' . $order->order_id . '</td>
                </tr>
                <tr>
                    <td>Tổng tiền</td>
                    <td>' . number_format($order->order_total_amount, 0, ',', '.') . ' VND</td>
                </tr>
                <tr>
                    <td>Ngày tạo</td>
                    <td>' . $order->order_created_at . '</td>
                </tr>
                <tr>
                    <th colspan="2">Chi tiết đơn hàng</th>
                </tr>
                <tr>
                    <td>Mã sản phẩm</td>
                    <td>' . $order_detail->product_id . '</td>
                </tr>
                <tr>
                    <td>Số lượng</td>
                    <td>' . $order_detail->order_quantity . '</td>
                </tr>
                <tr>
                    <td>Giá</td>
                    <td>' . number_format($order_detail->order_price, 0, ',', '.') . ' VND</td>
                </tr>
                <tr>
                    <td>Tổng giá</td>
                    <td>' . number_format($order_detail->order_total_price, 0, ',', '.') . ' VND</td>
                </tr>
            </table>';
            Queue::push(new SendMailNotify($email_user, $content));
            DB::commit();
            return $this->responseSuccessWithData($data,'Đặt hàng thành công!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function checkoutCart(RequestUserCheckoutCart $request){
        DB::beginTransaction();
        try{
            $user = auth('user_api')->user();
            $ids_cart = $request->ids_cart;
            $carts=Cart::whereIn('cart_id',$ids_cart)->get();
            if($carts->isEmpty()){
                return $this->responseError('Giỏ hàng rỗng!',404);
            }
            $total_amount=0;
            $order_details=[];
            $data=[
                'user_id' => $user->user_id,
                'receiver_address_id' => $request->receiver_address_id,
                'payment_id' => $request->payment_id,
                'delivery_id' => $request->delivery_id,
                'order_total_amount' => $total_amount,
            ];
            $order = $this->orderRepository->create($data);
            foreach($carts as $cart){
                $product = Product::find($cart->product_id);
                if(empty($product)){
                    return $this->responseError('Product not found!',404);
                }
                if($product->product_quantity < $cart->cart_quantity){
                    return $this->responseError('Số lượng sản phẩm trong kho không đủ!',400);
                }
                $order_detail =[ 
                    'order_id' => $order->order_id,
                    'product_id' => $product->product_id,
                    'order_quantity' => $cart->cart_quantity,
                    'order_price' => $product->product_price,
                    'order_total_price' => $product->product_price*$cart->cart_quantity,
                ];
                $order_detail = OrderDetail::create($order_detail);
                $order_details[]=$order_detail;
                $total_amount +=$order_detail['order_total_price'];
                $product->update([
                    'product_quantity' => $product->product_quantity - $cart->cart_quantity,
                    'product_sold' => $product->product_sold + $cart->cart_quantity,
                ]);
            }
            $order->update([
                'order_total_amount' => $total_amount,
            ]);
            Cart::whereIn('cart_id',$ids_cart)->delete();
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            $email_user = $user->email;
            //Send email notify
            Log::info("Thêm jobs vào hàng đợi, Email:$email_user");
            $content = '
            <p>Đặt hàng thành công! Đơn hàng của bạn là:</p>
            <table border="1" cellpadding="5" cellspacing="0" style="border-collapse: collapse; width: 100%;">
                <tr>
                    <th colspan="2">Thông tin đơn hàng</th>
                </tr>
                <tr>
                    <td>Mã đơn hàng</td>
                    <td>' . $order->order_id . '</td>
                </tr>
                <tr>
                    <td>Tổng tiền</td>
                    <td>' . number_format($order->order_total_amount, 0, ',', '.') . ' VND</td>
                </tr>
                <tr>
                    <td>Ngày tạo</td>
                    <td>' . $order->order_created_at . '</td>
                </tr>
                <tr>
                    <th colspan="2">Chi tiết đơn hàng</th>';
            foreach($order_details as $order_detail){
                $content .= '
                <tr>
                    <td>Mã sản phẩm</td>
                    <td>' . $order_detail->product_id . '</td>
                </tr>
                <tr>
                    <td>Số lượng</td>
                    <td>' . $order_detail->order_quantity . '</td>
                </tr>
                <tr>
                    <td>Giá</td>
                    <td>' . number_format($order_detail->order_price, 0, ',', '.') . ' VND</td>
                </tr>
                <tr>
                    <td>Tổng giá</td>
                    <td>' . number_format($order_detail->order_total_price, 0, ',', '.') . ' VND</td>
                </tr>';
            }
            $content .= '</table>';
            Queue::push(new SendMailNotify($email_user, $content));
            DB::commit();
            return $this->responseSuccessWithData($data,'Đặt hàng thành công!',200);

        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }

    }

    public function getOrderDetail(Request $request, $id){
        try{
            $user = auth('user_api')->user();
            $order = Order::where('order_id',$id)->where('user_id',$user->user_id)->first();
            if(empty($order)){
                return $this->responseError('Order not found!',404);
            }
            $order_details = OrderDetail::where('order_id',$id)->get();
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            return $this->responseSuccessWithData($data,'Lấy thông tin đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
}
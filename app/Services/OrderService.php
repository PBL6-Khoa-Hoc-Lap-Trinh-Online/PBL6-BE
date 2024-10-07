<?php
namespace App\Services;

use App\Http\Requests\RequestUserBuyProduct;
use App\Jobs\SendMailNotify;
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
            $user = auth('user_api')->user();
            if(empty($user)){
                return $this->responseError('User not found!',404);
            }
            $data = [
                'user_id' => auth('user_api')->user()->user_id,
                'receiver_address_id' => $request->receiver_address_id,
                'payment_id' => $request->payment_id,
                'delivery_id' => $request->delivery_id,
                'order_details' => $request->order_details,
                'order_total_amount' => 0,
            ];

            $order = Order::create($data);
            $orderTotal = 0;
            $orderDetails = [];
            foreach($request->order_details as $orderDetail){
                $product = Product::find($orderDetail['product_id']);
                $productPrice = $product->product_price;
                $product_price_discount = $product->product_price_discount == null ? 0.0 : $product->product_price_discount;
                if($product->product_quantity < $orderDetail['order_quantity']){
                    return $this->responseError('Số lượng sản phẩm trong kho không đủ!');
                }
                if($product_price_discount != 0.0){
                    $productPrice = $product_price_discount;
                }
                $totalProduct = $orderDetail['order_quantity'] * $productPrice;
                // dd($totalProduct);
                $detail = [
                    'order_id' => $order->order_id,
                    'product_id' => $orderDetail['product_id'],
                    'order_quantity' => $orderDetail['order_quantity'],
                    'order_price' => $productPrice,
                    'order_price_discount' => $product_price_discount,
                    'order_total_price' =>  $totalProduct
                ];
                $order_detail = OrderDetail::create($detail);
                $orderTotal += $detail['order_total_price'];
                $orderDetails[] = $order_detail;
                $product->update([
                    'product_quantity' => $product->product_quantity - $orderDetail['order_quantity'],
                    'product_sold' => $product->product_sold + $orderDetail['order_quantity'],
                ]);
            }
            $delivery = Delivery::find($request->delivery_id);
            $orderTotal += $delivery->delivery_fee;
            $order->update(['order_total_amount' => $orderTotal]);
            //sau khi nhấn mua hàng thì số lượng sản phẩm trong giỏ hàng sẽ bị xóa hết
            // DB::table('carts')->where('user_id',auth()->user()->id)->delete();

            DB::commit();
            $data = [
                'order' => $order,
                'order_details' => $orderDetails,
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
                </tr>';

                    foreach ($orderDetails as $detail) {
                        $content .= '
                <tr>
                    <td>Mã sản phẩm</td>
                    <td>' . $detail->product_id . '</td>
                </tr>
                <tr>
                    <td>Số lượng</td>
                    <td>' . $detail->order_quantity . '</td>
                </tr>
                <tr>
                    <td>Giá</td>
                    <td>' . number_format($detail->order_price, 0, ',', '.') . ' VND</td>
                </tr>
                <tr>
                    <td>Tổng giá</td>
                    <td>' . number_format($detail->order_total_price, 0, ',', '.') . ' VND</td>
                </tr>';
                    }

            $content .= '</table>';
            Queue::push(new SendMailNotify($email_user, $content));
            return $this->responseSuccessWithData($data,'Order successfully!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}
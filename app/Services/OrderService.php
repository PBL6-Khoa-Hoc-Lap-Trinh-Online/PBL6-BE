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
use App\Models\User;
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
            // $order_details = OrderDetail::where('order_id',$id)->get();
            $order_details = $this->orderRepository->getDetailOrder($id);
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
    public function cancelOrder(Request $request, $id){
        DB::beginTransaction();
        try{
            $user = auth('user_api')->user();
            $order = Order::where('order_id',$id)->where('user_id',$user->user_id)->first();
            if(empty($order)){
                return $this->responseError('Order not found!',404);
            }
            if($order->order_status == "shipped"){
                return $this->responseError('Đơn hàng đang được giao, không thể hủy!',400);
            }
            if ($order->order_status == "delivered") {
                return $this->responseError('Đơn hàng đã được giao, không thể hủy!', 400);
            }
            if ($order->order_status == "cancelled") {
                return $this->responseError('Đơn hàng đã bị hủy!', 400);
            }
            $order->update([
                'order_status' => "cancelled",
            ]);
            // $order_details = OrderDetail::where('order_id',$id)->get();
            $order_details = $this->orderRepository->getDetailOrder($id);
            foreach($order_details as $order_detail){
                $product = Product::find($order_detail->product_id);
                $product->update([
                    'product_quantity' => $product->product_quantity + $order_detail->order_quantity,
                    'product_sold' => $product->product_sold - $order_detail->order_quantity,
                ]);
            }
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            DB::commit();
            return $this->responseSuccessWithData($data,'Hủy đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getOrderHistory(Request $request){
        try{
            $user = auth('user_api')->user();
            $order_status=$request->order_status;
            $orders=Order::where('user_id',$user->user_id)->where('order_status', $order_status)->get();
            if($orders->isEmpty()){
                return $this->responseSuccess('Không có đơn hàng!',200);
            }
            return $this->responseSuccessWithData($orders,'Lấy lịch sử đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function getAll(Request $request){
        try{
            $orderBy = $request->typesort ?? 'order_id';
            switch($orderBy){
                case 'order_total_amount':
                    $orderBy = 'order_total_amount';
                    break;
                case 'order_id':
                    $orderBy = 'order_id';
                    break;
                case 'payment_id':
                    $orderBy = 'payment_id';
                    break;
                case 'delivery_id':
                    $orderBy = 'delivery_id';
                    break;
                case 'user_id':
                    $orderBy = 'user_id';
                    break;
                default:
                    $orderBy = 'order_id';
                    break;
            }
            $orderDirection = $request->sortlatest ?? 'true';
            switch($orderDirection){
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter=(object)[
                'search' => $request->search ?? '',
                'order_status' => $request->order_status ?? '',
                'payment_status' => $request->payment_status ?? '',
                'product_name' => $request->product_name ?? '',
                'order_created_at'=> $request->order_created_at ?? 'all',
                'from_date' => $request->from_date ?? '',
                'to_date' => $request->to_date ?? '',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,

            ];
            $orders = $this->orderRepository->getAll($filter);
           
            if(!empty($request->paginate)){
                $orders = $orders->paginate($request->paginate);
            }
            else{
                $orders = $orders->get();
            }
            if ($orders->isEmpty()) {
                return $this->responseSuccess('Không có đơn hàng!', 200);
            }
            return $this->responseSuccessWithData($orders,'Lấy danh sách đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function getDetailOrder(Request $request, $id){
        try{
            $order = Order::find($id);
            if(empty($order)){
                return $this->responseError('Order not found!',404);
            }
            $order_details = $this->orderRepository->getDetailOrder($id);
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            return $this->responseSuccessWithData($data,'Lấy thông tin chi tiết đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function updateStatus(Request $request, $id){
        DB::beginTransaction();
        try{
            $order = Order::find($id);
            if(empty($order)){
                return $this->responseError('Order not found!',404);
            }
            if($order->order_status == "cancelled"){
                return $this->responseError('Đơn hàng đã bị hủy!',400);
            }
            else if($order->order_status == "delivered"){
                return $this->responseError('Đơn hàng đã được giao!',400);
            }
            else if($order->order_status == "pending"){
                $order->update([
                    'order_status' => 'confirmed',
                ]);
            }
            else if($order->order_status == "confirmed"){
                $order->update([
                    'order_status' => 'shipped',
                ]);
            }
            else {
                $order->update([
                    'order_status' => 'delivered',
                    'payment_status' => 'paid',
                ]);
            }
            DB::commit();
            $user_email= User::find($order->user_id)->email;
            $content = 'Đơn hàng của bạn có mã đơn hàng là '.$id.' đã được cập nhật trạng thái thành: '.$order->order_status;
            Log::info("Thêm jobs vào hàng đợi, Email:$user_email");
            Queue::push(new SendMailNotify($user_email, $content));
            return $this->responseSuccessWithData($order,'Cập nhật trạng thái đơn hàng thành công!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}
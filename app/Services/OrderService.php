<?php

namespace App\Services;

use App\Enums\UserEnum;
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
use PayOS\PayOS;
use Throwable;

class OrderService
{
    use APIResponse;
    protected OrderInterface $orderRepository;
    protected PayOSService $payOSService;
    public function __construct(OrderInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
        $this->payOSService = new PayOSService();
    }
    private function createOrderDetail($order, $product, $quantity)
    {
        $orderDetailData = [
            'order_id' => $order->order_id,
            'product_id' => $product->product_id,
            'order_quantity' => $quantity,
            'order_price' => $product->product_price,
            'order_total_price' => $product->product_price * $quantity,
        ];
        return OrderDetail::create($orderDetailData);
    }
    private function updateProductQuantityAndSold($product, $quantity)
    {
        $product->update([
            'product_quantity' => $product->product_quantity - $quantity,
            'product_sold' => $product->product_sold + $quantity,
        ]);
    }
    private function sendOrderConfirmationEmail($user, $order, $orderDetails)
    {
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
        Queue::push(new SendMailNotify($user->email, $content));
    }
    public function buyNow(RequestUserBuyProduct $request)
    {
        DB::beginTransaction();
        try {
            $product = Product::find($request->product_id);
            $user = auth('user_api')->user();

            if (empty($product)) {
                return $this->responseError('Product not found!', 404);
            }
            if ($product->product_quantity < $request->quantity) {
                return $this->responseError('Số lượng sản phẩm trong kho không đủ!', 400);
            }

            $total_amount = $product->product_price * $request->quantity;
            $data = [
                'user_id' => $user->user_id,
                'receiver_address_id' => $request->receiver_address_id,
                'payment_id' => $request->payment_id,
                'delivery_id' => $request->delivery_id,
                'order_total_amount' => $total_amount,
            ];
            $order = $this->orderRepository->create($data);

            $orderDetail = $this->createOrderDetail($order, $product, $request->quantity);
            $this->updateProductQuantityAndSold($product, $request->quantity);
            DB::commit();
            $this->sendOrderConfirmationEmail($user, $order, [$orderDetail]);

            if ($request->payment_id == 2) {
                return $this->handlePayOSPayment($order, $total_amount);
            }

           
            return $this->responseSuccessWithData(['order' => $order, 'order_detail' => $orderDetail], 'Đặt hàng thành công!', 200);
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->responseError($th->getMessage(), 500);
        }
    }
    public function checkoutCart(RequestUserCheckoutCart $request)
    {
        DB::beginTransaction();
        try {
            $user = auth('user_api')->user();
            $ids_cart = $request->ids_cart;
            $carts = Cart::whereIn('cart_id', $ids_cart)->get();

            if ($carts->isEmpty()) {
                return $this->responseError('Giỏ hàng rỗng!', 404);
            }

            $total_amount = 0;
            $order_details = [];

            $data = [
                'user_id' => $user->user_id,
                'receiver_address_id' => $request->receiver_address_id,
                'payment_id' => $request->payment_id,
                'delivery_id' => $request->delivery_id,
                'order_total_amount' => $total_amount,
            ];
            $order = $this->orderRepository->create($data);

            foreach ($carts as $cart) {
                $product = Product::find($cart->product_id);
                if (empty($product)) {
                    return $this->responseError('Product not found!', 404);
                }
                if ($product->product_quantity < $cart->cart_quantity) {
                    return $this->responseError('Số lượng sản phẩm trong kho không đủ!', 400);
                }

                $orderDetail = $this->createOrderDetail($order, $product, $cart->cart_quantity);
                $this->updateProductQuantityAndSold($product, $cart->cart_quantity);
                $order_details[] = $orderDetail;

                $total_amount += $orderDetail->order_total_price;
            }

            $order->update(['order_total_amount' => $total_amount]);
            Cart::whereIn('cart_id', $ids_cart)->delete();
            DB::commit();
            $this->sendOrderConfirmationEmail($user, $order, $order_details);
            
            if ($request->payment_id == 2) {
                return $this->handlePayOSPayment($order, $total_amount);
            }
            return $this->responseSuccessWithData(['order' => $order, 'order_detail' => $order_details], 'Đặt hàng thành công!', 200);
        } catch (Throwable $th) {
            DB::rollBack();
            return $this->responseError($th->getMessage(), 500);
        }
    }
    private function handlePayOSPayment($order, $total_amount)
    {
        $YOUR_DOMAIN = UserEnum::URL_CLIENT;
        $paymentData = [
            "orderCode" => $order->order_id,
            "amount" => $total_amount,
            "description" => "Thanh toán đơn hàng #" . $order->order_id,
            "returnUrl" => $YOUR_DOMAIN . "/success",
            "cancelUrl" => $YOUR_DOMAIN . "/cancel"
        ];
        $response = $this->payOSService->createPaymentLink($paymentData);
        return $this->responseSuccessWithData($response['checkoutUrl'], 'Vui lòng thanh toán hoá đơn!', 200);
    }
    public function getOrderDetail(Request $request, $id)
    {
        try {
            $user = auth('user_api')->user();
            $order = $this->orderRepository->getAll((object)['order_id' => $id, 'user_id' => $user->user_id])->first();
            if (empty($order)) {
                return $this->responseError('Order not found!', 404);
            }
            $order_details = $this->orderRepository->getDetailOrder($id);
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            return $this->responseSuccessWithData($data, 'Lấy thông tin đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function cancelOrder(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $user = auth('user_api')->user();
            $order = Order::where('order_id', $id)->where('user_id', $user->user_id)->first();
            if (empty($order)) {
                return $this->responseError('Order not found!', 404);
            }
            if ($order->order_status == "shipped") {
                return $this->responseError('Đơn hàng đang được giao, không thể hủy!', 400);
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
            foreach ($order_details as $order_detail) {
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
            return $this->responseSuccessWithData($data, 'Hủy đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getOrderHistory(Request $request)
    {
        try {
            $user = auth('user_api')->user();
            $user_id = $user->user_id;
            $order_status = $request->order_status;
            $orders = $this->orderRepository->getAll((object)['user_id' => $user_id, 'order_status' => $order_status])->get();
            if ($orders->isEmpty()) {
                return $this->responseSuccess('Không có đơn hàng!', 200);
            }
            return $this->responseSuccessWithData($orders, 'Lấy lịch sử đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getAll(Request $request)
    {
        try {
            $orderBy = $request->typesort ?? 'order_id';
            switch ($orderBy) {
                case 'order_total_amount':
                    $orderBy = 'order_total_amount';
                    break;
                case 'new':
                    $orderBy = 'order_id';
                    break;
                case 'order_status':
                    $orderBy = 'order_status';
                    break;
                case 'payment_status':
                    $orderBy = 'payment_status';
                    break;
                case 'payment_method':
                    $orderBy = 'payment_method';
                    break;
                case 'delivery_method':
                    $orderBy = 'delivery_method';
                    break;
                case 'order_id':
                    $orderBy = 'order_id';
                    break;
                default:
                    $orderBy = 'order_id';
                    break;
            }
            $orderDirection = $request->sortlatest == 'true' ? 'DESC' : 'ASC';

            $filter = (object)[
                'search' => $request->search ?? '',
                'user_id' => $request->user_id ?? '',
                'order_status' => $request->order_status ?? 'pending',
                'payment_status' => $request->payment_status ?? '',
                'payment_method' => $request->payment_method ?? '',
                'delivery_method' => $request->delivery_method ?? '',
                'order_created_at' => $request->order_created_at ?? 'all',
                'from_date' => $request->from_date ?? '',
                'to_date' => $request->to_date ?? '',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,

            ];
            $orders = $this->orderRepository->getAll($filter);
            if (!empty($request->paginate)) {
                $orders = $orders->paginate($request->paginate);
            } else {
                $orders = $orders->get();
            }
            if ($orders->isEmpty()) {
                return $this->responseSuccess('Không có đơn hàng!', 200);
            }
            return $this->responseSuccessWithData($orders, 'Lấy danh sách đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getDetailOrder(Request $request, $id)
    {
        try {
            // $order = Order::find($id);
            $order = $this->orderRepository->getAll((object)['order_id' => $id])->first();
            if (empty($order)) {
                return $this->responseError('Order not found!', 404);
            }
            $order_details = $this->orderRepository->getDetailOrder($id);
            $data = [
                'order' => $order,
                'order_detail' => $order_details,
            ];
            return $this->responseSuccessWithData($data, 'Lấy thông tin chi tiết đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function updateStatus(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $order = Order::find($id);
            // $order = $this->orderRepository->getAll((object)['order_id' => $id])->first();
            if (empty($order)) {
                return $this->responseError('Order not found!', 404);
            }
            if ($order->order_status == "cancelled") {
                return $this->responseError('Đơn hàng đã bị hủy!', 400);
            } else if ($order->order_status == "delivered") {
                return $this->responseError('Đơn hàng đã được giao!', 400);
            } else if ($order->order_status == "pending") {
                $order->update([
                    'order_status' => 'confirmed',
                ]);
            } else if ($order->order_status == "confirmed") {
                $order->update([
                    'order_status' => 'shipped',
                ]);
            } else {
                $order->update([
                    'order_status' => 'delivered',
                    'payment_status' => 'paid',
                ]);
            }
            DB::commit();
            $user_email = User::find($order->user_id)->email;
            $content = 'Đơn hàng của bạn có mã đơn hàng là ' . $id . ' đã được cập nhật trạng thái thành: ' . $order->order_status;
            Log::info("Thêm jobs vào hàng đợi, Email:$user_email");
            Queue::push(new SendMailNotify($user_email, $content));
            return $this->responseSuccessWithData($order, 'Cập nhật trạng thái đơn hàng thành công!', 200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}

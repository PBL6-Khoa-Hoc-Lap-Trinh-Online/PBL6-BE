<?php
namespace App\Services;

use App\Http\Requests\RequestUserBuyProduct;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Repositories\OrderInterface;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

            return $this->responseSuccessWithData($data,'Order successfully!',200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}
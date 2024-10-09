<?php
namespace App\Repositories;
use App\Models\Order;
class OrderRepository extends BaseRepository implements OrderInterface{
    public function getModel(){
        return Order::class;
    }
    public static function getDetailOrder($id){
        $orderDetail = (new self)->model->selectRaw('order_details.*,products.*')
            ->join('order_details', 'orders.order_id', '=', 'order_details.order_id')
            ->join('products', 'order_details.product_id', '=', 'products.product_id')
            ->where('orders.order_id', $id)
            ->select('order_details.*', 'products.product_name','products.product_images')
            ->get();
        return $orderDetail;
    }
}
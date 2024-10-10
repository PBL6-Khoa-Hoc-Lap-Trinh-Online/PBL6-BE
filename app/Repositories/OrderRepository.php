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
    public static function getAll($filter){
        $filter = (object) $filter;
        $data = (new self)->model->join('order_details', 'orders.order_id', '=', 'order_details.order_id') // Changed this to join order_details, not orders
            ->join('products', 'order_details.product_id', '=', 'products.product_id')
            ->select('orders.*')
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('product_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('order_total_amount', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->product_name), function ($query) use ($filter) {
                return $query->where('products.product_name', '=', $filter->product_name);
            })
            ->when(!empty($filter->order_status), function ($query) use ($filter) {
                return $query->where('orders.order_status', '=', $filter->order_status);
            })
            ->when(!empty($filter->payment_status), function ($query) use ($filter) {
                return $query->where('orders.payment_status', '=', $filter->payment_status);
            })
            ->when(isset($filter->order_created_at), function ($query) use ($filter) {
                if ($filter->order_created_at !== 'all') {
                    $query->whereDate('order_created_at', $filter->order_created_at);
                }
            })
            ->when(!empty($filter->from_date) || !empty($filter->to_date), function ($query) use ($filter) {
                if (!empty($filter->from_date) && empty($filter->to_date)) {
                    return $query->whereDate('order_created_at', '>=', $filter->from_date);
                } elseif (empty($filter->from_date) && !empty($filter->to_date)) {
                    return $query->whereDate('order_created_at', '<=', $filter->to_date);
                } else {
                    return $query->whereBetween('order_created_at', [$filter->from_date, $filter->to_date]);
                }
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy('orders.' . $filter->orderBy, $filter->orderDirection); // Explicitly specify the table name
            });
        return $data;
    }
}
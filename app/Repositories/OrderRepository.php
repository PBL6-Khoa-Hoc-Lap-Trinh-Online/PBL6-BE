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
        $data = (new self)->model->join('order_details', 'orders.order_id', '=', 'order_details.order_id')
            ->join('users', 'orders.user_id', '=', 'users.user_id')
            ->join('payments', 'orders.payment_id', '=', 'payments.payment_id')
            ->join('deliveries', 'orders.delivery_id', '=', 'deliveries.delivery_id')
            ->join('receiver_addresses', 'orders.receiver_address_id', '=', 'receiver_addresses.receiver_address_id')
            ->join('products', 'order_details.product_id', '=', 'products.product_id')
            ->select('orders.*', 'users.user_fullname','payments.payment_method', 'deliveries.delivery_method', 'receiver_addresses.receiver_name', 'receiver_addresses.receiver_phone', 'receiver_addresses.receiver_address')
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('product_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('payment_method', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('delivery_method', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('order_status', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('payment_status', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('order_total_amount', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->payment_method), function ($query) use ($filter) {
                return $query->where('payments.payment_method', '=', $filter->payment_method);
            })
            ->when(!empty($filter->delivery_method), function ($query) use ($filter) {
                return $query->where('deliveries.delivery_method', '=', $filter->delivery_method);
            })
            ->when(!empty($filter->user_id), function ($query) use ($filter) {
                return $query->where('orders.user_id', '=', $filter->user_id);
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
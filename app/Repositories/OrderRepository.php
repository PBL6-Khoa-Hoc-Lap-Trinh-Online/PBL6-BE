<?php
namespace App\Repositories;
use App\Models\Order;
class OrderRepository extends BaseRepository implements OrderInterface{
    public function getModel(){
        return Order::class;
    }
}
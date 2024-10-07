<?php

namespace App\Http\Controllers;


use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    public function __construct(OrderService $orderService){
        $this->orderService = $orderService;
    }
    public function buyNow(Request $request){
        return $this->orderService->buyNow($request);
    }
    public function checkoutCart(Request $request){
        return $this->orderService->checkoutCart($request);
    }
}

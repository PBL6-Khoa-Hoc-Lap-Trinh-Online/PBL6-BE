<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestUserBuyProduct;
use App\Http\Requests\RequestUserCheckoutCart;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected OrderService $orderService;
    public function __construct(OrderService $orderService){
        $this->orderService = $orderService;
    }
    public function buyNow(RequestUserBuyProduct $request){
        return $this->orderService->buyNow($request);
    }
    public function checkoutCart(RequestUserCheckoutCart $request){
        return $this->orderService->checkoutCart($request);
    }
}

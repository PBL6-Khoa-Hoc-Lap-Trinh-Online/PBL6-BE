<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use App\Models\Cart;
use App\Services\CartService;

class CartController extends Controller
{
    protected CartService $cartService;
    public function __construct(CartService $cartService){
        $this->cartService = $cartService;
    }

    public function get(Request $request){
        return $this->cartService->get($request);
    }
}

<?php

namespace App\Services;
use App\Traits\APIResponse;

use App\Models\Cart;

use App\Repositories\CartInterface;
use App\Repositories\CartRepository;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;


class CartService
{
    use APIResponse;
    protected CartInterface $cartRepository;
    public function __construct(CartInterface $cartRepository){
        $this->cartRepository = $cartRepository;
    }

    public function get(Request $request){
        DB::beginTransaction();
        try {
            $id_user = auth('user_api')->user()->user_id;
            $cart = Cart::where('user_id', $id_user)->first();

            if ($cart) {
                DB::commit();
                return $this->responseSuccessWithData($cart,'Lấy thông tin giỏ hàng người dùng thành công', 201);
            } else {
                $newCart = Cart::create([
                    'user_id' => $id_user,
                    'cart_created_at' => now(),
                    'cart_updated_at' => now(),
                ]);
                DB::commit();
                return $this->responseSuccessWithData($newCart,'Tạo giỏ hàng người dùng thành công', 201);
            }
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}

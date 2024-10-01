<?php

namespace App\Services;
use App\Traits\APIResponse;

use App\Models\CartDetail;
use App\Models\Product;

use App\Repositories\CartDetailInterface;
use App\Repositories\CartDetailRepository;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Illuminate\Http\Request;
use App\Http\Requests\RequestAddCartDetail;
use App\Http\Requests\RequestDeleteManyCartDetail;

class CartDetailService
{
    use APIResponse;
    protected CartDetailInterface $cartdetailRepository;
    public function __construct(CartDetailInterface $cartdetailRepository){
        $this->cartdetailRepository = $cartdetailRepository;
    }

    public function get(Request $request){
        try {
            $cart_id = $request->cart_id;
            $cartDetails = CartDetail::where('cart_id', $cart_id)->get();
            
            if ($cartDetails->isEmpty()) {
                return $this->responseSuccess("Không có sản phẩm nào trong giỏ hàng", 200);
            }

            $cartProducts = [];

            foreach ($cartDetails as $cartDetail) {
                $product_id = $cartDetail->product_id;

                $product = Product::find($product_id);

                if ($product) {
                    $cartProducts[] = [
                        'cart_detail_id' => $cartDetail -> cart_detail_id,
                        'cart_id' => $cartDetail -> cart_id,
                        'cart_quantity' => $cartDetail -> cart_quantity,
                        'cart_price' => $cartDetail -> cart_price,
                        'product_id' => $cartDetail -> product_id,
                        'product_name' => $product -> product_name,
                        'product_images' => $product -> product_images,
                        'product_quantity' => $product -> product_quantity,
                    ];
                }
            }
            return $this->responseSuccessWithData($cartProducts, "Lấy chi tiết giỏ hàng thành công", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function add(RequestAddCartDetail $request){
        DB::beginTransaction();
        try {
            $data = $request->validated();

            $cartDetail = CartDetail::where('cart_id', $data['cart_id'])
                                    ->where('product_id', $data['product_id'])->first();
            
            $product = Product::where('product_id', $data['product_id'])->first();

            if ($cartDetail) {
                if ($cartDetail->cart_quantity + $data['cart_quantity'] > $product->product_quantity) {
                    $cartDetail->cart_quantity = $product->product_quantity;
                    $cartDetail->cart_price = $product->product_price; 
                    $cartDetail->save();
                    DB::commit();
                    $message = "Số lượng đặt hàng vượt quá sản phẩm trong kho";
                    return $this->responseSuccessWithData($cartDetail,$message,201);
                }

                $cartDetail->cart_quantity += $data['cart_quantity'];
                $cartDetail->cart_price = $product->product_price; 
                $cartDetail->save();
            } else {
                if ($data['cart_quantity'] > $product->product_quantity) {

                    $cartDetail = CartDetail::create([
                        'cart_id' => $data['cart_id'],
                        'product_id' => $data['product_id'],
                        'cart_quantity' => $product->product_quantity,
                        'cart_price' => $product->product_price,
                    ]);
                    DB::commit();

                    $message = "Số lượng đặt hàng vượt quá sản phẩm trong kho";
                    return $this->responseSuccessWithData($cartDetail,$message,201);

                }
                
                $cartDetail = CartDetail::create([
                    'cart_id' => $data['cart_id'],
                    'product_id' => $data['product_id'],
                    'cart_quantity' => $data['cart_quantity'],
                    'cart_price' => $product->product_price,
                ]);
            }
            DB::commit();
            return $this->responseSuccessWithData($cartDetail,'Thêm vào giỏ hàng thành công',201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }

    public function update(RequestAddCartDetail $request){
        DB::beginTransaction();
        try {
            $data = $request->validated();
            
            $cartDetail = CartDetail::where('cart_id', $data['cart_id'])
                                    ->where('product_id', $data['product_id'])->first();
            
            $product = Product::where('product_id', $data['product_id'])->first();
            
            if ($cartDetail) {
                if ($cartDetail->cart_quantity > $product->product_quantity) {
                    $cartDetail->cart_quantity = $product->product_quantity;
                    $cartDetail->cart_price = $product->product_price; 
                    $cartDetail->save();
                    DB::commit();
                    $message = "Số lượng đặt hàng vượt quá sản phẩm trong kho";
                    return $this->responseSuccessWithData($cartDetail,$message,201);
                }

                $cartDetail->cart_quantity = $data['cart_quantity'];
                $cartDetail->cart_price = $product->product_price; 
                $cartDetail->save();
            }
            DB::commit();
            return $this->responseSuccessWithData($cartDetail,'Cập nhật sản phẩm thành công',201);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }

    public function delete(Request $request, $id){
        DB::beginTransaction();
        try{
            $cartdetail = CartDetail::find($id);
            if(empty($cartdetail)){
                return $this->responseError("Sản phẩm không tồn tại!", 404);
            }
            $cartdetail->delete();

            DB::commit();
            $message = "Xóa sản phẩm thành công";
            return $this->responseSuccess($message, 200);
        }
        catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function deleteMany(RequestDeleteManyCartDetail $request){
        DB::beginTransaction();
        try{
            $ids_cartdetail = $request->ids_cartdetail;
            $cartdetails = CartDetail::whereIn('cart_detail_id', $ids_cartdetail)->get();
            if($cartdetails->isEmpty()){
                return $this->responseError("Không tìm thấy sản phẩm!");
            }
            foreach($cartdetails as $index => $cartdetail){
                $cartdetail->delete();
            }
            DB::commit();
            $message = "Xóa sản phẩm thành công";
            return $this->responseSuccess($message, 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
}

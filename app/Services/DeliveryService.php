<?php
namespace App\Services;

use App\Models\Delivery;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Throwable;

class DeliveryService{
    use APIResponse;
    public function getAll(Request $request){
        try{
            $deliveries = Delivery::all();
            return $this->responseSuccessWithData($deliveries,"Get all deliveries successfully",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
}
<?php
namespace App\Services;

use App\Models\Payment;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentService{
    use APIResponse;
    public function getAll(Request $request){
        try{
            $payemts = Payment::all();
            return $this->responseSuccessWithData($payemts,"Get all payments successfully",200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    
}
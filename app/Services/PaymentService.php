<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PayOS\PayOS;
use Throwable;

class PaymentService
{
    protected PayOSService $payOSService;
    public function __construct(PayOSService $payOSService)
    {
        $this->payOSService = $payOSService;
    }
    use APIResponse;
    public function getAll(Request $request)
    {
        try {
            $payments = Payment::all();
            return $this->responseSuccessWithData($payments, "Get all payments successfully", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function getPaymentInfo($orderCode){
        try{
            $response=$this->payOSService->getPaymentLink($orderCode);
            return $this->responseSuccess($response,200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function cancelPayment($orderCode,Request $request){
        try{
            $response=$this->payOSService->cancelPaymentLink($orderCode);
            return $this->responseSuccess($response,200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function handlePayOSWebhook(Request $request)
    {
        try{
           $webhookData=$request->all();
           Log::info('Webhook received:', $request->all());
           $orderCode=$webhookData['data']['orderCode'];
           $paymentStatus = $webhookData['success'] ? 'completed' : 'failed';
            // $paymentStatus = ($webhookData['data']['code'] === '00') ? 'completed' : 'failed'; // '00' thường là mã thành công
            $order=Order::where('order_id',$orderCode)->first();
            if($order){
                $order->payment_status=$paymentStatus;
                $order->save();
            }
            else{
                return $this->responseError("Order not found",404);
            }
           return $this->responseSuccess("Cập nhật trạng thái thanh toán ".$paymentStatus,200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    
   

}

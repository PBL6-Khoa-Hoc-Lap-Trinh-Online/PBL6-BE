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

    // public function handlePayOSWebhook(Request $request)
    // {
    //     // Decode the JSON payload
    //     $body = json_decode($request->getContent(), true);
    //     // dd($body);
    //     // Log the incoming webhook payload for debugging
    //     Log::info("PayOS Webhook: ", $body);

    //     // Check for JSON decoding errors
    //     if (json_last_error() !== JSON_ERROR_NONE) {
    //         return response()->json([
    //             "error" => 1,
    //             "message" => "Invalid JSON payload"
    //         ], 400);
    //     }

    //     // Validate if the necessary data exists in the request body
    //     if (!isset($body['data']) || !isset($body['data']['description'])) {
    //         return response()->json([
    //             "error" => 1,
    //             "message" => "Missing required data fields"
    //         ], 400);
    //     }
    //     // Verify webhook data
    //     try {
    //         $this->payOSService->verifyWebhook($body);
    //     } catch (\Exception $e) {
    //         // Log the exception message for debugging
    //         Log::error("PayOS Webhook verification failed: " . $e->getMessage());

    //         return response()->json([
    //             "error" => 1,
    //             "message" => "Invalid webhook data",
    //             "details" => $e->getMessage()
    //         ], 400);
    //     }
    //     if (!isset($body['data']['orderCode'])) {
    //         Log::error("Missing orderCode in webhook payload");
    //         return response()->json([
    //             "error" => 1,
    //             "message" => "orderCode not found"
    //         ], 400);
    //     }
    //     // Process the webhook data and find the associated order
    //     $order = Order::where("order_id", $body['data']['orderCode'])->first();
    //     if (!$order) {
    //         return response()->json([
    //             "error" => 1,
    //             "message" => "Order not found"
    //         ], 404);
    //     }

    //     // Handle payment status based on the code received
    //     $status = $body["data"]["code"];
    //     switch ($status) {
    //         case "00":
    //             $order->update([
    //                 "payment_status" => "completed"
    //             ]);
    //             break;
    //         case "20":
    //             $order->update([
    //                 "payment_status" => "failed"
    //             ]);
    //             break;
    //         default:
    //             $order->update([
    //                 "payment_status" => "failed"
    //             ]);
    //             break;
    //     }

    //     // Return success response with order data
    //     return response()->json([
    //         "error" => 0,
    //         "message" => "Webhook processed successfully",
    //         "data" => $order
    //     ]);
    // }

    public function handlePayOSWebhook(Request $request)
    {
        $body = json_decode($request->getContent(), true);
        Log::info("payos: " , $body);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                "error" => 1,
                "message" => "Invalid JSON payload"
            ], 400);
        }
//  {"code":"00","desc":"success","success":true,"data":{"accountNumber":"56010001731721","amount":2000,"description":"CSED51ZGP85 Thanh toan don hang 52","reference":"1f9d6038-f851-4f4b-aa6d-9c941a104606","transactionDateTime":"2024-11-10 15:36:52","virtualAccountNumber":"V3CAS56010001731721","counterAccountBankId":"","counterAccountBankName":"","counterAccountName":null,"counterAccountNumber":null,"virtualAccountName":"","currency":"VND","orderCode":52,"paymentLinkId":"f517f07e1d064fe998244b35d871a9bc","code":"00","desc":"success"},"signature":"b1c7d143c8407619b6a211c9d71de75e67ce9fbd115db2b963d88bbb0eb14369"} 
// [2024-11-10 08:37:54] local.ERROR: Undefined array key "order_code" {"exception":"[object] (ErrorException(code: 0): Undefined array key \"order_code\" at C:\\laragon\\www\\PBL6-BE\\app\\Services\\PaymentService.php:143)
// [stacktrace]
        // Handle webhook test
        if (in_array($body["data"]["description"], ["Ma giao dich thu nghiem", "VQRIO123"])) {
            return response()->json([
                "error" => 0,
                "message" => "Ok",
                "data" => $body["data"]
            ]);
        }

        try {
            $this->payOSService->verifyWebhook($body);

        } catch (\Exception $e) {
            return response()->json([
                "error" => 1,
                "message" => "Invalid webhook data",
                "details" => $e->getMessage()
            ], 400);
        }

        // Process webhook data
        $order = Order::where("order_id", $body["data"]["orderCode"])->first();
        if (!$order) {
            return response()->json([
                "error" => 1,
                "message" => "Order not found"
            ], 404);
        }
        $status = $body["data"]["code"];
        if($status =="00"){
            $order->update([
                "payment_status" => "completed"
            ]);
        }
        else{
            $order->update([
                "payment_status" => "failed"
            ]);
        }

        return response()->json([
            "error" => 0,
            "message" => "Ok",
            "data" => $order
        ]);
    }
    
   

}

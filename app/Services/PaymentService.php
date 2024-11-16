<?php

namespace App\Services;

use App\Http\Requests\RequestAddPaymentMethod;
use App\Http\Requests\RequestUpdatePaymentMethod;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Repositories\PaymentMethodRepository;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function add(RequestAddPaymentMethod $request){
        DB::beginTransaction();
        try{
            $data = $request->all();
            if ($request->hasFile('payment_method_logo')) {
                $image = $request->file('payment_method_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                // Gán logo vào dữ liệu
                $data['payment_method_logo'] = $url;
            }
            $payment_method=PaymentMethod::create($data);
            DB::commit();
            return $this->responseSuccessWithData($payment_method, "Thêm mới phương thức thành công!", 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getPaymentMethod(Request $request, $id){
        try{
            $payment_method=PaymentMethod::find($id);
            if(!$payment_method){
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            return $this->responseSuccessWithData($payment_method, "Lấy thông tin phương thức thanh toán thành công!", 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function update(RequestUpdatePaymentMethod $request, $id){
        DB::beginTransaction();
        try{
            $payment_method=PaymentMethod::find($id);
            if(!$payment_method){
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            if ($request->hasFile('payment_method_logo')) {
                if ($payment_method->payment_method_logo) {
                    $id_file = explode('.', implode('/', array_slice(explode('/', $payment_method->payment_method_logo), 7)))[0];
                    Cloudinary::destroy($id_file);
                }
                $image = $request->file('payment_method_logo');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/thumbnail/brand_logo',
                    'resource_type' => 'auto'
                ]);
                $url = $uploadFile->getSecurePath();
                $data = array_merge($request->all(), ['payment_method_logo' => $url]);
                $payment_method->update($data);
            } else {
                $request['payment_method_logo'] = $payment_method->payment_method_logo;
                $payment_method->update($request->all());
            }
            DB::commit();
            return $this->responseSuccessWithData($payment_method, "Cập nhật phương thức thanh toán thành công!", 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function delete(Request $request, $id){
        DB::beginTransaction();
        try{
            $payment_method=PaymentMethod::find($id);
            if(!$payment_method){
                return $this->responseError("Không tìm thấy phương thức thanh toán!", 404);
            }
            $status =!$payment_method->payment_is_active;
            $payment_method->update(['payment_is_active'=>$status]);
            $message = $status ? "Khôi phục phương thức thanh toán thành công!" : "Xóa phương thức thanh toán thành công!";
            DB::commit();
            return $this->responseSuccess($message, 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }
    public function getPaymentMethods(Request $request)
    {
        $orderBy = $request->typesort ?? 'payment_method_id';
        switch ($orderBy) {
            case 'payment_method_name':
                $orderBy = 'payment_method_name';
                break;
            case 'new':
                $orderBy = "payment_method_id";
                break;
            default:
                $orderBy = 'payment_method_id';
                break;
        }
        $orderDirection = $request->sortlatest ?? 'true';
        switch ($orderDirection) {
            case 'true':
                $orderDirection = 'DESC';
                break;
            default:
                $orderDirection = 'ASC';
                break;
        }
        $filter = (object) [
            'search' => $request->search ?? '',
            'payment_is_active' => $request->payment_is_active ?? 'all',
            'orderBy' => $orderBy,
            'orderDirection' => $orderDirection,
        ];
        $paymentMethods = PaymentMethodRepository::getAll($filter);
        if (!(empty($request->paginate))) {
            $paymentMethods = $paymentMethods->paginate($request->paginate);
        } else {
            $paymentMethods = $paymentMethods->get();
        }
        return $paymentMethods;
    }
    public function getAllPaymentMethodByUser(Request $request){
        try{
            $payment_methods=$this->getPaymentMethods($request)->where('payment_is_active',1)->values();
            return $this->responseSuccessWithData($payment_methods, "Lấy danh sách phương thức thanh toán thành công!", 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }
    public function getAllPaymentMethodByAdmin(Request $request){
        try{
            $payment_methods=$this->getPaymentMethods($request)->values();
            return $this->responseSuccessWithData($payment_methods, "Lấy danh sách phương thức thanh toán thành công!", 200);
        }
        catch(Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }

    public function getAll(Request $request)
    {
        try {
            $payments = Payment::all();
            return $this->responseSuccessWithData($payments, "Quản lý thanh toán của các đơn hàng", 200);
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function createPayment(Request $request)
    {
        return Payment::create($request->all());
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

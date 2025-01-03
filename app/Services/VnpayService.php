<?php
namespace App\Services;

use App\Enums\UserEnum;
use App\Models\Order;

use App\Traits\APIResponse;
use Throwable;
use Illuminate\Http\Request;
class VnpayService implements PaymentServiceInterface{
    use APIResponse;
   
    public function handlePayment($orderId, $oderTotalAmount)
    {
        try {
            $order = Order::find($orderId);
            if ($order == null) {
                return $this->responseError("Đơn hàng không tồn tại!", 404);
            }
            $YOUR_DOMAIN = UserEnum::URL_CLIENT;
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            $vnp_Returnurl = $YOUR_DOMAIN . "/success";
            // $vnp_Returnurl = "http://localhost:8000/api/payments/vnpay-return";
            $vnp_TmnCode = "ESJF650Y"; //Mã website tại VNPAY 
            $vnp_HashSecret = "J7HVWBXWWJMPSMAU02WU365SX7E4KOXJ";
            $vnp_TxnRef = $orderId;
            $vnp_OrderInfo = "Thanh toán hoá đơn";
            $vnp_OrderType = "Thanh toán hoá đơn";
            $vnp_Amount = $oderTotalAmount * 100;
            $vnp_Locale = "VN";
            $vnp_BankCode = "NCB";
            $vnp_IpAddr = $_SERVER['REMOTE_ADDR'];
            $inputData = array(
                "vnp_Version" => "2.1.0",
                "vnp_TmnCode" => $vnp_TmnCode,
                "vnp_Amount" => $vnp_Amount,
                "vnp_Command" => "pay",
                "vnp_CreateDate" => date('YmdHis'),
                "vnp_CurrCode" => "VND",
                "vnp_IpAddr" => $vnp_IpAddr,
                "vnp_Locale" => $vnp_Locale,
                "vnp_OrderInfo" => $vnp_OrderInfo,
                "vnp_OrderType" => $vnp_OrderType,
                "vnp_ReturnUrl" => $vnp_Returnurl,
                "vnp_TxnRef" => $vnp_TxnRef
            );

            if (isset($vnp_BankCode) && $vnp_BankCode != "") {
                $inputData['vnp_BankCode'] = $vnp_BankCode;
            }
            if (isset($vnp_Bill_State) && $vnp_Bill_State != "") {
                $inputData['vnp_Bill_State'] = $vnp_Bill_State;
            }

            //var_dump($inputData);
            ksort($inputData);
            $query = "";
            $i = 0;
            $hashdata = "";
            foreach ($inputData as $key => $value) {
                if ($i == 1) {
                    $hashdata .= '&' . urlencode($key) . "=" . urlencode($value);
                } else {
                    $hashdata .= urlencode($key) . "=" . urlencode($value);
                    $i = 1;
                }
                $query .= urlencode($key) . "=" . urlencode($value) . '&';
            }

            $vnp_Url = $vnp_Url . "?" . $query;
            if (isset($vnp_HashSecret)) {
                $vnpSecureHash =   hash_hmac('sha512', $hashdata, $vnp_HashSecret); //  
                $vnp_Url .= 'vnp_SecureHash=' . $vnpSecureHash;
            }
            // return $this->responseSuccessWithData($vnp_Url, "<::>", 200);

            $data =  $vnp_Url;
            if (isset($_POST['redirect'])) {
                header('Location: ' . $vnp_Url);
                die();
            } else {
                // echo json_encode($returnData);
                return $data;
            }
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    
   
}
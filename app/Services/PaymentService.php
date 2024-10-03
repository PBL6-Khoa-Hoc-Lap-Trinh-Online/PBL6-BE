<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Throwable;

class PaymentService
{
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
    public function createVnPayPayment(Request $request)
    {
        try {
            $order = Order::find($request->order_id);
            if ($order == null) {
                return $this->responseError("Order not found", 404);
            }
            $vnp_Url = "https://sandbox.vnpayment.vn/paymentv2/vpcpay.html";
            // $vnp_Returnurl = "https://localhost/vnpay_php/vnpay_return.php";
            $vnp_Returnurl= "http://localhost:8000/api/payments/vnpay-return";
            $vnp_TmnCode = "RC3B971A"; //Mã website tại VNPAY 
            $vnp_HashSecret = "MYZJWYLF45X1PGAT37RKBM3OWD8X5B99"; //Chuỗi bí mật

            $vnp_TxnRef = $request->order_id;
            $vnp_OrderInfo = "Thanh toán hoá đơn";
            $vnp_OrderType = "Medicine";
            $vnp_Amount = $request->order_total_amount * 100;
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
            
            $data= array(
                'code' => '00',
                'url' => $vnp_Url
            );
            if (isset($_POST['redirect'])) {
                header('Location: ' . $vnp_Url);
                die();
            } else {
                // echo json_encode($returnData);
                return $this->responseSuccessWithData($data, "Payment successfully", 200);
            }
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    public function vnPayReturn(Request $request)
    {
        try {
            $vnp_SecureHash = $request->vnp_SecureHash;
            $vnp_HashSecret = "MYZJWYLF45X1PGAT37RKBM3OWD8X5B99"; // Mã bảo mật của bạn
            $inputData = $request->all();
            $vnp_TxnRef = $request->order_id; // Mã đơn hàng
            $order = Order::find($vnp_TxnRef); // Tìm đơn hàng dựa trên mã đơn hàng
            // $vnp_ResponseCode = $inputData['vnp_ResponseCode']; // Mã phản hồi từ VNPay

            // Lọc các tham số và tạo chuỗi hash
            unset($inputData['vnp_SecureHash']);
            ksort($inputData);
            $hashData = "";
            foreach ($inputData as $key => $value) {
                $hashData .= urlencode($key) . "=" . urlencode($value) . '&';
            }
            $hashData = rtrim($hashData, '&');
            $secureHash = hash('sha256', $vnp_HashSecret . $hashData);

            if ($secureHash == $vnp_SecureHash) { // So sánh hash để kiểm tra tính hợp lệ
                if ($inputData['vnp_TransactionStatus'] == '00') {
                    // Thanh toán thành công
                    $order->update(['payment_status' => 'paid', 'order_status' => 'confirmed']);
                    return $this->responseSuccess("Thanh toán thành công!", 200);
                } else {
                    // Thanh toán thất bại
                    $order->update(['payment_status' => 'unpaid', 'order_status' => 'cancelled']);
                    return $this->responseError("Thanh toán thất bại!", 400);
                }
            } else {
                return $this->responseError("Chữ ký không hợp lệ!", 400);
            }
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

}

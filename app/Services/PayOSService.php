<?php
namespace App\Services;

use App\Enums\UserEnum;
use App\Services\PaymentServiceInterface;
use PayOS\PayOS;
use Throwable;
class PayOSService implements PaymentServiceInterface, PayOSServiceInterface{
    protected $payOS;

    public function __construct()
    {
        $this->payOS = new PayOS("ee6604fb-66b8-4078-874f-11824323fdaf", "3f9d54fb-6c5a-48dd-a71a-5a75e99634e8", "531c42fba7d885c1c2dc916fb6fac8f52c85e54fc4e262a4ca1c48a304669c56");
    }
    public function handlePayment($orderId, $totalAmount)
    {
        try {
            $YOUR_DOMAIN = UserEnum::URL_CLIENT;
            $data = [
                "orderCode" => $orderId,
                "amount" => $totalAmount,
                "description" => "Thanh toán đơn hàng #" . $orderId,
                "returnUrl" => $YOUR_DOMAIN . "/success",
                "cancelUrl" => $YOUR_DOMAIN . "/cancel",
            ];
            $response = $this->payOS->createPaymentLink($data);
            return $response['checkoutUrl'];
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
    //Get payment link
    public function getPaymentLink($orderCode)
    {
        try {
            $response = $this->payOS->getPaymentLinkInformation($orderCode);
            return $response;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
    //cancel payment link
    public function cancelPaymentLink($orderCode)
    {
        try {
            $reason = "Huỷ đơn hàng";
            $response = $this->payOS->cancelPaymentLink($orderCode, $reason);
            return $response;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }
    //Verify webhook
    public function verifyWebhook($body)
    {
        try {
            $this->payOS->verifyPaymentWebhookData($body);
            
            return true;
        } catch (Throwable $e) {
            return $e->getMessage();
        }
    }

}
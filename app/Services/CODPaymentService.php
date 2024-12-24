<?php

namespace App\Services;

class CODPaymentService implements PaymentServiceInterface
{
    public function handlePayment($orderId, $totalAmount)
    {
        // COD không cần xử lý thanh toán
        return null;
    }

}

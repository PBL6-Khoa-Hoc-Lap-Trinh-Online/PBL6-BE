<?php
namespace App\Services;

interface PaymentServiceInterface
{
    public function handlePayment($orderId, $totalAmount);
}
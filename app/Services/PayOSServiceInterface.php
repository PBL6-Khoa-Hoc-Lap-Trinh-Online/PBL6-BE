<?php

namespace App\Services;

interface PayOSServiceInterface
{
    public function getPaymentLink($orderId);
    public function cancelPaymentLink($orderId);
}

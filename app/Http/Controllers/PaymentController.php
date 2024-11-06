<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    public function getAll(Request $request)
    {
        return $this->paymentService->getAll($request);

    }
  
    public function handlePayOSWebhook(Request $request)
    {
        return $this->paymentService->handlePayOSWebhook($request);
    }
}

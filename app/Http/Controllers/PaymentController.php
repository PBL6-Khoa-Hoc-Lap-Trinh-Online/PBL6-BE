<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddPaymentMethod;
use App\Http\Requests\RequestUpdatePaymentMethod;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }
    public function add(RequestAddPaymentMethod $request)
    {
        return $this->paymentService->add($request);
    }
    public function getPaymentMethod(Request $request, $id)
    {
        return $this->paymentService->getPaymentMethod($request, $id);
    }
    public function update(RequestUpdatePaymentMethod $request, $id)
    {
        return $this->paymentService->update($request, $id);
    }
    public function delete(Request $request, $id)
    {
        return $this->paymentService->delete($request, $id);
    }
    public function getAllByAdmin(Request $request)
    {
        return $this->paymentService->getAllPaymentMethodByAdmin($request);
    }
    public function getAll(Request $request)
    {
        return $this->paymentService->getAllPaymentMethodByUser($request);

    }
  
    public function handlePayOSWebhook(Request $request)
    {
        return $this->paymentService->handlePayOSWebhook($request);
    }
}

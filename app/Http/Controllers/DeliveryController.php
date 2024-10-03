<?php

namespace App\Http\Controllers;

use App\Services\DeliveryService;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    protected DeliveryService $deliveryService;
    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }
    public function getAll(Request $request)
    {
        return $this->deliveryService->getAll($request);

    }
}

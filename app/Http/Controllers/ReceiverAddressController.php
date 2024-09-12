<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddReceiverAddress;
use App\Models\ReceiverAddress;
use App\Services\ReceiverAddressService;
use Illuminate\Http\Request;

class ReceiverAddressController extends Controller
{
    protected ReceiverAddressService $receiverAddressService;
    public function __construct(ReceiverAddressService $receiverAddressService)
    {
        $this->receiverAddressService = $receiverAddressService;
    }
    public function add(RequestAddReceiverAddress $request)
    {
        return $this->receiverAddressService->add($request);
    }
}

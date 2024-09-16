<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateSupplier;
use App\Models\Supplier;

use App\Services\SupplierService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    protected SupplierService $supplierService;
    public function __construct(SupplierService $supplierService){
        $this->supplierService = $supplierService;
    }
    public function add(RequestCreateSupplier $request){
        return $this->supplierService->add($request);
    }

}

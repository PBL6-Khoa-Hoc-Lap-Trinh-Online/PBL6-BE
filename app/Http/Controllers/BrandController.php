<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateBrand;
use App\Services\BrandService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    protected BrandService $brandService;
    public function __construct(BrandService $brandService){
        $this->brandService = $brandService;
    }
    public function add(RequestCreateBrand $request){
        $this->brandService->add($request);
    }
}

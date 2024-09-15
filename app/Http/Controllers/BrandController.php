<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestCreateBrand;
use App\Http\Requests\RequestUpdateBrand;
use App\Services\BrandService;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    protected BrandService $brandService;
    public function __construct(BrandService $brandService){
        $this->brandService = $brandService;
    }
    public function add(RequestCreateBrand $request){
        return $this->brandService->add($request);
    }
    public function update(RequestUpdateBrand $request,$id){
         return $this->brandService->update($request,$id);
    }
}

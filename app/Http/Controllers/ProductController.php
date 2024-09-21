<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddProduct;
use App\Services\ProductService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    protected ProductService $productService;
    public function __construct(ProductService $productService){
        $this->productService = $productService;
    }
    public function add(RequestAddProduct $request){
        return $this->productService->add($request);
    }
    public function get(Request $request,$id){
        return $this->productService->get($request, $id);
    }
    public function getAll(Request $request){
        return $this->productService->getAll($request);
    }
    public function addUploadS3(RequestAddProduct $request){
        return $this->productService->addUploadS3($request);
    }
}

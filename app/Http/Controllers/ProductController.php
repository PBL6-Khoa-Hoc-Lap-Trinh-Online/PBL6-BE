<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddProduct;
use App\Services\ProductService;
use Illuminate\Support\Facades\Request;

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
}

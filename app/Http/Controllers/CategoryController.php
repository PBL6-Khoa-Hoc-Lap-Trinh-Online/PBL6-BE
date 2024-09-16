<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestUpdateCategory;
use App\Http\Requests\RequestCreateCategory;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;
    public function __construct(CategoryService $categoryService){
        $this->categoryService = $categoryService;
    }
    public function add(RequestCreateCategory $request){
        return $this->categoryService->add($request);
    }
    public function update(RequestUpdateCategory $request, $id){
         return $this->categoryService->update($request, $id);
    }
}

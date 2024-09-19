<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiverAddressController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

//user
Route::prefix('user')->controller(UserController::class)->group(function () {
    Route::post('register', 'register');
    Route::post('verify-email', 'verifyEmail');
    Route::post('login', 'login');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('reset-password', 'resetPassword');
    Route::middleware('check.auth:user_api')->group(function () {
        Route::get('logout', 'logout');
        Route::get('profile', 'profile');
        Route::post('update-profile', 'updateProfile');
        Route::post('change-password', 'changePassword');
    });
});
Route::prefix('receiver-address')->controller(ReceiverAddressController::class)->group(function () {
    Route::middleware('check.auth:user_api')->group(function () {
        Route::post('add', 'add');
        Route::get('{id}', 'getAddress');
        Route::post('update/{id}', 'update');
        Route::get('', 'getAll');
        Route::delete('delete/{id}', 'delete');
    });
});


//admin
Route::middleware('auth:sanctum')->get('/admin', function (Request $request) {
    return $request->admin();
});

Route::prefix('admin')->controller(AdminController::class)->group(function (){
    Route::post('login','login');
    Route::post('forgot-password', 'forgotPassword');
    Route::post('reset-password', 'resetPassword');
    Route::middleware('check.auth:admin_api')->group(function(){
        Route::get('logout', 'logout');
        Route::get('profile', 'profile');
        Route::post('update-profile', 'updateProfile');
        
    });
});


//brand
Route::prefix('brands')->controller(BrandController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}', 'update');
        Route::delete('{id}', 'delete');
    });
    Route::get('{id}', 'get');
    Route::get('', 'getAll');
});

//supplier
Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}', 'update');
        Route::get('{id}', 'get');
        Route::delete('{id}', 'delete');
        Route::get('', 'getAll');
    });
});

//category
Route::prefix('categories')->controller(CategoryController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}','update');
        Route::post('delete/{id}', 'delete');
        Route::post('delete-many', 'deleteMany');
    });
    Route::get('{id}', 'get');//get chính nó nếu không có danh sách con
    Route::get('', 'getAll');
});


//product
Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
       
    });
    Route::get('{id}', 'get');
});
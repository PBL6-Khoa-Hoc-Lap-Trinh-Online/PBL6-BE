<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\DeliveryController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReceiverAddressController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CartDetailController;
use App\Http\Controllers\VietnamZoneController;

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
    Route::post('resend-verify-email', 'resendVerifyEmail');
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
    Route::post('verify-email', 'verifyEmail');
    Route::post('resend-verify-email', 'resendVerifyEmail');
    
    Route::middleware('check.auth:admin_api')->group(function(){
        Route::get('logout', 'logout');
        Route::get('profile', 'profile');
        Route::post('update-profile', 'updateProfile');
        Route::post('change-password', 'changePassword');
        Route::get('manage-users', 'manageUsers');
        Route::post('change-block/{id}', 'changeBlock');
        Route::post('delete-user/{id}', 'deleteUser');
        
        Route::middleware('check.role:1,2')->group(function(){
            Route::get('manage-admins', 'manageAdmins');
            Route::post('delete-admin/{id}','deleteAdmin');
                Route::middleware('check.role:2')->group(function(){
                    Route::post('change-role/{id}', 'changeRole');
                    Route::post('add-admin','addAdmin');
                });  	
            }); 

        
    });
    
    
});


//brand
Route::prefix('brands')->controller(BrandController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}', 'update');
        Route::delete('{id}', 'delete');
    });
    Route::get('names', 'getNameBrand');
    Route::get('{id}', 'get');
    Route::get('', 'getAll');
});

//supplier
Route::prefix('suppliers')->controller(SupplierController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}', 'update');
        Route::get('names', 'getNameSupplier');
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
        Route::get('2', 'getAll');
    });
    Route::get('names', 'getNameCategory');
    Route::get('{id}', 'get');//get chính nó nếu không có danh sách con
    Route::get('', 'getAllCategories');
    
});


//product
Route::prefix('products')->controller(ProductController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::post('update/{id}', 'update');
        Route::post('delete/{id}', 'delete');
        Route::post('delete-many', 'deleteMany');
    });
    Route::get('names', 'getNameProduct');
    Route::get('{id}', 'get');
    Route::get('', 'getAll');
});


//Import
Route::prefix('imports')->controller(ImportController::class)->group(function () {
    Route::middleware('check.auth:admin_api')->group(function () {
        Route::post('add', 'add');
        Route::get('{id}', 'getImportDetails');
        Route::get('', 'getAll');
    });
});



//user order
Route::prefix('orders')->controller(OrderController::class)->group(function () {
    Route::middleware('check.auth:user_api')->group(function () {
        Route::post('buy-now', 'buyNow');
        Route::post('checkout-cart', 'checkoutCart');
        Route::get('detail/{id}', 'getOrderDetail');
        Route::post('cancel/{id}', 'cancelOrder');
        Route::get('history', 'getOrderHistory');

    });
});

//payment
Route::prefix('payments')->controller(PaymentController::class)->group(function () {
    Route::middleware('check.auth:user_api')->group(function () {
        Route::post('vnpay', 'createVnPayPayment');
        Route::get('vnpay-return', 'vnpayReturn');
    });
    Route::get('', 'getAll');
});

//delivery
Route::prefix('deliveries')->controller(DeliveryController::class)->group(function () {
    Route::get('', 'getAll');
});

//Cart
Route::prefix('cart')->controller(CartController::class)->group(function () {
    Route::middleware('check.auth:user_api')->group(function () {
        Route::get('/', 'get');
        Route::get('/', 'get');
        Route::post('add', 'add');
        Route::post('update', 'update');
        Route::post('delete/{id}', 'delete');
        Route::post('delete-many', 'deleteMany');
    });
});

//Address
Route::get('/provinces', [VietnamZoneController::class, 'getProvinces']);
Route::get('/districts/{provinceId}', [VietnamZoneController::class, 'getDistricts']);
Route::get('/wards/{districtId}', [VietnamZoneController::class, 'getWards']);

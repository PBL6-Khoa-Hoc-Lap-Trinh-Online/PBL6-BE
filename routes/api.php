<?php

use App\Http\Controllers\ReceiverAddressController;
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
        Route::post('change-password', 'changePassword');
        Route::get('manage-users', 'manageUsers');
        Route::middleware('check.superadmin')->group(function(){
            Route::get('manage-admins', 'manageAdmins');
        }); 
    });
});


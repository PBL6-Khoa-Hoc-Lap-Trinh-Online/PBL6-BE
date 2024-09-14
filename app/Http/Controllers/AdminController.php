<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestForgotPassword;
use App\Http\Requests\RequestResetPassword;
use App\Http\Requests\RequestUpdateProfileAdmin;
use App\Http\Requests\RequestChangePassword;
 
use App\Services\AdminService;


class AdminController extends Controller
{
    protected AdminService $adminService;
    public function __construct(AdminService $adminService) {
        $this->adminService = $adminService;
    }
    public function login(RequestLogin $request)
    {
        return $this->adminService->login($request);
    }
    public function forgotPassword(RequestForgotPassword $request)
    {
        return $this->adminService->forgotPassword($request);
    }
    public function resetPassword(RequestResetPassword $request)
    {
        return $this->adminService->resetPassword($request);
    }
    public function logout(Request $request){
        return $this->adminService->logout($request);
    }
    public function profile(Request $request){
        return $this->adminService->profile($request);
    }
    public function updateProfile(RequestUpdateProfileAdmin $request){
        return $this->adminService->updateProfile($request);
    }
    public function changePassword(RequestChangePassword $request){
        return $this->adminService->changePassword($request);
    }
}
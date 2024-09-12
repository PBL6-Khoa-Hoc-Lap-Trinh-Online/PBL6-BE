<?php

namespace App\Services;

use App\Http\Requests\RequestLogin;
use App\Traits\APIResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use App\Repositories\AdminInterface;
use App\Enums\AdminEnum;
use App\Http\Requests\RequestForgotPassword;
use App\Http\Requests\RequestResetPassword;

use App\Http\Requests\RequestUserRegister;
use App\Jobs\SendForgotPassword;
use App\Jobs\SendVerifyEmail;
use App\Models\PasswordReset;
use Illuminate\Auth\Events\Registered;

use App\Models\Admin;

use Throwable;

class AdminService {
    use APIResponse;
    protected AdminInterface $adminRepository;
    public function __construct(AdminInterface $adminRepository)
    {
        $this->adminRepository = $adminRepository;
    }
    public function login(RequestLogin $request)
    {
        try {
            $admin = Admin::where('email', $request->email)->first();
            if (empty($admin)) {
                return $this->responseError('Email không tồn tại!');
            }
            if ($admin->admin_is_delete == 1) {
                return $this->responseError('Tài khoản đã bị xóa!');
            }
            // if ($admin->admin_is_block == 1) {
            //     return $this->responseError('Tài khoản đã bị khóa!');
            // }
            if ($admin->email_verified_at == null) {
                return $this->responseError('Email chưa được xác thực! Vui lòng xác thực email trước khi đăng nhập!');
            }

            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
            ];

            if (!auth()->guard('admin_api')->attempt($credentials)) {
                return $this->responseError('Mật khẩu không chính xác!');
            }
            $admin->access_token = auth()->guard('admin_api')->attempt($credentials);
            $admin->token_type = 'bearer';
            $admin->expires_in = auth()->guard('admin_api')->factory()->getTTL() * 60;

            return $this->responseSuccessWithData($admin, 'Đăng nhập thành công!');
        } catch (Throwable $e) {
            dd($e->getMessage());
            return $this->responseError($e->getMessage());
        }
    }
}
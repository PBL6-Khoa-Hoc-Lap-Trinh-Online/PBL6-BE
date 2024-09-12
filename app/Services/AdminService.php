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

    public function forgotPassword(RequestForgotPassword $request)
    {
        DB::beginTransaction();
        try {
            $email = $request->email;
            $admin = Admin::where('email', $email)->first();
            if (empty($admin)) {
                DB::rollback();
                return $this->responseError('Email không tồn tại trong hệ thống!');
            }
            $token = Str::random(32);
            PasswordReset::create([
                'email' => $request->email,
                'token' => $token,
            ]);
            $url = AdminEnum::FORGOT_PASSWORD_ADMIN . $token;
            Log::info("Add jobs to Queue, Email:$email with URL: $url");
            Queue::push(new SendForgotPassword($email, $url));
            DB::commit();
            return $this->responseSuccess('Link form đặt lại mật khẩu đã được gửi tới email của Bạn!');
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }

    public function resetPassword(RequestResetPassword $request)
    {
        DB::beginTransaction();
        try {
            $token = $request->token ?? '';
            $newPassword = $request->new_password;
            $passwordReset = PasswordReset::where('token', $token)->first();
            if ($passwordReset) {
                $admin = Admin::where('email', $passwordReset->email)->first();
                $data = [
                    'password' => Hash::make($newPassword),
                ];
                $admin->update($data);
                $passwordReset->delete();
                DB::commit();
                return $this->responseSuccess('Đặt lại mật khẩu thành công!');
            } else {
                DB::rollback();
                return $this->responseError('Token đã hết hạn!', 400);
            }
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }

    public function logout()
    {
        try {
            auth('admin_api')->logout();
            return $this->responseSuccess('Đăng xuất thành công!');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
}
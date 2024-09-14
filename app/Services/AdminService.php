<?php

namespace App\Services;

use App\Enums\AdminEnum;

use App\Traits\APIResponse;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\Registered;

use App\Repositories\AdminInterface;

use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestForgotPassword;
use App\Http\Requests\RequestResetPassword;
use App\Http\Requests\RequestUpdateProfileAdmin;
use App\Http\Requests\RequestUserRegister;
use App\Http\Requests\RequestChangePassword;

use App\Jobs\SendForgotPassword;
use App\Jobs\SendVerifyEmail;
use App\Jobs\SendMailNotify;

use App\Models\Admin;
use App\Models\PasswordReset;


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
    public function profile(){
        try {
            $admin = auth('admin_api')->user();
            return $this->responseSuccessWithData($admin,'Lấy thông tin quản trị viên thành công');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function updateProfile(RequestUpdateProfileAdmin $request){
        DB::beginTransaction();
        try {
            $id_admin = auth('admin_api')->user()->admin_id;
            $admin = Admin::find($id_admin);
            // $email_admin = $admin->email; 
            
            if ($request->hasFile('admin_avatar')) {
                $image = $request->file('admin_avatar');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/avatar/admin',
                    'resource_type' => 'auto',
                ]);
                $url = $uploadFile->getSecurePath();
                
                if ($admin->admin_avatar) {
                    $parsedUrl = pathinfo($admin->admin_avatar);
                    $id_file = $parsedUrl['filename'];  // Lấy phần tên file mà không bao gồm phần mở rộng
                    
                    // Xóa tệp từ Cloudinary
                    Cloudinary::destroy($id_file);
                }
                
                $data = array_merge($request->all(), ['admin_avatar' => $url]);
                $admin->update($data);
            } else {
                $request['admin_avatar'] = $admin->admin_avatar;
                $admin->update($request->all());
            }

            // Check update email
            // if ($email_admin != $request->email) {
            //     $token = Str::random(32);
            //     $url = AdminEnum::VERIFY_MAIL_ADMIN . $token;
            //     Log::info("Thêm jobs vào hàng đợi, Email:$request->email, with url: $url");
            //     Queue::push(new SendVerifyEmail($request->email, $url));
                
            //     $content = 'Email của bạn đã được thay đổi thành ' . $admin->email . '.';
            //     Queue::push(new SendMailNotify($email_admin, $content));
                
            //     $data = [
            //         'token_verify_email' => $token,
            //         'email_verified_at' => null,
            //     ];
            //     $admin->update($data);
            //     DB::commit();
                
            //     return $this->responseSuccessWithData($admin, 'Cập nhật thông tin tài khoản thành công! Vui lòng kiểm tra email để xác thực tài khoản!', 200);
            // }

            DB::commit();
            return $this->responseSuccessWithData($admin, "Cập nhật thông tin tài khoản thành công!");
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }

    public function changePassword(RequestChangePassword $request){
        DB::beginTransaction();
        try{
            $id_admin = auth('admin_api')->user()->admin_id;
            $admin = Admin::find($id_admin);
            if(!(Hash::check($request->current_password, $admin->password))){
                return $this->responseError('Mật khẩu hiện tại không chính xác!');
            }
            $data = ['password' => Hash::make($request->new_password)];
            $admin->update($data);
            DB::commit();
            return $this->responseSuccess('Thay đổi mật khẩu thành công!');
        }
        catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }

}
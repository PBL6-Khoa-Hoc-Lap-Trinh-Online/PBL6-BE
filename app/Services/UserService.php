<?php

namespace App\Services;

use App\Enums\UserEnum;
use App\Http\Requests\RequestForgotPassword;
use App\Http\Requests\RequestLogin;
use App\Http\Requests\RequestResetPassword;
use App\Http\Requests\RequestUpdateProfileUser;
use App\Http\Requests\RequestUserRegister;
use App\Jobs\SendForgotPassword;
use App\Jobs\SendMailNotify;
use App\Jobs\SendVerifyEmail;
use App\Models\PasswordReset;
use App\Models\User;
use App\Repositories\UserInterface;
use App\Traits\APIResponse;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;

class UserService
{
    use APIResponse;
    protected UserInterface $userRepository;
    public function __construct(UserInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function register(RequestUserRegister $request)
    {
        DB::beginTransaction();
        try {
            //Kiểm tra xem email đã tồn tại trong cơ sở dữ liệu chưa
            $user = User::where('email', $request->email)->first();
            if ($user) {
                return $this->responseError('Email đã tồn tại! Vui lòng chọn email khác');
            }
            $data = [
                'user_fullname' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ];
            $user = User::create($data);
            //verify email
            $token = Str::random(32);
            $url = UserEnum::VERIFY_MAIL_USER . $token;
            Log::info("Add jobs to Queue, Email:$user->email, with url: $url");
            Queue::push(new SendVerifyEmail($user->email, $url));
            $data = [
                'token_verify_email' => $token,
            ];
            $user->update($data);
            DB::commit();
            return $this->responseSuccessWithData($user, 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản!', 201);
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }
    public function verifyEmail(Request $request)
    {
        DB::beginTransaction();
        try {
            $token = $request->token ?? '';
            // $user = $this->userRepository->findUserByTokenVerifyEmail($token);
            $user = User::where('token_verify_email', $token)->first();
            if ($user) {
                $data = [
                    'email_verified_at' => now(),
                    'token_verify_email' => null,
                ];
                $user->update($data);
                DB::commit();
                return $this->responseSuccess('Email đã xác thực thành công!');
            } else {
                return $this->responseError('Token đã hết hạn!');
            }
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function login(RequestLogin $request)
    {
        try {
            $user = User::where('email', $request->email)->first();
            if (empty($user)) {
                return $this->responseError('Email không tồn tại!');
            }
            if ($user->user_is_delete == 1) {
                return $this->responseError('Tài khoản đã bị xóa!');
            }
            if ($user->user_is_block == 1) {
                return $this->responseError('Tài khoản đã bị khóa!');
            }
            if ($user->email_verified_at == null) {
                return $this->responseError('Email chưa được xác thực! Vui lòng xác thực email trước khi đăng nhập!');
            }

            // $credentials = request(['email', 'password']);
            $credentials = [
                'email' => $request->email,
                'password' => $request->password,
            ];
            // dd(request()->all());  // In ra tất cả các dữ liệu trong request để kiểm tra

            if (!auth()->guard('user_api')->attempt($credentials)) {
                return $this->responseError('Mật khẩu không chính xác!');
            }
            $user->access_token = auth()->guard('user_api')->attempt($credentials);
            $user->token_type = 'bearer';
            $user->expires_in = auth()->guard('user_api')->factory()->getTTL() * 60;

            return $this->responseSuccessWithData($user, 'Đăng nhập thành công!');
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
            $user = User::where('email', $email)->first();
            if (empty($user)) {
                DB::rollback();
                return $this->responseError('Email không tồn tại trong hệ thống!');
            }
            $token = Str::random(32);
            PasswordReset::create([
                'email' => $request->email,
                'token' => $token,
            ]);
            $url = UserEnum::FORGOT_PASSWORD_USER . $token;
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
                $user = User::where('email', $passwordReset->email)->first();
                $data = [
                    'password' => Hash::make($newPassword),
                ];
                $user->update($data);
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
            auth('user_api')->logout();
            return $this->responseSuccess('Đăng xuất thành công!');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function profile(Request $request)
    {
        try {
            $user = auth('user_api')->user();
            return $this->responseSuccessWithData($user, 'Lấy thông tin người dùng thành công!');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function updateProfile(RequestUpdateProfileUser $request){
        DB::beginTransaction();
        try{
            $id_user = auth('user_api')->user()->user_id;
            $user = User::find($id_user);
            $email_user = $user->email; 
            if($request->hasFile('user_avatar')){
                $image = $request->file('user_avatar');
                $uploadFile = Cloudinary::upload($image->getRealPath(), [
                    'folder' => 'pbl6_pharmacity/avatar/user',
                    'resource_type' => 'auto',
                ]);
                $url = $uploadFile->getSecurePath();
                if($user->user_avatar){
                    $parsedUrl = pathinfo($user->user_avatar);
                    $id_file = $parsedUrl['filename'];  // Lấy phần tên file mà không bao gồm phần mở rộng
                    // Xóa tệp từ Cloudinary
                    Cloudinary::destroy($id_file);
                   
                }
                $data = array_merge($request->all(), ['user_avatar' => $url]);
                $user->update($data);
            }
            else{
                $request['user_avatar'] = $user->user_avatar;
                $user->update($request->all());
            }
            //check update email
            if($email_user != $request->email){
                $token = Str::random(32);
                $url = UserEnum::VERIFY_MAIL_USER . $token;
                Log::info("Thêm jobs vào hàng đợi, Email:$request->email, with url: $url");
                Queue::push(new SendVerifyEmail($request->email, $url));
                $content= 'Email của bạn đã được thay đổi thành '.$user->email. '.';
                Queue::push(new SendMailNotify($email_user, $content));
                $data = [
                    'token_verify_email' => $token,
                    'email_verified_at' => null,
                ];
                $user->update($data);
                DB::commit();
                return $this->responseSuccessWithData($user, 'Cập nhật thông tin tài khoản thành công! Vui lòng kiểm tra email để xác thực tài khoản!', 200);
            }

            DB::commit();
            return $this->responseSuccessWithData($user, "Cập nhật thông tin tài khoản thành công!");
        }
        catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }
}

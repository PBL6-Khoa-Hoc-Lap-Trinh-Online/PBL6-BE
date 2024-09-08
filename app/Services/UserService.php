<?php
namespace App\Services;

use App\Enums\UserEnum;
use App\Http\Requests\RequestUserRegister;
use App\Jobs\SendVerifyEmail;
use App\Models\User;
use App\Repositories\UserInterface;
use App\Traits\APIResponse;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Throwable;
class UserService{
    use APIResponse;
    protected UserInterface $userRepository;
    public function __construct(UserInterface $userRepository){
        $this->userRepository = $userRepository;
    }
    public function register(RequestUserRegister $request){
        DB::beginTransaction();
        try{
            //Kiểm tra xem email đã tồn tại trong cơ sở dữ liệu chưa
            $user = User::where('user_email',$request->email)->first();
            if($user){
                return $this->responseError('Email đã tồn tại! Vui lòng chọn email khác');
            }
            $data = [
                'user_fullname' => $request->fullname,
                'user_email' => $request->email,
                'user_password' => Hash::make($request->password),
            ];
            $user = User::create($data);
           //verify email
            $token = Str::random(32);
            $url = UserEnum::VERIFY_MAIL_USER . $token; 
            Log::info("Add jobs to Queue, Email:$user->user_email, with url: $url");
            Queue::push(new SendVerifyEmail($user->user_email, $url));
            $data=[
                'token_verify_email' => $token,
            ];
            $user->update($data);
            DB::commit();
            return $this->responseSuccessWithData($user, 'Đăng ký thành công! Vui lòng kiểm tra email để xác thực tài khoản!',201);
        }catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage());
            
        }

    }
    public function verifyEmail(Request $request){
        DB::beginTransaction();
        try{
            $token = $request->token ?? '';
            // $user = $this->userRepository->findUserByTokenVerifyEmail($token);
            $user = User::where('token_verify_email', $token)->first();
            if($user){
                $data = [
                    'email_verified_at' => now(),
                    'token_verify_email' => null,
                ];
                $user->update($data);
                DB::commit();
                return $this->responseSuccess('Email đã xác thực thành công!');
            }else{
                return $this->responseError('Token đã hết hạn!');
            }
        }catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }
}
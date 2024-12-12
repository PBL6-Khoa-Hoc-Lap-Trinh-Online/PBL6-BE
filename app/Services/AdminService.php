<?php

namespace App\Services;

use App\Enums\AdminEnum;

use App\Repositories\RoleInterface;
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
use App\Http\Requests\RequestAddAdmin;
use App\Http\Requests\RequestResendVerifyEmail;

use App\Jobs\SendForgotPassword;
use App\Jobs\SendVerifyEmail;
use App\Jobs\SendMailNotify;

use App\Models\Admin;
use App\Models\User;
use App\Models\PasswordReset;
use App\Models\Role;
use App\Repositories\RoleRepository;
use App\Repositories\UserInterface;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Throwable;

class AdminService {
    use APIResponse;
    protected AdminInterface $adminRepository;
    protected UserInterface $userRepository;
    public function __construct(AdminInterface $adminRepository, UserInterface $userRepository)
    {
        $this->adminRepository = $adminRepository;
        $this->userRepository = $userRepository;
    }
    // Gán quyền trực tiếp cho Admin( Admin - Permission)
    public function assignPermissionToAdmin(Request $request,$admin_id){
        DB::beginTransaction();
        try {
            $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer|exists:permissions,permission_id'
            ]);
            $admin = Admin::find($admin_id);
            if(!$admin){
                return $this->responseError('Admin không tồn tại',404);
            }
            //Lấy danh sách quyền trực tiếp đã có từ DB
            $existingPermission=DB::table('admin_permission')
                                ->where('admin_id',$admin->admin_id)
                                ->whereIn('permission_id',$request->permission_ids)
                                ->pluck('permission_id')
                                ->toArray();
            $permissionOfRole=DB::table('role_permission')
                                ->join('roles','role_permission.role_id','=','roles.role_id')
                                ->where('roles.role_id',$admin->role_id)
                                ->pluck('permission_id')
                                ->toArray();
            $permissionAll=array_unique(array_merge($existingPermission,$permissionOfRole));
            //Loại bỏ các quyền trùng lặp
            $newPermission=array_diff($request->permission_ids, $permissionAll);
            if(!empty($newPermission)){
                //Gán quyền mới 
                $insertData=array_map(function($permission_id) use ($admin){
                    return [
                        'admin_id'=>$admin->admin_id,
                        'permission_id'=>$permission_id,
                        'created_at'=>now(),
                        'updated_at'=>now()
                    ];
                },$newPermission);
                DB::table('admin_permission')->insert($insertData);
                DB::commit();
                return $this->responseSuccess('Gán permission thành công',200);
            }
            else{
                return $this->responseError('Các permission đã tồn tại trong admin',400);
            }
           
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(),500);
        }
    }
   public function changeRole(Request $request){
        DB::beginTransaction();
        try{
            $request->validate(
                [
                    'role_id' => 'required|integer|exists:roles,role_id',
                ]
            );
            $admin_id = $request->route('id');
            $admin = Admin::find($admin_id);
            if(!$admin){
                return $this->responseError('Admin không tồn tại',404);
            }
            $role_id = $request->role_id;
            $admin->update(['role_id' => $role_id]);
            DB::commit();
            return $this->responseSuccess('Gán role thành công',200);
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(), 500);
        }
   }
   public function hasPermission(int $admin_id): bool{
        return DB::table('admin_permission')->where('admin_id', $admin_id)->exists();
   }
    public function removePermissionFromAdmin(Request $request,$admin_id){
        DB::beginTransaction();
        try{
            $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer|exists:permissions,permission_id'
            ]);
            $admin = Admin::find($admin_id);
            if (!$admin) {
                return $this->responseError('Admin không tồn tại', 404);
            }
            if(!$this->hasPermission($admin_id)){
                return $this->responseError('Admin không có permission riêng',404);
            }
            $deleted = DB::table('admin_permission')
                        ->where('admin_id',$admin->admin_id)
                        ->whereIn('permission_id',$request->permission_ids)
                        ->delete();
            if($deleted){
                DB::commit();
                return $this->responseSuccess('Thu hồi permission thành công',200);
            }
            return $this->responseError('Thu hồi permission thất bại',500);
            
        } catch (Throwable $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(), 500);
        }
    }
    public function getPermissionAndRoleName($admin)
    {
        $roleId = $admin->role_id;
        $roleName = Role::where('role_id', $roleId)->pluck('role_name')->get(0);
        $admin->role_name = $roleName;
        $permissions = RoleRepository::getPermissionByRole($roleId);
        $admin->permission_by_role = $permissions;
        $admin->permission_private = $this->adminRepository->getPermissionOfAdmin($admin->admin_id)->get();
        return $admin;
    }
    public function getById($id)
    {
        try {
            $admin = Admin::find($id);
            if (empty($admin)) {
                return $this->responseError('Không tìm thấy quản trị viên', 404);
            }
            $admin = $this->getPermissionAndRoleName($admin);
            $data = $admin;
            return $this->responseSuccessWithData($data, 'Lấy thông tin quản trị viên thành công');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
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
            $admin->role = 'admin';
            $data = $admin;
            return $this->responseSuccessWithData($data, 'Đăng nhập thành công!');
        } catch (Throwable $e) {
            // dd($e->getMessage());
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
            $$admin = $this->getPermissionAndRoleName($admin);
            $data = $admin;
            return $this->responseSuccessWithData($data,'Lấy thông tin quản trị viên thành công');
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
                
                $data = array_merge($request->all(), ['admin_avatar' => $url,'admin_updated_at'=>now()]);
                $admin->update($data);
            } else {
                $request['admin_avatar'] = $admin->admin_avatar;
                $admin->update($request->all(),['admin_updated_at'=>now()]);
            }

            DB::commit();
            $data=$admin;
            return $this->responseSuccessWithData($data, "Cập nhật thông tin tài khoản thành công!");
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
            $admin->update($data, ['admin_updated_at' => now()]);
            DB::commit();
            return $this->responseSuccess('Thay đổi mật khẩu thành công!');
        }
        catch(Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage(), 400);
        }
    }

    public function manageUsers(Request $request){
        try {
            $orderBy = $request->orderBy ?? 'user_id';
            $orderDirection=$request->sortlatest ?? 'true';
            switch($orderBy){
                case 'user_fullname':
                    $orderBy = 'user_fullname';
                    break;
                case 'email':
                    $orderBy = 'email';
                    break;
                case 'user_gender':
                    $orderBy = 'user_gender';
                    break;
                case 'user_birthday':
                    $orderBy = 'user_birthday';
                    break;
                case 'new':
                    $orderBy = "user_id";
                    break;
                default:
                    $orderBy = 'user_id';
                    break;
            }
            switch($orderDirection){
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter= (object) [
                'search' => $request->search ?? '',
                'user_is_delete' => $request->user_is_delete ?? 'all',
                'user_is_block' => $request->user_is_block ?? 'all',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
            $users = $this->userRepository->getAllUser($filter);
            if(!(empty($request->paginate))){
                $users = $users->paginate($request->paginate);
            }
            else{
                $users = $users->get();
            }

            $data=$users;
            return $this->responseSuccessWithData($data, 'Lấy danh sách người dùng thành công!');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }
    
    public function manageAdmins(Request $request){
        try {
            $adminId=auth('admin_api')->user()->admin_id;
            $orderBy = $request->orderBy ?? 'admin_id';
            $orderDirection = $request->sortlatest ?? 'true';
            switch ($orderBy) {
                case 'admin_fullname':
                    $orderBy = 'admin_fullname';
                    break;
                case 'email':
                    $orderBy = 'email';
                    break;
                case 'admin_is_admin':
                    $orderBy = 'admin_is_admin';
                    break;
                case 'new':
                    $orderBy = "admin_id";
                    break;
                default:
                    $orderBy = 'admin_id';
                    break;
            }
            switch ($orderDirection) {
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter = (object) [
                'search' => $request->search ?? '',
                'orderBy' => $orderBy,
                'admin_is_delete' => $request->admin_is_delete ?? 'all',
                'admin_id' => $adminId ??'',
                'role_id' => $request->role_id ?? '',
                'role_name' => $request->role_name??'' ,
                'orderDirection' => $orderDirection,
            ];
            $admins = $this->adminRepository->getAllAdmin($filter);
            if($admins->get()->isEmpty()){
                return $this->responseError('Không tìm thấy quản trị viên',404);
            }
            if (!(empty($request->paginate))) {
                $admins = $admins->paginate($request->paginate);
            } else {
                $admins = $admins->get();
            }
            $data = $admins;
            return $this->responseSuccessWithData($data, 'Danh sách quản trị viên được lấy thành công!');
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
    }

    public function changeBlock(Request $request){
        DB::beginTransaction();
        try {
            $user_id = $request->route('id');
            $user = User::find($user_id);

            if (empty($user)) {
                return $this->responseError('Người dùng không tồn tại!');
            }

            $status_block = ! $user->user_is_block;
            $new_block = ['user_is_block' => $status_block];   
            $user->update($new_block, ['user_updated_at' => now()]);
            DB::commit(); 
            
            $status = ($status_block==0) ? 'được mở khóa' : 'bị khóa';
            return $this->responseSuccess("Tài khoản người dùng đã $status thành công!");

            
        } catch (Throwable $e){
            return $this->responseError($e->getMessage());
        }
    }

    public function deleteUser(Request $request){
        DB::beginTransaction();
        try {
            $user_id = $request->route('id');
            $user = User::find($user_id);

            if (empty($user)) {
                return $this->responseError('Người dùng không tồn tại!');
            }

            $status_delete = ! $user->user_is_delete;
            $new_delete = ['user_is_delete' => $status_delete];   
            $user->update($new_delete, ['user_updated_at' => now()]);
            DB::commit(); 
            
            $status = ($status_delete==0) ? 'được khôi phục' : 'xóa';
            return $this->responseSuccess("Tài khoản người dùng đã $status thành công!");
        } catch (Throwable $e){
            return $this->responseError($e->getMessage());
        }

    }
    //Add admin yêu cầu request thêm role_id 
    // Chỉ hiện thị role_id của admin và supper admin cho manager chọn
    // Nếu mở rộng thì Supperadmin quản lý danh sách admin --> Thêm admin
    // Manager quản lý SupperAdmin và Admin--> Thêm Admin và SupperAdmin đều được
    // Vậy việc hiển thị role cho Manager hay SupperAdmin cần có một điều kiện

    public function addAdmin(RequestAddAdmin $request){
        DB::beginTransaction();
        try {
            $admin = Admin::where('email', $request->email)->first();
            if ($admin) {
                return $this->responseError('Email đã tồn tại! Vui lòng chọn email khác');
            }

            $data = [
                'admin_fullname' => $request->admin_fullname,
                'email' => $request->email,
                // 'password' => Hash::make(Str::random(8)),
                'role_id'=> $request->role_id,
                'password' => Str::random(8),
                'admin_created_at' => now(),

            ];
            $admin = Admin::create($data);

            $token = Str::random(32);
            $url = AdminEnum::VERIFY_MAIL_ADMIN . $token;
            Log::info("Add jobs to Queue, Email:$admin->email, with url: $url");
            Queue::push(new SendVerifyEmail($admin->email, $url));
            $data = [
                'token_verify_email' => $token,
                'admin_updated_at' => now(),
            ];
            $admin->update($data);
            DB::commit();
            $data=$admin;
            return $this->responseSuccessWithData($data, 'Thêm tài khoản admin thành công!', 201);
            
        } catch (Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage());
        }
    }

    public function verifyEmail(Request $request){
        DB::beginTransaction();
        try {
            $token = $request->token ?? '';

            $admin = Admin::where('token_verify_email', $token)->first();

            if ($admin) {
                $content = 'Mật khẩu tài khoản của bạn là:  ' . $admin->password;
                
                $data = [
                    'email_verified_at' => now(),
                    'token_verify_email' => null,
                    'password' => Hash::make($admin->password),
                    'admin_updated_at' => now(),
                ];
                $admin->update($data);
                DB::commit();
                Queue::push(new SendMailNotify($admin->email, $content));

                return $this->responseSuccess('Email đã xác thực thành công! Mật khẩu đăng nhập đã được gửi đến email');
            } else {
                return $this->responseError('Token đã hết hạn!');
            }
        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }

    public function resendVerifyEmail(RequestResendVerifyEmail $request){
        DB::beginTransaction();
        try {
            $email = $request->email;
            $admin = Admin::where('email', $email)->first();
            if (empty($admin)) {
                DB::rollback();
                return $this->responseError('Email không tồn tại trong hệ thống!');
            }

            if ($admin->email_verified_at != NULL) {
                DB::rollback();
                return $this->responseError('Email đã được xác thực!');
            }

            $token = Str::random(32);
            $url = AdminEnum::VERIFY_MAIL_ADMIN . $token;
            Log::info("Add jobs to Queue, Email:$admin->email, with url: $url");
            Queue::push(new SendVerifyEmail($admin->email, $url));
            $data = [
                'token_verify_email' => $token,
                'admin_updated_at' => now(),
            ];
            $admin->update($data);
            DB::commit();
            $data=$admin;
            return $this->responseSuccessWithData($data, 'Đã gửi lại link xác thực! Vui lòng kiểm tra email để xác thực tài khoản!', 201);

        } catch (Throwable $e) {
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }


    public function deleteAdmin(Request $request){
        DB::beginTransaction();
        try {
            $admin_id = $request->route('id');
            $admin = Admin::find($admin_id);

            if (empty($admin)) {
                return $this->responseError('Quản trị viên không tồn tại!');
            }

            $current_admin = auth('admin_api')->user();

            if ($current_admin->admin_id == $admin->admin_id) {
                DB::rollback();
                return $this->responseError('Bạn không thể xóa/khôi phục tài khoản chính mình!', 403);
            }

            if ($current_admin->admin_is_admin <= $admin->admin_is_admin){
                DB::rollback();
                return $this->responseError('Chỉ có quyền xóa/khôi phục tài khoản quản trị viên có bậc thấp hơn');
            }

            $status_delete = ! $admin->admin_is_delete;
            $new_delete = ['admin_is_delete' => $status_delete];   
            $admin->update($new_delete);
            DB::commit(); 
            
            $status = ($status_delete==0) ? 'được khôi phục' : 'xóa';
            return $this->responseSuccess("Quản trị viên đã $status thành công!");
        } catch (Throwable $e){
            DB::rollback();
            return $this->responseError($e->getMessage());
        }
    }
}
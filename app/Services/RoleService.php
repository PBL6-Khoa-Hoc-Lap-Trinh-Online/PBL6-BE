<?php 
namespace App\Services;

use App\Http\Requests\RequestAddRole;
use App\Http\Requests\RequestUpdateRole;
use App\Models\Admin;
use App\Models\Role;
use App\Repositories\RoleInterface;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class RoleService{
    use APIResponse;
    protected RoleInterface $roleRepository;
    public function __construct(RoleInterface $roleRepository){
        $this->roleRepository = $roleRepository;
    }
    public function addPermissionToRole(Request $request, $role_id){
        DB::beginTransaction();
        try{
            $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer|exists:permissions,permission_id'
            ]);
            $role = Role::find($role_id);
            if(!$role){
                return $this->responseError('Không tìm thấy role',404);
            }
            $existingPermission = DB::table('role_permission')
                ->where('role_id', $role_id)
                ->whereIn('permission_id', $request->permission_ids)
                ->pluck('permission_id')
                ->toArray();

            //Loại bỏ các quyền trùng lặp
            $newPermission = array_diff($request->permission_ids, $existingPermission);
            if(count($newPermission) === 0){
                return $this->responseError('Các permission đã tồn tại trong role',400);
            }
            $insertData = array_map(function ($permission_id) use ($role) {
                return [
                    'role_id' => $role->role_id,
                    'permission_id' => $permission_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
            }, $newPermission);
            DB::table('role_permission')->insert($insertData);
            DB::commit();
            $data = $role;
            $data['permissions'] = $this->roleRepository->getPermissionByRole($role_id);
            return $this->responseSuccessWithData($data,'Gán permission thành công', 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage(),500);
        }
    }
    public function removePermissionFromRole(Request $request, $role_id){
        DB::beginTransaction();
        try{
            $request->validate([
                'permission_ids' => 'required|array',
                'permission_ids.*' => 'integer|exists:permissions,permission_id'
            ]);
            $role = Role::find($role_id);
            if(!$role){
                return $this->responseError('Không tìm thấy role',404);
            }
            $existingPermission = DB::table('role_permission')
                ->where('role_id', $role_id)
                ->whereIn('permission_id', $request->permission_ids)
                ->pluck('permission_id')
                ->toArray();
            if(count($existingPermission) === 0){
                return $this->responseError('Không tìm thấy permission trong role',404);
            }
            DB::table('role_permission')
                ->where('role_id', $role_id)
                ->whereIn('permission_id', $request->permission_ids)
                ->delete();
            DB::commit();
            $data = $role;
            $data['permissions'] = $this->roleRepository->getPermissionByRole($role_id);
            return $this->responseSuccessWithData($data,'Xoá permission thành công', 200);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Xoá permission thất bại',500);
        }
    }
    public function add(RequestAddRole $request){
        DB::beginTransaction();
        try{
            $data = Role::create($request->all());
            DB::commit();
            return $this->responseSuccessWithData($data, 'Thêm role thành công',201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Thêm role thất bại',500);
        }
    }
    //Get role chi tiết + permission
    public function get($id){
        $data = Role::find($id);
        if($data){
            $data['permissions'] = $this->roleRepository->getPermissionByRole($id);
            return $this->responseSuccessWithData($data, 'Lấy role thành công',200);
        }
        return $this->responseError('Không tìm thấy role',404);
    }
    // Update thông tin role. Có update update permission_id của role không? Kiểm thêm bớt permission chẳng hạn.
    public function update(RequestUpdateRole $request, $id){
        DB::beginTransaction();
        try{
            $data = Role::find($id);
            if($data){
                $data->update($request->all());
                DB::commit();
                return $this->responseSuccessWithData($data, 'Cập nhật role thành công',200);
            }
            return $this->responseError('Không tìm thấy role',404);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Cập nhật role thất bại',500);
        }
    }
    
    // Hiển thị Role và Permission tương ứng
    
    public function getAll(Request $request){
        try {
            $orderBy = $request->typesort ?? 'role_id';
            switch ($orderBy) {
                case 'role_name':
                    $orderBy = 'role_name';
                    break;
                case 'new':
                    $orderBy = "role_id";
                    break;
            }
            $orderDirection = $request->sortlatest ?? 'true';
            switch ($orderDirection) {
                case 'true':
                    $orderDirection = 'DESC';
                    break;
                default:
                    $orderDirection = 'ASC';
                    break;
            }
            $filter = [
                'search' => $request->search,
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
                'role_id' => $request->role_id
            ];
            $data = $this->roleRepository->getAll($filter);
            if($request->paginate){
                $data = $data->paginate($request->paginate);
            }else{
                $data = $data->get();
            }
            if($data->isEmpty()){
                return $this->responseError('Không tìm thấy role',404);
            }
            return $this->responseSuccessWithData($data, 'Lấy danh sách role thành công',200);
        } catch (Throwable $e) {
            return $this->responseError('Lấy danh sách role thất bại',500);
        }
    }
    public function hasAdmins(int $roleId): bool
    {
        // Kiểm tra xem có admin nào liên kết với role này không
        return DB::table('admins')->where('role_id', $roleId)->exists();
    }
    public function hasPermissions(int $roleId): bool
    {
        // Kiểm tra xem có permission nào liên kết với role này không
        return DB::table('role_permission')->where('role_id', $roleId)->exists();
    }
   
    public function delete($id){
        DB::beginTransaction();
        try{
            $data = Role::find($id);
            if($data){
                if($this->hasAdmins($id)){
                    return $this->responseError('Role này đang được sử dụng',400);
                }
                if($this->hasPermissions($id)){
                    DB::table('role_permission')->where('role_id',$id)->delete();
                }
                $data->delete();
                DB::commit();
                return $this->responseSuccess('Xóa role thành công',200);
            }
            return $this->responseError('Không tìm thấy role',404);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError($e->getMessage(),500);
        }
    }
}
<?php
namespace App\Services;

use App\Http\Requests\RequestAddPermission;
use App\Http\Requests\RequestUpdatePermission;
use App\Models\Permission;
use App\Repositories\PermissionInterface;
use App\Traits\APIResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Throwable;

class PermissionService{
    use APIResponse;
    protected PermissionInterface $permissionInterface;
    public function __construct(PermissionInterface $permissionInterface)
    {
        $this->permissionInterface = $permissionInterface;
    }
    public function add(RequestAddPermission $request){
        DB::beginTransaction();
        try{
            $data = Permission::create($request->all());
            DB::commit();
            return $this->responseSuccessWithData($data, 'Thêm permission thành công',201);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Thêm permission thất bại',500);
        }
    }
    public function get($id){
        $data = Permission::find($id);
        if($data){
            return $this->responseSuccessWithData($data, 'Lấy permission thành công',200);
        }
        return $this->responseError('Không tìm thấy permission',404);
    }
    public function update(RequestUpdatePermission $request, $id){
        DB::beginTransaction();
        try{
            $data = Permission::find($id);
            if($data){
                $data->update($request->all());
                DB::commit();
                return $this->responseSuccessWithData($data, 'Cập nhật permission thành công',200);
            }
            return $this->responseError('Không tìm thấy permission',404);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Cập nhật permission thất bại',500);
        }
    }
    //Hiển thị danh sách permission
    public function getAll(Request $request){
        try {
            $orderBy = $request->typesort ?? 'permission_id';
            switch ($orderBy) {
                case 'permission_name':
                    $orderBy = 'permission_name';
                    break;
                case 'new':
                    $orderBy = "permission_id";
                    break;
                default:
                    $orderBy = 'permission_id';
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
            $filter = (object) [
                'search' => $request->search ?? '',
                'orderBy' => $orderBy,
                'orderDirection' => $orderDirection,
            ];
           $permissions = $this->permissionInterface->getAll($filter);
            if (!(empty($request->paginate))) {
                $permissions = $permissions->paginate($request->paginate);
            } else {
                $permissions = $permissions->get();
            }
            if ($permissions->count() === 0) {
                return $this->responseError('Không tìm thấy permission', 404);
            }
            $data = $permissions;
            return $this->responseSuccessWithData($data, "Lấy danh sách permission thành công!");
        } catch (Throwable $e) {
            return $this->responseError($e->getMessage());
        }
      
    }
    public function hasRoles(int $permissionId): bool
    {
       return DB::table('role_permission')->where('permission_id', $permissionId)->exists();
    }
    public function hasAdmins(int $permissionId): bool
    {
        return DB::table('admin_permission')->where('permission_id', $permissionId)->exists();
    }
    public function delete($id){
        DB::beginTransaction();
        try{
            $data = Permission::find($id);
            if($data){
                if($this->hasAdmins($id)){
                    DB::tables('admin_permission')->where('permission_id', $id)->delete();
                }
                if($this->hasRoles($id)){
                    DB::tables('role_permission')->where('permission_id', $id)->delete();
                }
                $data->delete();
                DB::commit();
                return $this->responseSuccess('Xóa permission thành công',200);
            }
            return $this->responseError('Không tìm thấy permission',404);
        }
        catch(Throwable $e){
            DB::rollBack();
            return $this->responseError('Xóa permission thất bại',500);
        }
    }
    
}

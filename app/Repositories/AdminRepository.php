<?php
namespace App\Repositories;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
class AdminRepository extends BaseRepository implements AdminInterface
{
    public function getModel()
    {
        return Admin::class;
    }

    public static function getAllAdmin($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model->selectRaw('admins.*, roles.role_name')
            ->leftJoin('roles', 'admins.role_id', '=', 'roles.role_id')
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('admin_fullname', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('email', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(isset($filter->admin_is_delete), function ($query) use ($filter) {
                if ($filter->admin_is_delete !== 'all') {
                    $query->where('admins.admin_is_delete', $filter->admin_is_delete);
                }
            })
            ->when(!empty($filter->role_id), function ($query) use ($filter) {
                $query->where('admins.role_id','<', $filter->role_id);
            })
            ->when(!empty($filter->admin_id), function ($query) use ($filter) {
                $query->where('admin_id','!=', $filter->admin_id);
            })
            ->when(!empty($filter->role_name), function ($query) use ($filter) {
                $query->where('roles.role_name', $filter->role_name);
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            });
            
        return $data;
    }
    
    public static function getPermissionOfAdmin($adminId){
        $data = DB::table('admin_permission')
                ->join('permissions', 'admin_permission.permission_id', '=', 'permissions.permission_id')
                ->where('admin_permission.admin_id', $adminId)
                ->select('permissions.permission_id', 'permissions.permission_name', 'permissions.permission_description');
        return $data;
    }
}
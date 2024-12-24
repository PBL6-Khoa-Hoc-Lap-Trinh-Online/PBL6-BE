<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Review;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

/**
 * Interface ExampleRepository.
 */
class RoleRepository extends BaseRepository implements RoleInterface
{
    public function getModel()
    {
        return Role::class;
    }
    public static function getAll($filter)
    {

        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('role_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('role_description', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            })
            ->when(!empty($filter->role_id), function ($query) use ($filter) {
                $query->where('role_id', $filter->role_id);
            });
           

        return $data;
    }
    public static function getPermissionByRole($role_id)
    {
        $data = Permission::select('permissions.permission_id', 'permissions.permission_name', 'permissions.permission_description')
            ->join('role_permission', 'permissions.permission_id', '=', 'role_permission.permission_id')
            ->where('role_permission.role_id', $role_id)
            ->get();
        return $data;
    }
}

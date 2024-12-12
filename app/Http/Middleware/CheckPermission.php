<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\Role;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CheckPermission
{
    
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        $admin = auth('admin_api')->user();
        if (! $admin) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
        
        $permissionRole=DB::table('role_permission')
                        ->join('permissions','role_permission.permission_id','=','permissions.permission_id')
                        ->where('role_permission.role_id',$admin->role_id)
                        ->pluck('permission_name')
                        ->toArray();
        $permissionAdmin=DB::table('admin_permission')
                            ->join('permissions','admin_permission.permission_id','=','permissions.permission_id')
                            ->where('admin_permission.admin_id',$admin->admin_id)
                            ->pluck('permission_name')
                            ->toArray();
        $permissionsAll = array_merge($permissionRole,$permissionAdmin);
       
        if (array_intersect($permissionsAll, $permissions)) {
            return $next($request);
        }

        return response()->json(['message' => 'Bạn không có quyền truy cập!'], 403); 
    }
}

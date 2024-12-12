<?php

namespace App\Http\Middleware;

use App\Traits\APIResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class CheckRole
{
    use APIResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $admin = auth('admin_api')->user();
        if (! $admin) {
            return response()->json(['status' => 'Bạn không có quyền truy cập!'], 403); 
            // return $this->responseError('Unauthorized', 401);
        }
        $roleName = DB::table('roles')
                    ->where('role_id', $admin->role_id) // Sửa thành 'where'
                    ->value('role_name');
        if (in_array($roleName,$roles)){
            return $next($request);
        }
        // return $this->responseError('Forbidden', 403);
        return response()->json(['status' => 'Bạn không có quyền truy cập!'],403); 
    }
}

<?php

namespace App\Http\Middleware;

use App\Traits\APIResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAuthenticate
{
    use APIResponse;
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
  
    public function handle(Request $request, Closure $next, ...$guards)
    {
        foreach ($guards as $guard) {
            return $next($request);
        }
        foreach ($guards as $guard) {
            if ($guard == 'user_api') {
                return $this->responseError('Unauthenticated!', 401);
            }
            if ($guard == 'admin_api') {
                return $this->responseError('Unauthenticated!', 401);
            }
        }
    }
}

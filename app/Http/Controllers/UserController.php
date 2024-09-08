<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestUserRegister;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected UserService $userService;
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function register(RequestUserRegister $request)
    {
        return $this->userService->register($request);
    }
    public function verifyEmail(Request $request)
    {
        return $this->userService->verifyEmail($request);
    }
    
}

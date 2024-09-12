<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

use App\Http\Requests\RequestLogin;
use App\Services\AdminService;


class AdminController extends Controller
{
    protected AdminService $adminService;
    public function __construct(AdminService $adminService) {
        $this->adminService = $adminService;
    }
    public function login(RequestLogin $request)
    {
        return $this->adminService->login($request);
    }
}

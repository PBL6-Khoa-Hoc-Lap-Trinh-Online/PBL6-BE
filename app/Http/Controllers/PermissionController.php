<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddPermission;
use App\Http\Requests\RequestUpdatePermission;
use App\Services\PermissionService;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    protected PermissionService $permissionService;
    public function __construct(PermissionService $permissionService){
        $this->permissionService = $permissionService;
    }
    public function add(RequestAddPermission $request){
        return $this->permissionService->add($request);
    }
    public function update(RequestUpdatePermission $request, $id){
        return $this->permissionService->update($request, $id);
    }
    public function get($id){
        return $this->permissionService->get($id);
    }
    public function getAll(Request $request){
        return $this->permissionService->getAll($request);
    }
    public function delete($id){
        return $this->permissionService->delete($id);
    }
}

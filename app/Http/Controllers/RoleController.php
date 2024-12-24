<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestAddRole;
use App\Http\Requests\RequestUpdateRole;
use App\Repositories\RoleInterface;
use App\Services\RoleService;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    protected RoleService $roleService;
    public function __construct(RoleService $roleService)
    {
        $this->roleService = $roleService;
    }
    public function add(RequestAddRole $request)
    {
        return $this->roleService->add($request);
    }
    public function update(RequestUpdateRole $request, $id)
    {
        return $this->roleService->update($request, $id);
    }
    public function get($id)
    {
        return $this->roleService->get($id);
    }
    public function getAll(Request $request)
    {
        return $this->roleService->getAll($request);
    }
    public function delete($id)
    {
        return $this->roleService->delete($id);
    }
    public function assignPermission(Request $request,$id)
    {
        return $this->roleService->addPermissionToRole($request, $id);
    }
    public function removePermission(Request $request,$id)
    {
        return $this->roleService->removePermissionFromRole($request, $id);
    }

  
}

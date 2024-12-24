<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface RoleInterface extends RepositoryInterface
{
    public static function getAll($filter);
    public static function getPermissionByRole($role_id);
}

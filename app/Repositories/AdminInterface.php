<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface AdminInterface extends RepositoryInterface
{
    public static function getAllAdmin($filter);
    public static function getPermissionOfAdmin($admin_id);
}

<?php

namespace App\Repositories;

/**
 * Interface ExampleRepository.
 */
interface PermissionInterface extends RepositoryInterface
{
    public static function getAll($filter);
}

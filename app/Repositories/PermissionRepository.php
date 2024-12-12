<?php

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

/**
 * Interface ExampleRepository.
 */
class PermissionRepository extends BaseRepository implements PermissionInterface
{
    public function getModel()
    {
        return Permission::class;
    }
    public static function getAll($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('permission_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('permission_description', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            })
            ->when(!empty($filter->brand_id), function ($query) use ($filter) {
                $query->where('permission_id', $filter->brand_id);
            });
        return $data;
    }
}

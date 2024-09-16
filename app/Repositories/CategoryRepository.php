<?php
namespace App\Repositories;

use App\Models\Category;
class CategoryRepository extends BaseRepository implements CategoryInterface{
    public function getModel(){
        return Category::class;
    }
    public static function getCategory($filter)
    {
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->id), function ($q) use ($filter) {
                $q->where('category_id', $filter->id);
            })
            ->when(!empty($filter->ids_category), function ($q) use ($filter) {
                $q->whereIn('category_id', $filter->ids_category);
            });

        return $data;
    }
}
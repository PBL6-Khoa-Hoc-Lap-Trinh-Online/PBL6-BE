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
    public static function getAll($filter){
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where('category_name', 'LIKE', '%' . $filter->search . '%')
                    ->orWhere('category_type', 'LIKE', '%' . $filter->search . '%')
                    ->orWhere('category_description', 'LIKE', '%' . $filter->search . '%');
            })
            ->when(isset($filter->category_is_delete), function ($query) use ($filter) {
                if ($filter->category_is_delete !== 'all') {
                    $query->where('categories.category_is_delete', $filter->category_is_delete);
                }
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            });
        return $data;
    }
}
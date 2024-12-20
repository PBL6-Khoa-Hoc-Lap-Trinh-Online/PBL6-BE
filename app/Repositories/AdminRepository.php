<?php
namespace App\Repositories;
use App\Models\Admin;
class AdminRepository extends BaseRepository implements AdminInterface{
    public function getModel(){
        return Admin::class;
    }
   
    public static function getAllAdmin($filter)
    {
       $filter =(object) $filter;
       $data=(new self)->model->when(!empty($filter->search),function($q) use ($filter){
           $q->where(function($query) use ($filter){
               $query->where('admin_fullname','LIKE','%'.$filter->search.'%')
               ->orWhere('email','LIKE','%'.$filter->search.'%');
           });
            })->when(!empty($filter->orderBy),function($query) use ($filter){
           $query->orderBy($filter->orderBy,$filter->orderDirection);
            });
        return $data;
    }
  
}
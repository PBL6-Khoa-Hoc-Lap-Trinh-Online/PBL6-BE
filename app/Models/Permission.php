<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    protected $primaryKey = 'permission_id';
    protected $fillable = [
        'permission_id',
        'permission_name',
        'permission_description',
    ];
    public function roles(){
        return $this->belongsToMany(Role::class,'role_permission');
    }
    public function admins(){
        return $this->belongsToMany(Admin::class,'admin_permission');
    }
    
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Admin extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;
    protected $primaryKey ='admin_id';
    const CREATED_AT ='admin_created_at';
    const UPDATED_AT ='admin_updated_at';
    protected $fillable = [
        'admin_id',
        'role_id',
        'admin_fullname',
        'email',
        'password',
        'admin_avatar',
        //'admin_is_admin',
        'admin_is_delete',
        'token_verify_email',
        'email_verified_at',
        'remember_token',
        'admin_created_at',
        'admin_updated_at',
    ];
    

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'token_verify_email',
    ];
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
    public function role(){
        return $this->belongsTo(Role::class);
    }
    public function permissions(){
        return $this->belongsToMany(Permission::class,'admin_permission');
    }
    public function getAllPermissions()
    {
        // Lấy quyền từ role của admin
        $rolePermissions = $this->role ? $this->role->permissions->pluck('permission_name')->toArray() : [];

        // Lấy quyền trực tiếp từ admin
        $adminPermissions = $this->permissions->pluck('permission_name')->toArray();

        // Kết hợp và loại bỏ trùng lặp
        return array_unique(array_merge($rolePermissions, $adminPermissions));
    }

    public function hasPermissions($permission)
    {
        $allPermissions = $this->getAllPermissions();
        return in_array($permission, $allPermissions);
    }

   
}

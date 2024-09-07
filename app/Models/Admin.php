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
        'admin_fullname',
        'admin_email',
        'admin_password',
        'admin_avatar',
        'admin_is_admin',
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
        'admin_password',
        'remember_token',
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
}

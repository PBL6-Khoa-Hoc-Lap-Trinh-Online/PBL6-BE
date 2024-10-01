<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;
    protected $primaryKey = 'payment_id';
    protected $fillable = [
        'payment_id',
        'payment_method',
    ];
    public $timestamps = false;
    public function order(){
        return $this->hasOne(Order::class);
    }
}

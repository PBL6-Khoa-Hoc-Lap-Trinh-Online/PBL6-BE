<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;
    protected $primaryKey = 'delivery_id';
    protected $fillable = [
        'delivery_id',
        'delivery_method',
        'delivery_fee',
        'delivery_tracking_number',
        'delivery_description',
        'delivery_shipped_at',
    ];
    public $timestamps = false;
    public function order(){
        return $this->hasOne(Order::class);
    }

}

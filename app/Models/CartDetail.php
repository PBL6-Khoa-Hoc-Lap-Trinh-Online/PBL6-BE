<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartDetail extends Model
{
    use HasFactory;
    protected $primaryKey = 'cart_detail_id';
    protected $fillable = [
        'cart_detail_id',
        'cart_id',
        'product_id',
        'cart_quantity',
        'cart_price',
    ];
    public $timestamps = false;
    public function cart(){
        return $this->belongsTo(Cart::class);
    }
    public function product(){
        return $this->belongsTo(Product::class);
    }
}

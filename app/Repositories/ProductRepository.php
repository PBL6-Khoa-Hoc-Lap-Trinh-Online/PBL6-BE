<?php
namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository implements ProductInterface{
    public function getModel()
    {
       return  Product::class;
    }
}
<?php

namespace App\Repositories;

use App\Models\Brand;

class BrandRepository extends BaseRepository implements BrandInterface
{
    public function getModel()
    {
        return Brand::class;
    }
}

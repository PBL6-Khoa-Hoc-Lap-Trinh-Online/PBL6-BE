<?php
namespace App\Repositories;

use App\Models\Product;

class ProductRepository extends BaseRepository implements ProductInterface{
    public function getModel()
    {
       return  Product::class;
    }
    public static function getAll($filter){
        $filter = (object) $filter;
        $data = (new self)->model->selectRaw('products.*,categories.*,brands.*')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.category_id')
            ->leftJoin('brands', 'products.brand_id', '=', 'brands.brand_id')
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('product_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('product_uses', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('product_ingredients', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('dosage_form', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('product_package', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('specification', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('product_notes', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('place_of_manufacture', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('manufacturer', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('product_description', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(!empty($filter->category_name), function ($query) use ($filter) {
                return $query->where('categories.category_name', '=', $filter->category_name);
            })
            ->when(!empty($filter->brand_names), function ($query) use ($filter) {
                return $query->whereIn('brands.brand_name', $filter->brand_names);
            })
            ->when(!empty($filter->price_min) || !empty($filter->price_max), function ($query) use ($filter) {
                if (!empty($filter->price_min) && empty($filter->price_max)) {
                    return $query->where('product_price', '>=', $filter->price_min);
                } elseif (empty($filter->price_min) && !empty($filter->price_max)) {
                    return $query->where('product_price', '<=', $filter->price_max);
                } else {
                    return $query->whereBetween('product_price', [$filter->price_min, $filter->price_max]);
                }
            })
            ->when(!empty($filter->product_price), function ($query) use ($filter) {
                if ($filter->product_price == 'below_100k') {
                    return $query->where('product_price', '<', 100000);
                } elseif ($filter->product_price == '100k_300k') {
                    return $query->whereBetween('product_price', [100000, 300000]);
                } elseif ($filter->product_price == '300k_500k') {
                    return $query->whereBetween('product_price', [300000, 500000]);
                } elseif ($filter->product_price == 'above_500k') {
                    return $query->where('product_price', '>', 500000);
                }
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            })
            ->when(isset($filter->product_is_delete), function ($query) use ($filter) {
                if ($filter->product_is_delete === 'both') {
                } else {
                    $query->where('products.product_is_delete', $filter->product_is_delete);
                }
            })
            ->when(!empty($filter->product_id), function ($query) use ($filter) {
                $query->where('products.product_id', '=', $filter->product_id);
            });
        return $data;
    }
}
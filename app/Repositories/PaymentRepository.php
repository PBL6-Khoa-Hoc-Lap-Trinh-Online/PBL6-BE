<?php

namespace App\Repositories;

use App\Models\Payment;

class PaymentRepository extends BaseRepository implements DeliveryInterface
{
    public function getModel()
    {
        return Payment::class;
    }
    public static function getAll($filter)
    {
        //Xí quay lại viết logic ở đây
        $filter = (object) $filter;
        $data = (new self)->model
            ->when(!empty($filter->search), function ($q) use ($filter) {
                $q->where(function ($query) use ($filter) {
                    $query->where('delivery_method_name', 'LIKE', '%' . $filter->search . '%')
                        ->orWhere('delivery_method_description', 'LIKE', '%' . $filter->search . '%');
                });
            })
            ->when(isset($filter->delivery_method_is_delete), function ($query) use ($filter) {
                if ($filter->delivery_is_active !== 'all') {
                    $query->where('delivery_methods.delivery_is_active', $filter->delivery_is_active);
                }
            })
            ->when(!empty($filter->orderBy), function ($query) use ($filter) {
                $query->orderBy($filter->orderBy, $filter->orderDirection);
            })
            ->when(!empty($filter->delivery_method_id), function ($q) use ($filter) {
                $q->where('delivery_method_id', $filter->delivery_method_id);
            });
        return $data;
    }
}

<?php
namespace App\Repositories;
use App\Models\CartDetail;
/**
 * Interface ExampleRepository.
 */
class CartDetailRepository extends BaseRepository implements CartDetailInterface {
    public function getModel(){
        return CartDetail::class;
    }
}
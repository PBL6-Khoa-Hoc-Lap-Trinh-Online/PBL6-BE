<?php

namespace App\Repositories;

use App\Models\Supplier;

/**
 * Interface ExampleRepository.
 */
class SupplierRepository extends BaseRepository implements SupplierInterface {
    public function getModel(){
        return Supplier::class;
    }
    

}

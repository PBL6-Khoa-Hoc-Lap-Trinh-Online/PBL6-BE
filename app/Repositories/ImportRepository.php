<?php

namespace App\Repositories;

use App\Models\Import;

class ImportRepository extends BaseRepository implements ImportInterface {
    public function getModel()
    {
        return Import::class;
    }
}

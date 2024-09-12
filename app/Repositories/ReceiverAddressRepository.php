<?php 
namespace App\Repositories;

use App\Models\ReceiverAddress;

class ReceiverAddressRepository extends BaseRepository implements ReceiverAddressInterface
{
    public function getModel()
    {
        return ReceiverAddress::class;
    }
}
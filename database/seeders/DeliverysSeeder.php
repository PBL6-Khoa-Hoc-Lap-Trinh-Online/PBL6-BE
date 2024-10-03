<?php

namespace Database\Seeders;

use App\Models\Delivery;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DeliverysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                "delivery_method" => "GHTK",
                "delivery_description" => "3-5 ngày",
                "delivery_fee" => 35000
            ],
            [
                "delivery_method" => "GHN",
                "delivery_description" => "1-2 ngày",
                "delivery_fee" => 50000
            ],
            [
                "delivery_method" => "VIETTEL",
                "delivery_description" => "2-4 ngày",
                "delivery_fee" => 30000
            ],
            [
                "delivery_method" => "AT_PHARMACITY",
                "delivery_description" => "1-5 ngày",
                "delivery_fee" => 0
            ],
            [
                "delivery_method" => "SHIPPER",
                "delivery_description" => "5-7 ngày",
                "delivery_fee" => 25000
            ]
        ];
        foreach($data as $index => $delivery){
            Delivery::create($delivery);
        }
    }
}

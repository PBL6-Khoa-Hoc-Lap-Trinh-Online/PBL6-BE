<?php

namespace Database\Seeders;

use App\Models\Payment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PaymentsSeeder extends Seeder
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
                "payment_method" => "COD"
            ],
            [
                "payment_method" => "PAYOS"
            ],
        ];
        foreach ($data as $payment) {
            Payment::create($payment);
        }
    }
}

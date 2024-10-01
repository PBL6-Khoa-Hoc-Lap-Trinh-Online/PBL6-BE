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
                "payment_method" => "momo"
            ],
            [
                "payment_method" => "zalopay"
            ],
            [
                "payment_method" => "vnpay"
            ],
            [
                "payment_method" => "cash_on_delivery"
            ]
        ];
        foreach ($data as $payment) {
            Payment::create($payment);
        }
    }
}

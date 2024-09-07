<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            'admin_fullname' => 'Kim Tien Tran',
            'admin_email' => 'kimtientran0410@gmail.com',
            'admin_password' => Hash::make('123456'),
            'admin_is_admin' => 1
        ];
        Admin::create($data);
    }
}

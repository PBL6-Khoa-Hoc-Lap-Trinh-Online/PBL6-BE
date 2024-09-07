<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = [
            [
                'user_fullname' => 'Kim Tiến',
                'user_email' => 'kimtien@yopmail.com',
                'user_password' => Hash::make('123456')
            ],
            [
                'user_fullname' => 'Minh Nhật',
                'user_email' => 'minhnhat@yopmail.com',
                'user_password' => Hash::make('123456')
            ],
            [
                'user_fullname' => 'Đình Phước',
                'user_email' => 'dinhphuoc@yopmail.com',
                'user_password' => Hash::make('123456')
            ],
            [
                'user_fullname' => 'Đình Văn',
                'user_email' => 'dinhvan@yopmail.com',
                'user_password' => Hash::make('123456')
            ],
            [
                'user_fullname' => 'Nhật Minh',
                'user_email' => 'nhatminh@yopmail.com',
                'user_password' => Hash::make('123456')
            ],
        ];
        foreach ($users as $user) {
            User::create($user);
        }
    }
}

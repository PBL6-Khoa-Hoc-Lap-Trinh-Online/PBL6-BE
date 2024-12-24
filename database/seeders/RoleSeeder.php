<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
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
                'role_name' => 'admin',
                'role_description' => 'Quản lý nội dung'
            ],
            [
                'role_name' => 'superadmin',
                'role_description' => 'Quản lý Admin và User'
            ],
            [
                'role_name' => 'manager',
                'role_description' => 'Quản lý hệ thống'
            ],
           
        ];
        foreach ($data as $index => $role) {
            Role::create($role);
        }
    }
}

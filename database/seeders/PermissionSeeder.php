<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissions = [
            // [
            //     'permission_name'=>'change_block_users',
            //     'permission_description'=>'Khoá/ Mở khoá người dùng'
            // ],
            // [
            //     'permission_name'=>'delete_users',
            //     'permission_description'=>'Xoá người dùng'
            // ],
            // [
            //     'permission_name'=>'add_brands',
            //     'permission_description'=>'Thêm nhãn hàng'
            // ],
            // [
            //     'permission_name' => 'update_brands',
            //     'permission_description' => 'Cập nhật nhãn hàng'
            // ],
            // [
            //     'permission_name' => 'delete_brands',
            //     'permission_description' => 'Xoá nhãn hàng'
            // ],
            // [
            //     'permission_name' => 'add_suppliers',
            //     'permission_description' => 'Thêm nhà cung cấp'
            // ],
            // [
            //     'permission_name' => 'update_suppliers',
            //     'permission_description' => 'Cập nhật nhà cung cấp'
            // ],
            // [
            //     'permission_name' => 'delete_suppliers',
            //     'permission_description' => 'Xoá nhà cung cấp'
            // ],
            // [
            //     'permission_name'=>'add_categories',
            //     'permission_description'=>'Thêm danh mục'
            // ],
            // [
            //     'permission_name' => 'update_categories',
            //     'permission_description' => 'Cập nhật danh mục'
            // ],
            // [
            //     'permission_name' => 'delete_categories',
            //     'permission_description' => 'Xoá danh mục'
            // ],
            // [
            //     'permission_name' => 'delete_many_categories',
            //     'permission_description' => 'Xoá nhiều danh mục'
            // ],
            // [
            //     'permission_name' => 'add_products',
            //     'permission_description' => 'Thêm sản phẩm'
            // ],
            // [
            //     'permission_name'=>'update_products',
            //     'permission_description'=>'Cập nhật sản phẩm'
            // ],
            // [
            //     'permission_name' => 'delete_products',
            //     'permission_description' => 'Xoá sản phẩm'
            // ],
            // [
            //     'permission_name' => 'delete_many_products',
            //     'permission_description' => 'Xoá nhiều sản phẩm'
            // ],
            // [
            //     'permission_name' => 'add_imports',
            //     'permission_description' => 'Thêm nhập kho mới'
            // ],
            // [
            //     'permission_name'=>'update_imports',
            //     'permission_description'=>'Cập nhật nhập kho'
            // ],
            // [
            //     'permission_name'=>'update_orders',
            //     'permission_description'=>'Cập nhật tình trạng đơn hàng'
            // ],
            // [
            //     'permission_name' => 'update_status_payments',
            //     'permission_description' => 'Cập nhật tình trạng thanh toán đơn hàng'
            // ],
            // [
            //     'permission_name'=>'update_status_deliveries',
            //     'permission_description'=>'Quản lý phương thức thanh toán'
            // ],
            // [
            //     'permission_name'=>'add_diseases',
            //     'permission_description'=>'Thêm bài viết về bệnh'
            // ]
            // ,
            // [
            //     'permission_name' => 'update_diseases',
            //     'permission_description' => 'Cập nhật bài viết về bệnh'
            // ],
            [
                'permission_name' => 'add_category_disease',
                'permission_description' => 'Thêm bài viết về bệnh vào danh mục'
            ],
            [
                'permission_name' => 'delete_disease',
                'permission_description' => 'Xoá bài viết về bệnh'
            ],
            [
                'permission_name' => 'delete_category_disease',
                'permission_description' => 'Xoá bài viết về bệnh khỏi danh mục'
            ],
           
        ];
        foreach ($permissions as $data) {
            Permission::create($data);
        }
    }
}

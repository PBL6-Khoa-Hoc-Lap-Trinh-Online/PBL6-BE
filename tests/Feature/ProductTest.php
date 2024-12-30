<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    // public function test_example()
    // {
    //     $response = $this->get('/');

    //     $response->assertStatus(200);
    // }
    public $token = '';

    protected function setUp(): void
    {
        parent::setUp();

        $data = [
            'email' => 'kimtien@yopmail.com',
            'password' => '123456',
        ];
        $response = $this->post('api/admin/login', $data);
        $responseData = $response->json();
        $this->assertArrayHasKey('access_token', $responseData['data']);
        $this->token = $responseData['data']['access_token'];
    }

    private function getAuthorizationHeaders()
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
        ];
    }
    public function testAddProductLackBrand(){
        $data=[
            'brand_id'=>'',
            'category_id'=>1,
            'product_name'=>'Thuốc chống đau',
            'product_slug'=>'thuoc-chong-dau',
            'product_price'=>10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(422);
              $response->assertJson([
                'messages' =>
                array(
                    0 => 'Trường brand id không được bỏ trống.',
                ),
                'errors' =>
                array(
                    'brand_id' =>
                    array(
                        0 => 'Trường brand id không được bỏ trống.',
                    ),
                ),
        ]);
        
    }
    public function testAddProductLackCategory()
    {
        $data = [
            'brand_id' => '13',
            // 'category_id' => '',
            'product_name' => 'Thuốc chống đau',
            'product_slug' => 'thuocssa-chong-dau',
            'product_price' => 10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường category id không được bỏ trống.',
            ),
            'errors' =>
            array(
                'category_id' =>
                array(
                    0 => 'Trường category id không được bỏ trống.',
                ),
            ),
        ]);
    }
    public function testAddProductLackProductName()
    {
        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            // 'product_name' => '',
            'product_slug' => 'thuoc-chong-dauss',
            'product_price' => 10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường product name không được bỏ trống.',
            ),
            'errors' =>
            array(
                'product_name' =>
                array(
                    0 => 'Trường product name không được bỏ trống.',
                ),
            ),
        ]);
    }
    public function testAddProductLackProductSlug()
    {
        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Thuốc chống đau',
            // 'product_slug' => '',
            'product_price' => 10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường product slug không được bỏ trống.',
            ),
            'errors' =>
            array(
                'product_slug' =>
                array(
                    0 => 'Trường product slug không được bỏ trống.',
                ),
            ),
        ]);
    }
    public function testAddProductLackProductPrice()
    {
        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Thuốc chống đau',
            'product_slug' => 'thuoc-sssschong-dausssssaa',
            // 'product_price' => 10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường product price không được bỏ trống.',
            ),
            'errors' =>
            array(
                'product_price' =>
                array(
                    0 => 'Trường product price không được bỏ trống.',
                ),
            ),
        ]);
    }
    public function testAddProductSuccess(){
        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Thuốcsdf chống đau',
            'product_slug' => 'thuossssc-chong-1111dau22222',
            'product_price' => 10000,
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(201);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Thêm sản phẩm mới thành công!',
            ),
        ]);
    }
    public function testAddProductSuccessWithImage(){
        Storage::fake('thumbnails');

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg');

        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Thuốcsdfds chống đau',
            'product_slug' => 'thuossssdfsfc-chong-dau12313214',
            'product_price' => 10000,
            'product_images[]' => [
                $thumbnail
            ],
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(201);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Thêm sản phẩm mới thành công!',
            ),
        ]);
    }
    public function testAddProductSuccessWithMultipleImage(){
        Storage::fake('thumbnails');

        $thumbnail1 = UploadedFile::fake()->image('thumbnail1.jpg');
        $thumbnail2 = UploadedFile::fake()->image('thumbnail2.jpg');

        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Thuốc chống đau',
            'product_slug' => 'thuocsfdsfs-chong-dau12222',
            'product_price' => 10000,
            'product_images[]' => [
                $thumbnail1,
                $thumbnail2
            ],
        ];
        $response = $this->post('api/products/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(201);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Thêm sản phẩm mới thành công!',
            ),
        ]);
    }
    public function testUpdateProduct(){
        $product_id = 294;
        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Bánh tráng trộn',
            'product_slug' => 'hellsso1122222',
            'product_price' => 10000,
        ];
        $response = $this->post('api/products/update/'.$product_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Cập nhật sản phẩm thành công!',
            ),
        ]);
    }
    public function testUpdateProductWithImage(){
        $product_id = 294;
        Storage::fake('thumbnails');

        $thumbnail = UploadedFile::fake()->image('thumbnail.jpg');

        $data = [
            'brand_id' => '13',
            'category_id' => 13,
            'product_name' => 'Bánh tráng trộn',
            'product_slug' => 'helloss212222222',
            'product_price' => 10000,
            'product_images[]' => [
                $thumbnail
            ],
        ];
        $response = $this->post('api/products/update/'.$product_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Cập nhật sản phẩm thành công!',
            ),
        ]);
    }
    public function testGetProductById(){
        $product_id = 294;
        $response = $this->get('api/products/'.$product_id);
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Lấy sản phẩm thành công!',
            ),
        ]);
    }
    public function testGetAllProduct(){
        $response = $this->get('api/products');
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Danh sách sản phẩm!',
            ),
        ]);
    }
    public function testDeleteProductWithInOrder(){
        $data = [
            'product_is_delete' => 1,
        ];
        $product_id = 287;
        $response = $this->post('api/products/delete/'.$product_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(400);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Sản phẩm đang nằm trong đơn hàng, không thể xoá!',
            ),
        ]);

    }
    public function testDeletePoduct(){
        $data = [
            'product_is_delete' => 1,
        ];
        $product_id = 294;
        $response = $this->post('api/products/delete/'.$product_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Xoá sản phẩm thành công!',
            ),
        ]);
    }
    public function testRestoreProduct(){
        $data = [
            'product_is_delete' => 0,
        ];
        $product_id = 294;
        $response = $this->post('api/products/delete/' . $product_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Khôi phục sản phẩm thành công!',
            ),
        ]); 
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public $token = '';

    protected function setUp(): void
    {
        parent::setUp();

        $data = [
            'email' => 'kimtien@yopmail.com',
            'password' => '123456'
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
    public function testAddCategory(){
        $data = [
            'category_name' => 'Testssdsfsfsdgds Category2',
            'category_slug' => 'testsds-sdfsdfcategory22',
            'category_type' => 'post',
        ];
        $response = $this->post('api/categories/add', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(201);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Thêm category mới thành công!',
            ),
        ]);
    }
    public function testUpdateCategory(){

        $category_id = 54;
        $data = [
            'category_name' => 'Tests Category2',
            'category_slug' => 'tests-category22',
            'category_type' => 'post',
        ];
        $response = $this->post('api/categories/update/'.$category_id, $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Cập nhật category thành công!',
            ),
        ]);
    }
    public function testDeleteCategory(){
        $category_id = 54;
        $data=[
            'category_is_delete'=>1
        ];
        $response = $this->post('api/categories/delete/'.$category_id,$data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Xoá category thành công!',
            ),
        ]);
    }
    public function testAdminGetCategory(){
        $response = $this->get('api/categories/all', $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Lấy danh sách category thành công!',
            ),
        ]);
    }
    public function testUserGetCategory(){
        $response = $this->get('api/categories');
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Lấy danh sách category thành công!',
            ),
        ]);
    }
}

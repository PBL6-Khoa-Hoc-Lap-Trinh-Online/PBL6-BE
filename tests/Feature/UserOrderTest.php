<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserOrderTest extends TestCase
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
            'password' => '1234567'
        ];
        $response = $this->post('api/user/login', $data);
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
    public function testUserBuyNow()
    {
        $data = [
            'receiver_address_id' => 4,
            'payment_id' => 2,
            'delivery_id' => '1',
            'product_id' => 4,
            'quantity' => 1,
        ];
        $response = $this->post('api/orders/buy-now', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Vui lòng thanh toán hoá đơn!',
            ),
        ]);
    }
    public function testUserAddToCart()
    {
        $data = [
            'product_id' => 288,
            'cart_quantity' => 1,
        ];
        $response = $this->post('api/cart/add', $data, $this->getAuthorizationHeaders());
        // $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Thêm vào giỏ hàng thành công',
            ),
        ]);
    }
    public function testUserUpdateCart()
    {
        $data = [
            'cart_id' => 1,
            'product_id' => 288,
            'cart_quantity' => 2,
        ];
        $response = $this->post('api/cart/update', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(201);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Cập nhật sản phẩm thành công',
            ),
        ]);
    }
    public function testUserCheckoutCart(){
        $data = [
            'ids_cart' => [73,2],
            'receiver_address_id' => 4,
            'payment_id' => 2,
            'delivery_id' => '1',
        ];
        $response = $this->post('api/orders/checkout-cart', $data, $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Vui lòng thanh toán hoá đơn!',
            ),
        ]);
    }
    public function testUserGetOrder(){
        $response = $this->get('api/orders/detail/261', $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Lấy thông tin đơn hàng thành công!',
            ),
        ]);
    }
    // public function testUserCancelOrder(){
       
    //     $response = $this->post('orders/cancel/231', $this->getAuthorizationHeaders());
    //     $response->assertStatus(200);
    //     $response->assertJson([
    //         'messages' =>
    //         array(
    //             0 => 'Hủy đơn hàng thành công!',
    //         ),
    //     ]);
    // }
    public function testUserGetHistoryOrder(){
        $response = $this->get('api/orders/history', $this->getAuthorizationHeaders());
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Lấy lịch sử đơn hàng thành công!',
            ),
        ]);
    }
}

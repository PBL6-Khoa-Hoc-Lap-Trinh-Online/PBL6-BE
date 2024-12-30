<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserLoginTest extends TestCase
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
    public function testUserLoginLackEmail()
    {
        $data = [
            'email' => '',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(422);
        $response->assertJson([
               'messages' =>
                    array (
                        0 => 'Trường email không được bỏ trống.',
                        ),
                'errors' =>
                    array (
                        'email' =>
                            array (
                                0 => 'Trường email không được bỏ trống.',
                                ),
                        ),
        ]);
    }

    public function testUserLoginLackPassword()
    {
        $data = [
            'email' => 'kimtien@yopmail.com',
            'password' => '',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường mật khẩu không được bỏ trống.',
            ),
            'errors' =>
            array(
                'password' =>
                array(
                    0 => 'Trường mật khẩu không được bỏ trống.',
                ),
            ),
        ]);
    }

    public function testUserLoginEmailMalformed()
    {
        $data = [
            'email' => 'hellodsd',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(422);
        $response->assertJson([
            'messages' =>
            array(
                0 => 'Trường email phải là một địa chỉ email hợp lệ.',
            ),
            'errors' =>
            array(
                'email' =>
                array(
                    0 => 'Trường email phải là một địa chỉ email hợp lệ.',
                ),
            ),
        ]);
    }

    public function testUserLoginEmailNotExist()
    {
        $data = [
            'email' => 'benhviengiadinh9999@yopmail.com',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(400);
        $response->assertJson([
            'messages' =>
            array(
                0 => "Email không tồn tại!",
            )
            // 
            ]);
    }

    public function testUserLoginPasswordIncorrect()
    {
        $data = [
            'email' => 'kimtien@yopmail.com',
            'password' => '1234563',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(400);
        $response->assertJson([
            'messages' =>
            array(
                0 => "Mật khẩu không chính xác!",
            )
            // 'message' => 'Email hoặc mật khẩu không chính xác !'
        ]);
    }

    public function testUserLoginAccountDeleted()
    {
        $data = [
            'email' => 'nhatminh@yopmail.com',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(400);
        $response->assertJson(['messages' =>
            array(
                0 => "Tài khoản đã bị xóa!",
            )
            // 'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !'
        ]);
    }
    public function testUserLoginAccountBlocked()
    {
        $data = [
            'email' => 'dinhvan@yopmail.com',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(400);
        $response->assertJson([
            'messages' =>
            array(
                0 => "Tài khoản đã bị khóa!",
            )
            // 'message' => 'Tài khoản của bạn đã bị khóa hoặc chưa được phê duyệt !'
        ]);
    }
    public function testUserLoginAccountNotVerifyEmail()
    {
        $data = [
            'email' => 'minhtien@yopmail.com',
            'password' => '123456',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(400);
        $response->assertJson([
            'messages' =>
            array(
                0 => "Email chưa được xác thực! Vui lòng xác thực email trước khi đăng nhập!",
            )
        ]);
    }

    public function testUserLoginSuccessful()
    {
        $data = [
            'email' => 'kimtien@yopmail.com',
            'password' => '1234567',
        ];
        $response = $this->post('api/user/login', $data);
        $response->assertStatus(200);
        $response->assertJson([
            'messages' =>
            array(
                0 => "Đăng nhập thành công!",
            )
        ]);
    }
}

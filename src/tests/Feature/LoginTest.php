<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class LoginTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app()->setLocale('ja');
    }

    // ～一般ユーザー～

    // メールアドレスが未入力の時エラーメッセージ
    public function test_email_is_required_for_login()
    {
        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => '12345678',
            'login_type' => 'user',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }

    // パスワードが未入力の時エラーメッセージ
    public function test_password_is_required_for_login()
    {
        $response = $this->post(route('login.store'), [
            'email' => 'test@example.com',
            'password' => '',
            'login_type' => 'user',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );

        $this->assertGuest();
    }

    // 違ったログイン情報を入力した時にエラーメッセージ
    public function test_login_fails_with_incorrect_credentials_after_registration()
    {
        $user = User::factory()->create([
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '12345678',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->post(route('login.store'), [
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '87654321',
            'login_type' => 'user',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }

    // ～管理者～

    // メールアドレスが未入力の場合エラーメッセージ（管理者）
    public function test_admin_login_reqires_emil()
    {
        $response = $this->post(route('login.store'), [
            'email' => '',
            'password' => '12345678',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }

    // パスワードが未入力の時にエラーメッセージ（管理者）
    public function test_admin_login_requires_password()
    {
        $response = $this->post(route('login.store'), [
            'email' => 'admin@example.com',
            'password' => '',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );

        $this->assertGuest();
    }

    // 管理者として登録後に違ったログイン情報エラーメッセージ
    public function test_admin_login_fails_with_wrong_pasword()
    {
        $response = $this->post(route('login.store'), [
            'name' => '管理者',
            'email' => 'admin@axample.com',
            'password' => 'wrongpass',
            'login_type' => 'admin',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            'ログイン情報が登録されていません',
            session('errors')->first('email')
        );

        $this->assertGuest();
    }
}

<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class RegisterTest extends TestCase
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

    // 名前が未入力の時エラーメッセージ
    public function test_name_is_required_for_registeration()
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertSessionHasErrors(['name']);

        $this->assertSame(
            'お名前を入力してください',
            session('errors')->first('name')
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    // メールアドレスが未入力の時エラーメッセージ
    public function test_email_is_required_for_registeration()
    {
        $response = $this->post(route('register.store'), [
            'name' => '太郎',
            'email' => '',
            'password' => '12345678',
            'password_confrmation' => '12345678',
        ]);

        $response->assertSessionHasErrors('email');

        $this->assertSame(
            'メールアドレスを入力してください',
            session('errors')->first('email')
        );

        $this->assertDatabaseMissing('users', [
            'name' => '太郎',
        ]);
    }

    // パスワードが８文字未満の時エラーメッセージ
    public function test_password_must_be_at_lest_8_characters()
    {
        $response = $this->post(route('register.store'), [
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '1234567',
            'password' => '1234567',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertSame(
            'パスワードは8文字以上で入力してください',
            session('errors')->first('password')
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    // パスワードが一致しない時エラーメッセージ
    public function test_password_confirmation_must_match()
    {
        $response = $this->post(route('register.store'), [
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '87654321',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertSame(
            'パスワードと一致しません',
            session('errors')->first('password')
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com'
        ]);
    }

    // パスワードが未入力の時エラーメッセージ
    public function test_password_is_required_for_register()
    {
        $response = $this->post(route('register.store'), [
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertSessionHasErrors('password');

        $this->assertSame(
            'パスワードを入力してください',
            session('errors')->first('password')
        );

        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }

    // 全て正常に入力したらDBへ保存される
    public function test_user_is_registered_when_all_inputs_are_valid()
    {
        $response = $this->post(route('register.store'), [
            'name' => '太郎',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => '太郎',
            'email' => 'test@example.com',
        ]);

        $this->assertDatabaseMissing('users', [
            'password' => '12345678',
        ]);

        $this->assertAuthenticated();

        $response->assertRedirect('/email/verify');
    }
}

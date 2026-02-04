<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Auth\Events\Register;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\User;


class VerifyEmailTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    // 会員登録後、メール認証対象ユーザーであることを確認
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $response = $this->post(route('register.store'), [
            'name' =>  'テストユーザー',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);

        $this->assertNull($user->email_verified_at);
        
        $this->assertInstanceOf(MustVerifyEmail::class, $user);
    }

    // メール認証画面で「認証はこちらから」を押すと認証サイトに還移
    public function test_unverified_user_can_request_verification_email_again()
    {
        Notification::fake();

        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user);

        $response = $this->get(route('verification.notice'));
        $response->assertStatus(200);
        $response->assertViewIs('auth.verify-email');

        $response = $this->post(route('verification.send'));

        $response->assertStatus(302);

        $this->assertNull($user->fresh()->email_verified_at);
    }

    // メール認証を完了すると勤怠登録画面へ還移
    public function test_user_is_redirected_to_generals_index_after_email_verification()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertredirectContains(route('generals.index'));

        $this->assertNotNull($user->fresh()->email_verified_at);
    }
}

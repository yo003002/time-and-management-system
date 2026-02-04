<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Carbon;
use Tests\TestCase;
use App\Models\User;

class AttendanceStampTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    // 一般ユーザーの勤怠打刻画面で現在の日時が表示されている
    public function test_general_user_sees_current_date_on_attendance_screen()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 10, 30, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);
        $response->assertSee('2026年2月1日');
        $response->assertSee('(日)');
        $response->assertSee('10:30');
    }
}

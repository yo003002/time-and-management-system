<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceStatusTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // ステータスが勤務外のユーザーは勤務外と表示
    public function test_general_user_before_work_status_on_attendance_screen()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);
        $response->assertSee('勤務外');
    }

    // ステータスが出勤中のユーザーは出勤中と表示
    public function test_general_user_working_status_on_attendance_screen()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 10, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_status' => 'working',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);
        $response->assertSee('出勤中');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('退勤済');
        $response->assertDontSee('休憩中');
    }

    // ステータスが休憩中のユーザーは休憩中と表示
    public function test_general_user_sees_on_break_status_on_attendance_screen()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 12, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_status' => 'on_break',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);
        $response->assertSee('休憩中');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('出勤中');
        $response->assertDontSee('退勤済');
    }

    // ステータスが退勤済みのユーザーは退勤済みと表示
    public function test_general_user_sees_finished_status_on_attendance_screen()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 18, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'work_status' => 'finished',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);
        $response->assertSee('退勤済');

        $response->assertDontSee('勤務外');
        $response->assertDontSee('出勤中');
        $response->assertDontSee('休憩中');
    }
}

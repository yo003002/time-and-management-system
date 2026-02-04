<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceClockOutTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 退勤ボタンを押すとステータスが退勤済になる
    public function test_user_can_clock_out()
    {
        $testDate = Carbon::create(2026, 2, 1, 18, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(8),
            'work_status' => 'working',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertStatus(200);
        $response->assertSee('退勤');

        $this->actingAs($user)->post(route('generals.clockOut'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'finished',
            'clock_out' => $testDate,
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('退勤済');
    }

    // 退勤時刻を勤怠一覧で表示
    public function test_clock_out_time_is_displayd_in_attendance_list()
    {
        $clockInTime = Carbon::create(2026, 2, 1, 9, 0);
        Carbon::setTestNow($clockInTime);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('generals.clockIn'));

        $clockOutTime = $clockInTime->copy()->addHours(8);
        Carbon::setTestNow($clockOutTime);
        $this->actingAs($user)->post(route('generals.clockOut'));

        $response = $this->actingAs($user)->get(route('generals.list'));
        $response->assertStatus(200);

        $response->assertSee($clockInTime->format('m/d'));
        $response->assertSee($clockOutTime->format('H:i'));
    }
}

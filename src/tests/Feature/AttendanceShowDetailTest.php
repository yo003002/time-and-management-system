<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;

class AttendanceShowDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 詳細を開くとログインユーザーの名前が表示
    public function test_attendance_detail_show_logged_in_user_name()
    {
        $clockIn = Carbon::create(2026, 2, 1, 9, 0);
        Carbon::setTestNow($clockIn);

        $user = User::factory()->create([
            'name' => '山田太郎',
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $clockIn->toDateString(),
            'clock_in' => $clockIn,
        ]);

        $response = $this->actingAs($user)->get(route('generals.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee($user->name);
    }

    // 詳細を開くと選択した日付になっている
    public function test_attendance_detail_shows_selected_date()
    {
        $attendanceDate = Carbon::create(2026, 2, 1, 9, 0);
        Carbon::setTestNow($attendanceDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $attendanceDate->toDateString(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee($attendanceDate->format('Y年'));
        $response->assertSee($attendanceDate->format('n月j日'));
    }

    // 詳細開くと出勤・退勤が正確に表示されている
    public function test_attendance_detail_shows_clock_in_end_clock_out_time()
    {
        $clockIn = Carbon::create(2026, 2, 1, 9, 0);
        $clockOut = Carbon::create(2026, 2, 1, 18, 0);
        Carbon::setTestNow($clockIn);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $clockIn->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        $response = $this->actingAs($user)->get(route('generals.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee($clockIn->format('H:i'));
        $response->assertSee($clockOut->format('H:i'));
    }

    // 詳細開くと休憩開始・終了が正確に表示されている
    public function test_attendance_detail_shows_break_time()
    {
        $clockIn = Carbon::create(2026, 2, 1, 9, 0);
        $clockOut = Carbon::create(2026, 2, 1, 18, 0);

        $breakStart = '12:00';
        $breakEnd = '13:00';

        Carbon::setTestNow($clockIn);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $clockIn->toDateString(),
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
        ]);

        AttendanceBreak::factory()->create([
            'attendance_id' => $attendance->id,
            'break_start' => $breakStart,
            'break_end' => $breakEnd,
        ]);


        $response = $this->actingAs($user)->get(route('generals.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee($breakStart);
        $response->assertSee($breakEnd);
    }
}

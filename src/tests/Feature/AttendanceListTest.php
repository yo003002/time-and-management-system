<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;


class AttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 自分が行った勤怠が一覧に表示されている
    public function test_general_user_can_see_only_own_attendance_list()
    {
        $clockInTime = Carbon::create(2026, 2, 1, 9, 0);
        $clockOutTime = Carbon::create(2026, 2, 1, 18, 0);

        $otherClockIn = Carbon::create(2026, 2, 1, 10, 0);
        $otherClockOut = Carbon::create(2026, 2, 1, 19, 0);

        Carbon::setTestNow($clockInTime);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $clockInTime->toDateString(),
            'clock_in' => $clockInTime,
            'clock_out' => $clockOutTime,
        ]);

        Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => $clockInTime->toDateString(),
            'clock_in' => $otherClockIn,
            'clock_out' => $otherClockOut,
        ]);

        $response = $this->actingAs($user)->get(route('generals.list'));

        $response->assertStatus(200);

        $response->assertSee($clockInTime->format('m/d'));
        $response->assertSee($clockInTime->format('H:i'));
        $response->assertSee($clockOutTime->format('H:i'));
        
        $response->assertDontSee($otherClockIn->format('H:i'));
        $response->assertDontSee($otherClockOut->format('H:i'));
    }
    
    // 勤怠一覧を開くと現在の月が表示
    public function test_current_month_is_displayed_when_opening_attendance_list()
    {
        $now = Carbon::create(2026, 2, 1, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.list'));

        $response->assertStatus(200);

        $response->assertSee($now->format('Y/m'));
    }

    // 勤怠一覧開き、前月ボタンを押す→前月の勤怠情報表示
    public function test_general_user_can_see_revious_month_attendance_list()
    {
        $now = Carbon::create(2026, 2, 1, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $prevClockIn = Carbon::create(2026, 1, 10, 9, 0);
        $prevClockOut = Carbon::create(2026, 1, 10, 18, 0);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $prevClockIn->toDateString(),
            'clock_in' => $prevClockIn,
            'clock_out' => $prevClockOut,
        ]);

        $response = $this->actingAs($user)->get(route('generals.list', ['month' => '2026-01']));

        $response->assertStatus(200);

        $response->assertSee('2026/01');

        $response->assertSee($prevClockIn->format('m/d'));
        $response->assertSee($prevClockIn->format('H:i'));
        $response->assertSee($prevClockOut->format('H:i'));
    }
    
    // 勤怠一覧開き、翌月ボタンを押す→翌月の勤怠情報表示
    public function test_general_user_can_see_next_month_attendance_list()
    {
        $now = Carbon::create(2026, 2, 1, 10, 0);
        Carbon::setTestNow($now);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $nextClockIn = Carbon::create(2026, 3, 10, 9, 0);
        $nextClockOut = Carbon::create(2026, 3, 10, 18, 0);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $nextClockIn->toDateString(),
            'clock_in' => $nextClockIn,
            'clock_out' => $nextClockOut,
        ]);

        $response = $this->actingAs($user)->get(route('generals.list', ['month' => '2026-03']));

        $response->assertStatus(200);

        $response->assertSee('2026/03');

        $response->assertSee($nextClockIn->format('m/d'));
        $response->assertSee($nextClockIn->format('H:i'));
        $response->assertSee($nextClockOut->format('H:i'));
    }

    // 勤怠一覧の詳細ボタンを押すと詳細ぺージへ還移
    public function test_general_user_can_access_attendance_detail_page_from_list()
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

        $response = $this->actingAs($user)->get(route('generals.list'));
        $response->assertStatus(200);

        $response->assertSee('詳細');
        $response->assertSee(route('generals.detail', $attendance->id));

        $detailResponse = $this->actingAs($user)->get(route('generals.detail', $attendance->id));

        $detailResponse->assertStatus(200);
    }
}

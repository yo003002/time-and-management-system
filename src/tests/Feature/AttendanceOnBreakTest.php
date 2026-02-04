<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceOnBreakTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 休憩入ボタン　出勤中ユーザーが休憩入ボタンを押すとステータスが休憩中になる
    public function test_working_user_can_start_break()
    {
        $testDate = Carbon::create(2026, 2, 1, 12, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(3),
            'work_status' => 'working',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertSee('休憩入');

        $this->actingAs($user)->post(route('generals.breakIn'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'on_break',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $testDate,
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('休憩中');
    }

    // 休憩は１日に何回も押せる　休憩入り→戻る→休憩入ボタン表示
    public function test_break_in_button_shown_after_one_break()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 12, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(3),
            'work_status' => 'working',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('休憩入');

        $this->actingAs($user)->post(route('generals.breakIn'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'on_break',
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => now(),
        ]);

        $this->actingAs($user)->post(route('generals.breakOut'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'working',
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => now(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('休憩入');
    }

    // 休憩戻ボタン　休憩戻ボタンを押すとステータスが出勤中になる
    public function test_break_out_button_functionality()
    {
        $testDate = Carbon::create(2026, 2, 1, 12, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(3),
            'work_status' => 'working',
        ]);

        $this->actingAs($user)->post(route('generals.breakIn'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'on_break',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $testDate,
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('休憩戻');

        $this->actingAs($user)->post(route('generals.breakOut'));

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'working',
        ]);

        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => $testDate,
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('出勤中');
    }

    // 休憩は１日に何回も押せる　休憩入り→戻る→休憩入→休憩戻ボタン表示
    public function test_user_can_take_multiple_breaks_in_a_day()
    {
        $testDate = Carbon::create(2026, 2, 1, 12, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(3),
            'work_status' => 'working',
        ]);

        $this->actingAs($user)->post(route('generals.breakIn'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'on_break',
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $testDate,
        ]);

        $this->actingAs($user)->post(route('generals.breakOut'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'working',
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_end' => $testDate,
        ]);

        Carbon::setTestNow($testDate->copy()->addHour());
        $this->actingAs($user)->post(route('generals.breakIn'));
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'work_status' => 'on_break',
        ]);
        $this->assertDatabaseHas('attendance_breaks', [
            'attendance_id' => $attendance->id,
            'break_start' => $testDate->copy()->addHour(),
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));
        $response->assertSee('休憩戻');
    }

    // 休憩時刻が勤怠一覧に表示
    public function test_break_time_is_displayd_in_attendance_list()
    {
        $testDate = Carbon::create(2026, 2, 1, 12, 0);
        Carbon::setTestNow($testDate);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(3),
            'work_status' => 'working',
        ]);

        $this->actingAs($user)->post(route('generals.breakIn'));

        Carbon::setTestNow($testDate->copy()->addMinutes(30));
        $this->actingAs($user)->post(route('generals.breakOut'));

        $response = $this->actingAs($user)->get(route('generals.list'));

        $response->assertStatus(200);

        $response->assertSee($attendance->date->format('m/d'));

        $breakTimeFormatted = $attendance->fresh()->break_time_formatted;
        $response->assertSee($breakTimeFormatted);
    }
}

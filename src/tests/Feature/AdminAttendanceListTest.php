<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceListTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 管理者でログイン→勤怠一覧にその日の全ユーザーの勤怠情報が表示
    public function test_admin_can_see_all_users_attendance_of_the_day()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user1 = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザーA',
        ]);

        $user2 = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザーB',
        ]);

        Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.list'));

        $response->assertStatus(200);

        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');

        $response->assertDontSee($admin->name);
    }

    // 管理者でログイン→勤怠一覧にその日の日にちが表示
    public function test_admin_attendance_list_shows_today_date()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.list'));

        $response->assertStatus(200);

        $response->assertSee('2026/02/01');
    }

    // 管理者でログイン→勤怠一覧で前日ボタンを押すと前日の勤怠表示
    public function test_admin_can_view_previous_day_attendance()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '前日のユーザー',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::yesterday()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.list', [
                'date' => Carbon::today()->subDay()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('前日のユーザー');
        $response->assertSee('2026/02/01');
    }

    // 管理者でログイン→勤怠一覧で翌日ボタンを押すと翌日の勤怠表示
    public function test_admin_can_view_next_day_attendance()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 2));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '翌日のユーザー',
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => Carbon::tomorrow()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin)->get(
            route('admin.list', [
                'date' => Carbon::today()->addDay()->format('Y-m-d'),
            ])
        );

        $response->assertStatus(200);

        $response->assertSee('翌日のユーザー');
        $response->assertSee('2026/02/03');
    }
}

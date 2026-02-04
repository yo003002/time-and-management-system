<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;


class AdminStaffListTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 管理者は全一般ユーザーの氏名・メールアドレスが表示される
    public function test_can_see_all_general_users_in_staff_list()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $userA = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザーA',
            'email' => 'userA@example.com'
        ]);

        $userB = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザーB',
            'email' => 'userB@example.com'
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staffList'));

        $response->assertStatus(200);

        $response->assertSee('ユーザーA');
        $response->assertSee('userA@example.com');

        $response->assertSee('ユーザーB');
        $response->assertSee('userB@example.com');

        $response->assertDontSee($admin->email);
    }

    // ユーザーの月次勤怠が正確に表示
    public function test_admin_can_see_selected_users_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 18, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staffAttendanceList', $user->id));

        $response->assertStatus(200);

        $response->assertSee('太郎');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 管理者として選択したユーザーの勤怠一覧を開く→前月ボタンを押したときに前月の勤怠情報が表示
    public function test_admin_can_see_previieus_month_attendance_in_staff_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-01-03',
            'clock_in' => Carbon::create(2026, 1, 3, 9, 0),
            'clock_out' => Carbon::create(2026, 1, 3, 18, 0),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 19, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staffAttendanceList', [
            'id' => $user->id,
            'month' => '2026-01',
        ]));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    // 管理者として選択したユーザーの勤怠一覧を開く→前月ボタンを押したときに前月の勤怠情報が表示
    public function test_admin_can_see_next_month_attendance_in_staff_attendance_list()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 15));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-03-03',
            'clock_in' => Carbon::create(2026, 3, 3, 9, 0),
            'clock_out' => Carbon::create(2026, 3, 3, 18, 0),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 19, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.staffAttendanceList', [
            'id' => $user->id,
            'month' => '2026-03',
        ]));

        $response->assertStatus(200);

        $response->assertSee('09:00');
        $response->assertSee('18:00');

        $response->assertDontSee('10:00');
        $response->assertDontSee('19:00');
    }

    // 管理者として選択したユーザーの勤怠一覧を開く→詳細ボタンを押したときに詳細に還移
    public function test_admin_can_navigate_from_staff_attendance_list_to_detail()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => '太郎',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'clock_in' => Carbon::create(2026, 2, 1, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 18, 0),
        ]);

        $listResponse = $this->actingAs($admin)->get(route('admin.staffAttendanceList', $user->id));
        $listResponse->assertStatus(200);

        $detailUrl = route('admin.detail', $attendance->id);
        $listResponse->assertSee($detailUrl);

        $detailResponse = $this->actingAs($admin)->get($detailUrl);
        $detailResponse->assertStatus(200);
    }
}

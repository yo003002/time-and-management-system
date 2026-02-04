<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AdminAttendanceDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 管理者として詳細を開く→内容が選択したものと一致
    public function test_admin_detail_shows_correct_attendance_information()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 9, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'テストユーザー',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 18, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('admin.detail', $attendance->id));

        $response->assertStatus(200);

        $response->assertSee('テストユーザー');
        $response->assertSee('2026年');
        $response->assertSee('2月3日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 管理者として詳細開く→出勤時間が出勤時間が退勤時間より後に修正→エラーメッセージが表示
    public function test_admin_cannot_set_clock_in_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.correction.store', $attendance->id),
            [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'remark' => 'テスト',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 管理者として詳細開く→休憩開始時間を退勤時間より後に修正→エラーメッセージが表示
    public function test_admin_cannot_set_break_start_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        
        $response = $this->actingAs($admin)->post(
            route('admin.correction.store', $attendance->id),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'start' => '19:00',
                        'end' => '19:30',
                    ],
                ],
                'remark' => '修正テスト',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です'
        ]);
    }

    // 管理者として詳細開く→休憩終了時間を退勤時間より後に修正→エラーメッセージが表示
    public function test_admin_cannot_set_break_end_after_clock_out()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);
        
        $response = $this->actingAs($admin)->post(
            route('admin.correction.store', $attendance->id),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'breaks' => [
                    [
                        'start' => '17:00',
                        'end' => '18:30',
                    ],
                ],
                'remark' => '修正テスト',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 管理者として詳細開く→備考が未入力→エラーメッセージが表示
    public function test_admin_cannot_submit_without_remark()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-03',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin)->post(
            route('admin.correction.store', $attendance->id),
            [
                'clock_in' => '19:00',
                'clock_out' => '18:00',
                'remark' => '',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください'
        ]);
    }
}

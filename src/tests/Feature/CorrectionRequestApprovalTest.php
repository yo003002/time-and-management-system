<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class CorrectionRequestApprovalTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 管理者として申請一覧を開くと全ユーザーの未承認の修正申請が表示
    public function test_admin_can_view_pending_corrections_with_clock_in_and_out_for_multiple_users()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 9, 0));

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

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 18, 0),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2026-02-03',
            'clock_in' => Carbon::create(2026, 2, 3, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 3, 19, 0),
        ]);

        $correction1 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $user1->id,
            'status' => 'pending',
            'remark' => '修正A',
        ]);

        $correction2 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $user2->id,
            'status' => 'pending',
            'remark' => '修正B',
        ]);

        $response = $this->actingAs($admin)->get(route('corrections.list', ['status' => 'pending']));

        $response->assertStatus(200);

        $response->assertSee('承認待ち');

        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');

        $response->assertSee('修正A');
        $response->assertSee('修正B');
    }

    // 管理者として申請一覧を開くと全ユーザーの承認済みの修正申請が表示
    public function test_admin_can_view_all_approved_corrections_from_approved_tab()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 9, 0));

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

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user1->id,
            'date' => '2026-02-01',
            'clock_in' => Carbon::create(2026, 2, 1, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 18, 0),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user2->id,
            'date' => '2026-02-02',
            'clock_in' => Carbon::create(2026, 2, 2, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 2, 19, 0),
        ]);

        $correction1 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $user1->id,
            'status' => 'approved',
            'remark' => '承認済み修正A',
        ]);

        $correction2 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $user2->id,
            'status' => 'approved',
            'remark' => '承認済み修正B',
        ]);

        $response = $this->actingAs($admin)->get(route('corrections.list', ['status' => 'approved']));

        $response->assertStatus(200);

        $response->assertSee('承認済み');

        $response->assertSee('ユーザーA');
        $response->assertSee('ユーザーB');

        $response->assertSee('承認済み修正A');
        $response->assertSee('承認済み修正B');
    }

    // 管理者として申請一覧を開く→修正申請の詳細画面で勤怠情報を確認
    public function test_admin_can_view_correction_approval_form_with_correct_information()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザー',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'clock_in' => Carbon::create(2026, 2, 1, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 18, 0),
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => '修正',
            'clock_in' => Carbon::create(2026, 2, 1, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 19, 0),
        ]);

        $response = $this->actingAs($admin)->get(route('correction.approve.form', $correction->id));

        $response->assertStatus(200);

        $response->assertSee('ユーザー');
        $response->assertSee('2026年');
        $response->assertSee('2月1日');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
        $response->assertSee('修正');
    }

    // 管理者として申請修正承認画面を開く→承認→勤怠情報が更新
    public function test_admin_can_approve_correction_and_attendance_is_updated()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 3, 9, 0));

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'name' => 'ユーザー',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-01',
            'clock_in' => Carbon::create(2026, 2, 1, 9, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 18, 0),
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => '修正',
            'clock_in' => Carbon::create(2026, 2, 1, 10, 0),
            'clock_out' => Carbon::create(2026, 2, 1, 19, 0),
        ]);

        $response = $this->actingAs($admin)->patch(route('correction.approve', $correction->id));

        $response->assertRedirect(route('correction.approve.form', $correction->id));

        $this->assertDatabaseHas('attendance_corrections', [
            'id' => $correction->id,
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => Carbon::create(2026, 2, 1, 10, 0)->todateTimeString(),
            'clock_out' => Carbon::create(2026, 2, 1, 19, 0)->todateTimeString(),
        ]);
    }
}

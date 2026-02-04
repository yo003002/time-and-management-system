<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

class CorrectionRequestDetailTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 出勤時間が退勤時間より後に修正・保存した時にエラーメッセージ
    public function test_clock_in_after_clock_out_shows_validation_error()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)->post(
            route('generals.correction.store', $attendance->id),
            [
                'clock_in' => '18:00',
                'clock_out' => '09:00',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 休憩開始時間が退勤時間より後になった場合、エラーメッセージ
    public function test_break_start_after_clock_out_shows_validation_error()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)->post(
            route('generals.correction.store', $attendance->id),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',

                'breaks' => [
                    [
                        'start' => '19:00',
                        'end' => '19:00',
                    ],
                ],

                'remark' => 'テスト',
            ],
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です'
        ]);
    }

    // 休憩終了時間が退勤時間より後になった場合、エラーメッセージ
    public function test_break_end_after_clock_out_shows_valodation_error()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)->post(
            route('generals.correction.store', $attendance->id),
            [
                'clock_in' => '09:00',
                'clock_out' => '18:00',

                'breaks' => [
                    [
                        'start' => '17:00',
                        'end' => '19:00',
                    ],
                ],

                'remark' => 'テスト',
            ],
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です'
        ]);
    }

    // 備考が未入力でエラーメッセージ
    public function test_remark_is_required_shows_validation_error()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($user)->post(
            route('generals.correction.store', $attendance->id),
            [
                'clock_in' => '18:00',
                'clock_out' => '09:00',
                'remark' => '',
            ]
        );

        $response->assertStatus(302);

        $response->assertSessionHasErrors([
            'remark' => '備考を記入してください'
        ]);
    }

    // 詳細で修正申請すると管理者の申請一覧に表示
    public function test_admin_can_see_pending_correction_in_list()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => 'テスト',
        ]);

        $response = $this->actingAs($admin)->get(route('corrections.list', ['status' => 'pending']));

        $response->assertStatus(200);

        $response->assertSee('承認待ち');
        $response->assertSee($user->name);
        $response->assertSee($attendance->date->format('Y/m/d'));
        $response->assertSee('テスト');
        $response->assertSee(route('correction.approve.form', $correction->id));
    }

    // 管理者が承認画面を開ける
    public function test_admin_can_open_correction_approve_form()
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin)->get(route('correction.approve.form', $correction->id));

        $response->assertStatus(200);
    }

    // 一般ユーザーが申請したものが申請一覧（一般）に全て表示される
    public function test_general_user_can_see_only_own_corrections_in_list()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $otherUser = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance1 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $attendance2 = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        $otherAttendance = Attendance::factory()->create([
            'user_id' => $otherUser->id,
            'date' => now()->toDateString(),
        ]);

        $correction1 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance1->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => '自分の申請１',
        ]);

        $correction2 = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance2->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => '自分の申請２',
        ]);

        AttendanceCorrection::factory()->create([
            'attendance_id' => $otherAttendance->id,
            'requested_by' => $otherUser->id,
            'status' => 'pending',
            'remark' => '他人の申請',
        ]);

        $response = $this->actingAs($user)->get(route('corrections.list', ['status' => 'pending']));

        $response->assertStatus(200);

        $response->assertSee('承認待ち');
        $response->assertSee('自分の申請１');
        $response->assertSee('自分の申請２');

        $response->assertDontSee('他人の申請');
    }

    // 一般ユーザーが申請→管理者が承認したものが申請一覧（一般）に全て表示される
    public function test_general_user_can_see_approved_corrections_in_approved_list()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => '承認される申請',
        ]);

        $this->actingAs($admin)->patch(route('correction.approve', $correction->id));

        $response = $this->actingAs($user)->get(route('corrections.list', ['status' => 'approved']));

        $response->assertStatus(200);

        $response->assertSee('承認済み');
        $response->assertSee('承認される申請');
    }

    // 一般ユーザーが各申請の詳細ボタンを押すと詳細に還移
    public function test_general_user_correction_detail_link_redirects_to_attendance_detail()
    {
        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $correction = AttendanceCorrection::factory()->create([
            'attendance_id' => $attendance->id,
            'requested_by' => $user->id,
            'status' => 'pending',
            'remark' => 'テスト'
        ]);

        $listResponse = $this->actingAs($user)->get(route('corrections.list', ['status' => 'pending']));

        $listResponse->assertStatus(200);

        $detailUrl = route('generals.detail', [
            'id' => $attendance->id,
        ]);

        $listResponse->assertSee($detailUrl);

        $detailResponse = $this->actingAs($user)->get($detailUrl);

        $detailResponse->assertStatus(200);
    }
}

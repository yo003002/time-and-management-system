<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceClockInTest extends TestCase
{
    /**
     * A basic feature test example.
     */

    use RefreshDatabase;

    // 勤務外→出勤ボタンを押すとステータスが出勤になる
    public function test_general_user_can_clock_in()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 9, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->post(route('generals.clockIn'));
        
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'work_status' => 'working',
            ]);
            
        $response = $this->get(route('generals.index'));
        $response->assertStatus(200);
        $response->assertSee('出勤中');
    }

    // 退勤後は出勤ボタンが表示されない
    public function test_clock_in_button_not_show_for_finished_user()
    {
        Carbon::setTestNow(Carbon::create(2026, 2, 1, 17, 0));

        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now()->toDateString(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
            'work_status' => 'finished',
        ]);

        $response = $this->actingAs($user)->get(route('generals.index'));

        $response->assertStatus(200);

        $response->assertDontSee('出勤');
    }

    // 出勤処理後、勤怠一覧で出勤時間が表示されている
    public function test_clock_in_time_shown_in_attendance_list()
    {
        $testDate = Carbon::create(2026, 2, 1, 9, 15);
        Carbon::setTestNow($testDate);


        $user = User::factory()->create([
            'role' => 'user',
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post(route('generals.clockIn'));

        $response = $this->actingAs($user)->get(route('generals.list'));

        $response->assertStatus(200);

        $response->assertSee('09:15');

        $dateString = $testDate->format('m/d') . '(' . ['日', '月', '火', '水', '木', '金', '土',][$testDate->dayOfWeek] . ')';
        $timeString = $testDate->format('H:i');

        $response->assertSeeInOrder([$dateString, $timeString]);
    }
}

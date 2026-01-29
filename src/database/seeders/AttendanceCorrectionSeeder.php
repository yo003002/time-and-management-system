<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\AttendanceCorrectionService;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;
use App\Models\AttendanceCorrection;


class AttendanceCorrectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $remarks = [
            '打刻ミスのため修正を申請します。',
            '打刻を忘れてしまいました。',
            '業務終了後の打刻漏れです。',
        ];

        $admin = User::where('role', 'admin')->first();

        $service = app(AttendanceCorrectionService::class);

        Attendance::all()->each(function ($attendance) use ($admin, $remarks, $service) {
            
            if (rand(1, 100) > 30) {
                return;
            }

            $isApproved = rand(0, 1) === 1;

            $clockIn = $attendance->clock_in?->copy()->addMinutes(rand(-10, 10));
            $clockOut = $attendance->clock_out?->copy()->addMinutes(rand(-10, 10));

            $correction = AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'remark' => $remarks[array_rand($remarks)],
                'status' => 'pending',
            ]);

            if ($isApproved && $admin) {

                $service->approve(
                    $correction,
                    $admin
                );
            }
        });
    }
}

<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\AttendanceBreak;

class AttendanceCorrectionService
{
    /**
     * Create a new class instance.
     */
    public function approve(
        AttendanceCorrection $correction,
        User $admin
    ): void
    {
        DB::transaction(function () use ($correction, $admin) {

            $attendance = $correction->attendance;

            $attendance->update([
                'clock_in' => $correction->clock_in,
                'clock_out' => $correction->clock_out,
            ]);

            $attendance->breaks()->delete();

            foreach ($correction->breaks ?? [] as $break) {
                AttendanceBreak::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => Carbon::parse($attendance->date)
                        ->setTimeFromTimeString($break['start']),
                    'break_end' => Carbon::parse($attendance->date)
                        ->setTimeFromTimeString($break['end']),
                ]);
            }

            $correction->update([
                'status' => 'approved',
                'approved_by' => $admin->id,
            ]);
        });
    }
}

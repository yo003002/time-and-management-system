<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;


class AttendanceCorrectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('role', 'admin')->first();

        Attendance::all()->each(function ($attendance) use ($admin) {
            
            if (rand(1, 100) > 30) {
                return;
            }

            $status = rand(0, 1) ? 'pending' : 'approved';

            $clockIn = $attendance->clock_in?->copy()->addMinutes(rand(-10, 10));
            $clockOut = $attendance->clock_out?->copy()->addMinutes(rand(-10, 10));

            AttendanceCorrection::create([
                'attendance_id' => $attendance->id,
                'approved_by' => $status === 'approved' ? $admin?->id : null,
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
                'breaks' => [
                    ['start' => '12:00', 'end' => '13:00']
                ],
                'remark' => fake()->realText(30),
                'status' => $status,
            ]);
        });
    }
}

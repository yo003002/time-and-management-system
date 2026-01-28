<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceBreak;


class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::where('role', 'user')->each(function ($user) {

            for ($i = 0; $i < 30; $i++) {
                $date = Carbon::today()->subDays($i);

                $clockIn = $date->copy()->setTime(9, rand(0, 30));
                $clockOut = $clockIn->copy()->addHours(8)->addMinutes(rand(0, 30));

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'date' => $date,
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'work_status' => 'finished',
                ]);

                $breakCount = rand(1, 2);
                $breakStart = $clockIn->copy()->addHours(3);

                for ($b = 0; $b < $breakCount; $b++) {
                    $breakEnd = $breakStart->copy()->addMinutes(45);

                    AttendanceBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_start' => $breakStart,
                        'break_end' => $breakEnd,
                    ]);

                    $breakStart = $breakEnd->copy()->addHours(1);
                }
            }
        });
    }
}

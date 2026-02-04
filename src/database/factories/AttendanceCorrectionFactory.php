<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrection>
 */
class AttendanceCorrectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = AttendanceCorrection::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'requested_by' => User::factory(),
            'clock_in' => null,
            'clock_out' => null,
            'breaks' => null,
            'remark' => 'テスト',
            'status' => 'pending',
        ];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use App\Models\Attendance;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */

    protected $model = Attendance::class;

    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d');

        $clockIn = Carbon::parse($date)->setTime(9, fake()->numberBetween(0, 30));
        $clockOut = (clone $clockIn)->addHours(8)->addMinutes(fake()->numberBetween(0, 30));

        return [
            'date' => $date,
            'clock_in' => $clockIn,
            'clock_out' => $clockOut,
            'work_status' => 'finished',
        ];
    }
}

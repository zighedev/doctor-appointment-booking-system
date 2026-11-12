<?php

namespace Database\Factories;

use App\Models\Clinic;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startHour = fake()->numberBetween(8, 12);
        $startTime = sprintf('%02d:00:00', $startHour);

        return [
            'doctor_id' => Doctor::factory(),
            'clinic_id' => Clinic::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'start_time' => $startTime,
            'end_time' => sprintf('%02d:00:00', $startHour + 4),
            'slot_duration_minutes' => 30,
            'break_start' => sprintf('%02d:00:00', $startHour + 2),
            'break_end' => sprintf('%02d:30:00', $startHour + 2),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}

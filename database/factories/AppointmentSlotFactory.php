<?php

namespace Database\Factories;

use App\Enums\AppointmentSlotStatus;
use App\Models\Clinic;
use App\Models\Doctor;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentSlotFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startTime = fake()->numberBetween(8, 15);

        return [
            'doctor_id' => Doctor::factory(),
            'clinic_id' => Clinic::factory(),
            'slot_date' => fake()->dateTimeBetween('tomorrow', '+30 days')->format('Y-m-d'),
            'start_time' => sprintf('%02d:00:00', $startTime),
            'end_time' => sprintf('%02d:30:00', $startTime),
            'status' => AppointmentSlotStatus::AVAILABLE->value,
        ];
    }

    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentSlotStatus::BOOKED->value,
        ]);
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentSlotStatus::BLOCKED->value,
        ]);
    }
}

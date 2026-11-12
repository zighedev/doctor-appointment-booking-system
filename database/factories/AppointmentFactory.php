<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\AppointmentSlot;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'appointment_slot_id' => AppointmentSlot::factory(),
            'patient_id' => Patient::factory(),
            'status' => AppointmentStatus::PENDING->value,
            'reason' => fake()->optional()->sentence(),
            'patient_notes' => fake()->optional()->paragraph(),
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::CONFIRMED->value,
            'confirmed_at' => now(),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::CANCELLED->value,
            'cancelled_at' => now(),
            'cancellation_reason' => fake()->sentence(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => AppointmentStatus::REJECTED->value,
            'rejection_reason' => fake()->sentence(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->doctor(),
            'license_number' => fake()->unique()->bothify('LIC-########'),
            'bio' => fake()->optional()->paragraph(),
            'years_of_experience' => fake()->numberBetween(0, 40),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->patient(),
            'blood_type' => fake()->optional()->randomElement([
                'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-',
            ]),
            'emergency_contact_name' => fake()->optional()->name(),
            'emergency_contact_phone' => fake()->optional()->phoneNumber(),
            'emergency_contact_relationship' => fake()->optional()->randomElement([
                'Parent', 'Spouse', 'Sibling', 'Friend',
            ]),
            'address' => fake()->optional()->address(),
        ];
    }
}

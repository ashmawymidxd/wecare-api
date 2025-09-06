<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EmployeeFactory extends Factory
{
    public function definition()
    {
        // Helper function to generate optional dates
        $optionalDate = function ($probability = 0.7, $minDays = 0, $maxDays = 730) {
            return $this->faker->optional($probability)->dateTimeBetween(
                now()->addDays($minDays),
                now()->addDays($maxDays)
                ?->format('Y-m-d'));
        };

        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'), // default password
            'profile_image' => $this->faker->optional()->imageUrl(200, 200, 'people'),
            'nationality' => $this->faker->optional(0.8)->country(),
            'mobile' => $this->faker->optional()->phoneNumber(),
            'preferred_language' => $this->faker->optional()->randomElement(['en', 'ar', 'fr', 'es']),
            'address' => $this->faker->optional()->address(),
            'contract_start_date' => $this->faker->date(),
            'contract_end_date' => $optionalDate(0.7, 30, 730), // 70% chance, between 1 month to 2 years
            'salary' => $this->faker->randomFloat(2, 2000, 10000),
            'commission' => $this->faker->randomFloat(2, 0, 20),
            'labor_card_end_date' => $optionalDate(0.7, 30, 730),
            'passport_end_date' => $optionalDate(0.8, 180, 1825), // 80% chance, between 6 months to 5 years
            'accommodation_end_date' => $optionalDate(0.6, 30, 365), // 60% chance, between 1 month to 1 year
            'notes' => $this->faker->optional()->paragraph(),
            'role_id' => \App\Models\Role::inRandomOrder()->first()->id ?? 1,
            'remember_token' => Str::random(10),
        ];
    }
}

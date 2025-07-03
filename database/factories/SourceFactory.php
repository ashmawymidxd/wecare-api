<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SourceFactory extends Factory
{
    public function definition()
    {
        $sourceTypes = ['Tasheel', 'Typing Center', 'PRO', 'Social Media', 'Referral', 'Inactive'];

        // Helper function for optional dates
        $optionalDate = function ($probability = 0.6, $minDays = -365, $maxDays = 0) {
            return $this->faker->optional($probability)
                ->dateTimeBetween(now()->addDays($minDays), now()->addDays($maxDays))
                ?->format('Y-m-d');
        };

        return [
            'name' => $this->faker->company(),
            'phone_number' => $this->faker->optional(0.8)->phoneNumber(),
            'nationality' => $this->faker->optional(0.7)->country(),
            'preferred_language' => $this->faker->optional(0.6)->randomElement(['en', 'ar', 'fr', 'es']),
            'account_manager_id' => $this->faker->optional(0.5)->randomElement(
                \App\Models\Employee::exists()
                    ? \App\Models\Employee::pluck('id')->toArray()
                    : [null]
            ),
            'last_connect_date' => $optionalDate(0.6, -365, 0), // 60% chance, within past year
            'clients_number' => $this->faker->numberBetween(0, 100),
            'source_type' => $this->faker->randomElement($sourceTypes),
        ];
    }
}

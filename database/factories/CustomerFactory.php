<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CustomerFactory extends Factory
{
    public function definition()
    {
        $sourceTypes = ['Tasheel', 'Typing Center', 'PRO', 'Social Media', 'Referral', 'Inactive'];
        $businessCategories = ['Construction', 'Retail', 'Hospitality', 'Technology', 'Healthcare', 'Education', 'Manufacturing', null];

        return [
            'name' => $this->faker->name(),
            'mobile' => $this->faker->unique()->phoneNumber(),
            'email' => $this->faker->optional(0.7)->safeEmail(),
            'nationality' => $this->faker->optional(0.8)->country(),
            'preferred_language' => $this->faker->randomElement(['en', 'ar', 'fr', 'es']),
            'address' => $this->faker->optional(0.6)->address(),
            'company_name' => $this->faker->optional(0.5)->company(),
            'business_category' => $this->faker->optional(0.6)->randomElement($businessCategories),
            'country' => $this->faker->optional(0.8)->country(),
            'joining_date' => $this->faker->optional(0.7)->dateTimeBetween('-2 years', 'now'),
            'source_type' => $this->faker->optional(0.6)->randomElement($sourceTypes),
            'profile_image' => $this->faker->optional(0.3)->imageUrl(200, 200, 'people'),
            'employee_id'=> Employee::factory(),
        ];
    }
}

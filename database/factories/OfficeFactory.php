<?php

namespace Database\Factories;

use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class OfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $totalDesks = $this->faker->numberBetween(5, 20);
        $reservedDesks = $this->faker->numberBetween(0, $totalDesks);

        return [
            'room_id' => Room::factory(),
            'office_type' => $this->faker->randomElement(['Private', 'Shared', 'Open Space', 'Meeting']),
            'total_desks' => $totalDesks,
            'number_of_reserved_desks' => $reservedDesks,
            'number_of_availability_desks' => $totalDesks - $reservedDesks,
            'status' => $this->faker->randomElement(['Available', 'Occupied', 'Under Maintenance']),
        ];
    }
}

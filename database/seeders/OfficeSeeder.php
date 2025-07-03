<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Office;
use Illuminate\Database\Seeder;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create offices for each room
        Room::all()->each(function ($room) {
            Office::factory()->count(rand(1, 3))->create([
                'room_id' => $room->id,
            ]);
        });

        // Create some specific office examples
        Office::create([
            'room_id' => Room::first()->id,
            'office_type' => 'Executive Suite',
            'total_desks' => 1,
            'number_of_reserved_desks' => 1,
            'number_of_availability_desks' => 0,
            'status' => 'Occupied',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Room;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create rooms for each branch
        Branch::all()->each(function ($branch) {
            Room::factory()->count(5)->create([
                'branch_id' => $branch->id,
            ]);
        });

        // Alternatively, create specific rooms
        Room::create([
            'branch_id' => Branch::first()->id,
            'room_number' => 'VIP-001',
        ]);
    }
}

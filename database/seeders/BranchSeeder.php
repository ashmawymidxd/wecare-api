<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create 10 branches using the factory
        Branch::factory()->count(10)->create();

        // Alternatively, you can create specific branches
        Branch::create([
            'name' => 'Main Branch',
            'address' => '123 Main Street, Cityville',
        ]);
    }
}

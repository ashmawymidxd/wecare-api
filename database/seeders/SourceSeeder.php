<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Source;

class SourceSeeder extends Seeder
{
    public function run()
    {
        // Create 50 sources using the factory
        Source::factory()->count(20)->create();
    }
}

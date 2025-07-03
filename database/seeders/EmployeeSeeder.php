<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employee;

class EmployeeSeeder extends Seeder
{
    public function run()
    {
        // Create 20 employees using the factory
        Employee::factory()->count(10)->create();
    }
}

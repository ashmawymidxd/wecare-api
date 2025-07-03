<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Customer;

class CustomerSeeder extends Seeder
{
    public function run()
    {
        // Create 100 customers using the factory
        Customer::factory()->count(10)->create();
    }
}

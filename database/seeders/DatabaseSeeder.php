<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
         $this->call([
            RolesTableSeeder::class,
            EmployeeSeeder::class,
            SourceSeeder::class,
            CustomerSeeder::class,
            BranchSeeder::class,
            RoomSeeder::class,
            OfficeSeeder::class,
            ContractSeeder::class,
            GeneralSettingsSeeder::class,
        ]);
    }
}

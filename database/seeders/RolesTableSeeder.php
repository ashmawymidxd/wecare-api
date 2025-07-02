<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'admin',
                'permissions' => [
                    'manage-roles',
                    'manage-employees',
                    'view-reports',
                    'manage-accounting',
                    'manage-accounts',
                    'manage-customers',
                    'manage-sources',
                    'manage-branches',
                    'manage-rooms',
                    'manage-offices',
                    'manage-contracts'
                ]
            ],
            [
                'name' => 'accountant',
                'permissions' => [
                    'view-reports',
                    'manage-accounting'
                ]
            ],
            [
                'name' => 'account-manager',
                'permissions' => [
                    'view-reports',
                    'manage-accounts'
                ]
            ]
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }

        // Create admin user
        \App\Models\Employee::create([
            'name' => 'Admin User',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin1234'),
            'role_id' => 1
        ]);
    }
}

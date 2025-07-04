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
                    'view-dashboard',
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
                    'manage-contracts',
                    'manage-inquiries',
                    'manage-reports'
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
                    'manage-accounts',
                    'view-reports'
                ]
            ],
            [
                'name' => 'hr',
                'permissions' => [
                    'manage-employees',
                    'view-reports'
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

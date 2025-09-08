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
                    'manage-reports',
                    'manage-notifications',
                    'manage-logs',
                    'manage-documents',
                    'manage-settings'
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
            "mobile"=>'01554300351',
            'profile_image'=>"http://127.0.0.1:8000/employee_profile_images/default.png",
            'password' => bcrypt('admin1234'),
            'role_id' => 1,
            'nationality' => 'Egyption',
            'preferred_language'=>'Arabic',
            'address'=>'6 Mohamed Ali Street, Cairo',
            'contract_start_date'=>'14 Jan, 2024',
            'contract_end_date'=>'14 Jan, 2027',
            'salary'=>'2200',
        ]);
    }
}

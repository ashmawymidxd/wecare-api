<?php

namespace App\Http\Controllers;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleSettingsController extends Controller
{
    public function index()
    {
        try {
            // Get all roles with their employee count
            $roles = Role::withCount('employees')->get();

            // Transform the data to include permission count
            $roleStatistics = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'role_name' => $role->name,
                    'employee_count' => $role->employees_count,
                    'permission_count' => count($role->permissions ?? [])
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $roleStatistics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve role statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

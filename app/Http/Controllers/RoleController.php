<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:manage-roles');
    }

    public function index()
    {
        $roles = Role::all();
        return response()->json($roles);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles',
            'permissions' => 'nullable|array',
            'permissions.*' => 'distinct|string',
        ], [
            'permissions.*.distinct' => 'The permissions array contains duplicate values.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $role = Role::create([
            'name' => $request->name,
            'permissions' => $request->permissions ?? []
        ]);

        return response()->json($role, 201);
    }

    public function show(Role $role)
    {
        $employees = Employee::where('role_id', $role->id)->get();

        return response()->json([
            'role' => $role,
            'employees' => $employees->map(function($employee) {
                return [
                    'name' => $employee->name,
                    'assigned_at' => $employee->created_at->format('j F Y g:i A'),
                ];
            }),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:roles,name,' . $role->id,
            'permissions' => 'nullable|array',
            'permissions.*' => 'distinct|string',
        ], [
            'permissions.*.distinct' => 'The permissions array contains duplicate values.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $role->update([
            'name' => $request->name,
            'permissions' => $request->permissions ?? $role->permissions
        ]);

        return response()->json($role);
    }

    public function destroy(Role $role)
    {
        $role->delete();
        return response()->json(null, 204);
    }
}

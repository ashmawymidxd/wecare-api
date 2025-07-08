<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if (!$token = auth()->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Record attendance
        Attendance::create([
            'employee_id' => auth()->user()->id,
            'login_time' => now(),
        ]);

        return $this->createNewToken($token);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:employees',
            'password' => 'required|string|confirmed|min:6',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $employee = Employee::create(array_merge(
            $validator->validated(),
            ['password' => Hash::make($request->password)]
        ));

        return response()->json([
            'message' => 'Employee successfully registered',
            'employee' => $employee
        ], 201);
    }

    public function logout()
    {
        // Update the latest attendance record with logout time
        $employeeId = auth()->user()->id;

        $attendance = Attendance::where('employee_id', $employeeId)
            ->whereNull('logout_time')
            ->latest('login_time')
            ->first();

        if ($attendance) {
            $attendance->update(['logout_time' => now()]);
        }
        auth()->logout();
        return response()->json(['message' => 'Employee successfully signed out']);
    }

    public function refresh()
    {
        return $this->createNewToken(auth()->refresh());
    }

    public function userProfile()
    {
        return response()->json(
            $message = [
                'message' => 'Employee profile retrieved successfully',
                'employee' => auth()->user(),
                'permissions' => auth()->user()->role->permissions,
                'attachments' => auth()->user()->attachments,
            ]
        );
    }

    protected function createNewToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'employee' => auth()->user(),
            'attachments' => auth()->user()->attachments,
            'permissions' => auth()->user()->role->permissions,
        ]);
    }
}

<?php
// app/Http/Controllers/API/NotificationSettingController.php

namespace App\Http\Controllers;

use App\Models\NotificationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class NotificationSettingController extends Controller
{
    /**
     * Display the notification settings for the authenticated employee.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $employee = Auth::guard('api')->user();
            // Get or create notification settings for the employee
            $settings = NotificationSetting::firstOrCreate(
                ['employee_id' => $employee->id],
                [
                    'contract_expiry' => true,
                    'renewal_reminders' => true,
                    'inspection' => true,
                    'new_customer_added' => true,
                    'commission_payment' => true,
                    'archived_contracts' => true,
                    'document_expiry_alerts' => true,
                    'required_document_missing' => true,
                ]
            );

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the notification settings for the authenticated employee.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        try {
            $employee = Auth::guard('api')->user();
            // return response()->json($employee->id);

            $validator = Validator::make($request->all(), [
                'contract_expiry' => 'sometimes|boolean',
                'renewal_reminders' => 'sometimes|boolean',
                'inspection' => 'sometimes|boolean',
                'new_customer_added' => 'sometimes|boolean',
                'commission_payment' => 'sometimes|boolean',
                'archived_contracts' => 'sometimes|boolean',
                'document_expiry_alerts' => 'sometimes|boolean',
                'required_document_missing' => 'sometimes|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Update or create notification settings
            $settings = NotificationSetting::updateOrCreate(
                ['employee_id' => $employee->id],
                $validator->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully',
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get notification settings for a specific employee (admin only).
     *
     * @param  int  $employeeId
     * @return \Illuminate\Http\Response
     */
    public function show($employeeId)
    {
        try {
            // Check if the authenticated employee has permission to view other employees' settings
            // You might want to add authorization logic here

            $settings = NotificationSetting::where('employee_id', $employeeId)->first();

            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification settings not found for this employee'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $settings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve notification settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

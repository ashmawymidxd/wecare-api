<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class EmployeeNotificationController extends Controller
{
    /**
     * Get all notifications for the authenticated employee
     */
    public function index()
    {
        $employee = Auth::guard('api')->user();
        return response()->json($employee->notifications);
    }

    /**
     * Get unread notifications for the authenticated employee
     */
    public function unread()
    {
        $employee = Auth::guard('api')->user();
        return response()->json($employee->unreadNotifications);
    }

    /**
     * Get read notifications for the authenticated employee
     */
    public function read()
    {
        $employee = Auth::guard('api')->user();
        return response()->json($employee->readNotifications);
    }

    /**
     * Mark all notifications as read for the authenticated employee
     */
    public function markAllAsRead()
    {
        $employee = Auth::guard('api')->user();
        $employee->unreadNotifications->markAsRead();

        return response()->json([
            'message' => 'All notifications marked as read',
            'success' => true
        ]);
    }

    /**
     * Mark a single notification as read by ID
     */
    public function markAsRead($id)
    {
        $employee = Auth::guard('api')->user();

        $notification = $employee->notifications()->where('id', $id)->first();

        if (!$notification) {
            return response()->json([
                'message' => 'Notification not found',
                'success' => false
            ], 404);
        }

        $notification->markAsRead();

        return response()->json([
            'message' => 'Notification marked as read',
            'success' => true
        ]);
    }
}

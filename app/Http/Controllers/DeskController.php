<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Desk;
use App\Models\Room;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class DeskController extends Controller
{
    public function addDeskToSharedOffice(Request $request, $branchId, $roomId, $officeId)
    {
        // Validate the office exists and belongs to the room
        $office = Office::where('id', $officeId)
                      ->where('room_id', $roomId)
                      ->firstOrFail();

        // Validate the room belongs to the branch
        $room = Room::where('id', $roomId)
                   ->where('branch_id', $branchId)
                   ->firstOrFail();

        // Verify office type is shared
        // if ($office->office_type !== 'shared') {
        //     return response()->json([
        //         'message' => 'Desks can only be added to shared offices',
        //         'errors' => ['office' => ['Invalid office type']]
        //     ], 422);
        // }

        // Validate request using Validation facade
        $validator = Validator::make($request->all(), [
            'desk_number' => [
                'required',
                'string',
                Rule::unique('desks', 'desk_number')->where(function ($query) use ($office) {
                    return $query->where('office_id', $office->id);
                })
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the desk
        $desk = $office->desks()->create([
            'desk_number' => $validator->validated()['desk_number'],
            'status' => 'available'
        ]);

        return response()->json([
            'message' => 'Desk created successfully',
            'data' => $desk
        ], 201);
    }

    public function listDesks($branchId, $roomId, $officeId)
    {
        $office = Office::where('id', $officeId)
                      ->where('room_id', $roomId)
                      ->with('desks') // Eager load desks
                      ->firstOrFail();

        // Verify room belongs to branch
        Room::where('id', $roomId)
           ->where('branch_id', $branchId)
           ->firstOrFail();

        return response()->json([
            'data' => $office->desks
        ]);
    }
}

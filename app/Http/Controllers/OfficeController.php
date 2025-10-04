<?php

namespace App\Http\Controllers;

use App\Models\Office;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OfficeController extends Controller
{
    public function index($branchId, $roomId)
    {
        $offices = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->get();

        return response()->json($offices->load('desks'));
    }

    public function store(Request $request, $branchId, $roomId)
    {
        // Validate the room belongs to the given branch
        $room = Room::where('branch_id', $branchId)->findOrFail($roomId);

        // Validate the input
        $validator = Validator::make($request->all(), [
            'office_type' => 'required|in:private,shared',
            'number_of_desks' => 'required_if:office_type,shared|integer|min:1'
        ], [
            'number_of_desks.required_if' => 'The number of desks is required for shared offices.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the office
        $office = $room->offices()->create([
            'office_type' => $request->office_type,
        ]);

        // Create desks based on office type
        if ($office->office_type === 'shared') {
            $numberDesks = (int) $request->number_of_desks;

            for ($i = 1; $i <= $numberDesks; $i++) {
                $office->desks()->create([
                    'desk_number' => 'A' . $i,
                    'status' => 'available',
                ]);
            }
        } else {
            // Private office - create one main desk
            $office->desks()->create([
                'desk_number' => 'A1',
                'status' => 'available',
            ]);
        }

        return response()->json([
            'message' => 'Office created successfully.',
            'office' => $office->load('desks')
        ], 201);
    }


    public function show($branchId, $roomId, $id)
    {
        $office = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->findOrFail($id);

        return response()->json($office->load('desks'));
    }

    public function update(Request $request, $branchId, $roomId, $id)
    {
        $office = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'office_type' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $office->update($validator->validated());

        return response()->json($office);
    }

    public function destroy($branchId, $roomId, $id)
    {
        $office = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->findOrFail($id);

        $office->delete();
        return response()->json(null, 204);
    }
}

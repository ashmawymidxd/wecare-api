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

        return response()->json($offices);
    }

    public function store(Request $request, $branchId, $roomId)
    {
        $room = Room::where('branch_id', $branchId)->findOrFail($roomId);

        $validator = Validator::make($request->all(), [
            'office_type' => 'required|string|max:255',
            'number_of_reserved_desks' => 'required|integer|min:1',
            'number_of_availability_desks' => 'required|integer|min:1',
            'status' => 'required|string|in:available,occupied,maintenance',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $office = $room->offices()->create($validator->validated());

        return response()->json($office, 201);
    }

    public function show($branchId, $roomId, $id)
    {
        $office = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->findOrFail($id);

        return response()->json($office);
    }

    public function update(Request $request, $branchId, $roomId, $id)
    {
        $office = Office::whereHas('room', function($query) use ($branchId, $roomId) {
            $query->where('branch_id', $branchId)->where('id', $roomId);
        })->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'office_type' => 'sometimes|required|string|max:255',
            'number_of_desks' => 'sometimes|required|integer|min:1',
            'status' => 'sometimes|required|string|in:available,occupied,maintenance',
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

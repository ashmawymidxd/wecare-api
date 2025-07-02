<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RoomController extends Controller
{
    public function index($branchId)
    {
        $rooms = Room::where('branch_id', $branchId)->with('offices')->get();
        return response()->json($rooms);
    }

    public function store(Request $request, $branchId)
    {
        $branch = Branch::findOrFail($branchId);

        $validator = Validator::make($request->all(), [
            // No validation needed as room_number is auto-generated
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Auto-generate room number (you can customize this logic)
        $roomNumber = 'RM-' . str_pad(Room::where('branch_id', $branchId)->count() + 1, 4, '0', STR_PAD_LEFT);

        $room = $branch->rooms()->create([
            'room_number' => $roomNumber
        ]);

        return response()->json($room, 201);
    }

    public function show($branchId, $id)
    {
        $room = Room::where('branch_id', $branchId)->with('offices')->findOrFail($id);
        return response()->json($room);
    }

    public function destroy($branchId, $id)
    {
        $room = Room::where('branch_id', $branchId)->findOrFail($id);
        $room->delete();
        return response()->json(null, 204);
    }
}

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
        $room = Room::where('branch_id', $branchId)->findOrFail($roomId);

        $validator = Validator::make($request->all(), [
            'office_type' => 'required|in:private,shared',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $office = $room->offices()->create($validator->validated());

        // // For private offices, create a single main desk
        // if ($office->office_type === 'private') {
        //     $office->desks()->create([
        //         'desk_number' => 'main',
        //         'status' => 'available'
        //     ]);
        // }

        // For private offices, create a single main desk
        if ($office->office_type === 'shared') {
          $number_desks = $request->number_of_desks;
          for ($i=0; $i < $number_desks; $i++) {
                if ($i < 20) {
                    $office->desks()->create([
                        'desk_number' => 'A'.$i+1,
                        'status' => 'available'
                    ]);
                }
                elseif ($i > 20 && $i < 40) {
                     $office->desks()->create([
                        'desk_number' => 'B'.$i+1,
                        'status' => 'available'
                    ]);
                }
          }
        }

        return response()->json($office->load('desks'), 201);
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

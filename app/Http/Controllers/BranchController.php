<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BranchController extends Controller
{
    // public function index()
    // {
    //     $branches = Branch::with('rooms.offices.desks')->get();
    //     return response()->json($branches);
    // }
    public function index()
    {
        $branches = Branch::with(['rooms.offices'])->get()->map(function ($branch) {
            $offices = $branch->rooms->flatMap->offices;

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'address' => $branch->address,
                'rooms_count' => $branch->rooms->count(),
                'total_offices' => $offices->count(),
                'private_offices_count' => $offices->where('office_type', 'private')->count(),
                'shared_offices_count' => $offices->where('office_type', 'shared')->count(),
            ];
        });

        return response()->json($branches);
    }



    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'required|string',
            'room_numbers' => 'sometimes|array',
            'room_numbers.*' => 'string|max:50|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $branch = Branch::create($validator->validated());
        if ($request->has('room_numbers')) {
            foreach ($request->room_numbers as $room_number) {
                $branch->rooms()->create(['room_number' => 'room'.$room_number]);
            }
        }

        return response()->json($branch, 201);
    }

    public function show($id)
    {
        $branch = Branch::with('rooms.offices.desks')->findOrFail($id);
        return response()->json($branch);
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'address' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $branch->update($validator->validated());

        return response()->json($branch);
    }

    public function destroy($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete();
        return response()->json(null, 204);
    }
}

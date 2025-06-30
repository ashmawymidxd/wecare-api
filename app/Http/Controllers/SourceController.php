<?php

namespace App\Http\Controllers;

use App\Models\Source;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SourceController extends Controller
{
    public function index()
    {
        $sources = Source::all();
        return response()->json($sources);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $source = Source::create($validator->validated());

        return response()->json($source, 201);
    }

    public function show($id)
    {
        $source = Source::findOrFail($id);
        return response()->json($source);
    }

    public function update(Request $request, $id)
    {
        $source = Source::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $source->update($validator->validated());

        return response()->json($source);
    }

    public function destroy($id)
    {
        $source = Source::findOrFail($id);
        $source->delete();
        return response()->json(null, 204);
    }
}

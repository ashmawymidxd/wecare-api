<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class InquiryController extends Controller
{
    public function index()
    {
        $inquiries = Inquiry::with(['customer', 'source'])->get();
        return response()->json($inquiries);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'joining_date' => 'nullable|date',
            'source_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'expected_contract_amount' => 'nullable|numeric',
            'expected_discount' => 'nullable|numeric|between:0,100',
            'customer_id' => 'nullable|exists:customers,id',
            'source_id' => 'nullable|exists:sources,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Handle file upload
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('public/inquiry_profile_images');
            $data['profile_image'] = Storage::url($path);
        }

        $inquiry = Inquiry::create($data);

        return response()->json($inquiry, 201);
    }

    public function show($id)
    {
        $inquiry = Inquiry::with(['customer', 'source'])->findOrFail($id);
        return response()->json($inquiry);
    }

    public function update(Request $request, $id)
    {
        $inquiry = Inquiry::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'sometimes|required|string|max:255',
            'mobile' => 'sometimes|required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'joining_date' => 'nullable|date',
            'source_name' => 'nullable|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'expected_contract_amount' => 'nullable|numeric',
            'expected_discount' => 'nullable|numeric|between:0,100',
            'customer_id' => 'nullable|exists:customers,id',
            'source_id' => 'nullable|exists:sources,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Handle file upload
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($inquiry->profile_image) {
                $oldImagePath = str_replace('/storage', 'public', $inquiry->profile_image);
                Storage::delete($oldImagePath);
            }

            $path = $request->file('profile_image')->store('public/inquiry_profile_images');
            $data['profile_image'] = Storage::url($path);
        }

        $inquiry->update($data);

        return response()->json($inquiry);
    }

    public function destroy($id)
    {
        $inquiry = Inquiry::findOrFail($id);

        // Delete associated image
        if ($inquiry->profile_image) {
            $imagePath = str_replace('/storage', 'public', $inquiry->profile_image);
            Storage::delete($imagePath);
        }

        $inquiry->delete();

        return response()->json(null, 204);
    }
}

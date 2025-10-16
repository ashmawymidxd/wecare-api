<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\InquiryReminder;
use App\Models\InquiryNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class InquiryController extends Controller
{

    public function index()
    {
        $Per_Page = request()->get('per_page',25);
        // Add pagination with eager loading
        $inquiries = Inquiry::with(['customer', 'source'])
            ->paginate($Per_Page);

        // Transform the paginated data
        $transformedInquiries = collect($inquiries->items())->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'client' => [
                    'name' => $inquiry->name,
                    'phone' => $inquiry->mobile,
                    'avatar' => $inquiry->profile_image ? url($inquiry->profile_image) : url('employee_profile_images/default.png'),
                ],
                'companyName' => $inquiry->company_name,
                'status' => $inquiry->status,
                'expectedContractAmount' => $inquiry->expected_contract_amount,
                'expectedDiscount' => $inquiry->expected_discount,
                'joiningDate' => $inquiry->joining_date ? \Carbon\Carbon::parse($inquiry->joining_date)->format('d M, Y') : null,
            ];
        });

        // Return paginated response with metadata
        return response()->json([
            'data' => $transformedInquiries,
            'pagination' => [
                'current_page' => $inquiries->currentPage(),
                'per_page' => $inquiries->perPage(),
                'total' => $inquiries->total(),
                'last_page' => $inquiries->lastPage(),
                'from' => $inquiries->firstItem(),
                'to' => $inquiries->lastItem(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'source_name' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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
            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('inquiry_profile_images'), $filename);
            $data['profile_image'] = url('inquiry_profile_images/' . $filename);
        }

        $inquiry = Inquiry::create($data);

        return response()->json($inquiry, 201);
    }

    public function show($id)
    {
        $inquiry = Inquiry::with(['customer', 'source','timelines','notes','reminders'])->findOrFail($id);
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
            'status'=>'required|in:Active,Inactive'
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

            $data['profile_image'] = $request->file('profile_image')->store('inquiry_profile_images');
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

    public function addReminder(Request $request, $inquiryId)
    {
        $idValidator = Validator::make(['inquirie_id' => $inquiryId], [
            'inquirie_id' => 'required|integer|exists:inquiries,id',
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing inquirie ID.',
                'errors' => $idValidator->errors(),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
            'reminder_date' => 'nullable|date',
            'reminder_type' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid note input.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquirie = Inquiry::find($inquiryId);

        $noteData = [
            'note' => $request->note,
            'reminder_type' => $request->reminder_type,
            'reminder_date' => $request->reminder_date ?? now()->toDateString(),
        ];

        $note = $inquirie->reminders()->create($noteData);

        return response()->json([
            'status' => 'success',
            'message' => 'Reminder added successfully.',
            'note' => $note,
        ], 201);
    }

    public function addNote(Request $request, $inquiryId)
    {
        $idValidator = Validator::make(['inquirie_id' => $inquiryId], [
            'inquirie_id' => 'required|integer|exists:inquiries,id',
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing inquirie ID.',
                'errors' => $idValidator->errors(),
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
            'note_date' => 'nullable|date',
            'note_time' => 'nullable|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid note input.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $inquirie = Inquiry::find($inquiryId);

        $noteData = [
            'note' => $request->note,
            'note_date' => $request->note_date ?? now()->toDateString(),
        ];

        if ($request->filled('note_time')) {
            $noteData['note_time'] = $request->note_time;
        }

        $note = $inquirie->notes()->create($noteData);

        return response()->json([
            'status' => 'success',
            'message' => 'Note added successfully.',
            'note' => $note,
        ], 201);
    }

    public function updateProfileImage(Request $request, $id)
    {
        // Validate only the profile image
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the inquiry
        $inquiry = Inquiry::find($id);
        if (!$inquiry) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inquiry not found.',
            ], 404);
        }

        // Delete old image if it exists
        if ($inquiry->profile_image) {
            $oldImagePath = str_replace(url('/').'/', '', $inquiry->profile_image);
            if (file_exists(public_path($oldImagePath))) {
                unlink(public_path($oldImagePath));
            }
        }

        // Handle new file upload
        $file = $request->file('profile_image');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->move(public_path('inquiry_profile_images'), $filename);
        $inquiry->profile_image = url('inquiry_profile_images/' . $filename);

        // Save the updated image
        $inquiry->save();

        return response()->json([
            'message' => 'Profile image updated successfully.',
            'profile_image' => $inquiry->profile_image
        ]);
    }

}

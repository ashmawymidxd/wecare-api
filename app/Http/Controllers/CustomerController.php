<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerAttachment;
use App\Models\CustomerNote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::with(['source', 'attachments', 'notes'])->get();
        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'mobile' => 'required|string|max:20',
            'email' => 'required|email|max:255',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'source_id' => 'nullable|exists:sources,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer = Customer::create($validator->validated());

        return response()->json($customer, 201);
    }

    public function show($id)
    {
        $customer = Customer::with(['source', 'attachments', 'notes'])->findOrFail($id);
        return response()->json($customer);
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'mobile' => 'sometimes|required|string|max:20',
            'email' => 'sometimes|required|email|max:255',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:10',
            'address' => 'nullable|string',
            'company_name' => 'nullable|string|max:255',
            'business_category' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'joining_date' => 'nullable|date',
            'source_id' => 'nullable|exists:sources,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $customer->update($validator->validated());

        return response()->json($customer);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);
        $customer->delete();
        return response()->json(null, 204);
    }

    public function addAttachment(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file',
            'type' => 'required|in:client_id,company_licence,other',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('file');
        $path = $file->store('customer_attachments');

        $attachment = $customer->attachments()->create([
            'type' => $request->type,
            'file_path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ]);

        return response()->json($attachment, 201);
    }

    public function addNote(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
            'note_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $noteData = [
            'note' => $request->note,
            'note_date' => $request->note_date ?? now(),
        ];

        $note = $customer->notes()->create($noteData);

        return response()->json($note, 201);
    }
}

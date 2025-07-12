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
        $customers = Customer::with(['attachments', 'notes', 'contracts'])->get();

        $transformedCustomers = $customers->map(function ($customer) {
            // Format contracts
            $formattedContracts = $customer->contracts->map(function ($contract) {
                return [
                    'contractNumber' => $contract->contract_number,
                    'startDate' => $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d M, Y') : null,
                    'endDate' => $contract->expiry_date ? \Carbon\Carbon::parse($contract->expiry_date)->format('d M, Y') : null,
                    'amount' => $contract->contract_amount ? number_format($contract->contract_amount, 0) . ' AED' : null,
                    'status' => $contract->status,
                    'validUntil' => $contract->expiry_date ? \Carbon\Carbon::parse($contract->expiry_date)->format('d M,Y') : 'Waiting For Payment',
                ];
            });

            // Format notes
            $formattedNotes = $customer->notes->map(function ($note) {
                return [
                    'id' => 'CID-' . $note->id,
                    'dateAdded' => $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M, Y') : null,
                    'addedBy' => 'Ahmed Ali', // You'll need to replace this with actual user data
                ];
            });

            // Format attachments (assuming you have specific attachment types)
            $formattedAttachments = [
                'clientId' => 'Client ID.PDF', // Replace with actual data
                'companyLicense' => 'Company License.PDF', // Replace with actual data
            ];

            return [
                'id' => 'KD-' . $customer->id,
                'client' => [
                    'name' => $customer->name,
                    'phone' => $customer->mobile,
                    'avatar' => $customer->profile_image ? Storage::url($customer->profile_image) :  'http://127.0.0.1:8000'.Storage::url('employee_profile_images/default.png'),
                ],
                'companyName' => $customer->company_name,
                'officeNo' => 'CID-' . $customer->id,
                'accountManager' => $customer->employee->name,
                'status' => $customer->status,
                'email' => $customer->email,
                'expectedAmount' => 2500, // You'll need to replace this with actual data
                'expectedDiscount' => 14, // You'll need to replace this with actual data
                'joiningDate' => $customer->joining_date ? \Carbon\Carbon::parse($customer->joining_date)->format('d M, Y') : null,
                'contractNo' => $customer->contracts->first() ? $customer->contracts->first()->contract_number : null,
                'amount' => $customer->contracts->sum('contract_amount'),
                'source' => $customer->source_type,
                'validUntil' => $customer->contracts->first() ? \Carbon\Carbon::parse($customer->contracts->first()->expiry_date)->format('d M,Y') : null,
                'nationality' => $customer->nationality,
                'preferredLanguage' => $customer->preferred_language,
                'address' => $customer->address,
                'businessCategory' => $customer->business_category,
                'country' => $customer->country,
                'attachments' => $formattedAttachments,
                'contracts' => $formattedContracts,
                'notes' => $formattedNotes,
            ];
        });

        return response()->json($transformedCustomers);
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
            'source_type' => 'required|in:Tasheel,Typing Center,PRO,Social Media,Referral,Inactive',
            'profile_image' => 'required|image|max:2048', // 2MB max
            'employee_id' => 'nullable|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $path = $request->file('profile_image')->store('public/customer_profile_images');
            $data['profile_image'] = Storage::url($path);
        }

        $customer = Customer::create($data);

        // Handle document uploads
        $this->handleDocumentUploads($request, $customer);

        return response()->json($customer->load(['attachments']), 201);

    }

    public function show($id)
    {
        $customer = Customer::with(['attachments', 'notes'])->findOrFail($id);
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
            'source_type' => 'nullable|in:Tasheel,Typing Center,PRO,Social Media,Referral,Inactive',
            'profile_image' => 'nullable|image|max:2048',
            'employee_id' => 'nullable|exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Handle profile image update
        if ($request->hasFile('profile_image')) {
            // Delete old image if exists
            if ($customer->profile_image) {
                Storage::delete($customer->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('customer_profile_images');
        }

        $customer->update($data);

        return response()->json($customer);
    }

    public function destroy($id)
    {
        $customer = Customer::findOrFail($id);

        // Delete profile image if exists
        if ($customer->profile_image) {
            Storage::delete($customer->profile_image);
        }

        $customer->delete();
        return response()->json(null, 204);
    }

    public function updateProfileImage(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|image|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Delete old image if exists
        if ($customer->profile_image) {
            Storage::delete($customer->profile_image);
        }

        $path = $request->file('profile_image')->store('customer_profile_images');
        $customer->update(['profile_image' => $path]);

        return response()->json([
            'message' => 'Profile image updated successfully',
            'image_url' => Storage::url($path)
        ]);
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

    protected function handleDocumentUploads(Request $request, Customer $customer)
    {
        // Handle Client ID document
        if ($request->hasFile('client_id_document')) {
            $path = $request->file('client_id_document')->store('public/customer_documents');
            $customer->attachments()->create([
                'type' => 'client_id_document',
                'original_name' => $request->file('client_id_document')->getClientOriginalName(),
                'file_path' => Storage::url($path)
            ]);
        }

        // Handle Company License document
        if ($request->hasFile('company_license_document')) {
            $path = $request->file('company_license_document')->store('public/customer_documents');
            $customer->attachments()->create([
                'type' => 'company_license_document',
                'original_name' => $request->file('company_license_document')->getClientOriginalName(),
                'file_path' => Storage::url($path)
            ]);
        }

        // Handle other documents
        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents') as $file) {
                $path = $file->store('public/customer_documents');
                $customer->attachments()->create([
                    'type' => 'other_documents',
                    'original_name' => $file->getClientOriginalName(),
                    'file_path' => Storage::url($path)
                ]);
            }
        }
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

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
    // public function index()
    // {
    //     $customers = Customer::with(['attachments', 'notes', 'contracts'])->get();

    //     $transformedCustomers = $customers->map(function ($customer) {
    //         // Format contracts
    //         $formattedContracts = $customer->contracts->map(function ($contract) {
    //             return [
    //                 'contractNumber' => $contract->contract_number,
    //                 'startDate' => $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('d M, Y') : null,
    //                 'endDate' => $contract->expiry_date ? \Carbon\Carbon::parse($contract->expiry_date)->format('d M, Y') : null,
    //                 'amount' => $contract->contract_amount ? number_format($contract->contract_amount, 0) . ' AED' : null,
    //                 'status' => $contract->status,
    //                 'validUntil' => $contract->expiry_date ? \Carbon\Carbon::parse($contract->expiry_date)->format('d M,Y') : 'Waiting For Payment',
    //             ];
    //         });

    //         // Format notes
    //         $formattedNotes = $customer->notes->map(function ($note) {
    //             return [
    //                 'id' => 'CID-' . $note->id,
    //                 'dateAdded' => $note->created_at ? \Carbon\Carbon::parse($note->created_at)->format('d M, Y') : null,
    //                 'addedBy' => 'Ahmed Ali', // You'll need to replace this with actual user data
    //             ];
    //         });

    //         // Format attachments (assuming you have specific attachment types)
    //         $formattedAttachments = [
    //             'clientId' => 'Client ID.PDF', // Replace with actual data
    //             'companyLicense' => 'Company License.PDF', // Replace with actual data
    //         ];

    //         return [
    //             'key' => 'KD-' . $customer->id,
    //             'id' => $customer->id,
    //             'client' => [
    //                 'name' => $customer->name,
    //                 'phone' => $customer->mobile,
    //                 'avatar' => $customer->profile_image ? url($customer->profile_image) :  url('employee_profile_images/default.png'),
    //             ],
    //             'companyName' => $customer->company_name,
    //             'officeNo' => 'CID-' . $customer->id,
    //             'accountManager' => $customer->employee->name,
    //             'status' => $customer->status,
    //         ];
    //     });

    //     return response()->json($transformedCustomers);
    // }

    public function index()
    {
        // Add pagination with eager loading
        $customers = Customer::with(['attachments', 'notes', 'contracts', 'employee'])
            ->paginate(10); // You can adjust the number per page

        // Transform the paginated data - use items() instead of getCollection()
        $transformedCustomers = collect($customers->items())->map(function ($customer) {
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
                'key' => 'KD-' . $customer->id,
                'id' => $customer->id,
                'client' => [
                    'name' => $customer->name,
                    'phone' => $customer->mobile,
                    'avatar' => $customer->profile_image ? url($customer->profile_image) : url('employee_profile_images/default.png'),
                ],
                'companyName' => $customer->company_name,
                'officeNo' => 'CID-' . $customer->id,
                'accountManager' => $customer->employee->name ?? 'N/A', // Added null check
                'status' => $customer->status,
            ];
        });

        // Return paginated response with metadata
        return response()->json([
            'data' => $transformedCustomers,
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'per_page' => $customers->perPage(),
                'total' => $customers->total(),
                'last_page' => $customers->lastPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ]
        ]);
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
            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('customer_profile_images'), $filename);
            $data['profile_image'] = url('customer_profile_images/' . $filename);
        }


        $customer = Customer::create($data);

        // handel note and attachment uploads
        if ($request->has('note')) {
             $noteData = [
                'note' => $request->note,
                'note_date' => $request->note_date ?? now(),
                'note_time' => $request->note_time ?? now()->format('H:i'),
            ];
            $customer->notes()->create($noteData);
        }

        // Handle document uploads
        $this->handleDocumentUploads($request, $customer);

        return response()->json($customer->load(['attachments']), 201);

    }

    public function show($id)
    {
        $customer = Customer::with(['attachments', 'notes','contracts'])->findOrFail($id);
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
        $filename = time() . '_' . $file->getClientOriginalName();

        // Ensure the directory exists
        $destinationPath = public_path('customer_attachments');
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        // Move file to public path
        $file->move($destinationPath, $filename);

        // Save attachment with full URL
        $attachment = $customer->attachments()->create([
            'type' => $request->type,
            'file_path' => url('customer_attachments/' . $filename),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return response()->json($attachment, 201);
    }


    protected function handleDocumentUploads(Request $request, Customer $customer)
    {
        // Handle Client ID document
        if ($request->hasFile('client_id_document')) {
            $file = $request->file('client_id_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('client_id_document'), $filename);

            $customer->attachments()->create([
                'type' => 'client_id_document',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => url('client_id_document/' . $filename),
            ]);
        }

        // Handle Company License document
        if ($request->hasFile('company_license_document')) {
            $file = $request->file('company_license_document');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('company_license_document'), $filename);

            $customer->attachments()->create([
                'type' => 'company_license_document',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => url('company_license_document/' . $filename),
            ]);
        }

        // Handle other documents
        if ($request->hasFile('other_documents')) {
            $file = $request->file('other_documents');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('other_documents'), $filename);

            $customer->attachments()->create([
                'type' => 'other_documents',
                'original_name' => $file->getClientOriginalName(),
                'file_path' => url('other_documents/' . $filename),
            ]);
        }
    }


    public function addNote(Request $request, $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $validator = Validator::make($request->all(), [
            'note' => 'required|string',
            'note_date' => 'nullable|date',
            'note_time' => 'nullable|date_format:H:i',
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

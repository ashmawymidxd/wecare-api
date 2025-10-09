<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Inquiry;
use App\Models\InquiriesTimeLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{

    public function index()
    {
        $Per_Page = request()->get('per_page',25);
        // Add pagination with eager loading
        $customers = Customer::with(['attachments', 'notes', 'contracts', 'employee'])
            ->paginate($Per_Page); // You can adjust the number per page

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

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'mobile' => 'required|string|max:20',
    //         'email' => 'required|email|max:255',
    //         'nationality' => 'nullable|string|max:100',
    //         'preferred_language' => 'nullable|string|max:10',
    //         'address' => 'nullable|string',
    //         'company_name' => 'nullable|string|max:255',
    //         'business_category' => 'nullable|string|max:255',
    //         'country' => 'nullable|string|max:100',
    //         'joining_date' => 'nullable|date',
    //         'source_type' => 'required|in:Tasheel,Typing Center,PRO,Social Media,Referral,Inactive',
    //         'profile_image' => 'required|image|max:2048', // 2MB max
    //         'employee_id' => 'nullable|exists:employees,id'
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     $data = $validator->validated();

    //     // Handle profile image upload
    //     if ($request->hasFile('profile_image')) {
    //         $file = $request->file('profile_image');
    //         $filename = time() . '_' . $file->getClientOriginalName();
    //         $file->move(public_path('customer_profile_images'), $filename);
    //         $data['profile_image'] = url('customer_profile_images/' . $filename);
    //     }


    //     $customer = Customer::create($data);

    //     // handel note and attachment uploads
    //     if ($request->has('note')) {
    //          $noteData = [
    //             'note' => $request->note,
    //             'note_date' => $request->note_date ?? now(),
    //             'note_time' => $request->note_time ?? now()->format('H:i'),
    //         ];
    //         $customer->notes()->create($noteData);
    //     }

    //     // Handle document uploads
    //     $this->handleDocumentUploads($request, $customer);

    //     // Time Line
    //     $customer_email = $request->email;
    //     $customer_inquery = Inquiry::where('email',$customer_email);
    //     $account_manger = Employee::findOrfial($request->employee_id);

    //     // InquiriesTimeLine

    //     if($customer_email == $customer_inquery ->email){
    //         InquiriesTimeLine::craete([
    //             "stepOne"=>"Client Added",
    //             "stepTow"=>"Account Manger ".$account_manger->name." Contact With Client",
    //             "stepThree"=>"Client Agreed The Terms contract Should be signed this week",
    //             "inquirie_id" => $customer_inquery->id,
    //         ]);
    //     }

    //     return response()->json($customer->load(['attachments']), 201);

    // }

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
            $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
            $file->move(public_path('customer_profile_images'), $filename);
            $data['profile_image'] = 'customer_profile_images/' . $filename; // Store relative path
        }

        DB::beginTransaction();
        
        try {
            // Create customer
            $customer = Customer::create($data);

            // Handle note creation
            if ($request->filled('note')) {
                $customer->notes()->create([
                    'note' => $request->note,
                    'note_date' => $request->note_date ?? now()->format('Y-m-d'),
                    'note_time' => $request->note_time ?? now()->format('H:i:s'),
                ]);
            }

            // Handle document uploads
            $this->handleDocumentUploads($request, $customer);

            // Handle timeline creation
            $this->handleTimelineCreation($request->email, $request->employee_id);

            DB::commit();

            return response()->json([
                'message' => 'Customer created successfully',
                'data' => $customer->load(['attachments', 'notes'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            // Clean up uploaded file if transaction fails
            if (isset($filename) && file_exists(public_path('customer_profile_images/' . $filename))) {
                unlink(public_path('customer_profile_images/' . $filename));
            }

            Log::error('Customer creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create customer',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Handle timeline creation for existing inquiries
     */
    private function handleTimelineCreation($email, $employeeId)
    {
        // Find existing inquiry by email
        $inquiry = Inquiry::where('email', $email)->first();
        
        if (!$inquiry) {
            return;
        }

        // Get account manager name if exists
        $accountManagerName = 'Unknown';
        if ($employeeId) {
            $accountManager = Employee::find($employeeId);
            $accountManagerName = $accountManager ? $accountManager->name : 'Unknown';
        }

        // Create timeline entry using the relationship
        $inquiry->timelines()->create([
            "stepOne" => "Client Added",
            "stepTwo" => "Account Manager {$accountManagerName} Contact With Client",
            "stepThree" => "Client Agreed The Terms contract Should be signed this week",
        ]);
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
        // ✅ Step 1: Validate the customer ID first
        $idValidator = Validator::make(['customer_id' => $customerId], [
            'customer_id' => 'required|integer|exists:customers,id',
        ]);

        if ($idValidator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing customer ID.',
                'errors' => $idValidator->errors(),
            ], 422);
        }

        // ✅ Step 2: Validate note input
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

        // ✅ Step 3: Retrieve the validated customer
        $customer = Customer::find($customerId);

        // ✅ Step 4: Prepare the note data
        $noteData = [
            'note' => $request->note,
            'note_date' => $request->note_date ?? now()->toDateString(),
        ];

        // ✅ Optional: Include note_time if provided
        if ($request->filled('note_time')) {
            $noteData['note_time'] = $request->note_time;
        }

        // ✅ Step 5: Create the note
        $note = $customer->notes()->create($noteData);

        // ✅ Step 6: Return a clean response
        return response()->json([
            'status' => 'success',
            'message' => 'Note added successfully.',
            'note' => $note,
        ], 201);
    }

}

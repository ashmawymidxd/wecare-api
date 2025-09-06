<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Notifications\CustomerTransferredNotification;
class EmployeeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('permission:manage-employees');
    }

    public function index()
    {
        $employees = Employee::with(['role', 'attachments' , 'customers'])->get();
        return response()->json($employees);
    }

    public function accountMangersEmolyee(){
        $account_manager_employees = Employee::whereHas('role',function($query){
            $query->where('name','account-manager');
        })->get();

        return response()->json($account_manager_employees);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:employees',
            'mobile' => 'nullable|string|max:20',
            'password' => 'required|string|min:6|confirmed',
            'preferred_language' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'commission' => 'nullable|numeric|between:0,100',
            'role_id' => 'required|exists:roles,id',
            'labor_card_end_date' => 'nullable|date',
            'passport_end_date' => 'nullable|date',
            'accommodation_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'client_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'company_license_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'other_documents.*' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['password'] = Hash::make($data['password']);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('employee_profile_images'), $filename);
            $data['profile_image'] = url('employee_profile_images/' . $filename);
        }


        $employee = Employee::create($data);

        // Handle document uploads
        $this->handleDocumentUploads($request, $employee);

        return response()->json($employee->load(['role', 'attachments']), 201);
    }

    public function show(Employee $employee)
    {
        return response()->json([
            'employee' => $employee->load(['role', 'attachments', 'customers']),
            'statistics' => [
                "customers"=>$employee->customerStat(),
                "contracts"=>$employee->contractStats(),
                "expired_contracts"=>$employee->expiredContractStats(),
                "average_contracts_amount"=>$employee->averageContractAmountStats(),
                "conversion_rate"=>$employee->conversionRateStats(),
                "attendance"=>$employee->getAttendance(),
            ],
        ], 200);
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:employees,email,' . $employee->id,
            'mobile' => 'nullable|string|max:20',
            'password' => 'sometimes|nullable|string|min:6|confirmed',
            'preferred_language' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date',
            'salary' => 'nullable|numeric',
            'commission' => 'nullable|numeric|between:0,100',
            'role_id' => 'sometimes|required|exists:roles,id',
            'labor_card_end_date' => 'nullable|date',
            'passport_end_date' => 'nullable|date',
            'accommodation_end_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'client_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'company_license_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'other_documents.*' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
        ], [
            'password.confirmed' => 'The password confirmation does not match.',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Remove password if empty
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old profile image if it exists
            if ($employee->profile_image) {
                // Get relative path from the URL
                $relativePath = str_replace(url('/') . '/', '', $employee->profile_image);
                $imagePath = public_path($relativePath);

                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }

            // Upload new image
            $file = $request->file('profile_image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->move(public_path('employee_profile_images'), $filename);

            // Save full URL
            $data['profile_image'] = url('employee_profile_images/' . $filename);
        }


        $employee->update($data);

        // Handle document uploads
        $this->handleDocumentUploads($request, $employee);

        return response()->json($employee->load(['role', 'attachments']));
    }

    public function destroy(Employee $employee)
    {
        // Delete profile image if it exists
        if ($employee->profile_image) {
            // Get the relative path from the URL
            $relativePath = str_replace(url('/') . '/', '', $employee->profile_image);
            $imagePath = public_path($relativePath);

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // Delete all attachments
        foreach ($employee->attachments as $attachment) {
            $relativeAttachmentPath = str_replace(url('/') . '/', '', $attachment->path);
            $attachmentFullPath = public_path($relativeAttachmentPath);

            if (file_exists($attachmentFullPath)) {
                unlink($attachmentFullPath);
            }

            $attachment->delete();
        }

        $employee->delete();

        return response()->json(null, 204);
    }


    public function deleteAttachment(Employee $employee, $attachmentId)
    {
        $attachment = $employee->attachments()->findOrFail($attachmentId);

        // Convert full URL to relative path
        $relativePath = str_replace(url('/') . '/', '', $attachment->path);
        $filePath = public_path($relativePath);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $attachment->delete();

        return response()->json(['message' => 'Attachment deleted successfully']);
    }


    protected function handleDocumentUploads(Request $request, Employee $employee)
    {
        // Ensure folder exists
        $uploadPath = public_path('employee_documents');
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Handle Client ID document
        if ($request->hasFile('client_id_document')) {
            $file = $request->file('client_id_document');
            $filename = time() . '_client_id_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);

            $employee->attachments()->create([
                'type' => 'client_id',
                'name' => $file->getClientOriginalName(),
                'path' => url('employee_documents/' . $filename),
            ]);
        }

        // Handle Company License document
        if ($request->hasFile('company_license_document')) {
            $file = $request->file('company_license_document');
            $filename = time() . '_license_' . $file->getClientOriginalName();
            $file->move($uploadPath, $filename);

            $employee->attachments()->create([
                'type' => 'company_license',
                'name' => $file->getClientOriginalName(),
                'path' => url('employee_documents/' . $filename),
            ]);
        }

        // Handle other documents
        if ($request->hasFile('other_documents')) {
            foreach ($request->file('other_documents') as $file) {
                $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $file->move($uploadPath, $filename);

                $employee->attachments()->create([
                    'type' => 'other',
                    'name' => $file->getClientOriginalName(),
                    'path' => url('employee_documents/' . $filename),
                ]);
            }
        }
    }


    public function transferCustomers(Request $request)
    {
        $request->validate([
            'from_employee_id' => 'required|exists:employees,id',
            'to_employee_id' => 'required|exists:employees,id|different:from_employee_id'
        ]);

        $fromEmployee = Employee::findOrFail($request->from_employee_id);
        $toEmployee = Employee::findOrFail($request->to_employee_id);

        $transferredCount = $fromEmployee->transferCustomersTo($toEmployee);

        ActivityLog::create([
            "auth_id" =>Auth::guard('api')->user()->id,
            "level" =>"info",
            "message" =>"Transferred {$transferredCount} customers from employee {$fromEmployee->name} to employee {$toEmployee->name}",
            "type" =>"Employees"
        ]);

        Log::info("Transferred {$transferredCount} customers from employee {$fromEmployee->name} to employee {$toEmployee->name}");

        $employee = Auth::guard('api')->user();
        $employee->notify(new CustomerTransferredNotification($fromEmployee, $toEmployee, $transferredCount));

        return response()->json([
            'success' => true,
            'message' => "Successfully transferred {$transferredCount} customers",
            'data' => [
                'from_employee_id' => $fromEmployee->id,
                'to_employee_id' => $toEmployee->id,
                'transferred_count' => $transferredCount
            ]
        ]);
    }
}

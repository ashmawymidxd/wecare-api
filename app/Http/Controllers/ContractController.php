<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Notifications\NewContractCreated;
use App\Notifications\PaymentContract;
use App\Notifications\ActionRequired;
use App\Notifications\Clearancedocuments;
use Illuminate\Support\Facades\Auth;
class ContractController extends Controller
{
    public function index()
    {
        $contracts = Contract::with(['customer', 'branch', 'attachments'])->get();
        return response()->json($contracts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'required|exists:customers,id',
            'start_date' => 'required|date',
            'expiry_date' => 'required|date|after:start_date',
            'office_type' => 'required|string',
            'city' => 'required|string',
            'branch_id' => 'required|exists:branches,id',
            'number_of_desks' => 'required|integer|min:1',
            'contract_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,cheque,bank_transfer',
            'cheque_covered' => 'sometimes|boolean',
            'cash_amount' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|required_if:payment_method,cheque',
            'due_date' => 'nullable|date|required_if:payment_method,cheque',
            'discount_type' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'electricity_fees' => 'nullable|numeric|min:0',
            'contract_ratification_fees' => 'nullable|numeric|min:0',
            'pro_amount_received' => 'nullable|numeric|min:0',
            'pro_expense' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'actual_amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'contract_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
            'payment_proof_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
            'status' => 'nullable|string|in:active,inactive,expired,renewed,terminated,new'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Auto-generate contract number
        $data['contract_number'] = 'CN-' . date('Ymd') . '-' . Str::random(6);

        // Set default status if not provided
        $data['status'] = $data['status'] ?? 'active';

        $contract = Contract::create($data);

        // Handle file uploads
        $this->handleFileUploads($request, $contract);

        // Send notification to authenticated employee
        if ($employee = Auth::guard('api')->user()) {
            $employee->notify(new NewContractCreated($contract));
        }

        // payment date notification
        if($request->payment_date){
            $employee = Auth::guard('api')->user();
            $employee->notify(new PaymentContract($contract));
        }

        if($request->status != "active"){
            $employee = Auth::guard('api')->user();
            $employee->notify(new ActionRequired($contract));
        }

        if (!$request->hasFile('contract_file')) {
            $employee = Auth::guard('api')->user();
            $employee->notify(new Clearancedocuments($contract));
        }

        // Optionally notify all employees or specific roles
        // Employee::all()->each->notify(new NewContractCreated($contract));

        return response()->json($contract->load('attachments'), 201);
    }

    public function show($id)
    {
        $contract = Contract::with(['customer', 'branch', 'attachments'])->findOrFail($id);
        return response()->json($contract);
    }

    public function update(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'customer_id' => 'sometimes|required|exists:customers,id',
            'start_date' => 'sometimes|required|date',
            'expiry_date' => 'sometimes|required|date|after:start_date',
            'office_type' => 'sometimes|required|string',
            'city' => 'sometimes|required|string',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'number_of_desks' => 'sometimes|required|integer|min:1',
            'contract_amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|string|in:cash,cheque,bank_transfer',
            'cheque_covered' => 'sometimes|boolean',
            'cash_amount' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|required_if:payment_method,cheque',
            'due_date' => 'nullable|date|required_if:payment_method,cheque',
            'discount_type' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'electricity_fees' => 'nullable|numeric|min:0',
            'contract_ratification_fees' => 'nullable|numeric|min:0',
            'pro_amount_received' => 'nullable|numeric|min:0',
            'pro_expense' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'actual_amount' => 'sometimes|required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'contract_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
            'payment_proof_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
            'status' => 'sometimes|string|in:active,expired,renewed,terminated'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $contract->update($validator->validated());

        // Handle file uploads
        $this->handleFileUploads($request, $contract);

        return response()->json($contract->load('attachments'));
    }

    public function destroy($id)
    {
        $contract = Contract::findOrFail($id);

        // Delete associated files
        foreach ($contract->attachments as $attachment) {
            Storage::delete($attachment->file_path);
        }

        $contract->delete();
        return response()->json(null, 204);
    }

    /**
     * Renew a contract by creating a new contract with similar details
     */
    public function renew(Request $request, $id)
    {
        $originalContract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'expiry_date' => 'required|date|after:start_date',
            'contract_amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:cash,cheque,bank_transfer',
            'cheque_covered' => 'sometimes|boolean',
            'cash_amount' => 'nullable|numeric|min:0',
            'cheque_number' => 'nullable|string|required_if:payment_method,cheque',
            'due_date' => 'nullable|date|required_if:payment_method,cheque',
            'discount_type' => 'nullable|string',
            'discount' => 'nullable|numeric|min:0',
            'electricity_fees' => 'nullable|numeric|min:0',
            'contract_ratification_fees' => 'nullable|numeric|min:0',
            'pro_amount_received' => 'nullable|numeric|min:0',
            'pro_expense' => 'nullable|numeric|min:0',
            'commission' => 'nullable|numeric|min:0',
            'actual_amount' => 'required|numeric|min:0',
            'payment_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'contract_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048',
            'payment_proof_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Create new contract with most details from original
        $newContractData = [
            'customer_id' => $originalContract->customer_id,
            'start_date' => $data['start_date'],
            'expiry_date' => $data['expiry_date'],
            'office_type' => $originalContract->office_type,
            'city' => $originalContract->city,
            'branch_id' => $originalContract->branch_id,
            'number_of_desks' => $originalContract->number_of_desks,
            'contract_amount' => $data['contract_amount'],
            'payment_method' => $data['payment_method'],
            'cheque_covered' => $data['cheque_covered'] ?? $originalContract->cheque_covered,
            'cash_amount' => $data['cash_amount'] ?? $originalContract->cash_amount,
            'cheque_number' => $data['cheque_number'] ?? $originalContract->cheque_number,
            'due_date' => $data['due_date'] ?? $originalContract->due_date,
            'discount_type' => $data['discount_type'] ?? $originalContract->discount_type,
            'discount' => $data['discount'] ?? $originalContract->discount,
            'electricity_fees' => $data['electricity_fees'] ?? $originalContract->electricity_fees,
            'contract_ratification_fees' => $data['contract_ratification_fees'] ?? $originalContract->contract_ratification_fees,
            'pro_amount_received' => $data['pro_amount_received'] ?? $originalContract->pro_amount_received,
            'pro_expense' => $data['pro_expense'] ?? $originalContract->pro_expense,
            'commission' => $data['commission'] ?? $originalContract->commission,
            'actual_amount' => $data['actual_amount'],
            'payment_date' => $data['payment_date'] ?? $originalContract->payment_date,
            'notes' => $data['notes'] ?? $originalContract->notes,
            'status' => 'active',
            'renewed_from_id' => $originalContract->id
        ];

        // Auto-generate new contract number
        $newContractData['contract_number'] = 'CN-' . date('Ymd') . '-' . Str::random(6);

        $newContract = Contract::create($newContractData);

        // Handle file uploads for the new contract
        $this->handleFileUploads($request, $newContract);

        // Update original contract status to 'renewed'
        $originalContract->update(['status' => 'renewed']);

        return response()->json([
            'message' => 'Contract renewed successfully',
            'original_contract' => $originalContract,
            'new_contract' => $newContract->load('attachments')
        ], 201);
    }

    /**
     * Update the status of a contract
     */
    public function updateStatus(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'status' => 'required|string|in:active,expired,renewed,terminated',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        $contract->update([
            'status' => $data['status'],
            'notes' => isset($data['notes']) ? $data['notes'] : $contract->notes
        ]);

        return response()->json([
            'message' => 'Contract status updated successfully',
            'contract' => $contract
        ]);
    }

    protected function handleFileUploads(Request $request, Contract $contract)
    {
        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            $path = $file->store('public/contracts/attachments');

            ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_path' => Storage::url($path),
                'file_name' => $file->getClientOriginalName(),
                'type' => 'contract'
            ]);
        }

        if ($request->hasFile('payment_proof_file')) {
            $file = $request->file('payment_proof_file');
            $path = $file->store('public/contracts/attachments');

            ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_path' => Storage::url($path),
                'file_name' => $file->getClientOriginalName(),
                'type' => 'payment_proof'
            ]);
        }
    }

    public function addAttachment(Request $request, $id)
    {
        $contract = Contract::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:pdf,jpg,png|max:2048',
            'type' => 'required|in:contract,payment_proof'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $file = $request->file('file');
        $path = $file->store('public/contracts/attachments');

        $attachment = ContractAttachment::create([
            'contract_id' => $contract->id,
            'file_path' => Storage::url($path),
            'file_name' => $file->getClientOriginalName(),
            'type' => $request->type
        ]);

        return response()->json($attachment, 201);
    }

    public function deleteAttachment($contractId, $attachmentId)
    {
        $attachment = ContractAttachment::where('contract_id', $contractId)
            ->findOrFail($attachmentId);

        Storage::delete($attachment->file_path);
        $attachment->delete();

        return response()->json(null, 204);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use App\Models\ContractAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
            'payment_proof_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();

        // Auto-generate contract number
        $data['contract_number'] = 'CN-' . date('Ymd') . '-' . Str::random(6);

        $contract = Contract::create($data);

        // Handle file uploads
        $this->handleFileUploads($request, $contract);

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
            'payment_proof_file' => 'sometimes|file|mimes:pdf,jpg,png|max:2048'
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

    protected function handleFileUploads(Request $request, Contract $contract)
    {
        if ($request->hasFile('contract_file')) {
            $file = $request->file('contract_file');
            $path = $file->store('contracts/attachments');

            ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
                'type' => 'contract'
            ]);
        }

        if ($request->hasFile('payment_proof_file')) {
            $file = $request->file('payment_proof_file');
            $path = $file->store('contracts/attachments');

            ContractAttachment::create([
                'contract_id' => $contract->id,
                'file_path' => $path,
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
        $path = $file->store('contracts/attachments');

        $attachment = ContractAttachment::create([
            'contract_id' => $contract->id,
            'file_path' => $path,
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

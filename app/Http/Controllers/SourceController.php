<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\Employee;
use App\Models\ActivityLog;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
class SourceController extends Controller
{
   public function index()
    {
        $sources = Source::with('accountManager')->get();

        $result = $sources->map(function ($source) {
            // Count customers with the same source_type
            $clientCount = Customer::where('source_type', $source->source_type)->count();

            return [
                'id' => $source->id,
                'name' => $source->name,
                'account_manager' => $source->accountManager ? $source->accountManager->name : null,
                'clients_count' => $clientCount,
                'created_at' => $source->created_at->format('Y-m-d H:i:s'),
                'source_type' => $source->source_type,
                'last_connect_date' => $source->last_connect_date ? $source->last_connect_date->format('Y-m-d') : null
            ];
        });

        // Get total counts
        $totalSources = Source::count();
        $totalClients = Customer::count();
        // Group by source_type for tab counts
        $sourceTypeCounts = $sources->groupBy('source_type')->map->count();
        $inactiveCount = $sources->where('last_connect_date', '<', now()->subMonths(6))->count();

        return response()->json([
            'data' => $result,
            'meta' => [
                'total_sources' => $totalSources,
                'total_clients' => $totalClients,
                'source_type_counts' => [
                    'All' => $totalSources,
                    'Tasheel' => $sourceTypeCounts['Tasheel'] ?? 0,
                    'Typing Center' => $sourceTypeCounts['Typing Center'] ?? 0,
                    'PRO' => $sourceTypeCounts['PRO'] ?? 0,
                    'Social Media' => $sourceTypeCounts['Social Media'] ?? 0,
                    'Referral' => $sourceTypeCounts['Referral'] ?? 0,
                    'Inactive' => $inactiveCount,
                ]
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:100',
            'account_manager_id' => [
                'nullable',
                Rule::exists('employees', 'id')
            ],
            'last_connect_date' => 'nullable|date|before_or_equal:today',
            'clients_number' => 'nullable|integer|min:0',
            'source_type' => 'required|in:Tasheel,Typing Center,PRO,Social Media,Referral,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $source = Source::create($validator->validated());

            if($request->account_manager_id){
                  ActivityLog::create([
                    "auth_id" =>Auth::guard('api')->user()->id,
                    "level" =>"info",
                    "message" =>$source->accountManager->name." Conected with ".$request->name,
                    "type" =>"Employees"
                ]);
            }

            // If this source has clients, we might want to update related records
            if ($source->clients_number > 0) {
                // Potential future logic to associate clients
            }

            DB::commit();

            return response()->json([
                'message' => 'Source created successfully',
                'data' => $source->load('accountManager')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $source = Source::with('accountManager')->find($id);

        if (!$source) {
            return response()->json([
                'message' => 'Source not found'
            ], 404);
        }

        return response()->json([
            'data' => $source
        ]);
    }

    public function update(Request $request, $id)
    {
        $source = Source::find($id);

        if (!$source) {
            return response()->json([
                'message' => 'Source not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'nationality' => 'nullable|string|max:100',
            'preferred_language' => 'nullable|string|max:100',
            'account_manager_id' => 'nullable|exists:employees,id',
            'last_connect_date' => 'nullable|date|before_or_equal:today',
            'clients_number' => 'nullable|integer|min:0',
            'source_type' => 'sometimes|required|in:Tasheel,Typing Center,PRO,Social Media,Referral,Inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $source->update($validator->validated());

            // Additional business logic when source type changes
            if ($request->has('source_type') && $source->wasChanged('source_type')) {
                // Potential future logic for source type changes
            }

            DB::commit();

            return response()->json([
                'message' => 'Source updated successfully',
                'data' => $source->load('accountManager')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $source = Source::find($id);

        if (!$source) {
            return response()->json([
                'message' => 'Source not found'
            ], 404);
        }

        // Check if this source has associated clients before deleting
        if ($source->clients_number > 0) {
            return response()->json([
                'message' => 'Cannot delete source with active clients'
            ], 400);
        }

        try {
            $source->delete();

            return response()->json([
                'message' => 'Source deleted successfully'
            ], 204);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete source',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Additional methods for business logic

    public function getSourceTypes()
    {
        return response()->json([
            'data' => [
                'Tasheel',
                'Typing Center',
                'PRO',
                'Social Media',
                'Referral',
                'Inactive'
            ]
        ]);
    }

    public function getSourcesByManager($managerId)
    {
        $manager = Employee::find($managerId);

        if (!$manager) {
            return response()->json([
                'message' => 'Account manager not found'
            ], 404);
        }

        $sources = Source::where('account_manager_id', $managerId)
            ->withCount('clients')
            ->orderBy('last_connect_date', 'desc')
            ->get();

        return response()->json([
            'data' => $sources,
            'meta' => [
                'manager_name' => $manager->name,
                'total_sources' => $sources->count(),
                'total_clients' => $sources->sum('clients_count')
            ]
        ]);
    }
}

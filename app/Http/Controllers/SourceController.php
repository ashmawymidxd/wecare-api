<?php

namespace App\Http\Controllers;

use App\Models\Source;
use App\Models\SourceNote;
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
        $Per_Page = request()->get('per_page',25);
        // Add pagination with eager loading
        $sources = Source::with('accountManager')->paginate($Per_Page);

        // Transform the paginated data
        $result = collect($sources->items())->map(function ($source) {
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

        // Get total counts (these queries remain the same as they're for meta data)
        $totalSources = Source::count();
        $totalClients = Customer::count();

        // Group by source_type for tab counts - we need to query separately for accurate counts
        $sourceTypeCounts = Source::select('source_type')
            ->selectRaw('COUNT(*) as count')
            ->groupBy('source_type')
            ->pluck('count', 'source_type');

        $inactiveCount = Source::where('last_connect_date', '<', now()->subMonths(6))->count();

        return response()->json([
            'data' => $result,
            'pagination' => [
                'current_page' => $sources->currentPage(),
                'per_page' => $sources->perPage(),
                'total' => $sources->total(),
                'last_page' => $sources->lastPage(),
                'from' => $sources->firstItem(),
                'to' => $sources->lastItem(),
            ],
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
        $Per_Page = request()->get('per_page', 25);

        // Fetch the source with account manager
        $source = Source::with('accountManager', 'notes')->find($id);

        if (!$source) {
            return response()->json([
                'message' => 'Source not found'
            ], 404);
        }

        // Get current month and last month dates
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Fetch clients for this source with contracts and employee
        $customers = Customer::with(['contracts', 'employee'])
            ->where('source_type', $source->source_type)
            ->paginate($Per_Page);

        // 🆕 Customer counts by month
        $thisMonthCustomerCount = Customer::where('source_type', $source->source_type)
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        $lastMonthCustomerCount = Customer::where('source_type', $source->source_type)
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        $totalCustomerCount = Customer::where('source_type', $source->source_type)->count();
        $averageCustomerCount = ($thisMonthCustomerCount + $lastMonthCustomerCount) / 2;

        // Fetch and calculate contract-related financial statistics
        $contractStatistics = Customer::where('source_type', $source->source_type)
            ->with(['contracts' => function ($query) use ($currentMonthStart, $currentMonthEnd, $lastMonthStart, $lastMonthEnd) {
                $query->whereNotNull('contract_amount')
                    ->where(function ($q) use ($currentMonthStart, $currentMonthEnd, $lastMonthStart, $lastMonthEnd) {
                        $q->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
                            ->orWhereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);
                    });
            }])
            ->get()
            ->map(function ($customer) use ($currentMonthStart, $currentMonthEnd, $lastMonthStart, $lastMonthEnd) {
                $thisMonthContracts = $customer->contracts
                    ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd]);
                $lastMonthContracts = $customer->contracts
                    ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd]);

                // Income and outcome calculations
                $thisMonthIncome = $thisMonthContracts->sum(function ($contract) {
                    return ($contract->contract_amount ?? 0) +
                        ($contract->pro_amount_received ?? 0) +
                        ($contract->cash_amount ?? 0) +
                        ($contract->actual_amount ?? 0);
                });

                $thisMonthOutcome = $thisMonthContracts->sum(function ($contract) {
                    return ($contract->pro_expense ?? 0) +
                        ($contract->electricity_fees ?? 0) +
                        ($contract->contract_ratification_fees ?? 0) +
                        ($contract->commission ?? 0) +
                        ($contract->discount ?? 0);
                });

                $lastMonthIncome = $lastMonthContracts->sum(function ($contract) {
                    return ($contract->contract_amount ?? 0) +
                        ($contract->pro_amount_received ?? 0) +
                        ($contract->cash_amount ?? 0) +
                        ($contract->actual_amount ?? 0);
                });

                $lastMonthOutcome = $lastMonthContracts->sum(function ($contract) {
                    return ($contract->pro_expense ?? 0) +
                        ($contract->electricity_fees ?? 0) +
                        ($contract->contract_ratification_fees ?? 0) +
                        ($contract->commission ?? 0) +
                        ($contract->discount ?? 0);
                });

                $totalIncome = $thisMonthIncome + $lastMonthIncome;
                $totalOutcome = $thisMonthOutcome + $lastMonthOutcome;
                $netProfit = $totalIncome - $totalOutcome;

                return [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'this_month_amount' => $thisMonthContracts->sum('contract_amount'),
                    'last_month_amount' => $lastMonthContracts->sum('contract_amount'),
                    'total_amount' => $thisMonthContracts->sum('contract_amount') + $lastMonthContracts->sum('contract_amount'),
                    'average_amount' => ($thisMonthContracts->sum('contract_amount') + $lastMonthContracts->sum('contract_amount')) / 2,
                    'income' => [
                        'this_month' => $thisMonthIncome,
                        'last_month' => $lastMonthIncome,
                        'total' => $totalIncome,
                        'average' => $totalIncome / 2
                    ],
                    'outcome' => [
                        'this_month' => $thisMonthOutcome,
                        'last_month' => $lastMonthOutcome,
                        'total' => $totalOutcome,
                        'average' => $totalOutcome / 2
                    ],
                    'net_profit' => [
                        'this_month' => $thisMonthIncome - $thisMonthOutcome,
                        'last_month' => $lastMonthIncome - $lastMonthOutcome,
                        'total' => $netProfit,
                        'average' => $netProfit / 2
                    ]
                ];
            });

        // Overall income/outcome stats
        $totalThisMonthIncome = $contractStatistics->sum('income.this_month');
        $totalLastMonthIncome = $contractStatistics->sum('income.last_month');
        $totalIncome = $totalThisMonthIncome + $totalLastMonthIncome;
        $averageIncome = $totalIncome > 0 ? $totalIncome / 2 : 0;

        $totalThisMonthOutcome = $contractStatistics->sum('outcome.this_month');
        $totalLastMonthOutcome = $contractStatistics->sum('outcome.last_month');
        $totalOutcome = $totalThisMonthOutcome + $totalLastMonthOutcome;
        $averageOutcome = $totalOutcome > 0 ? $totalOutcome / 2 : 0;

        // Transform clients
        $transformedCustomers = collect($customers->items())->map(function ($customer) {
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
                'accountManager' => $customer->employee->name ?? 'N/A',
                'status' => $customer->status,
            ];
        });

        // Final response
        return response()->json([
            'source' => [
                'id' => $source->id,
                'name' => $source->name,
                'source_type' => $source->source_type,
                'phone_number' => $source->phone_number,
                'nationality' => $source->nationality,
                'preferred_language' => $source->preferred_language,
                'last_connect_date' => $source->last_connect_date ? $source->last_connect_date->format('Y-m-d') : null,
                'clients_number' => $source->clients_number,
                'account_manager' => $source->accountManager ? [
                    'id' => $source->accountManager->id,
                    'name' => $source->accountManager->name,
                    'email' => $source->accountManager->email ?? null
                ] : null,
                'created_at' => $source->created_at->format('Y-m-d H:i:s'),
                'updated_at' => $source->updated_at->format('Y-m-d H:i:s')
            ],
            'clients' => [
                'data' => $transformedCustomers,
                'pagination' => [
                    'current_page' => $customers->currentPage(),
                    'per_page' => $customers->perPage(),
                    'total' => $customers->total(),
                    'last_page' => $customers->lastPage(),
                    'from' => $customers->firstItem(),
                    'to' => $customers->lastItem(),
                ]
            ],
            'statistics' => [
                'customers' => [
                    'this_month' => [
                        'count' => $thisMonthCustomerCount,
                    ],
                    'last_month' => [
                        'count' => $lastMonthCustomerCount,
                    ],
                    'total' => [
                        'count' => $totalCustomerCount,
                    ],
                    'average' => [
                        'count' => round($averageCustomerCount, 2),
                    ],
                ],
                'income' => [
                    'this_month' => [
                        'amount' => number_format($totalThisMonthIncome, 2) . ' AED',
                        'raw_amount' => $totalThisMonthIncome
                    ],
                    'last_month' => [
                        'amount' => number_format($totalLastMonthIncome, 2) . ' AED',
                        'raw_amount' => $totalLastMonthIncome
                    ],
                    'total' => [
                        'amount' => number_format($totalIncome, 2) . ' AED',
                        'raw_amount' => $totalIncome
                    ],
                    'average' => [
                        'amount' => number_format($averageIncome, 2) . ' AED',
                        'raw_amount' => $averageIncome
                    ]
                ],
                'outcome' => [
                    'this_month' => [
                        'amount' => number_format($totalThisMonthOutcome, 2) . ' AED',
                        'raw_amount' => $totalThisMonthOutcome
                    ],
                    'last_month' => [
                        'amount' => number_format($totalLastMonthOutcome, 2) . ' AED',
                        'raw_amount' => $totalLastMonthOutcome
                    ],
                    'total' => [
                        'amount' => number_format($totalOutcome, 2) . ' AED',
                        'raw_amount' => $totalOutcome
                    ],
                    'average' => [
                        'amount' => number_format($averageOutcome, 2) . ' AED',
                        'raw_amount' => $averageOutcome
                    ]
                ]
            ],
            'notes' => $source->notes->map(function ($note) {
                return [
                    'id' => $note->id,
                    'note' => $note->note,
                    'date_added' => $note->date_added->format('Y-m-d'),
                    'added_by_name' => $note->employees->name
                ];
            })
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
        $clients = Customer::where('source_type',$source->source_type);
        if ($clients->count() > 0) {
            return response()->json([
                'message' => 'this source associated to clients'
            ], 400);
        }

        try {
            $source->delete();

            return response()->json([
                'message' => 'Source deleted successfully'
            ]);

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

    public function addNote(Request $request, $sourceId)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'note' => 'required|string|max:1000',
            'date_added' => 'nullable|date|before_or_equal:today'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if source exists
        $source = Source::find($sourceId);
        if (!$source) {
            return response()->json([
                'message' => 'Source not found'
            ], 404);
        }

        try {
            DB::beginTransaction();

            // Get authenticated employee
            $employee = Auth::guard('api')->user();
            if (!$employee) {
                return response()->json([
                    'message' => 'Employee not authenticated'
                ], 401);
            }

            // Create the note
            $sourceNote = SourceNote::create([
                'source_id' => $sourceId,
                'note' => $request->note,
                'added_by' => $employee->id,
                'date_added' => $request->date_added ?? now()
            ]);

            // Load the relationships for response
            $sourceNote->load(['employees', 'source']);

            DB::commit();

            return response()->json([
                'message' => 'Note added successfully',
                'data' => [
                    'id' => $sourceNote->id,
                    'note' => $sourceNote->note,
                    'date_added' => $sourceNote->date_added->format('Y-m-d H:i:s'),
                    'added_by' => [
                        'id' => $sourceNote->employees->id,
                        'name' => $sourceNote->employees->name,
                        'email' => $sourceNote->employees->email
                    ],
                    'source' => [
                        'id' => $sourceNote->source->id,
                        'name' => $sourceNote->source->name,
                        'source_type' => $sourceNote->source->source_type
                    ],
                    'created_at' => $sourceNote->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to add note',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Contract;
use App\Models\Source;
use App\Models\Office;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get date range from request or use default (current month vs previous month)
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfMonth();

        // For comparison period (previous period)
        $periodDiff = $endDate->diffInDays($startDate);
        $previousStartDate = $startDate->copy()->subDays($periodDiff + 1);
        $previousEndDate = $startDate->copy()->subDay();

        // Validate date range
        if ($startDate->greaterThan($endDate)) {
            return response()->json([
                'error' => 'Start date cannot be greater than end date'
            ], 400);
        }

        // 1. New Customers Statistics
        $newCustomers = $this->getCustomerStatistics($startDate, $endDate, $previousStartDate, $previousEndDate);

        // 2. New Orders/Contracts Statistics
        $newContracts = $this->getContractStatistics($startDate, $endDate, $previousStartDate, $previousEndDate);

        // 3. Income based on contracts
        $incomeStats = $this->getIncomeStatistics($startDate, $endDate, $previousStartDate, $previousEndDate);

        // 4. Expiring Contracts
        $expiringContracts = $this->getExpiredContractsStatistics();

        // 5. Renew Customers
        $renewCastomers = $this->contractRenewalMetrics($startDate, $endDate, $previousStartDate, $previousEndDate);

        return response()->json([
            'date_range' => [
                'current_period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ],
                'previous_period' => [
                    'start_date' => $previousStartDate->format('Y-m-d'),
                    'end_date' => $previousEndDate->format('Y-m-d')
                ]
            ],
            'new_customers' => $newCustomers,
            'new_contracts' => $newContracts,
            'income_stats' => $incomeStats,
            'expiring_contracts' => $expiringContracts,
            'renew_castomers' => $renewCastomers,
        ]);
    }

    public function charts(Request $request)
    {
        // Get date range from request or use default
        $startDate = $request->get('start_date') ? Carbon::parse($request->get('start_date')) : Carbon::now()->startOfMonth();
        $endDate = $request->get('end_date') ? Carbon::parse($request->get('end_date')) : Carbon::now()->endOfMonth();

        $periodDiff = $endDate->diffInDays($startDate);
        $previousStartDate = $startDate->copy()->subDays($periodDiff + 1);
        $previousEndDate = $startDate->copy()->subDay();

        return response()->json([
            'date_range' => [
                'current_period' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d')
                ],
                'previous_period' => [
                    'start_date' => $previousStartDate->format('Y-m-d'),
                    'end_date' => $previousEndDate->format('Y-m-d')
                ]
            ],
            'revenue_stats' => $this->getRevenueStatistics($startDate, $endDate, $previousStartDate, $previousEndDate),
            'source_counts' => $this->sourceTypeStatistics(),
            'occupancy_rate' => $this->occupancyRate()
        ]);
    }

    public function statistice(Request $request)
    {
        return response()->json([
            'get_expiring_contracts' => $this->getExpiringContracts(),
            'get_employee_performance_report' => $this->getEmployeePerformanceReport()
        ]);
    }

    protected function getCustomerStatistics($startDate, $endDate, $previousStartDate, $previousEndDate)
    {
        $currentPeriodCount = Customer::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $previousPeriodCount = Customer::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $percentageChange = $previousPeriodCount > 0
            ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
            : ($currentPeriodCount > 0 ? 100 : 0);

        return [
            'count' => $currentPeriodCount,
            'previous_count' => $previousPeriodCount,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    protected function getContractStatistics($startDate, $endDate, $previousStartDate, $previousEndDate)
    {
        $currentPeriodCount = Contract::whereBetween('created_at', [$startDate, $endDate])
            ->count();

        $previousPeriodCount = Contract::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->count();

        $percentageChange = $previousPeriodCount > 0
            ? (($currentPeriodCount - $previousPeriodCount) / $previousPeriodCount) * 100
            : ($currentPeriodCount > 0 ? 100 : 0);

        return [
            'count' => $currentPeriodCount,
            'previous_count' => $previousPeriodCount,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    protected function getIncomeStatistics($startDate, $endDate, $previousStartDate, $previousEndDate)
    {
        $currentPeriodIncome = Contract::whereBetween('created_at', [$startDate, $endDate])
            ->sum('contract_amount');

        $previousPeriodIncome = Contract::whereBetween('created_at', [$previousStartDate, $previousEndDate])
            ->sum('contract_amount');

        $percentageChange = $previousPeriodIncome > 0
            ? (($currentPeriodIncome - $previousPeriodIncome) / $previousPeriodIncome) * 100
            : ($currentPeriodIncome > 0 ? 100 : 0);

        return [
            'amount' => $currentPeriodIncome,
            'previous_amount' => $previousPeriodIncome,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    protected function getExpiredContractsStatistics()
    {
        $currentDate = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        // Contracts that have already expired (before today)
        $expiredCount = Contract::whereDate('expiry_date', '<', $currentDate)
            ->count();

        // Contracts that expired in the last 30 days (for trend analysis)
        $recentlyExpiredCount = Contract::whereDate('expiry_date', '<', $currentDate)
            ->whereDate('expiry_date', '>=', $currentDate->copy()->subDays(30))
            ->count();

        // Contracts that expired in the previous month (for comparison)
        $previousMonthExpired = Contract::whereDate('expiry_date', '<', $previousMonth)
            ->whereDate('expiry_date', '>=', $previousMonth->copy()->subDays(30))
            ->count();

        // Calculate percentage change (compared to previous month)
        $percentageChange = $previousMonthExpired > 0
            ? (($recentlyExpiredCount - $previousMonthExpired) / $previousMonthExpired) * 100
            : ($recentlyExpiredCount > 0 ? 100 : 0);

        return [
            'count' => $expiredCount,          // Total expired contracts (all time)
            'recent_count' => $recentlyExpiredCount, // Expired in last 30 days
            'percentage_change' => round($percentageChange, 2), // Month-over-month trend
        ];
    }

    protected function getRevenueStatistics($startDate = null, $endDate = null, $previousStartDate = null, $previousEndDate = null)
    {
        // Use provided dates or default to current vs previous month
        $currentStart = $startDate ?: Carbon::now()->startOfMonth();
        $currentEnd = $endDate ?: Carbon::now()->endOfMonth();
        $previousStart = $previousStartDate ?: Carbon::now()->subMonth()->startOfMonth();
        $previousEnd = $previousEndDate ?: Carbon::now()->subMonth()->endOfMonth();

        // Current period revenue
        $currentRevenue = Contract::whereBetween('created_at', [$currentStart, $currentEnd])
            ->sum('contract_amount');

        // Previous period revenue
        $previousRevenue = Contract::whereBetween('created_at', [$previousStart, $previousEnd])
            ->sum('contract_amount');

        // Calculate percentage change
        $percentageChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : ($currentRevenue > 0 ? 100 : 0);

        // Last 8 months revenue data (if no custom date range provided)
        $last8MonthsRevenue = [];
        if (!$startDate && !$endDate) {
            for ($i = 7; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $revenue = Contract::whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->sum('contract_amount');

                $last8MonthsRevenue[] = [
                    'name' => $month->format('M'),
                    'value' => $revenue,
                ];
            }
        } else {
            // For custom date ranges, show data by weeks or days depending on range length
            $rangeLength = $currentEnd->diffInDays($currentStart);

            if ($rangeLength <= 31) {
                // Show daily data for ranges up to 31 days
                $currentDate = $currentStart->copy();
                while ($currentDate <= $currentEnd) {
                    $revenue = Contract::whereDate('created_at', $currentDate)
                        ->sum('contract_amount');

                    $last8MonthsRevenue[] = [
                        'name' => $currentDate->format('M j'),
                        'value' => $revenue,
                    ];
                    $currentDate->addDay();
                }
            } else {
                // Show monthly data for longer ranges
                $currentDate = $currentStart->copy();
                while ($currentDate <= $currentEnd) {
                    $monthStart = $currentDate->copy()->startOfMonth();
                    $monthEnd = $currentDate->copy()->endOfMonth();

                    $revenue = Contract::whereBetween('created_at', [$monthStart, $monthEnd])
                        ->sum('contract_amount');

                    $last8MonthsRevenue[] = [
                        'name' => $currentDate->format('M Y'),
                        'value' => $revenue,
                    ];
                    $currentDate->addMonth();
                }
            }
        }

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'percentage_change' => round($percentageChange, 2),
            'revenue_trend' => $last8MonthsRevenue
        ];
    }

    public function contractRenewalMetrics($startDate = null, $endDate = null, $previousStartDate = null, $previousEndDate = null)
    {
        // Use provided dates or default to current vs previous month
        $currentStart = $startDate ?: Carbon::now()->startOfMonth();
        $currentEnd = $endDate ?: Carbon::now()->endOfMonth();
        $previousStart = $previousStartDate ?: Carbon::now()->subMonth()->startOfMonth();
        $previousEnd = $previousEndDate ?: Carbon::now()->subMonth()->endOfMonth();

        // Current period renewed contracts
        $currentPeriodRenewed = Contract::where('status', 'renewed')
            ->whereBetween('updated_at', [$currentStart, $currentEnd])
            ->count();

        // Previous period expired contracts (for context)
        $previousPeriodExpired = Contract::where('status', 'expired')
            ->whereBetween('expiry_date', [$previousStart, $previousEnd])
            ->count();

        // Previous period renewed contracts
        $previousPeriodRenewed = Contract::where('status', 'renewed')
            ->whereBetween('updated_at', [$previousStart, $previousEnd])
            ->count();

        // Calculate percentage change
        $renewalPercentageChange = 0;
        if ($previousPeriodRenewed > 0) {
            $renewalPercentageChange = (($currentPeriodRenewed - $previousPeriodRenewed) / $previousPeriodRenewed) * 100;
        } elseif ($currentPeriodRenewed > 0) {
            $renewalPercentageChange = 100;
        }

        return [
            'count' => $currentPeriodRenewed,
            'previous_count' => $previousPeriodRenewed,
            'percentage_change' => round($renewalPercentageChange, 2),
        ];
    }

    public function sourceTypeStatistics()
    {
        // Define all valid source types
        $validTypes = [
            'Tasheel',
            'Typing Center',
            'PRO',
            'Social Media',
            'Referral',
            'Inactive',
        ];

        // Total number of sources
        $totalSources = Source::count();

        // Handle case when there are no sources at all
        if ($totalSources === 0) {
            return [
                'source_type_stats' => collect($validTypes)->map(fn($type) => [
                    'id' => $type,
                    'label' => $type,
                    'value' => 0,
                ])->values(),
            ];
        }

        // Get counts grouped by type
        $counts = Source::selectRaw('source_type, COUNT(*) as count')
            ->groupBy('source_type')
            ->pluck('count', 'source_type')
            ->toArray();

        // Validate and filter out invalid types (not in the allowed list)
        $validCounts = array_filter($counts, fn($type) => in_array($type, $validTypes, true), ARRAY_FILTER_USE_KEY);

        // Build statistics for each valid type
        $stats = [];
        foreach ($validTypes as $type) {
            $typeCount = $validCounts[$type] ?? 0;
            $stats[] = [
                'id' => $type,
                'label' => $type,
                'value' => round(($typeCount / $totalSources) * 100, 2),
            ];
        }

        // Optionally log or report invalid source types if found
        $invalidTypes = array_diff(array_keys($counts), $validTypes);
        if (!empty($invalidTypes)) {
            Log::warning('Invalid source types found in database:', $invalidTypes);
        }

        return [
            'source_type_stats' => $stats,
        ];
    }

    public function occupancyRate()
    {
        // Get all offices with their desks
        $offices = Office::with('desks')->get();

        $totalDesks = 0;
        $bookedDesks = 0;

        foreach ($offices as $office) {
            foreach ($office->desks as $desk) {
                $totalDesks++;
                if ($desk->status === 'booked') {
                    $bookedDesks++;
                }
            }
        }

        // Calculate occupancy rate (handle division by zero)
        $occupancyRate = $totalDesks > 0
            ? ($bookedDesks / $totalDesks) * 100
            : 0;

        return [
            'value' => round($occupancyRate, 2) // Rounded to 2 decimal places
        ];
    }

    public function getExpiringContracts()
    {
        // Define what "soon" means (e.g., within 30 days from now)
        $soonDate = Carbon::now()->addDays(30);

        $expiringContracts = Contract::with([
            'customer' => function($query) {
                $query->select('id', 'name', 'profile_image', 'employee_id')
                    ->with(['employee' => function($q) {
                        $q->select('id', 'name'); // Assuming 'name' is the employee name field
                    }]);
            }
        ])
            ->where('expiry_date', '<=', $soonDate)
            ->where('expiry_date', '>=', Carbon::now())
            ->orderBy('expiry_date', 'asc')
            ->get()
            ->map(function ($contract) {
                return [
                    'key' => $contract->customer->id,
                    'id' => $contract->customer->id,
                    'client' => $contract->customer->name,
                    'avatar' => $contract->customer->profile_image ? url($contract->customer->profile_image) : url('customer_profile_images/default.png'),
                    'expirationDate' => $contract->expiry_date->format('F j, Y'),
                    'accManager' => $contract->customer->employee->name ?? null, // Add employee name
                ];
            });

        return [
            'data' => $expiringContracts,
        ];
    }

    public function getEmployeePerformanceReport()
    {
        $employees = Employee::withCount(['customers as total_customers'])->with(['customers' => function($query) {
            $query->withCount(['contracts as has_contracts' => function($q) {
                $q->where('status', 'active');
            }]);
        }])->whereHas('role', function($query) {
            $query->where('name', 'account-manager');
        })->get()
            ->map(function($employee) {

                $customersWithContracts = $employee->customers->where('has_contracts', '>', 0)->count();

                // Total count of all renewed contracts for this employee's customers
                $totalRenewedContracts = $employee->customers()
                    ->withCount(['contracts' => function($query) {
                        $query->where('status', 'renewed');
                    }])
                    ->get()
                    ->sum('contracts_count');

                return [
                    'key' => $employee->id,
                    'manager' => $employee->name,
                    'avatar' => $employee->profile_image ? url($employee->profile_image) : url('employee_profile_images/default.png'),
                    'customers' => $employee->total_customers,
                    'target' => $customersWithContracts,
                    'renewals' => $totalRenewedContracts,
                    'renewalTarget' => 50,
                ];
            });

        return [
            'data' => $employees,
        ];
    }
}

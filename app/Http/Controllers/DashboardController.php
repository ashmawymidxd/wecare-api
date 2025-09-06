<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Contract;
use App\Models\Source;
use App\Models\Office;
use App\Models\Employee;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        // 1. New Customers Statistics
        $newCustomers = $this->getCustomerStatistics($currentMonth, $previousMonth);

        // 2. New Orders/Contracts Statistics
        $newContracts = $this->getContractStatistics($currentMonth, $previousMonth);

        // 3. Income based on contracts
        $incomeStats = $this->getIncomeStatistics($currentMonth, $previousMonth);

        // 4. Expiring Contracts
        $expiringContracts = $this->getExpiredContractsStatistics();

        // 5. Renew Customers
        $renewCastomers = $this->contractRenewalMetrics();

        return response()->json([
            'new_customers' => $newCustomers,
            'new_contracts' => $newContracts,
            'income_stats' => $incomeStats,
            'expiring_contracts' => $expiringContracts,
            'renew_castomers'=>$renewCastomers,
        ]);
    }

    public function charts(){
        return response()->json([
             'revenue_stats' => $this->getRevenueStatistics(),
             'source_counts' =>$this->sourceTypeStatistics(),
             'occupancy_rate' =>$this->occupancyRate()
        ]);
    }

    public function statistice(){
        return response()->json([
            'get_expiring_contracts' => $this->getExpiringContracts(),
            'get_employee_performance_report' => $this->getEmployeePerformanceReport()
        ]);

    }

    protected function getCustomerStatistics($currentMonth, $previousMonth)
    {
        $currentMonthCount = Customer::whereYear('created_at', $currentMonth->year)
            ->whereMonth('created_at', $currentMonth->month)
            ->count();

        $previousMonthCount = Customer::whereYear('created_at', $previousMonth->year)
            ->whereMonth('created_at', $previousMonth->month)
            ->count();

        $percentageChange = $previousMonthCount > 0
            ? (($currentMonthCount - $previousMonthCount) / $previousMonthCount) * 100
            : ($currentMonthCount > 0 ? 100 : 0);

        return [
            'count' => $currentMonthCount,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    protected function getContractStatistics($currentMonth, $previousMonth)
    {
        $currentMonthCount = Contract::whereYear('created_at', $currentMonth->year)
            ->whereMonth('created_at', $currentMonth->month)
            ->count();

        $previousMonthCount = Contract::whereYear('created_at', $previousMonth->year)
            ->whereMonth('created_at', $previousMonth->month)
            ->count();

        $percentageChange = $previousMonthCount > 0
            ? (($currentMonthCount - $previousMonthCount) / $previousMonthCount) * 100
            : ($currentMonthCount > 0 ? 100 : 0);

        return [
            'count' => $currentMonthCount,
            'percentage_change' => round($percentageChange, 2),
        ];
    }

    protected function getIncomeStatistics($currentMonth, $previousMonth)
    {
        $currentMonthIncome = Contract::whereYear('created_at', $currentMonth->year)
            ->whereMonth('created_at', $currentMonth->month)
            ->sum('contract_amount');

        $previousMonthIncome = Contract::whereYear('created_at', $previousMonth->year)
            ->whereMonth('created_at', $previousMonth->month)
            ->sum('contract_amount');

        $percentageChange = $previousMonthIncome > 0
            ? (($currentMonthIncome - $previousMonthIncome) / $previousMonthIncome) * 100
            : ($currentMonthIncome > 0 ? 100 : 0);

        return [
            'amount' => $currentMonthIncome,
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
    protected function getRevenueStatistics()
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        // Current month revenue
        $currentRevenue = Contract::whereYear('created_at', $currentMonth->year)
            ->whereMonth('created_at', $currentMonth->month)
            ->sum('contract_amount');

        // Previous month revenue
        $previousRevenue = Contract::whereYear('created_at', $previousMonth->year)
            ->whereMonth('created_at', $previousMonth->month)
            ->sum('contract_amount');

        // Calculate percentage change
        $percentageChange = $previousRevenue > 0
            ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100
            : ($currentRevenue > 0 ? 100 : 0);

        // Last 8 months revenue data
        $last8MonthsRevenue = [];
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

        return [
            'current_revenue' => $currentRevenue,
            'percentage_change' => round($percentageChange, 2),
            'last_8_months' => $last8MonthsRevenue
        ];
    }

    public function contractRenewalMetrics()
    {
        // Current month metrics
        $currentMonthStart = Carbon::now()->startOfMonth();
        $currentMonthEnd = Carbon::now()->endOfMonth();

        // Last month metrics
        $lastMonthStart = Carbon::now()->subMonth()->startOfMonth();
        $lastMonthEnd = Carbon::now()->subMonth()->endOfMonth();

        // Current month renewed contracts
        $currentMonthRenewed = Contract::where('status', 'renewed')
            ->whereBetween('updated_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        // Last month expired contracts
        $lastMonthExpired = Contract::where('status', 'expired')
            ->whereBetween('expiry_date', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // Last month renewed contracts
        $lastMonthRenewed = Contract::where('status', 'renewed')
            ->whereBetween('updated_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // Calculate percentage change
        $renewalPercentageChange = 0;
        if ($lastMonthExpired > 0) {
            $renewalPercentageChange = (($currentMonthRenewed - $lastMonthRenewed) / $lastMonthRenewed) * 100;
        }

        return [
            'count' => $currentMonthRenewed,
            'percentage_change' => round($renewalPercentageChange, 2),
        ];
    }

    public function sourceTypeStatistics()
    {
        // First get all possible types
        $allTypes = [
            'Tasheel',
            'Typing Center',
            'PRO',
            'Social Media',
            'Referral',
            'Inactive'
        ];

        // source count

        $count = Source::count();

        // Get counts from database
        $counts = Source::selectRaw('source_type, count(*) as count')
            ->groupBy('source_type')
            ->pluck('count', 'source_type')
            ->toArray();

        // Combine to ensure all types are represented
        $stats = [];
        foreach ($allTypes as $type) {
            $stats[] = [
                'id' => $type,
                'label' => $type,
                'value' => ($counts[$type] / $count) * 100 ?? 0
            ];
        }

        return [
            'source_type_stats' => $stats
        ];
    }

    public function occupancyRate() {
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

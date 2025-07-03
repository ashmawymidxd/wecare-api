<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $expiringContracts = $this->getExpiringContractsStatistics();

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
             'occupancy_rat' =>$this->occupancyRat()
        ]);
    }

    public function statistice(){
        return response()->json([
            'get_expiring_contracts' => $this->getExpiringContracts(),
            'getEmployeePerformanceReport' => $this->getEmployeePerformanceReport()
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
            'previous_count' => $previousMonthCount,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down'
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
            'previous_count' => $previousMonthCount,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down'
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
            'previous_amount' => $previousMonthIncome,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down'
        ];
    }

   protected function getExpiringContractsStatistics()
    {
        $currentMonth = Carbon::now();
        $previousMonth = Carbon::now()->subMonth();

        // Contracts expiring in next 30 days
        $thresholdDate = $currentMonth->copy()->addDays(30);

        // Current expiring contracts (soon to expire)
        $expiringCount = Contract::whereDate('expiry_date', '<=', $thresholdDate)
            ->whereDate('expiry_date', '>=', $currentMonth)
            ->count();

        // Recently expired contracts (past 30 days)
        $recentlyExpiredCount = Contract::whereDate('expiry_date', '<', $currentMonth)
            ->whereDate('expiry_date', '>=', $currentMonth->copy()->subDays(30))
            ->count();

        // Previous month's expiring contracts (for comparison)
        $previousMonthExpiring = Contract::whereDate('expiry_date', '<=', $previousMonth->copy()->addDays(30))
            ->whereDate('expiry_date', '>=', $previousMonth)
            ->count();

        // Calculate percentage change
        $percentageChange = $previousMonthExpiring > 0
            ? (($expiringCount - $previousMonthExpiring) / $previousMonthExpiring) * 100
            : ($expiringCount > 0 ? 100 : 0);

        return [
            'expiring_soon_count' => $expiringCount,
            'recently_expired_count' => $recentlyExpiredCount,
            'total_at_risk' => $expiringCount + $recentlyExpiredCount,
            'previous_month_expiring' => $previousMonthExpiring,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down'
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
                'month' => $month->format('M Y'),
                'revenue' => $revenue,
                'month_short' => $month->format('M')
            ];
        }

        return [
            'current_revenue' => $currentRevenue,
            'previous_revenue' => $previousRevenue,
            'percentage_change' => round($percentageChange, 2),
            'trend' => $percentageChange >= 0 ? 'up' : 'down',
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

        // Current month expired contracts
        $currentMonthExpired = Contract::where('status', 'expired')
            ->whereBetween('expiry_date', [$currentMonthStart, $currentMonthEnd])
            ->count();

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

        return response()->json([
            'metrics' => [
                'current_month' => [
                    'expired_contracts' => $currentMonthExpired,
                    'renewed_contracts' => $currentMonthRenewed,
                    'renewal_rate' => $currentMonthExpired > 0
                        ? round(($currentMonthRenewed / $currentMonthExpired) * 100, 2)
                        : 0,
                ],
                'last_month' => [
                    'expired_contracts' => $lastMonthExpired,
                    'renewed_contracts' => $lastMonthRenewed,
                    'renewal_rate' => $lastMonthExpired > 0
                        ? round(($lastMonthRenewed / $lastMonthExpired) * 100, 2)
                        : 0,
                ],
                'percentage_change' => round($renewalPercentageChange, 2),
            ],
            'summary' => sprintf(
                '%d renewals of %d expired contracts (%s%.2f%% vs Last Month)',
                $currentMonthRenewed,
                $currentMonthExpired,
                $renewalPercentageChange >= 0 ? '+' : '',
                $renewalPercentageChange
            )
        ]);
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
                'source_type' => $type,
                'count' => ($counts[$type] / $count) * 100 ?? 0
            ];
        }

        return response()->json([
            'source_type_stats' => $stats
        ]);
    }

    public function occupancyRat(){
         // Total private offices
        $totalPrivateOffices = Office::where('office_type', 'private')->count();

        // Fully rented private offices (availability = 0)
        $fullyRentedPrivateOffices = Office::where('office_type', 'private')
                                        ->where('number_of_availability_desks', 0)
                                        ->count();

        // Calculate percentage (handle division by zero)
        $percentage = $totalPrivateOffices > 0
            ? ($fullyRentedPrivateOffices / $totalPrivateOffices) * 100
            : 0;

        return response()->json([
            'total_private_offices' => $totalPrivateOffices,
            'fully_rented_private_offices' => $fullyRentedPrivateOffices,
            'percentage_rented' => round($percentage, 2) // Rounded to 2 decimal places
        ]);
    }

    public function getExpiringContracts()
    {
        // Define what "soon" means (e.g., within 30 days from now)
        $soonDate = Carbon::now()->addDays(30);

        $expiringContracts = Contract::with('customer')
            ->where('expiry_date', '<=', $soonDate)
            ->where('expiry_date', '>=', Carbon::now())
            ->orderBy('expiry_date', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $expiringContracts,
            'message' => 'Contracts expiring soon retrieved successfully'
        ]);
    }

    public function getEmployeePerformanceReport()
    {
        $employees = Employee::withCount(['customers as total_customers'])
            ->with(['customers' => function($query) {
                $query->withCount(['contracts as has_contracts' => function($q) {
                    $q->where('status', 'active'); // or whatever status indicates valid contracts
                }]);
            }])
            ->get()
            ->map(function($employee) {
                $customersWithContracts = $employee->customers->where('has_contracts', '>', 0)->count();
                $totalRevenue = $employee->customers->sum(function($customer) {
                    return $customer->contracts->sum('contract_amount');
                });

                return [
                    'employee_id' => $employee->id,
                    'employee_name' => $employee->name,
                    'total_customers' => $employee->total_customers,
                    'customers_with_contracts' => $customersWithContracts,
                    'total_revenue' => $totalRevenue,
                    'commission_earned' => $totalRevenue * ($employee->commission / 100),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $employees,
            'message' => 'Employee performance report generated successfully'
        ]);
    }

}

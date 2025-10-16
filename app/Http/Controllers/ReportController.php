<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contract;
use App\Models\Inquiry;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use App\Models\Source;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    // Orders & Clients
    public function orderClients()
    {
        return response()->json([
            'total_clients' => $this->totalClients(),
            'new_clients' => $this->newClients(),
            'new_orders' => $this->newOrders(),
            'pending_orders' => $this->pendingOrders(),
            'new_sources' => $this->newSources(),
            'enquiries' => $this->enquiries(),
        ]);
    }

    // Finance
    public function finance()
    {
        return response()->json([
            'finance_profit' => $this->financeProfit(),
            'finance_sales' => $this->financeSales(),
            'payment_methods' =>$this->paymentMethods(),
            'expenses' => $this->expenses(),
            'collected_cheques' =>$this->collectedCheques()
        ]);
    }

    // Contract
    public function contract(){
        return response()->json([
            'employee_expird_contracts' => $this->employeesWithExpiredContracts(),
            'customer_expird_contracts' => $this->customersWithExpiringContracts(),
            'office_percentage' => $this->getOfficeReservationPercentage(),
            'office_private_percentage' => $this->getOfficeReservationPrivatePercentage(),
            'source' => $this->sourceClientStats(),
        ]);
    }

    public function totalClients()
    {
        $totalClients = Customer::count();
        $totalClientsLastMonth = Customer::where('created_at', '>=', now()->subMonth())->count();
        $totalClientsThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($totalClientsLastMonth > 0) {
            $percentageChange = (($totalClientsThisMonth - $totalClientsLastMonth) / $totalClientsLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%% vs Last Month', $trendIndicator, $percentageChange) : 'No change vs Last Month';

        return [
            'total' => $totalClients,
            'this_month' => $totalClientsThisMonth,
            'percentage_change' => $percentageText
        ];
    }

    public function newClients()
    {
        $newClientsThisMonth = Customer::where('created_at', '>=', now()->startOfMonth())->count();
        $newClientsLastMonth = Customer::whereBetween('created_at', [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()])->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($newClientsLastMonth > 0) {
            $percentageChange = (($newClientsThisMonth - $newClientsLastMonth) / $newClientsLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%%', $trendIndicator, $percentageChange) : '0%';

        return [
            'count' => $newClientsThisMonth,
            'percentage_change' => $percentageText,
            'comparison_text' => 'vs Last Month'
        ];
    }

    public function newOrders()
    {
        $newOrdersThisMonth = Contract::where('created_at', '>=', now()->startOfMonth())->count();
        $newOrdersLastMonth = Contract::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($newOrdersLastMonth > 0) {
            $percentageChange = (($newOrdersThisMonth - $newOrdersLastMonth) / $newOrdersLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%%', $trendIndicator, $percentageChange) : '0%';

        return [
            'count' => $newOrdersThisMonth,
            'percentage_change' => $percentageText,
            'comparison_text' => 'vs Last Month'
        ];
    }

    public function pendingOrders()
    {
        // Count pending orders this month (created this month with pending status)
        $pendingThisMonth = Contract::where('status', 'pending')
            ->where('created_at', '>=', now()->startOfMonth())
            ->count();

        // Count pending orders from last month (created last month with pending status)
        $pendingLastMonth = Contract::where('status', 'pending')
            ->whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])
            ->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($pendingLastMonth > 0) {
            $percentageChange = (($pendingThisMonth - $pendingLastMonth) / $pendingLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%%', $trendIndicator, $percentageChange) : '0%';

        return [
            'count' => $pendingThisMonth,
            'percentage_change' => $percentageText,
            'comparison_text' => 'vs Last Month'
        ];
    }

    public function newSources()
    {
        // Count new sources added this month
        $newSourcesThisMonth = Source::where('created_at', '>=', now()->startOfMonth())->count();

        // Count new sources added last month
        $newSourcesLastMonth = Source::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($newSourcesLastMonth > 0) {
            $percentageChange = (($newSourcesThisMonth - $newSourcesLastMonth) / $newSourcesLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%%', $trendIndicator, $percentageChange) : '0%';

        return [
            'count' => $newSourcesThisMonth,
            'percentage_change' => $percentageText,
            'comparison_text' => 'vs Last Month'
        ];
    }

    public function enquiries()
    {
        // Count new enquiries this month
        $enquiriesThisMonth = Inquiry::where('created_at', '>=', now()->startOfMonth())->count();

        // Count new enquiries last month
        $enquiriesLastMonth = Inquiry::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->count();

        // Calculate percentage change vs last month
        $percentageChange = 0;
        if ($enquiriesLastMonth > 0) {
            $percentageChange = (($enquiriesThisMonth - $enquiriesLastMonth) / $enquiriesLastMonth) * 100;
        }

        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        $percentageText = $percentageChange != 0 ? sprintf('%s%.2f%%', $trendIndicator, $percentageChange) : '0%';

        return [
            'count' => $enquiriesThisMonth,
            'percentage_change' => $percentageText,
            'comparison_text' => 'vs Last Month'
        ];
    }

    public function financeProfit()
    {
        // Calculate total Sales for this month
        $totalSalesThisMonth = Contract::where('created_at', '>=', now()->startOfMonth())
            ->sum('actual_amount');

        // Employee salary this month (assuming salary is a fixed monthly field)
        $totalEmployeeSalaryThisMonth = Employee::sum('salary'); // If salary is fixed per month

        // Profit for this month
        $profitThisMonth = $totalSalesThisMonth - $totalEmployeeSalaryThisMonth;

        // Calculate total Sales for last month
        $totalSalesLastMonth = Contract::whereBetween('created_at', [
            now()->subMonth()->startOfMonth(),
            now()->subMonth()->endOfMonth()
        ])->sum('actual_amount');

        // Employee salary last month (same approach as this month)
        $totalEmployeeSalaryLastMonth = Employee::where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->sum('salary'); // If salary is fixed per month

        // Profit for last month
        $profitLastMonth = $totalSalesLastMonth - $totalEmployeeSalaryLastMonth;

        // Calculate percentage changes for Sales, Salary, and Profit
        $percentageChangeSales = $this->calculatePercentageChange($totalSalesLastMonth, $totalSalesThisMonth);
        $percentageChangeSalary = $this->calculatePercentageChange($totalEmployeeSalaryLastMonth, $totalEmployeeSalaryThisMonth);
        $percentageChangeProfit = $this->calculatePercentageChange($profitLastMonth, $profitThisMonth);

        return [
            "profit" => [
                'value' => $profitThisMonth,
                'percentage_change' => $percentageChangeProfit,
                'comparison_text' => 'vs Last Month'
            ],
            "sales" => [
                'value' => $totalSalesThisMonth,
                'percentage_change' => $percentageChangeSales,
                'comparison_text' => 'vs Last Month'
            ],
            "expenses" => [
                'value' => $totalEmployeeSalaryThisMonth,
                'percentage_change' => $percentageChangeSalary,
                'comparison_text' => 'vs Last Month'
            ],
        ];
    }

    public function financeSales()
    {
        try {
            // Total sales this month
            $totalSalesThisMonth = Contract::where('created_at', '>=', now()->startOfMonth())
                ->sum('actual_amount') ?? 0;

            // Total sales last month
            $totalSalesLastMonth = Contract::whereBetween('created_at', [
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ])->sum('actual_amount') ?? 0;

            // Calculate percentage change safely
            $percentageChangeSales = $this->calculatePercentageChange($totalSalesLastMonth, $totalSalesThisMonth);

            // Contract breakdowns
            $totalRenewSalesThisMonth = Contract::where('created_at', '>=', now()->startOfMonth())
                ->where('status', 'renewed')
                ->sum('actual_amount') ?? 0;

            $totalNewSalesThisMonth = Contract::where('created_at', '>=', now()->startOfMonth())
                ->where('status', 'new')
                ->sum('actual_amount') ?? 0;

            $total = $totalNewSalesThisMonth + $totalRenewSalesThisMonth;

            // Handle case when no contracts exist
            if ($total <= 0) {
                return response()->json([
                    "message" => "No contract data found for this month",
                    "sales" => [
                        'this_month' => 0,
                    ],
                    "newcontracts" => [
                        'new_this_month' => 0,
                        'percentage_change' => 0,
                    ],
                    "renewalcontracts" => [
                        'renew_this_month' => 0,
                        'percentage_change' => 0,
                    ]
                ], 200);
            }

            return [
                "sales" => [
                    'this_month' => $totalSalesThisMonth,
                    'percentage_change' => $percentageChangeSales,
                ],
                "newcontracts" => [
                    'new_this_month' => $totalNewSalesThisMonth,
                    'percentage_change' => round(($totalNewSalesThisMonth / $total) * 100, 1),
                ],
                "renewalcontracts" => [
                    'renew_this_month' => $totalRenewSalesThisMonth,
                    'percentage_change' => round(($totalRenewSalesThisMonth / $total) * 100, 1),
                ]
            ];
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred while calculating finance sales.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentMethods()
    {
        try {
            $totalContracts = Contract::count();

            // Handle empty dataset safely
            if ($totalContracts === 0) {
                return response()->json([
                    "message" => "No contracts found to calculate payment method statistics",
                    "cash_contracts" => 0,
                    "cheque_contracts" => 0,
                    "bank_transfer_contracts" => 0,
                ], 200);
            }

            $cashContracts = Contract::where('payment_method', 'Cash')->count();
            $chequeContracts = Contract::where('payment_method', 'Cheque')->count();
            $bankTransferContracts = Contract::where('payment_method', 'Bank Transfer')->count();

            return [
                "cash_contracts" => round(($cashContracts / $totalContracts) * 100, 1),
                "cheque_contracts" => round(($chequeContracts / $totalContracts) * 100, 1),
                "bank_transfer_contracts" => round(($bankTransferContracts / $totalContracts) * 100, 1)
            ];
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An unexpected error occurred while calculating payment methods.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function expenses(){
        // expenses
        $totalEmployeeSalaryThisMonth = Employee::sum('salary');
        $totalEmployeeSalaryLastMonth = Employee::where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->sum('salary');
        $percentageChangeSalary = $this->calculatePercentageChange($totalEmployeeSalaryLastMonth, $totalEmployeeSalaryThisMonth);

        // electricity_fees
        $totalElectricityFeesThisMonth = Contract::sum('electricity_fees');
        $totalElectricityFeesLastMonth = Contract::where('created_at', '>=', now()->subMonth()->startOfMonth())
        ->sum('electricity_fees');
        $percentageChangeElectricityFees = $this->calculatePercentageChange($totalElectricityFeesLastMonth, $totalElectricityFeesThisMonth);

        // commission
        $totalCommission = Contract::sum('commission');
        $totalCommissionLastMonth = Contract::where('created_at', '>=', now()->subMonth()->startOfMonth())
        ->sum('commission');
        $percentageChangeCommission = $this->calculatePercentageChange($totalCommissionLastMonth, $totalCommission);



        return [
             "expenses" => [
                'this_month' => $totalEmployeeSalaryThisMonth,
                'percentage_change' => $percentageChangeSalary,
                'comparison_text' => 'vs Last Month'
             ],
             "electricity_fees" => [
                'this_month' => $totalElectricityFeesThisMonth,
                'percentage_change' => $percentageChangeElectricityFees,
                'comparison_text' => 'vs Last Month'
            ],
            "commission" =>[
                'this_month' => $totalCommission,
                'percentage_change' => $percentageChangeCommission,
                'comparison_text' => 'vs Last Month'
            ],
             "Salaries" =>[
                'this_month' => $totalEmployeeSalaryThisMonth,
                'percentage_change' => $percentageChangeSalary,
                'comparison_text' => 'vs Last Month'
             ]
        ];
    }

   public function collectedCheques()
    {
        return [
            "contracts" => Contract::whereNotNull('payment_date')
                ->where('payment_method', 'cheque')
                ->get(['contract_number', 'payment_method', 'payment_date'])
        ];
    }

    public function employeesWithExpiredContracts()
    {
        $today = Carbon::today();

        $employees = Employee::whereHas('role',function($query){
            $query->where('name','account-manager');
        })->whereHas('customers.contracts', function($query) use ($today) {
            $query->where('expiry_date', '<', $today)
                ; // Optional: exclude renewed contracts
        })
        ->with(['customers' => function($query) use ($today) {
            $query->withCount(['contracts' => function($q) use ($today) {
                $q->where('expiry_date', '<', $today)
                ;
            }]);
        }])
        ->get()
        ->map(function ($employee) {
            // Calculate total expired contracts count for all customers of this employee
            $totalExpiredContracts = $employee->customers->sum('contracts_count');

            return [
                'employee_id' => $employee->id,
                'employee_name' => $employee->name,
                'profile_image' =>$employee->profile_image? url($employee->profile_image) : url('employee_profile_images/default.png'),
                'total_expired_contracts' => $totalExpiredContracts,
                'opened_cases' => $totalExpiredContracts-2,
            ];
        });

        return [
            'success' => true,
            'data' => $employees,
            'message' => 'Employees with expired customer contracts retrieved successfully'
        ];
    }

    public function customersWithExpiringContracts()
    {
        $today = Carbon::today();
        $thresholdDate = $today->copy()->addDays(30);

        $customers = Customer::whereHas('contracts', function($query) use ($today, $thresholdDate) {
            $query->whereBetween('expiry_date', [$today, $thresholdDate]);
        })
        ->with(['contracts' => function($query) use ($today, $thresholdDate) {
            $query->whereBetween('expiry_date', [$today, $thresholdDate])
                  ->orderBy('expiry_date', 'asc');
        }])
        ->get();

        return [
            'success' => true,
            'data' => $customers,
            'message' => 'Customers with expiring contracts retrieved successfully'
        ];
    }

    private function calculatePercentageChange($lastMonthValue, $thisMonthValue)
    {
        if ($lastMonthValue == 0) return '0%'; // Avoid division by zero

        $percentageChange = (($thisMonthValue - $lastMonthValue) / $lastMonthValue) * 100;
        $trendIndicator = $percentageChange >= 0 ? '+' : '';
        return sprintf('%s%.2f%%', $trendIndicator, $percentageChange);
    }

    public function getOfficeReservationPercentage()
    {
        $totalOffices = Office::count();

        $reservedOffices = Office::withCount(['desks', 'reservedDesks'])
            ->get()
            ->map(function ($office) {
                return [
                    'office_id' => $office->id,
                    'office_name' => $office->name, // assuming you have a name column
                    'total_desks' => $office->desks_count,
                    'reserved_desks' => $office->reserved_desks_count,
                    'available_desks' => $office->desks_count - $office->reserved_desks_count
                ];
            })->count();

        $percentage = $totalOffices > 0
            ? round(($reservedOffices / $totalOffices) * 100, 2)
            : 0;

        return [
            'success' => true,
            'data' => [
                'total_offices' => $totalOffices,
                'reserved_offices' => $reservedOffices,
                'reservation_percentage' => $percentage,
                'availability_percentage' => round(100 - $percentage,2)
            ],
            'message' => 'Office reservation statistics retrieved successfully'
        ];
    }

    public function getOfficeReservationPrivatePercentage()
    {
        $totalOffices = Office::where('office_type','Private')->count();

        $reservedOffices = Office::where('office_type','Private')->withCount(['desks', 'reservedDesks'])
        ->get()
        ->map(function ($office) {
            return [
                'office_id' => $office->id,
                'office_name' => $office->name, // assuming you have a name column
                'total_desks' => $office->desks_count,
                'reserved_desks' => $office->reserved_desks_count,
                'available_desks' => $office->desks_count - $office->reserved_desks_count
            ];
        })->count();
        $percentage = $totalOffices > 0
            ? round(($reservedOffices / $totalOffices) * 100, 2)
            : 0;

        return [
            'success' => true,
            'data' => [
                'total_offices' => $totalOffices,
                'reserved_offices' => $reservedOffices,
                'reservation_percentage' => $percentage,
                'availability_percentage' => round(100 - $percentage,2)
            ],
            'message' => 'Office reservation statistics retrieved successfully'
        ];
    }

    public function sourceClientStats()
    {

        $results = DB::table('customers')
            ->select('customers.source_type', DB::raw('COUNT(customers.id) as customer_count'))
            ->leftJoin('contracts', 'contracts.customer_id', '=', 'customers.id')
            ->selectRaw('COUNT(contracts.id) as contract_count')
            ->groupBy('customers.source_type')
            ->get();

        return [$results];
    }
}

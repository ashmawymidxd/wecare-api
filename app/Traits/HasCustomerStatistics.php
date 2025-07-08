<?php

namespace App\Traits;

trait HasCustomerStatistics
{
    public function customerStat()
    {
        // Total customer count
        $currentCount = $this->customers()->count();

        // Get count from previous month
        $previousMonthCount = $this->customers()
            ->where('created_at', '>=', now()->subMonth()->startOfMonth())
            ->where('created_at', '<', now()->subMonth()->endOfMonth())
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($previousMonthCount > 0) {
            $percentageChange = (($currentCount - $previousMonthCount) / $previousMonthCount) * 100;
        } elseif ($currentCount > 0) {
            $percentageChange = 100; // infinite% growth (from 0 to current)
        }

        // Format the percentage with +/-
        $formattedPercentage = $percentageChange >= 0
            ? '+'.number_format($percentageChange, 0).'%'
            : number_format($percentageChange, 0).'%';

        return [
            'customers_count' => $currentCount,
            'percentage_change' => $formattedPercentage,
        ];
    }

    public function contractStats()
    {
        // Get current month contracts count
        $currentMonthContracts = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->where('created_at', '<=', now()->endOfMonth());
            })
            ->count();

        // Get last month contracts count
        $lastMonthContracts = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('created_at', '>=', now()->subMonth()->startOfMonth())
                    ->where('created_at', '<=', now()->subMonth()->endOfMonth());
            })
            ->count();

        // Calculate percentage change
        $percentageChange = 0;
        if ($lastMonthContracts > 0) {
            $percentageChange = (($currentMonthContracts - $lastMonthContracts) / $lastMonthContracts) * 100;
        } elseif ($currentMonthContracts > 0) {
            $percentageChange = 100; // If no contracts last month but some this month
        }

        // Format the percentage with +/-
        $formattedPercentage = $percentageChange >= 0
            ? "+".round($percentageChange)."%"
            : "-".round(abs($percentageChange))."%";

        return [
            'count' => $currentMonthContracts,
            'percentage_change' => $formattedPercentage,
        ];
    }

    public function expiredContractStats()
    {
        // Get total expired contracts count
        $expiredContracts = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('expiry_date', '<', now());
            })
            ->count();

        // Get expired contracts from last month
        $lastMonthExpired = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('expiry_date', '>=', now()->subMonth()->startOfMonth())
                    ->where('expiry_date', '<=', now()->subMonth()->endOfMonth());
            })
            ->count();

        // Get expired contracts from current month
        $currentMonthExpired = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('expiry_date', '>=', now()->startOfMonth())
                    ->where('expiry_date', '<=', now()->endOfMonth());
            })
            ->count();

        // Calculate percentage change (current vs last month)
        $percentageChange = 0;
        if ($lastMonthExpired > 0) {
            $percentageChange = (($currentMonthExpired - $lastMonthExpired) / $lastMonthExpired) * 100;
        } elseif ($currentMonthExpired > 0) {
            $percentageChange = 100;
        }

        $formattedPercentage = $percentageChange >= 0
            ? "+".round($percentageChange)."%"
            : "-".round(abs($percentageChange))."%";

        return [
            'total_expired' => $expiredContracts,
            'percentage_change' => $formattedPercentage,
        ];
    }

    public function averageContractAmountStats()
    {
        // Get current month contracts
        $currentMonthContracts = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('created_at', '>=', now()->startOfMonth())
                    ->where('created_at', '<=', now()->endOfMonth())
                    ->where('contract_amount', '>', 0); // Exclude zero amounts
            });

        // Get last month contracts
        $lastMonthContracts = $this->customers()
            ->with('contracts')
            ->get()
            ->flatMap(function ($customer) {
                return $customer->contracts
                    ->where('created_at', '>=', now()->subMonth()->startOfMonth())
                    ->where('created_at', '<=', now()->subMonth()->endOfMonth())
                    ->where('contract_amount', '>', 0);
            });

        // Calculate current month average
        $currentMonthAverage = $currentMonthContracts->avg('contract_amount') ?? 0;

        // Calculate last month average
        $lastMonthAverage = $lastMonthContracts->avg('contract_amount') ?? 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($lastMonthAverage > 0) {
            $percentageChange = (($currentMonthAverage - $lastMonthAverage) / $lastMonthAverage) * 100;
        } elseif ($currentMonthAverage > 0) {
            $percentageChange = 100; // If no contracts last month but some this month
        }

        // Format the values
        $formattedAverage = number_format($currentMonthAverage, 2);
        $formattedPercentage = $percentageChange >= 0
            ? "+".round($percentageChange)."%"
            : "-".round(abs($percentageChange))."%";

        return [
            'average_amount' => $currentMonthAverage,
            'percentage_change' => $formattedPercentage,
        ];
    }

    public function conversionRateStats()
    {
        // Current month data
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        // Get total customers added this month
        $currentMonthCustomers = $this->customers()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->count();

        // Get converted customers (with contracts) this month
        $currentMonthConverted = $this->customers()
            ->whereBetween('created_at', [$currentMonthStart, $currentMonthEnd])
            ->has('contracts')
            ->count();

        // Last month data
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Get total customers added last month
        $lastMonthCustomers = $this->customers()
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->count();

        // Get converted customers last month
        $lastMonthConverted = $this->customers()
            ->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])
            ->has('contracts')
            ->count();

        // Calculate conversion rates
        $currentMonthRate = $currentMonthCustomers > 0
            ? ($currentMonthConverted / $currentMonthCustomers) * 100
            : 0;

        $lastMonthRate = $lastMonthCustomers > 0
            ? ($lastMonthConverted / $lastMonthCustomers) * 100
            : 0;

        // Calculate percentage change
        $percentageChange = 0;
        if ($lastMonthRate > 0) {
            $percentageChange = (($currentMonthRate - $lastMonthRate) / $lastMonthRate) * 100;
        } elseif ($currentMonthRate > 0) {
            $percentageChange = 100;
        }

        // Format the values
        $formattedRate = round($currentMonthRate) . '%';
        $formattedPercentage = $percentageChange >= 0
            ? "+".round($percentageChange)."%"
            : "-".round(abs($percentageChange))."%";

        return [
            'formatted_rate' => $formattedRate,
            'percentage_change' => $formattedPercentage,
        ];
    }

    public function attendance()
    {
        $attendance = $this->getAttendance();

        return response()->json([
            'message' => 'Attendance records retrieved successfully',
            'attendance' => $attendance
        ]);
    }


}

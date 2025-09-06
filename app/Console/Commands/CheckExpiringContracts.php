<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Contract;
use App\Models\ActivityLog;
use App\Models\Employee;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Support\Facades\Log as Logger;
use Carbon\Carbon;

class CheckExpiringContracts extends Command
{
    protected $signature = 'contracts:check-expiring';
    protected $description = 'Check for contracts that are about to expire and log warnings';

    public function handle()
    {
        // Define what "expiring soon" means (e.g., within 30 days)
        $warningPeriod = 30; // days
        $thresholdDate = Carbon::now()->addDays($warningPeriod);

        $expiringContracts = Contract::where('expiry_date', '<=', $thresholdDate)
            ->where('expiry_date', '>=', Carbon::now())
            ->where('status', 'Active') // Uncommented this as it's important
            ->with(['customer', 'branch'])
            ->get();

        if ($expiringContracts->isEmpty()) {
            Logger::info('No contracts are expiring within the next '.$warningPeriod.' days.');
            return;
        }

        // Get  receive notifications
        $notifiableUsers = Employee::get();

        foreach ($expiringContracts as $contract) {
            $daysUntilExpiry = Carbon::now()->diffInDays($contract->expiry_date);

            // 1. Log to file
            Logger::info('Command run at: ' . now()->timezone('Africa/Cairo')->format('Y-m-d H:i:s'));
            Logger::warning("Contract #{$contract->contract_number} is expiring soon", [
                'contract_id' => $contract->id,
                'customer' => $contract->customer->name,
                'branch' => $contract->branch->name,
                'expiry_date' => $contract->expiry_date->format('Y-m-d'),
                'days_until_expiry' => $daysUntilExpiry,
                'status' => 'warning'
            ]);

            // 2. Store in database logs
            try {
                ActivityLog::create([
                    "auth_id" => 1, // System user or default admin
                    "level" => "warning",
                    "message" => "Contract {$contract->contract_number} with {$contract->customer->name} is set to expire in {$daysUntilExpiry} days.",
                    "type" => "Contracts"
                ]);
            } catch (\Exception $e) {
                Logger::error('Failed to create database log entry', [
                    'error' => $e->getMessage(),
                    'contract_id' => $contract->id
                ]);
            }

            // 3. Send database notifications to API users
            if ($notifiableUsers->isNotEmpty()) {
                Notification::send($notifiableUsers, new ContractExpiringNotification(
                    $contract->contract_number,
                    $contract->customer->name,
                    $daysUntilExpiry,
                    $contract->expiry_date->format('Y-m-d')
                ));
            }
        }

        $this->info('Found '.$expiringContracts->count().' contracts expiring within '.$warningPeriod.' days. Notifications sent.');
    }
}

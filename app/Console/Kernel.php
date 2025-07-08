<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\CheckExpiringContracts::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('contracts:check-expiring')
            ->dailyAt('12:00')
            //   ->everyMinute()
            ->timezone('Africa/Cairo'); // Egypt timezone
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

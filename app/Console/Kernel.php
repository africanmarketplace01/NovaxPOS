<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\AutoPurchase::class,
        Commands\DsoAlert::class,
        Commands\ResetDB::class,
        Commands\MigrateLandlord::class,
        Commands\ProcessTenantExpiry::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('purchase:auto')->everyFifteenMinutes()->withoutOverlapping();
        $schedule->command('dsoalert:find')->dailyAt('00:00');
        $schedule->command('reset:db')->twiceDaily(1, 13)->withoutOverlapping();

        // SaaS: process expired tenants daily at midnight
        if (config('database.connections.saleprosaas_landlord')) {
            $schedule->command('tenants:process-expiry')->dailyAt('00:05')->withoutOverlapping();
            // Marketplace sellers: hide listings while a fee invoice is overdue.
            $schedule->command('marketplace:enforce-fee-holds')->dailyAt('00:10')->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}

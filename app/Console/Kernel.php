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
        Commands\ImportProducts::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        
        $schedule->command('ticket:last-message-time')->everyMinute();
        $schedule->command('check:product')->hourlyAt(5)->withoutOverlapping();
        $schedule->command('utmids:update')->hourlyAt(10)->withoutOverlapping();
        $schedule->command('channel:import-all-products')->cron('0 */2 * * *')->withoutOverlapping();
        $schedule->command('close-order:tasks')->daily()->withoutOverlapping();
        $schedule->command('find_utm_no:clients')->cron('0 1 * * *')->withoutOverlapping();
        $schedule->command('daily:limit')->dailyAt('23:59')->withoutOverlapping();
        $schedule->command('call:subscription')->hourlyAt(15)->withoutOverlapping();
        $schedule->command('call:records')->hourlyAt(20)->withoutOverlapping();
        $schedule->command('analytics:import')->hourlyAt(30)->withoutOverlapping();
        $schedule->command('analytics:get-metrika-logs')->cron('*/15 1-23 * * *')->withoutOverlapping();
        $schedule->command('analytics:add-metrika-api')->cron('*/16 1-23 * * *')->withoutOverlapping();
        $schedule->command('cdek:orders-update')->hourlyAt(40)->withoutOverlapping();
        //$schedule->command('http-test:fast')->cron('* * * * *')->withoutOverlapping();
        //$schedule->command('http-test:fast-incident')->cron('* * * * *')->withoutOverlapping();
        $schedule->command('daily:report')->dailyAt(config('report.time'))->withoutOverlapping();
        $schedule->command('telegram:orderAlerts')->everyMinute()->withoutOverlapping();
        $schedule->command('telegram:overdueTasks')->everyMinute()->withoutOverlapping();

        $schedule->command('daily:autoclose-tickets')->dailyAt('03:00')->withoutOverlapping();
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

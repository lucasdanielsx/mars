<?php

namespace App\Console;

use App\Consumers\AuthorizeTransactionConsumer;
use App\Consumers\NotifyClientConsumer;
use App\Consumers\TransactionNotPaidConsumer;
use App\Consumers\TransactionPaidConsumer;
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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(new AuthorizeTransactionConsumer)->everyMinute();
        $schedule->call(new NotifyClientConsumer)->everyMinute();
        $schedule->call(new TransactionNotPaidConsumer)->everyMinute();
        $schedule->call(new TransactionPaidConsumer)->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}

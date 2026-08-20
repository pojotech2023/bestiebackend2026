<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    
    protected $commands = [
        \App\Console\Commands\SendScheduledNotifications::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // ஒவ்வொரு minute-லும் pending notifications check பண்ணும்
        $schedule->command('notifications:send-scheduled')
                 ->everyMinute()
                 ->withoutOverlapping()
                 ->runInBackground();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
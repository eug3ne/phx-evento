<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Scheduled jobs (e.g. trial-end checks, statistics rollup verification)
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
    }
}

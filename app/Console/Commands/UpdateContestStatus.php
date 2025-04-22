<?php

namespace App\Console\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;
use App\Models\Contest;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UpdateContestStatus extends Command
{
    protected $signature = 'contest:update-status';
    protected $description = 'Update the status of contests based on start and end times';

    public function handle()
    {
        $now = Carbon::now();
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/cron_update_status.log'),
        ])->info('Cron Statatus:', ['Running contest:update-status at' => $now->toDateTimeString()]);
         
        // Update contests from "open" to "closed"
        $closed = Contest::where('status', 'open')
            ->where('end_date', '<', $now->toDateString())
            ->where('end_time', '<', $now->toTimeString())
            ->update(['status' => 'closed']);

        // Update contests from "closed" to "completed"
        $completed = Contest::where('status', 'closed')
            ->where('end_date', '<', $now->toDateString())
            ->where('end_time', '<', $now->toTimeString())
            ->update(['status' => 'completed']);
        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/cron_update_status.log'),
        ])->info('Cron Statatus:', ["Updated $closed contests to 'closed' and $completed contests to 'completed'."]);
        $this->info('Contest statuses updated successfully!');
    }

    /**
     * Schedule the command to run automatically.
     */
    public function schedule(Schedule $schedule): void
    {
        // Run every minute
        $schedule->everyMinute();
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; // Import DB Facade


class ResetBusinessPoints extends Command
{
    protected $signature = 'business:reset-points';
    protected $description = 'Reset monthly points for all active businesses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Reset points for active businesses with no pending payments
        DB::table('business_profiles')
        ->where('status','active')
        ->update(['monthly_points_available'=> '5000']);

        $this->info('Monthly points reset to 5000 for all active businesses');
    }
}

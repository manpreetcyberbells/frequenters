<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Badge;
use App\Models\CustomerBadge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EvaluateCustomerBadges extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:evaluate-customer-badges';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $customers = User::where('role', 'customer')->where('status','active')->get();
        $now = Carbon::now();
        foreach ($customers as $customer) {
            // echo"<pre>"; print_r($customer->id); echo"</br>"; 
            $interactions = DB::table('customer_business_interactions')
                ->where('customer_id', $customer->id)
                ->where('last_interaction_date', '>=', now()->subMonths(12)) // 12-month window
                ->get();

            // echo"<pre>"; print_r($interactions); die;
            
            $checkIns = $interactions->where('interaction_type', 'check_in')->count();
            $points = $interactions->sum('points_allocated');
            $businessIds = $interactions->pluck('business_id')->unique()->toArray();
            $reviews = DB::table('reviews')->where('customer_id', $customer->id)->whereIn('business_id', $businessIds)->count();
            $cities = DB::table('business_addresses')->whereIn('business_id', $businessIds)->distinct('city')->count('city');

            $contestSubmissions = DB::table('contest_participants')->where('user_id', $customer->id)->sum('participation_count');;
            $participantIds = DB::table('contest_participants')->where('user_id', $customer->id)->pluck('id');
            $contestWins = DB::table('contest_answers')->whereIn('participant_id', $participantIds)->where('is_winner', 1)->count();
            $votes = DB::table('contest_voting_history')->whereIn('participant_id', $participantIds)->count();


            // Later photo uploads, referred friends
            $eligibleBadges = [];

            // ENTRY-LEVEL BADGES
            if ($points >= 100 && $checkIns >= 3) {
                $eligibleBadges[] = 'frequenters_pioneers';
            }

            if ($points >= 500 && $checkIns >= 10 && $reviews >= 1 && $votes >= 1) {
                $eligibleBadges[] = 'frequenters_contributors';
            }

            if ($points >= 1000 && $cities >= 5) {
                $eligibleBadges[] = 'frequenters_explorers';
            }

            if ($points >= 5000 && $checkIns >= 100 && $reviews >= 5 && ( $contestSubmissions >= 1 || $votes >= 20) ) {
                $eligibleBadges[] = 'frequenters_rising_stars';
            }

            // MID-TIER BADGES
            if ($points >= 10000 || $checkIns >= 250) {
                $eligibleBadges[] = 'frequenters_insiders';
            }

            if ($points >= 15000 || ($checkIns >= 350 && $contestSubmissions >= 5) ) {
                $eligibleBadges[] = 'frequenters_trailblazers';
            }

            if ($contestSubmissions >= 1) {
                $eligibleBadges[] = 'frequenters_a_listers';
            }

            if ($points >= 25000) {
                $eligibleBadges[] = 'frequenters_master';
            }

            if ($points >= 25000 && $contestWins >= 5 && $reviews >= 20 && $votes >= 20) {
                $eligibleBadges[] = 'frequenters_vip';
            }

            // TOP-TIER BADGES
            if ($points >= 50000) {
                $eligibleBadges[] = 'frequenters_icons';
            }

            if (
                // Assume you track photo uploads, referrals, and wins
                $points >= 25000 && $contestWins >= 10
            ) {
                $eligibleBadges[] = 'frequenters_all_stars';
            }

            if ( $checkIns >= 500 && $cities >= 2) {
                $eligibleBadges[] = 'frequenters_legends';
            }

            if ($checkIns >= 1000 && $cities >= 10) {
                $eligibleBadges[] = 'frequenters_royalty';
            }

            if ($checkIns >= 10000 && $contestSubmissions >= 100 && $reviews >= 50) {
                $eligibleBadges[] = 'frequenters_elite';
            }

            // echo"<pre>"; print_r($eligibleBadges); //die;

            // Award badges
            foreach ($eligibleBadges as $slug) {
                $badge = Badge::where('slug', $slug)->first();

                // echo"<pre>"; print_r($badge); die;

                if (!$badge) continue;

                $alreadyExists = CustomerBadge::where('customer_id', $customer->id)->where('badge_id', $badge->id)->first();
                $expiresAt = $now->copy()->addMonths($badge->validity_months);

                if ($alreadyExists) {
                    // Update
                    $alreadyExists->update([
                        'earned_at' => $now,
                        'expires_at' => $expiresAt,
                    ]);
                } else {
                    // Create new
                    CustomerBadge::create([
                        'customer_id' => $customer->id,
                        'badge_id' => $badge->id,
                        'earned_at' => $now,
                        'expires_at' => $expiresAt,
                    ]);
                }
            }
        }
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

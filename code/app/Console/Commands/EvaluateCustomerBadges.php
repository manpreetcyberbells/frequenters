<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Badge;
use App\Models\CustomerBadge;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
    protected $description = 'Update customer badges';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Cron Calling.');
        $customers = User::where('role', 'customer')->where('status','active')->get();
        $now = Carbon::now();
        foreach ($customers as $customer) {
            // echo"<pre>"; print_r($customer->id); echo"</br>"; 
            $interactions = DB::table('customer_business_interactions')->where('customer_id', $customer->id)->where('last_interaction_date', '>=', now()->subMonths(12))->get();
            $availablePoints = DB::table('customer_profiles')->where('customer_id', $customer->id)->pluck('points_balance')->first();
            $checkIns = $interactions->where('interaction_type', 'check_in')->count();
            $lifetimePoints = $interactions->sum('points_allocated');
            $businessIds = $interactions->pluck('business_id')->unique()->toArray();
            $reviews = DB::table('reviews')->where('customer_id', $customer->id)->whereIn('business_id', $businessIds)->count();
            $cities = DB::table('business_addresses')->whereIn('business_id', $businessIds)->distinct('city')->count('city');
            $contestSubmissions = DB::table('contest_participants')->where('user_id', $customer->id)->sum('participation_count');;
            $participantIds = DB::table('contest_participants')->where('user_id', $customer->id)->pluck('id');
            $contestWins = DB::table('contest_answers')->whereIn('participant_id', $participantIds)->where('is_winner', 1)->count();
            $votes = DB::table('contest_voting_history')->whereIn('participant_id', $participantIds)->count();
            $referredFriendsCount = DB::table('invites')->where('customer_id', $customer->id)->where('status','accepted')->where('role','customer')->get()->count();
            // $photoUploads = 0;
            // echo"<pre>"; print_r($availablePoints); die;
            
            // ENTRY-LEVEL BADGES
            $eligibleBadges = ['frequenters_enthusiasts'];
            if ($availablePoints >= 100 && $checkIns >= 3) {
                $eligibleBadges[] = 'frequenters_pioneers';
            }

            if ($availablePoints >= 500 && $checkIns >= 10 && $reviews >= 1 && $votes >= 1) {
                $eligibleBadges[] = 'frequenters_contributors';
            }

            if ($availablePoints >= 1000 && $cities >= 5) {
                $eligibleBadges[] = 'frequenters_explorers';
            }

            if ($availablePoints >= 5000 && $checkIns >= 100 && $reviews >= 5 && ( $contestSubmissions >= 1 || $votes >= 20) ) {
                $eligibleBadges[] = 'frequenters_rising_stars';
            }

            // MID-TIER BADGES
            if ($availablePoints >= 10000 || $checkIns >= 250) {
                $eligibleBadges[] = 'frequenters_insiders';
            }

            if ($availablePoints >= 15000 || ($checkIns >= 350 && $contestSubmissions >= 5) ) {
                $eligibleBadges[] = 'frequenters_trailblazers';
            }

            if ($availablePoints >= 15000 || ($checkIns >= 350 && $contestSubmissions >= 5) && $contestWins >= 1) {
                $eligibleBadges[] = 'frequenters_a_listers';
            }

            if ($lifetimePoints >= 25000 && $availablePoints >= 15000 || ($checkIns >= 350 && $contestSubmissions >= 5) && $contestWins >= 1) {
                $eligibleBadges[] = 'frequenters_master';
            }

            if ($availablePoints >= 25000 && $contestWins >= 5 && $reviews >= 20 && $votes >= 20) {
                $eligibleBadges[] = 'frequenters_vip';
            }

            // TOP-TIER BADGES
            if ($availablePoints >= 25000 && $contestWins >= 10 && $reviews >= 20 && $votes >= 20 && $referredFriendsCount > 0) {
                $eligibleBadges[] = 'frequenters_all_stars';
            }

            if ($availablePoints >= 50000 && $contestWins >= 10 && $reviews >= 20 && $votes >= 20 && $referredFriendsCount > 0) {
                $eligibleBadges[] = 'frequenters_icons';
            }

            if ($availablePoints >= 50000 && $contestWins >= 10 && $reviews >= 20 && $votes >= 20 && $referredFriendsCount > 0 && $checkIns >= 500 && $cities > 1) {
                $eligibleBadges[] = 'frequenters_legends';
            }

            if ($availablePoints >= 50000 && $contestWins >= 10 && $reviews >= 20 && $votes >= 20 && $referredFriendsCount > 0 && $checkIns >= 1000 && $cities > 10) {
                $eligibleBadges[] = 'frequenters_royalty';
            }

            if ($availablePoints >= 50000 && $contestWins >= 10 && $contestSubmissions >= 100 && $reviews >= 50 && $votes >= 20 && $referredFriendsCount > 0 && $checkIns >= 10000 && $cities > 10) {
                $eligibleBadges[] = 'frequenters_elite';
            }

            // echo"<pre>"; print_r($eligibleBadges); //die;

            // Award badges
            foreach ($eligibleBadges as $slug) {
                Log::info('Update Customer ID::', array($customer->id));
                $badge = Badge::where('slug', $slug)->first();

                // echo"<pre>"; print_r($badge); die;

                if (!$badge) continue;

                $alreadyExists = CustomerBadge::where('customer_id', $customer->id)->where('badge_id', $badge->id)->first();
                $expiresAt = $now->copy()->addMonths($badge->validity_months);

                if (!$alreadyExists) {
                //     // Update
                //     $alreadyExists->update([
                //         'earned_at' => $now,
                //         'expires_at' => $expiresAt,
                //     ]);
                // } else {
                    // Create new
                    CustomerBadge::create([
                        'customer_id' => $customer->id,
                        'badge_id' => $badge->id,
                        'earned_at' => $now,
                        'expires_at' => $expiresAt,
                    ]);
                    Log::info('Create Customer for Badges::', array($customer->id));
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

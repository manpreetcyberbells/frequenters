<?php

namespace App\Console\Commands;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Contest;
use App\Models\ContestVotingHistory;
use App\Models\ContestParticipant;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Services\FCMService;

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
        ])->info('Cron Status:', ['Running contest:update-status at' => $now->toDateTimeString()]);

        // Move contests from 'open' to 'close'
        $closed = Contest::where('status', 'open')
            ->whereRaw("STR_TO_DATE(CONCAT(end_date, ' ', end_time), '%Y-%m-%d %H:%i:%s') < ?", [$now])
            ->update(['status' => 'close']);

        // Move contests from 'close' to 'completed'
        $completedContests = Contest::where('status', 'close')
            ->whereRaw("STR_TO_DATE(CONCAT(close_end_date, ' ', close_end_time), '%Y-%m-%d %H:%i:%s') < ?", [$now])
            ->get(['id']);

        $completedIds = $completedContests->pluck('id')->toArray();
        $completed = Contest::whereIn('id', $completedIds)->update(['status' => 'completed']);

        if (count($completedIds) > 0) {
            foreach ($completedIds as $contest_id) {
                $contest = Contest::findOrFail($contest_id);

                // Determine reward values
                $winnerReward = $contest->reward_type === "points" ? $contest->winner_points : ($contest->reward_type === "discount" ? $contest->winner_discount : $contest->winner_free_item);
                $participantReward = $contest->reward_type === "points" ? $contest->participant_points : ($contest->reward_type === "discount" ? $contest->participant_discount : $contest->participant_free_item);
                $voterReward = $contest->reward_type === "points" ? $contest->voter_points : ($contest->reward_type === "discount" ? $contest->voter_discount : $contest->voter_free_item);

                // Get vote counts per contest_answer_id
                $voteResults = DB::table('contest_voting_history')
                    ->select('contest_answer_id', DB::raw('COUNT(*) as total_votes'))
                    ->where('contest_id', $contest_id)
                    ->groupBy('contest_answer_id')
                    ->orderByDesc('total_votes')
                    ->get();

                if ($voteResults->isEmpty()) {
                    Log::warning("No votes recorded for contest ID: $contest_id");
                    continue;
                }

                // Map answer_id to participant_id
                $answerIds = $voteResults->pluck('contest_answer_id')->toArray();
                $answers = DB::table('contest_answers')->whereIn('id', $answerIds)->pluck('participant_id', 'id')->toArray();

                // Count total votes per participant
                $participantVotes = [];
                foreach ($voteResults as $vote) {
                    $participantId = $answers[$vote->contest_answer_id] ?? null;
                    if ($participantId) {
                        $participantVotes[$participantId] = ($participantVotes[$participantId] ?? 0) + $vote->total_votes;
                    }
                }

                // Get all participants based on contest_answers
                $participantIds = DB::table('contest_answers')->where('contest_id', $contest_id)->pluck('participant_id')->unique()->toArray();
                $participants = ContestParticipant::whereIn('id', $participantIds)->with('user:id,name,profile_image')->get()->keyBy('id');
                // Add vote count to each participant
                foreach ($participants as $pid => $p) {
                    $participants[$pid]->total_votes = $participantVotes[$pid] ?? 0;
                }

                // Sort participants by vote count
                $sortedParticipants = $participants->sortByDesc('total_votes');

                $winner = $sortedParticipants->first();
                $winnerId = $winner->id ?? null;
                $winnerUser = $winner->user ?? null;

                $winnerData = [
                    'participant_id' => $winner->id ?? null,
                    'user_id' => $winner->user_id ?? null,
                    'name' => $winnerUser->name ?? 'Unknown',
                    'thumbnail' => $winnerUser->profile_image ?? null,
                    'points' => $winnerReward
                ];

                // Other participants (without winner)
                $pointCrew = [];
                foreach ($sortedParticipants as $pid => $p) {
                    if ($p->id == $winnerId) continue;

                    $pUser = $p->user ?? null;
                    $pointCrew[] = [
                        'participant_id' => $p->id ?? null,
                        'user_id' => $p->user_id ?? null,
                        'name' => $pUser->name ?? 'Unknown',
                        'thumbnail' => $pUser->profile_image ?? null,
                        'points' => $participantReward
                    ];
                }

                // Get all voter user_ids
                $voterParticipantIds = DB::table('contest_voting_history')->where('contest_id', $contest_id)->pluck('participant_id')->unique()->toArray();
                $voterUserIds = ContestParticipant::whereIn('id', $voterParticipantIds)->pluck('user_id')->unique()->toArray();
                $voters = User::whereIn('id', $voterUserIds)->get(['id', 'name', 'profile_image']);

                // All voters
                $voterList = [];
                foreach ($voters as $voter) {
                    $voterList[] = [
                        'user_id' => $voter->id,
                        'name' => $voter->name,
                        'thumbnail' => $voter->profile_image,
                        'points' => $voterReward
                    ];
                }

                
                // echo "<pre>"; print_r($winnerData);
                // echo "<pre>"; print_r($pointCrew);
                // echo "<pre>"; print_r($voterList);

                $title = 'You Did It! Our Champion Shines Bright!';
                $body = 'A huge shoutout to YOU for being part of this amazing journey';
                if(count($voterList) > 0){
                    $fcmService = new FCMService();
                    // $image = asset('storage/notifications/notification.png');
                    $image = url('storage/notifications/notification.png');
                    foreach ($voters as $voter) {
                        $customerId = $voter['user_id'];
                        if($contest->reward_type === "points"){
                            DB::table('customer_profiles')->where('id', $customerId)->increment('points_balance', $contest->voter_points);
                        }
                        // Save Voter notifications
                        customerNotification($customerId, $body, "push", null, true);

                        // Push Notification on Device
                        $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->value('fcm_token');
                        // Sending Notification on Device
                        if (!empty($fcmToken)) {
                            try {
                                $fcmService->sendToCustomerApp($fcmToken, $title, $body, $image);
                            } catch (\Throwable $e) {
                                \Log::error("Customer FCM failed for {$customerId}: " . $e->getMessage());
                            }
                        }
                    }
                }

                if(count($pointCrew) > 0){
                    $fcmService = new FCMService();
                    $image = url('storage/notifications/notification.png');
                    foreach ($pointCrew as $participant) {
                        $customerId = $participant['user_id'];
                        if($contest->reward_type === "points"){
                            DB::table('customer_profiles')->where('id', $customerId)->increment('points_balance', $contest->participant_points);
                        }
                        // Save Participants notifications
                        customerNotification($customerId, $body, "push", null, true);

                        // Push Notification on Device
                        $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->value('fcm_token');
                        // Sending Notification on Device
                        if (!empty($fcmToken)) {
                            try {
                                $fcmService->sendToCustomerApp($fcmToken, $title, $body, $image);
                            } catch (\Throwable $e) {
                                \Log::error("Customer FCM failed for {$customerId}: " . $e->getMessage());
                            }
                        }
                    }
                }

                if(!empty($winnerData['user_id'])){
                    $winner_title = 'Woohoo! You’ve Snatched the Spotlight!';
                    $winner_body = 'You’re the Star of the Show! Let’s Connect to Claim Your Prize!';
                    $customerId = $winnerData['user_id'];
                    if($contest->reward_type === "points"){
                        DB::table('customer_profiles')->where('id', $customerId)->increment('points_balance', $contest->winner_points);
                    }
                    // Winner notifications
                    customerNotification($customerId, $winner_body, "push", null, true);

                    // Push Notification on Device
                    $fcmService = new FCMService();
                    $image = asset('storage/notifications/notification.png');
                    // Push Notification on Device
                    $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->value('fcm_token');
                    // Sending Notification on Device
                    if (!empty($fcmToken)) {
                        try {
                            $fcmService->sendToCustomerApp($fcmToken, $winner_title, $winner_body, $image);
                        } catch (\Throwable $e) {
                            \Log::error("Customer FCM failed for {$customerId}: " . $e->getMessage());
                        }
                    }
                }
            }
        }

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/cron_update_status.log'),
        ])->info('Cron Status:', ["Updated $closed contests to 'closed' and $completed contests to 'completed'."]);

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

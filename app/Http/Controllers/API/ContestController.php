<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Contest;
use App\Models\ContestParticipant;
use App\Models\ContestAnswer;
use App\Models\ContestQuestion;
use App\Models\ContestQuestionOption;
use App\Models\ContestVotingHistory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;

class ContestController extends Controller
{
    /**
     *  API Function Contest Listing
    */
    public function contestListing(Request $request)
    {
        try{
            $business = auth()->user();
            $perPage = $request->get('per_page', 10);
            $contests = Contest::where('business_id', $business->id)->paginate($perPage);
            	
            $transformed = $contests->map(function ($contest) {
                return [
                    'id' => $contest->id,
                    'business_id' => $contest->business_id,
                    'name' => $contest->name,
                    'banner_image' => $contest->banner_image,
                    'createdAt' => $contest->created_at->diffForHumans(),
                ];
            });


            return response()->json([
                'message' => 'Contests retrieved successfully',
                'data' => [
                    'current_page' => $contests->currentPage(),
                    'last_page' => $contests->lastPage(),
                    'per_page' => $contests->perPage(),
                    'total' => $contests->total(),
                    'contests' => $transformed,
                ]
            ], 200);

            
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong. Please try again.',
            ], 500);
        }
    }

    /**
     *  API Function Create Contest
    */
    public function create(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        try {
            $businessId = auth()->id();
            $user = auth()->user();

            if ($user->role !== 'business') {
                return response()->json(['message' => 'Please login with a valid Business account'], 403);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'banner_image' => 'nullable|file', // URL or file path
                'status' => 'required|in:open,close,completed',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'start_time' => 'required',
                'end_time' => 'required',
                'close_description' => 'nullable|string',
                'close_start_date' => 'nullable|date',
                'close_end_date' => 'nullable|date|after_or_equal:close_start_date',
                'close_start_time' => 'nullable',
                'close_end_time' => 'nullable',
                'max_participants' => 'nullable|integer|min:1',
                'reward_type' => 'required|in:points,discount,free_item',

                // Question Section Validation
                'question_type' => 'required|in:multiple_choice,text_answer',
                'question_field' => 'required|string|max:255',
                'options' => 'nullable|array', // Required only if MCQ
                'options.*' => 'required_if:question_type,multiple_choice|string|max:255',
            ]);

            // Handle event banner upload
            $bannerPath = null;
            if ($request->hasFile('banner_image')) {
                $bannerPath = $request->file('banner_image')->store('contest/banners', 'public');
            }

            // Add authenticated business ID
            $validatedData['business_id'] = $businessId;
            $validatedData['banner_image'] = asset('storage/' . $bannerPath);

            // echo"<pre>"; print_r($validatedData); die;
            switch ($request->reward_type) {
                case 'points':
                    $request->validate([
                        'winner_points' => 'required|integer',
                        'participant_points' => 'required|integer',
                        'voter_points' => 'required|integer',
                    ]);
                    $validatedData['winner_points'] = $request->winner_points;
                    $validatedData['participant_points'] = $request->participant_points;
                    $validatedData['voter_points'] = $request->voter_points;
                    break;
        
                case 'discount':
                    $request->validate([
                        'winner_discount' => 'required|string',
                        'participant_discount' => 'required|string',
                        'voter_discount' => 'required|string',
                    ]);
                    $validatedData['winner_discount'] = $request->winner_discount;
                    $validatedData['participant_discount'] = $request->participant_discount;
                    $validatedData['voter_discount'] = $request->voter_discount;
                    break;
        
                case 'free_item':
                    $request->validate([
                        'winner_free_item' => 'required|string',
                        'participant_free_item' => 'required|string',
                        'voter_free_item' => 'required|string',
                    ]);
                    $validatedData['winner_free_item'] = $request->winner_free_item;
                    $validatedData['participant_free_item'] = $request->participant_free_item;
                    $validatedData['voter_free_item'] = $request->voter_free_item;
                    break;
            }
            // Create Contest
            $contest = Contest::create($validatedData);

            // Create Question
            $question = ContestQuestion::create([
                'contest_id' => $contest->id,
                'question_type' => $validatedData['question_type'],
                'question' => $validatedData['question_field'],
            ]);

            // Store Options if it's an MCQ
            if ($validatedData['question_type'] === 'multiple_choice' && isset($validatedData['options'])) {
                foreach ($validatedData['options'] as $option) {
                    ContestQuestionOption::create([
                        'contest_question_id' => $question->id,
                        'option_text' => $option,
                    ]);
                }
            }

            $businessAddress = DB::table('business_addresses')->where('business_id',$businessId)->first();
            if(!empty($businessAddress->latitude) && !empty($businessAddress->longitude)){
                $radius = 10; // kilometers
                $latitude = @$businessAddress->latitude;
                $longitude = @$businessAddress->longitude;

                $nearbyService = new NearbyCustomerService();
                $nearbyCustomerIds = $nearbyService->getNearbyCustomerIds($latitude, $longitude, $radius);

                $fcmService = new FCMService();
                $title = "A Contest Just Landed Nearby!";
                $body = "Jump in, earn points. It’s local, it’s live, and it's all yours";
                $image = asset('storage/notifications/notification.png');

                foreach ($nearbyCustomerIds as $customerId) {
                    $fcmToken = DB::table('customer_profiles')->where('customer_id',$customerId)->pluck('fcm_token')->first();
                    // Sending Notification on Device
                    $fcmService->sendNotificationToDevice($fcmToken, $title, $body, $image);

                    // Store into DB
                    customerNotification($customerId, $body, "push", null, true);
                }
            }

            return response()->json([
                'message' => 'Contest created successfully',
                'contest' => $contest,
                'question' => $question,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // public function fetchContests(Request $request)
    // {
    //     $query = Contest::query();
    //     // Optional filter: status=open|close|completed
    //     if ($request->has('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     // Include relations
    //     $query->with(['questions.options','questions.answers.participant.user']);
    //     $contests = $query->orderBy('start_date', 'desc')->paginate(10);

    //     return response()->json([
    //         'message' => 'Contests fetched successfully',
    //         'data' => $contests
    //     ]);
    // }

    public function fetchContests(Request $request)
    {
        $query = Contest::query();

        // Optional filter: status=open|close|completed
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Optional filter: search by contest name
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Eager load necessary relations
        $query->with(['questions.options', 'questions.answers.participant.user']);

        $contests = $query->orderBy('start_date', 'desc')->paginate(10);

        // Transform to clean up the response
        $contests->getCollection()->transform(function ($contest) {
            $contest->questions->each(function ($question) {
                $question->answers->transform(function ($answer) {
                    $user = optional(optional($answer->participant)->user);

                    return [
                        'id' => $answer->id,
                        'answer' => $answer->answer,
                        'contest_question_option_id' => $answer->contest_question_option_id,
                        'is_winner' => $answer->is_winner,
                        'created_at' => $answer->created_at,
                        'user' => $user ? [
                            'id' => $user->id,
                            'name' => $user->name,
                            'profile_image' => $user->profile_image,
                        ] : null,
                    ];
                });
            });

            return $contest;
        });

        return response()->json([
            'message' => 'Contests fetched successfully',
            'data' => $contests
        ]);
    }


    public function updateStatus($id, Request $request)
    {
        $contest = Contest::findOrFail($id);
        $contest->update(['status' => $request->status]);

        return response()->json(['message' => 'Contest status updated']);
    }

    /**
     *  API Function of Submit Customer Answer. 
    */
    public function submitAnswer(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid Customer account'], 403);
        }

        $validated = $request->validate([
            'contest_id' => 'required|exists:contests,id',
            'contest_question_id' => 'required|exists:contest_questions,id',
            'answer' => 'nullable|string|max:500', // For written answers
            'contest_question_option_id' => 'nullable|exists:contest_question_options,id' // For MCQ selection
        ]);

        try {
            // max_participants
            $question = ContestQuestion::findOrFail($validated['contest_question_id']);
            $contest = Contest::where('id', $question->contest_id)->where('status', 'open')->first();
            if (!$contest) {
                return response()->json(['message' => 'Contest is not open for submission'], 403);
            }

            // Check if max participants limit is reached
            $currentParticipantCount = ContestAnswer::where('contest_id', $contest->id)->distinct('participant_id')->count('participant_id');
            if ($contest->max_participants !== null && $currentParticipantCount >= $contest->max_participants) {
                return response()->json(['message' => 'Maximum number of participants has been reached'], 403);
            }

            // Ensure either 'answer' OR 'contest_question_option_id' is provided (but not both)
            if ($question->question_type === 'multiple_choice') {
                if (empty($validated['contest_question_option_id'])) {
                    return response()->json(['message' => 'For MCQs, contest_question_option_id is required'], 400);
                }
                $validated['answer'] = null; // Ensure no text answer is saved
            } else {
                if (empty($validated['answer'])) {
                    return response()->json(['message' => 'For written questions, an answer is required'], 400);
                }
                $validated['contest_question_option_id'] = null; // Ensure no MCQ option is saved
            }

            // Check if customer is already a participant
            $participant = ContestParticipant::where([
                'contest_id' => $validated['contest_id'],
                'user_id' => $user->id // user_id as customer_id
            ])->first();

            // If not, register the participant
            if (!$participant) {
                $participant = ContestParticipant::create([
                    'contest_id' => $validated['contest_id'],
                    'user_id' => $user->id,
                    // 'status' => 'Regular'
                ]);
            }

            // Add participant_id to validated data
            $validated['participant_id'] = $participant->id;

            // Ensure the user has not already submitted an answer for this question
            $existingAnswer = ContestAnswer::where([
                'contest_id' => $validated['contest_id'],
                'contest_question_id' => $validated['contest_question_id'],
                'participant_id' => $participant->id
            ])->first();

            if ($existingAnswer) {
                return response()->json(['message' => 'You have already submitted an answer for this question'], 409);
            }

            // Save the answer
            $answer = ContestAnswer::create($validated);

            
            // Business Notification into DB
            createNotification($contest->business_id, "User by the name $user->name submitted answer on your contest",  "push", null, true);
            
            // Push Notification on Device
            $fcmService = new FCMService();
            $fcmToken = DB::table('business_profiles')->where('business_id', $contest->business_id)->pluck('fcm_token')->first();
            $title = "Your Question Got a Hit!";
            $body = "Someone just answered your contest question. Dive in and read what they have said!";
            $image = asset('storage/notifications/notification.png');
            $fcmService->sendNotificationToDevice($fcmToken, $title, $body, $image);

            return response()->json(['message' => 'Answer submitted successfully', 'answer' => $answer], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  API Function Delete answer 
    */
    public function deleteAnswerByBusiness($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            if ($user->role !== 'business') {
                return response()->json(['message' => 'Access denied. You are not permitted to perform this action.'], 403);
            }

            $contestAnswer = DB::table('contest_answers')->where('id', $id)->first();
            if (!$contestAnswer) {
                return response()->json(['message' => 'Answer not found.'], 404);
            }

            $contest = DB::table('contests')->where('id', $contestAnswer->contest_id)->first();
            if (!$contest) {
                return response()->json(['message' => 'Contest not found or has been deleted.'], 404);
            }

            if ($contest->business_id !== $user->id) {
                return response()->json(['message' => 'You are not authorized to delete this answer.'], 403);
            }

            // Delete related voting history and answer
            DB::table('contest_voting_history')->where('contest_id', $contest->id)->where('contest_answer_id', $id)->delete();

            DB::table('contest_answers')->where('id', $id)->delete();

            return response()->json(['message' => 'Answer deleted successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     *  API Function of Submit Customer Vote. 
    */
    public function vote(Request $request)
    {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid customer account'], 403);
        }

        $validated = $request->validate([
            'contest_id' => 'required|exists:contests,id',
            'contest_question_id' => 'required|exists:contest_questions,id',
            'contest_answer_id' => 'required|exists:contest_answers,id',
        ]);

        try {
            $contest = Contest::findOrFail($validated['contest_id']);
            if (!in_array($contest->status, ['close'])) {
                return response()->json(['message' => 'Voting is only allowed for Closed contests'], 403);
            }

            // Fetch question type
            $question = ContestQuestion::findOrFail($validated['contest_question_id']);

            // Ensure the user is a participant
            $participant = ContestParticipant::where([
                'contest_id' => $validated['contest_id'],
                'user_id' => $user->id
            ])->first();

            if (!$participant) {
                $participant = ContestParticipant::create([
                    'contest_id' => $validated['contest_id'],
                    'user_id' => $user->id,
                ]);
            }

            $validated['participant_id'] = $participant->id;

            // Ensure the answer exists and belongs to the same question
            $answerExists = ContestAnswer::where([
                'id' => $validated['contest_answer_id'],
                'contest_question_id' => $validated['contest_question_id'],
            ])->exists();

            if (!$answerExists) {
                return response()->json(['message' => 'Invalid contest_answer_id'], 400);
            }

            // Ensure voting rules
            if (!in_array($question->question_type, ['multiple_choice', 'text_answer'])) {
                return response()->json(['message' => 'Voting is only allowed for multiple-choice or text-based answers'], 400);
            }

            // Ensure the same participant can't vote multiple times for the same answer
            $existingVote = ContestVotingHistory::where([
                'contest_id' => $validated['contest_id'],
                'contest_question_id' => $validated['contest_question_id'],
                'contest_answer_id' => $validated['contest_answer_id'],
                'participant_id' => $participant->id
            ])->first();

            if ($existingVote) {
                return response()->json(['message' => 'You have already voted for this answer'], 409);
            }

            // Record the vote
            $vote = ContestVotingHistory::create($validated);

            return response()->json(['message' => 'Vote recorded successfully', 'vote' => $vote], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  API Function for getting Winners. 
    */
    public function getWinners($contest_id)
    {
        try {
            // $contest = Contest::findOrFail($contest_id);
            // echo"<pre>"; print_r($contest); die;
            // $winnerReward = 0;
            // $participantReward = 0;
            // $voterReward = 0;
            // if($contest->reward_type == "points"){
            //     $winnerReward = $contest->winner_points;
            //     $participantReward = $contest->participant_points;
            //     $voterReward = $contest->voter_points;
            // } else if($contest->reward_type == "discount"){
            //     $winnerReward = $contest->winner_discount;
            //     $participantReward = $contest->participant_discount;
            //     $voterReward = $contest->voter_discount;
            // } else {
            //     $winnerReward = $contest->winner_free_item;
            //     $participantReward = $contest->participant_free_item;
            //     $voterReward = $contest->voter_free_item;
            // }
            // $voteResults = ContestVotingHistory::select(
            //         'participant_id',
            //         DB::raw('COUNT(id) as total_votes')
            //     )
            //     ->where('contest_id', $contest_id)
            //     ->groupBy('participant_id')
            //     ->orderByDesc('total_votes')
            //     ->get();

            // if ($voteResults->isEmpty()) {
            //     return response()->json(['message' => 'No votes recorded for this contest'], 404);
            // }

            // // Get all participant IDs from vote results
            // $participantIds = $voteResults->pluck('participant_id')->toArray();
            // $participants = ContestParticipant::whereIn('id', $participantIds)->with('user:id,name,profile_image')->get()->keyBy('id');
            // // Winner
            // $topVote = $voteResults->first();
            // $winner = $participants[$topVote->participant_id] ?? null;
            // $winnerUser = $winner->user ?? null;
            // $winnerData = [
            //     'participant_id' => $winner->id ?? null,
            //     'name' => $winnerUser->name ?? 'Unknown',
            //     'thumbnail' => $winnerUser->profile_image ?? null,
            //     'points' => $topVote->total_votes
            // ];

            // // Voting Champs (Top 6 without winner)
            // $votingChamps = [];
            // $top6Votes = $voteResults->slice(1, 6); // First is winner, next 9 are champs
            // $top6ParticipantIds = [$topVote->participant_id];
            // foreach ($top6Votes as $champ) {
            //     $participant = $participants[$champ->participant_id] ?? null;
            //     $participantUser = $participant->user ?? null;

            //     $votingChamps[] = [
            //         'participant_id' => $participant->id ?? null,
            //         'name' => $participantUser->name ?? 'Unknown',
            //         'thumbnail' => $participantUser->profile_image ?? null,
            //         'points' => $participantReward //$champ->total_votes
            //     ];

            //     $top6ParticipantIds[] = $champ->participant_id; // Add to exclusion list
            // }

            // // Point Crew (All participants not in top 6)
            // $pointCrew = [];
            // foreach ($voteResults as $result) {
            //     if (!in_array($result->participant_id, $top6ParticipantIds)) {
            //         $participant = $participants[$result->participant_id] ?? null;
            //         $participantUser = $participant->user ?? null;

            //         $pointCrew[] = [
            //             'participant_id' => $participant->id ?? null,
            //             'name' => $participantUser->name ?? 'Unknown',
            //             'thumbnail' => $participantUser->profile_image ?? null,
            //             'points' => $voterReward //$result->total_votes
            //         ];
            //     }
            // }


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
                return response()->json(['message' => 'No votes recorded for this contest'], 404);
            }

            // Map answer_id to participant_id
            $answerIds = $voteResults->pluck('contest_answer_id')->toArray();
            $answers = DB::table('contest_answers')->whereIn('id', $answerIds)->pluck('participant_id', 'id')->toArray();

            // Step 5: Count total votes per participant
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

            return response()->json([
                'winner' => $winnerData,
                'voting_champs' => $voterList,
                'point_crew' => $pointCrew
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }

}

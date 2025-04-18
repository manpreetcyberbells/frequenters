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

class ContestController extends Controller
{
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

            return response()->json([
                'message' => 'Contest created successfully',
                'contest' => $contest,
                'question' => $question,
            ], 201);

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
    //     // Optional filter: status=open|closed|completed
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

        // Optional filter: status=open|closed|completed
        if ($request->has('status')) {
            $query->where('status', $request->status);
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
            $question = ContestQuestion::findOrFail($validated['contest_question_id']);
            $contest = Contest::where('id', $question->contest_id)->where('status', 'open')->first();
            if (!$contest) {
                return response()->json(['message' => 'Contest is not open for submission'], 403);
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

            return response()->json(['message' => 'Answer submitted successfully', 'answer' => $answer], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
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

            return response()->json(['message' => 'Vote recorded successfully', 'vote' => $vote], 201);
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
            $contest = Contest::findOrFail($contest_id);

            $voteResults = ContestVotingHistory::select(
                    'participant_id',
                    DB::raw('COUNT(id) as total_votes')
                )
                ->where('contest_id', $contest_id)
                ->groupBy('participant_id')
                ->orderByDesc('total_votes')
                ->get();

            if ($voteResults->isEmpty()) {
                return response()->json(['message' => 'No votes recorded for this contest'], 404);
            }

            // Get all participant IDs from vote results
            $participantIds = $voteResults->pluck('participant_id')->toArray();
            $participants = ContestParticipant::whereIn('id', $participantIds)->with('user:id,name,profile_image')->get()->keyBy('id');
            // Winner
            $topVote = $voteResults->first();
            $winner = $participants[$topVote->participant_id] ?? null;
            $winnerUser = $winner->user ?? null;
            $winnerData = [
                'participant_id' => $winner->id ?? null,
                'name' => $winnerUser->name ?? 'Unknown',
                'thumbnail' => $winnerUser->profile_image ?? null,
                'points' => $topVote->total_votes
            ];

            // Voting Champs (Top 6 excluding winner)
            $votingChamps = [];
            $top6Votes = $voteResults->slice(1, 6); // First is winner, next 9 are champs
            $top6ParticipantIds = [$topVote->participant_id];
            foreach ($top6Votes as $champ) {
                $participant = $participants[$champ->participant_id] ?? null;
                $participantUser = $participant->user ?? null;

                $votingChamps[] = [
                    'participant_id' => $participant->id ?? null,
                    'name' => $participantUser->name ?? 'Unknown',
                    'thumbnail' => $participantUser->profile_image ?? null,
                    'points' => $champ->total_votes
                ];

                $top6ParticipantIds[] = $champ->participant_id; // Add to exclusion list
            }

            // Point Crew (All participants not in top 6)
            $pointCrew = [];
            foreach ($voteResults as $result) {
                if (!in_array($result->participant_id, $top6ParticipantIds)) {
                    $participant = $participants[$result->participant_id] ?? null;
                    $participantUser = $participant->user ?? null;

                    $pointCrew[] = [
                        'participant_id' => $participant->id ?? null,
                        'name' => $participantUser->name ?? 'Unknown',
                        'thumbnail' => $participantUser->profile_image ?? null,
                        'points' => $result->total_votes
                    ];
                }
            }

            return response()->json([
                'winner' => $winnerData,
                'voting_champs' => $votingChamps,
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

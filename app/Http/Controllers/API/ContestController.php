<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestAnswer;
use App\Models\ContestParticipant;
use App\Models\ContestQuestion;
use App\Models\ContestQuestionOption;
use App\Models\ContestVotingHistory;
use App\Models\User;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContestController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    // Business: Contest Listing
    public function contestListing(Request $request)
    {
        try {
            $perPage  = $request->integer('per_page', 10);
            $contests = Contest::where('business_id', Auth::id())->paginate($perPage);

            $transformed = $contests->map(fn($c) => [
                'id'           => $c->id,
                'business_id'  => $c->business_id,
                'name'         => $c->name,
                'banner_image' => $c->banner_image,
                'createdAt'    => $c->created_at->diffForHumans(),
            ]);

            return response()->json([
                'message' => 'Contests retrieved successfully',
                'data'    => [
                    'current_page' => $contests->currentPage(),
                    'last_page'    => $contests->lastPage(),
                    'per_page'     => $contests->perPage(),
                    'total'        => $contests->total(),
                    'contests'     => $transformed,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }

    // Business: Create Contest
    public function create(Request $request)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Please login with a valid Business account'], 403);
        }

        try {
            $businessId    = Auth::id();
            $validatedData = $request->validate([
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'banner_image'      => 'nullable|file',
                'status'            => 'required|in:open,close,completed',
                'start_date'        => 'required|date',
                'end_date'          => 'required|date|after_or_equal:start_date',
                'start_time'        => 'required',
                'end_time'          => 'required',
                'close_description' => 'nullable|string',
                'close_start_date'  => 'nullable|date',
                'close_end_date'    => 'nullable|date|after_or_equal:close_start_date',
                'close_start_time'  => 'nullable',
                'close_end_time'    => 'nullable',
                'max_participants'  => 'nullable|integer|min:1',
                'reward_type'       => 'required|in:points,discount,free_item',
                'question_type'     => 'required|in:multiple_choice,text_answer',
                'question_field'    => 'required|string|max:255',
                'options'           => 'nullable|array',
                'options.*'         => 'required_if:question_type,multiple_choice|string|max:255',
            ]);

            $bannerPath = $request->hasFile('banner_image') ? asset('storage/' . $request->file('banner_image')->store('contest/banners', 'public')) : null;
            $validatedData['business_id']  = $businessId;
            $validatedData['banner_image'] = $bannerPath;
            $rewardFields = $this->validateAndExtractRewardFields($request, $validatedData['reward_type']);
            $validatedData = array_merge($validatedData, $rewardFields);

            $contest  = Contest::create($validatedData);
            $question = ContestQuestion::create([
                'contest_id'    => $contest->id,
                'question_type' => $validatedData['question_type'],
                'question'      => $validatedData['question_field'],
            ]);

            if ($validatedData['question_type'] === 'multiple_choice' && !empty($validatedData['options'])) {
                foreach ($validatedData['options'] as $option) {
                    ContestQuestionOption::create([
                        'contest_question_id' => $question->id,
                        'option_text'         => $option,
                    ]);
                }
            }

            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if ($address?->latitude && $address?->longitude) {
                $this->sendNearbyNotifications($address->latitude, $address->longitude);
            }

            return response()->json([
                'message'  => 'Contest created successfully',
                'contest'  => $contest,
                'question' => $question,
            ], 201);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    // Fetch Contests
    public function fetchContests(Request $request)
    {
        $query = Contest::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $contests = $query
            ->with(['questions.options', 'questions.answers.participant.user'])
            ->orderByDesc('start_date')
            ->paginate(10);

        $contests->getCollection()->transform(function ($contest) {
            $contest->questions->each(function ($question) {
                $question->answers->transform(function ($answer) {
                    $user = optional(optional($answer->participant)->user);
                    return [
                        'id'                         => $answer->id,
                        'answer'                     => $answer->answer,
                        'contest_question_option_id' => $answer->contest_question_option_id,
                        'is_winner'                  => $answer->is_winner,
                        'created_at'                 => $answer->created_at,
                        'user'                       => $user?->id ? ['id' => $user->id, 'name' => $user->name, 'profile_image' => $user->profile_image] : null,
                    ];
                });
            });
            return $contest;
        });

        return response()->json(['message' => 'Contests fetched successfully', 'data' => $contests]);
    }

    public function updateStatus(int $id, Request $request)
    {
        $request->validate(['status' => 'required|in:open,close,completed']);
        Contest::findOrFail($id)->update(['status' => $request->status]);
        return response()->json(['message' => 'Contest status updated']);
    }

    // Customer: Submit Answer
    public function submitAnswer(Request $request)
    {
        if (Auth::user()->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid Customer account'], 403);
        }

        $validated = $request->validate([
            'contest_id'                 => 'required|exists:contests,id',
            'contest_question_id'        => 'required|exists:contest_questions,id',
            'answer'                     => 'nullable|string|max:500',
            'contest_question_option_id' => 'nullable|exists:contest_question_options,id',
        ]);

        try {
            $question = ContestQuestion::findOrFail($validated['contest_question_id']);
            $contest  = Contest::where('id', $question->contest_id)->where('status', 'open')->firstOrFail();

            $participantCount = ContestAnswer::where('contest_id', $contest->id)->distinct('participant_id')->count('participant_id');
            if ($contest->max_participants !== null && $participantCount >= $contest->max_participants) {
                return response()->json(['message' => 'Maximum number of participants has been reached'], 403);
            }

            if ($question->question_type === 'multiple_choice') {
                if (empty($validated['contest_question_option_id'])) {
                    return response()->json(['message' => 'For MCQs, contest_question_option_id is required'], 400);
                }
                $validated['answer'] = null;
            } else {
                if (empty($validated['answer'])) {
                    return response()->json(['message' => 'For written questions, an answer is required'], 400);
                }
                $validated['contest_question_option_id'] = null;
            }

            $participant = ContestParticipant::firstOrCreate([
                'contest_id' => $validated['contest_id'],
                'user_id'    => Auth::id(),
            ]);
            $validated['participant_id'] = $participant->id;

            $exists = ContestAnswer::where([
                'contest_id'          => $validated['contest_id'],
                'contest_question_id' => $validated['contest_question_id'],
                'participant_id'      => $participant->id,
            ])->exists();

            if ($exists) {
                return response()->json(['message' => 'You have already submitted an answer for this question'], 409);
            }

            $answer = ContestAnswer::create($validated);

            // Business notification
            createNotification($contest->business_id, "User " . Auth::user()->name . " submitted an answer on your contest", "push", null, true);

            $fcmToken = DB::table('business_profiles')->where('business_id', $contest->business_id)->value('fcm_token');
            if ($fcmToken) {
                $this->fcmService->sendNotificationToDevice(
                    $fcmToken,
                    'Your Question Got a Hit!',
                    'Someone just answered your contest question.',
                    asset('storage/notifications/notification.png')
                );
            }

            return response()->json(['message' => 'Answer submitted successfully', 'answer' => $answer]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    // Business: Delete Answer
    public function deleteAnswerByBusiness(int $id)
    {
        if (Auth::user()->role !== 'business') {
            return response()->json(['message' => 'Access denied.'], 403);
        }

        try {
            $contestAnswer = ContestAnswer::findOrFail($id);
            $contest       = Contest::findOrFail($contestAnswer->contest_id);

            if ($contest->business_id !== Auth::id()) {
                return response()->json(['message' => 'You are not authorized to delete this answer.'], 403);
            }

            DB::table('contest_voting_history')
                ->where('contest_id', $contest->id)
                ->where('contest_answer_id', $id)
                ->delete();

            $contestAnswer->delete();

            return response()->json(['message' => 'Answer deleted successfully.']);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'details' => $e->getMessage()], 500);
        }
    }

    // Customer: Vote
    public function vote(Request $request)
    {
        if (Auth::user()->role !== 'customer') {
            return response()->json(['message' => 'Please login with a valid customer account'], 403);
        }

        $validated = $request->validate([
            'contest_id'          => 'required|exists:contests,id',
            'contest_question_id' => 'required|exists:contest_questions,id',
            'contest_answer_id'   => 'required|exists:contest_answers,id',
        ]);

        try {
            $contest = Contest::findOrFail($validated['contest_id']);
            if ($contest->status !== 'close') {
                return response()->json(['message' => 'Voting is only allowed for Closed contests'], 403);
            }

            $answerExists = ContestAnswer::where('id', $validated['contest_answer_id'])
                ->where('contest_question_id', $validated['contest_question_id'])
                ->exists();

            if (!$answerExists) {
                return response()->json(['message' => 'Invalid contest_answer_id'], 400);
            }

            $participant = ContestParticipant::firstOrCreate([
                'contest_id' => $validated['contest_id'],
                'user_id'    => Auth::id(),
            ]);
            $validated['participant_id'] = $participant->id;

            $alreadyVoted = ContestVotingHistory::where([
                'contest_id'          => $validated['contest_id'],
                'contest_question_id' => $validated['contest_question_id'],
                'contest_answer_id'   => $validated['contest_answer_id'],
                'participant_id'      => $participant->id,
            ])->exists();

            if ($alreadyVoted) {
                return response()->json(['message' => 'You have already voted for this answer'], 409);
            }

            $vote = ContestVotingHistory::create($validated);

            return response()->json(['message' => 'Vote recorded successfully', 'vote' => $vote]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    // Winners
    public function getWinners(int $contest_id)
    {
        try {
            $contest = Contest::findOrFail($contest_id);

            [$winnerReward, $participantReward, $voterReward] = $this->resolveRewards($contest);

            $voteResults = DB::table('contest_voting_history')
                ->select('contest_answer_id', DB::raw('COUNT(*) as total_votes'))
                ->where('contest_id', $contest_id)
                ->groupBy('contest_answer_id')
                ->orderByDesc('total_votes')
                ->get();

            if ($voteResults->isEmpty()) {
                return response()->json(['message' => 'No votes recorded for this contest'], 404);
            }

            $answerIds       = $voteResults->pluck('contest_answer_id')->toArray();
            $answerToParticipant = DB::table('contest_answers')->whereIn('id', $answerIds)->pluck('participant_id', 'id')->toArray();

            $participantVotes = [];
            foreach ($voteResults as $vote) {
                $pid = $answerToParticipant[$vote->contest_answer_id] ?? null;
                if ($pid) {
                    $participantVotes[$pid] = ($participantVotes[$pid] ?? 0) + $vote->total_votes;
                }
            }

            $participantIds = DB::table('contest_answers')->where('contest_id', $contest_id)->pluck('participant_id')->unique()->toArray();
            $participants   = ContestParticipant::whereIn('id', $participantIds)->with('user:id,name,profile_image')->get()->keyBy('id');

            foreach ($participants as $pid => $p) {
                $participants[$pid]->total_votes = $participantVotes[$pid] ?? 0;
            }

            $sorted   = $participants->sortByDesc('total_votes');
            $winner   = $sorted->first();
            $winnerId = $winner?->id;

            $winnerData = [
                'participant_id' => $winner?->id,
                'user_id'        => $winner?->user_id,
                'name'           => $winner?->user?->name ?? 'Unknown',
                'thumbnail'      => $winner?->user?->profile_image,
                'points'         => $winnerReward,
            ];

            $pointCrew = $sorted->filter(fn($p) => $p->id !== $winnerId)->values()->map(fn($p) => [
                'participant_id' => $p->id,
                'user_id'        => $p->user_id,
                'name'           => $p->user?->name ?? 'Unknown',
                'thumbnail'      => $p->user?->profile_image,
                'points'         => $participantReward,
            ]);

            $voterParticipantIds = DB::table('contest_voting_history')->where('contest_id', $contest_id)->pluck('participant_id')->unique()->toArray();
            $voterUserIds        = ContestParticipant::whereIn('id', $voterParticipantIds)->pluck('user_id')->unique()->toArray();
            $voterList           = User::whereIn('id', $voterUserIds)->get(['id', 'name', 'profile_image'])->map(fn($v) => [
                'user_id'   => $v->id,
                'name'      => $v->name,
                'thumbnail' => $v->profile_image,
                'points'    => $voterReward,
            ]);

            return response()->json([
                'winner'        => $winnerData,
                'voting_champs' => $voterList,
                'point_crew'    => $pointCrew,
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    // Helpers
    private function validateAndExtractRewardFields(Request $request, string $rewardType): array
    {
        $map = [
            'points'    => ['winner_points', 'participant_points', 'voter_points'],
            'discount'  => ['winner_discount', 'participant_discount', 'voter_discount'],
            'free_item' => ['winner_free_item', 'participant_free_item', 'voter_free_item'],
        ];

        $fields    = $map[$rewardType];
        $rules     = array_fill_keys($fields, 'required');
        $validated = $request->validate($rules);

        return array_intersect_key($validated, array_flip($fields));
    }

    private function resolveRewards(Contest $contest): array
    {
        return match ($contest->reward_type) {
            'points'   => [$contest->winner_points, $contest->participant_points, $contest->voter_points],
            'discount' => [$contest->winner_discount, $contest->participant_discount, $contest->voter_discount],
            default    => [$contest->winner_free_item, $contest->participant_free_item, $contest->voter_free_item],
        };
    }

    private function sendNearbyNotifications(float $lat, float $lng): void
    {
        $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds($lat, $lng, 10);
        if (empty($nearbyIds)) return;
        $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');

        $image = asset('storage/notifications/notification.png');
        $body  = "Jump in, earn points. It's local, it's live, and it's all yours";

        foreach ($tokens as $customerId => $token) {
            $this->fcmService->sendNotificationToDevice($token, 'A Contest Just Landed Nearby!', $body, $image);
            customerNotification($customerId, $body, 'push', null, true);
        }
    }
}

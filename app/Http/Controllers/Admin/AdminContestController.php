<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contest;
use App\Models\ContestAnswer;
use App\Models\ContestQuestion;
use App\Models\ContestQuestionOption;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminContestController extends Controller
{
    public function __construct(
        private readonly FCMService $fcmService,
        private readonly NearbyCustomerService $nearbyCustomerService,
    ) {}

    public function index()
    {
        return view('admin.businesses.contest.index', [
            'contents' => Contest::orderByDesc('id')->get(),
        ]);
    }

    public function create() { return view('admin.businesses.contest.create'); }

    public function store(Request $request)
    {
        try {
            $businessId = $request->business_id;

            $validator = Validator::make($request->all(), [
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'banner_image'      => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
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
            ]);

            if ($request->question_type === 'multiple_choice') {
                $validator->addRules(['options' => 'required|array|min:2', 'options.*' => 'required|string|max:255']);
            }

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $validatedData = $validator->validated();
            $validatedData['banner_image'] = $request->hasFile('banner_image') ? asset('storage/' . $request->file('banner_image')->store('contest/banners', 'public')) : null;
            $validatedData['business_id'] = $businessId;

            // Reward fields
            $rewardMap = [
                'points'    => ['winner_points', 'participant_points', 'voter_points'],
                'discount'  => ['winner_discount', 'participant_discount', 'voter_discount'],
                'free_item' => ['winner_free_item', 'participant_free_item', 'voter_free_item'],
            ];
            // $rewardFields = $request->validate(array_fill_keys($rewardMap[$request->reward_type], 'required'));
            $rewardValidator = \Validator::make($request->all(),
                array_fill_keys($rewardMap[$request->reward_type], 'required')
            );
            if ($rewardValidator->fails()) {
                return redirect()->back()->withErrors($rewardValidator)->withInput();
            }
            $rewardFields = $rewardValidator->validated();
            $validatedData = array_merge($validatedData, $rewardFields);
            $contest  = Contest::create($validatedData);
            $question = ContestQuestion::create([
                'contest_id'    => $contest->id,
                'question_type' => $validatedData['question_type'],
                'question'      => $validatedData['question_field'],
            ]);

            if ($validatedData['question_type'] === 'multiple_choice' && !empty($validatedData['options'])) {
                foreach ($validatedData['options'] as $option) {
                    ContestQuestionOption::create(['contest_question_id' => $question->id, 'option_text' => $option]);
                }
            }

            $address = DB::table('business_addresses')->where('business_id', $businessId)->first();
            if ($address?->latitude && $address?->longitude) {
                $nearbyIds = $this->nearbyCustomerService->getNearbyCustomerIds($address->latitude, $address->longitude, 10);
                if (!empty($nearbyIds)) {
                    $tokens = DB::table('customer_profiles')->whereIn('customer_id', $nearbyIds)->whereNotNull('fcm_token')->pluck('fcm_token', 'customer_id');
                    $title = 'A Contest Just Landed Nearby!';
                    $body  = "Jump in, earn points. It's local, it's live!";
                    $image = url('storage/notifications/notification.png');
                    foreach ($tokens as $customerId => $token) {
                        try {
                            $this->fcmService->sendToCustomerApp($token, $title, $body, $image);
                        } catch (\Throwable $e) {
                            \Log::error("Nearby contest FCM failed for customer {$customerId}: " . $e->getMessage());
                        }
                        customerNotification($customerId, $body, 'push', null, true);
                    }
                }
            }

            return redirect()->route('contests.index')->with('success', 'Contest created successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Something went wrong');
        }
    }

    public function show(string $id)
    {
        return view('admin.businesses.contest.show', [
            'content' => Contest::with(['questions', 'questions.options'])->findOrFail($id),
        ]);
    }

    public function edit(string $id)
    {
        return view('admin.businesses.contest.edit', [
            'content' => Contest::with(['questions', 'questions.options', 'questions.answers.participant.user'])->findOrFail($id),
        ]);
    }

    public function update(Request $request, string $id)
    {
        try {
            $validator = \Validator::make($request->all(), [
                'name'              => 'required|string|max:255',
                'description'       => 'nullable|string',
                'banner_image'      => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
                'status'            => 'required|in:open,close,completed',
                'start_date'        => 'required|date',
                'end_date'          => 'required|date|after_or_equal:start_date',
                'start_time'        => 'required',
                'end_time'          => 'required',
                'close_description' => 'nullable|string',
                'close_start_date'  => 'nullable|date',
                'close_end_date'    => 'nullable|date|after_or_equal:close_start_date',
                'max_participants'  => 'nullable|integer|min:1',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }
            $validated = $validator->validated();
            
            $contest = Contest::findOrFail($id);
            if ($request->hasFile('banner_image')) {
                $validated['banner_image'] = asset('storage/' . $request->file('banner_image')->store('contest/banners', 'public'));
            }

            $validated['business_id'] = $request->business_id;
            $contest->update($validated);

            return redirect()->route('contests.index')->with('success', 'Contest updated successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Something went wrong');
        }
    }

    public function destroy(string $id)
    {
        try {
            Contest::findOrFail($id)->delete();
            return redirect()->route('contests.index')->with('success', 'Contest deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('contests.index')->with('error', 'Failed to delete contest: ' . $e->getMessage());
        }
    }

    public function deleteAnswerByAdmin(int $id)
    {
        try {
            $answer  = ContestAnswer::findOrFail($id);
            DB::table('contest_voting_history')->where('contest_id', $answer->contest_id)->where('contest_answer_id', $id)->delete();
            $answer->delete();
            return response()->json(['message' => 'Answer deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong.', 'details' => $e->getMessage()], 500);
        }
    }
}

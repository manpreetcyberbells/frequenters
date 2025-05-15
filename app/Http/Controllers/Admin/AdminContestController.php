<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Contest;
use App\Models\ContestParticipant;
use App\Models\ContestAnswer;
use App\Models\ContestQuestion;
use App\Models\ContestQuestionOption;
use App\Models\ContestVotingHistory;
use App\Services\FCMService;
use App\Services\NearbyCustomerService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AdminContestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contents = Contest::orderBy('id', 'desc')->get();
        return view('admin.businesses.contest.index', compact('contents'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.businesses.contest.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // echo"<pre>"; print_r($request->all());  die;
        try {
            $businessId = $request->business_id;
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'banner_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
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
            
                // Always required
                'question_type' => 'required|in:multiple_choice,text_answer',
                'question_field' => 'required|string|max:255',
            ]);
            
            // Conditionally add rules for options
            if ($request->question_type === 'multiple_choice') {
                $validator->addRules([
                    'options' => 'required|array|min:2',
                    'options.*' => 'required|string|max:255',
                ]);
            }
            
            $validatedData = $validator->validate();
            

            // Handle event banner upload
            $bannerPath = null;
            if ($request->hasFile('banner_image')) {
                $bannerPath = $request->file('banner_image')->store('contest/banners', 'public');
            }

            // Add authenticated business ID
            $validatedData['business_id'] = $businessId;
            $validatedData['banner_image'] = asset('storage/' . $bannerPath);

            // echo"<pre>"; print_r($validatedData);  die;

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

            return redirect()->route('contests.index')->with('success', 'Contest created successfully');

        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Something went wrong');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $content = Contest::with(['questions','questions.options'])->findOrFail($id);
        return view('admin.businesses.contest.show', compact('content'));
    }
    

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $content = Contest::with(['questions', 'questions.options', 'questions.answers.participant.user'])->findOrFail($id);
        return view('admin.businesses.contest.edit', compact('content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $businessId = $request->business_id;
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'banner_image' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp',
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
            ]);

            $validatedData = $validator->validate();
            $contest = Contest::findOrFail($id);
            if ($request->hasFile('banner_image')) {
                $bannerPath = $request->file('banner_image')->store('contest/banners', 'public');
                $validatedData['banner_image'] = asset('storage/' . $bannerPath);
            }

            $validatedData['business_id'] = $businessId;
            $contest->update($validatedData);

            return redirect()->route('contests.index')->with('success', 'Contest updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage() ?? 'Something went wrong');
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            
            $contest = Contest::findOrFail($id);
            $contest->delete(); // Soft delete with cascading logic

            return redirect()->route('contests.index')->with('success', 'Contest deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('contests.index')->with('error', 'Failed to delete contest: ' . $e->getMessage());
        }
    }

    /**
     * Delete Contest Answer by Admin.
     */
    public function deleteAnswerByAdmin($id)
    {
        try{
            $contestAnswer = DB::table('contest_answers')->where('id', $id)->first();
            if (!$contestAnswer) {
                return response()->json(['message' => 'Answer not found.'], 404);
            }

            $contest = DB::table('contests')->where('id', $contestAnswer->contest_id)->first();
            if (!$contest) {
                return response()->json(['message' => 'Contest not found or has been deleted.'], 404);
            }

            // Delete related voting history and answer
            DB::table('contest_voting_history')->where('contest_id', $contest->id)->where('contest_answer_id', $id)->delete();

            DB::table('contest_answers')->where('id', $id)->delete();

            return response()->json(['message' => 'Answer deleted successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\B2B\CreateCollabPostRequest;
use App\Http\Requests\B2B\RespondToPostRequest;
use App\Http\Requests\B2B\SendB2BMessageRequest;
use App\Models\B2BConversation;
use App\Models\B2BMessage;
use App\Models\CollabPost;
use App\Services\B2BCollabService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class B2BCollabController extends Controller
{
    public function __construct(
        private readonly B2BCollabService $service
    ) {}

    // POST /business/b2b/posts
    public function createPost(CreateCollabPostRequest $request): JsonResponse
    {
        try {
            $post = $this->service->createPost(
                Auth::user()->effectiveBusinessId(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Collaboration post created successfully.',
                'data'    => $post,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to create post.', 'error' => $e->getMessage()], 500);
        }
    }

    // GET /business/b2b/posts — nearby feed
    public function listNearbyPosts(Request $request): JsonResponse
    {
        $request->validate([
            'radius'      => 'nullable|numeric|min:1|max:50',
            'post_type'   => 'nullable|in:request,offer',
            'category_id' => 'nullable|integer|exists:categories,id',
        ]);

        $posts = $this->service->getNearbyPosts(
            businessId:  Auth::user()->effectiveBusinessId(),
            radiusMiles: $request->float('radius', 5.0),
            filters:     $request->only(['post_type', 'category_id'])
        );

        return response()->json($posts);
    }

    // GET /business/b2b/posts/{id}
    public function showPost(int $id): JsonResponse
    {
        $post = CollabPost::with([
            'business:id,name,profile_image',
            'businessProfile:business_id,business_name,logo,industry_type',
        ])->active()->findOrFail($id);

        $businessId     = Auth::user()->effectiveBusinessId();
        $hasResponded   = $post->hasRespondedBy($businessId);
        $isOwn          = $post->business_id === $businessId;

        $data = $post->toArray();
        $data['has_responded'] = $hasResponded;
        $data['is_own']        = $isOwn;

        // Only reveal contact info if contact_type = direct
        if ($post->contact_type !== 'direct') {
            unset($data['contact_name'], $data['contact_email'], $data['contact_phone']);
        }

        return response()->json($data);
    }

    // GET /business/b2b/my-posts — own posts with responder details
    public function myPosts(): JsonResponse
    {
        $posts = $this->service->getMyPosts(
            Auth::user()->effectiveBusinessId()
        );

        return response()->json($posts);
    }

    // DELETE /business/b2b/posts/{id}
    public function deletePost(int $id): JsonResponse
    {
        $post = CollabPost::where('business_id', Auth::user()->effectiveBusinessId())
            ->findOrFail($id);

        $post->delete();

        return response()->json(['message' => 'Post deleted successfully.']);
    }

    // POST /business/b2b/posts/{id}/respond
    public function respondToPost(RespondToPostRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->respondToPost(
                collabPostId:        $id,
                responderBusinessId: Auth::user()->effectiveBusinessId(),
                message:             $request->validated('message')
            );

            return response()->json([
                'message'      => 'Response sent successfully.',
                'contact_type' => $result['contact_type'],
                'contact_info' => $result['contact_info'],
                'conversation' => $result['conversation'] ? [
                    'id' => $result['conversation']->id,
                ] : null,
            ], 201);

        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Something went wrong.', 'error' => $e->getMessage()], 500);
        }
    }

    // GET /business/b2b/conversations — conversation list
    public function conversationList(): JsonResponse
    {
        $list = $this->service->getConversationList(
            Auth::user()->effectiveBusinessId()
        );

        return response()->json([
            'status' => true,
            'data'   => $list,
        ]);
    }

    // GET /business/b2b/conversations/{id} — full chat history
    public function getConversation(int $id): JsonResponse
    {
        $businessId   = Auth::user()->effectiveBusinessId();
        $conversation = B2BConversation::with(['messages.sender:id,name,profile_image', 'businessA:id,name,profile_image', 'businessB:id,name,profile_image'])
            ->where(fn($q) => $q->where('business_a_id', $businessId)->orWhere('business_b_id', $businessId))
            ->findOrFail($id);

        // Mark as read
        $this->service->markConversationRead($id, $businessId);

        return response()->json([
            'conversation' => $conversation,
            'other_party'  => $conversation->otherParty($businessId)->only(['id', 'name', 'profile_image']),
        ]);
    }

    // POST /business/b2b/conversations/{id}/messages — send message
    public function sendMessage(SendB2BMessageRequest $request, int $id): JsonResponse
    {
        try {
            $message = $this->service->sendMessage(
                conversationId:    $id,
                senderBusinessId:  Auth::user()->effectiveBusinessId(),
                body:              $request->validated('body')
            );

            return response()->json([
                'message' => 'Message sent.',
                'data'    => $message,
            ], 201);

        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 403);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Something went wrong.'], 500);
        }
    }
}
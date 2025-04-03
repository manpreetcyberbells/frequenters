<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Invite;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class InviteController extends Controller
{
    // Send Invitation
    public function invite(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        $request->validate([
            'email' => 'required|email|unique:users,email|unique:invites,email',
            'role'  => 'required|in:customer,business'
        ]);

        $token = Str::random(32);

        $inviteData = [
            'email'  => $request->email,
            'token'  => $token,
            'status' => 'pending',
            'role'   => $request->role,
        ];

        if ($request->role === 'customer') {
            $inviteData['customer_id'] = auth()->id();
        } elseif ($request->role === 'business') {
            $inviteData['business_id'] = auth()->id();
        }

        $invite = Invite::create($inviteData);

        // Send Email (Can be customized)
        Mail::raw("You're invited! Accept the invite: " . url('/accept-invite/' . $token), function ($message) use ($request) {
            $message->to($request->email)
                    ->subject('Invitation to Join');
        });

        return response()->json(['message' => 'Invitation sent successfully.']);
    }

    // Accept Invitation
    public function acceptInvite($token)
    {
        $invite = Invite::where('token', $token)->where('status', 'pending')->first();

        if (!$invite) {
            return response()->json(['message' => 'Invalid or expired invite'], 400);
        }

        // Here, you can redirect to the registration page with email prefilled
        return response()->json([
            'message' => 'Invite accepted. Please register.',
            'email' => $invite->email,
            'role' => $invite->role
        ]);
    }

    // Check Invite Status
    public function checkInvite(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $invite = Invite::where('email', $request->email)->first();

        if (!$invite) {
            return response()->json(['message' => 'No invitation found.'], 404);
        }

        return response()->json(['status' => $invite->status, 'role' => $invite->role]);
    }
}

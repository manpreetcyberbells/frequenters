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
        $request->validate([
            'email' => 'required|email|unique:users,email|unique:invites,email',
            'role'  => 'required|in:customer,business'
        ]);

        $token = Str::random(32);
        $sender = auth()->user();

        $inviteData = [
            'email'  => $request->email,
            'token'  => $token,
            'status' => 'pending',
            'role'   => $request->role,
        ];

        if ($request->role === 'customer') {
            $inviteData['customer_id'] = $sender->id;
        } elseif ($request->role === 'business') {
            $inviteData['business_id'] = $sender->id;
        }

        $invite = Invite::create($inviteData);

        $url = url('/accept-invite/' . $token);
        $role = $request->role;
        $receiverName = $request->name ?? ucfirst(str_replace(['.', '_'], ' ', explode('@', $request->email)[0]));
        $senderName = $sender->name ?? 'Your Friend';

        Mail::send('emails.invite', compact('url', 'role', 'receiverName', 'senderName'), function ($message) use ($request, $senderName) {
            $message->to($request->email, $request->name ?? null)
                    ->subject("You're Invited by $senderName to Join Frequenters");
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

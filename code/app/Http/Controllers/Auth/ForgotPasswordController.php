<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Validation\Rule;

class ForgotPasswordController extends Controller
{
    /**
     *  Funcation to Genrate Link for Reset Password 
    */
    public function sendResetLink(Request $request)
    {
        try {
            $request->validate([
                'email' => [
                    'required',
                    'email',
                    Rule::exists('users', 'email')->where(function ($query) {
                        $query->whereIn('role', ['customer', 'business']);
                    }),
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'This email does not exist in our records. Please enter a valid email.'], 400);
        }
    
        $status = Password::sendResetLink(
            $request->only('email'),
            function ($user, $token) {
                $user->notify(new ResetPasswordNotification($token, $user->email));
            }
        );
    
        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Password reset link sent successfully.'], 200);
        }
    
        return response()->json(['error' => trans($status)], 400);
    }
}


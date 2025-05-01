<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use App\Services\FCMService;
use Illuminate\Support\Facades\DB; 

class ResetPasswordController extends Controller
{
    /**
     * Show Reset Password Form to EndUser 
    */
    public function showResetForm(Request $request)
    {
        return view('auth.reset-password', ['token' => $request->token, 'email' => $request->email]);
    }
    
    /**
     *  Reset Password API Funcation // Validations 
    */
    public function reset(Request $request)
    {
        // die('HELLo');
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);
    
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();
                
                $userId = $user->id;
                $role = $user->role;
                $fcm_token = DB::table('customer_profiles')->where('customer_id',$userId)->pluck('fcm_token')->first();
                $user->tokens()->delete(); // Delete old tokens

                if ($fcm_token) {
                    $fcmService = new FCMService();
                    $title = "Password Reset Successful";
                    $body = "Your password has been changed successfully.";
                    $image = asset('storage/notifications/notification.png');
    
                    $fcmService->sendNotificationToDevice($fcm_token, $title, $body, $image);

                    if($role == "customer"){
                        customerNotification($userId, $body, "push", null, true);
                    }else{
                        createNotification($userId, $body, "push", null, true);
                    }
                }
            }
        );
    
        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'error' => 'Password reset failed'
            ], 400);
        }
    
        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    
    }
}


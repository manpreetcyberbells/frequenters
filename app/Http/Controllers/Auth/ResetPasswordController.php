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
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                $userId = $user->id;
                $role   = $user->role;
                $title  = 'Password Reset Successful';
                $body   = 'Your password has been changed successfully.';
                $image  = url('storage/notifications/notification.png');
                $user->tokens()->delete();

                $fcmService = new FCMService();
                if ($role === 'customer') {
                    $fcmToken = DB::table('customer_profiles')->where('customer_id', $userId)->value('fcm_token');
                    if (!empty($fcmToken)) {
                        try {
                            $fcmService->sendToCustomerApp($fcmToken, $title, $body, $image);
                        } catch (\Throwable $e) {
                            \Log::error("Password reset FCM failed for customer {$userId}: " . $e->getMessage());
                        }
                    }
                    customerNotification($userId, $body, 'push', null, true);

                } else {
                    $fcmToken = DB::table('business_profiles')->where('business_id', $userId)->value('fcm_token');
                    if (!empty($fcmToken)) {
                        try {
                            $fcmService->sendToBusinessApp($fcmToken, $title, $body, $image);
                        } catch (\Throwable $e) {
                            \Log::error("Password reset FCM failed for business {$userId}: " . $e->getMessage());
                        }
                    }
                    createNotification($userId, $body, 'push', null, true);
                }
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['error' => 'Password reset failed'], 400);
        }

        return response()->json(['message' => 'Password has been reset successfully.'], 200);
    }
}


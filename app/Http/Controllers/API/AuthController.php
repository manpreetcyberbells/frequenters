<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\CustomerAddress;
use App\Models\BusinessProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterBusinessRequest;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new customer.
     * @param RegisterCustomerRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerCustomer(RegisterCustomerRequest $request)
    {
        try {
            Log::info('Register Customer API called with:', $request->all());

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]);

            Log::info('Customer user created with ID:', ['user_id' => $user->user_id]);
            
            CustomerProfile::create([
                'customer_id' => $user->id,
                'birthday' => $request->dob,
            ]);

            // Create the customer address
            CustomerAddress::create([
                'customer_id' => $user->id,
            ]);

            Log::info('Customer profile created for user_id:', ['user_id' => $user->user_id]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Customer registered successfully.',
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Customer registration failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during registration.'], 500);
        }
    }


    /**
     * Register a new business.
     * @param RegisterBusinessRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerBusiness(RegisterBusinessRequest $request)
    {
        try {
            // Create the user with role 'business'
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'business',
            ]);

            // Create business profile
            BusinessProfile::create([
                'business_id' => $user->user_id, // Use `user_id` from the users table
                'business_name' => $request->business_name,
                'industry_type' => $request->industry_type,
                'location' => $request->location ? json_decode($request->location, true) : null,
                'points_per_checkin' => $request->points_per_checkin ?? 10,
                'conversion_rate' => $request->conversion_rate ?? 1.0,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line2,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'zip_code' => $request->zip_code,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Business registered successfully.',
                'token' => $token,
                'user' => $user,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Business registration failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during registration.'], 500);
        }
    }


    /**
     * Login a user.
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Check if user status is active
            if ($user->status !== 'active') {
                Auth::logout(); // Logout user if not active
                return response()->json([
                    'message' => 'Your account is not active. Please contact support.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            // Update last_login_at
            $user->last_login_at = now();
            $user->save();

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user->load('customerProfile'),
                'role' => $user->role, // Include the role in the response
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials.'], 401);
    }

    /**
     * Logout a user.
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        try {
            Auth::user()->tokens()->delete();

            return response()->json(['message' => 'Logged out successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during logout.'], 500);
        }
    }

    /**
     * Get authenticated user details.
     * @return \Illuminate\Http\JsonResponse
     */
    public function profile()
    {
        try {
            return response()->json([
                'user' => Auth::user(),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Fetching profile failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving user information.'], 500);
        }
    }

    /**
     * Funcation to Changes password using API.
    */
    public function changePassword(Request $request)
    {
        try{
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6|confirmed',
            ]);

            $user = Auth::user();
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 401);
            }
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json(['message' => 'Password changed successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong. Please try again.',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}

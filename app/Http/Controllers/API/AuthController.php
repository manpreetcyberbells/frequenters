<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\CustomerAddress;
use App\Models\BusinessAddress;
use App\Models\BusinessProfile;
use App\Models\Invite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterCustomerRequest;
use App\Http\Requests\RegisterBusinessRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Crypt;
use Laravolt\Avatar\Facade as Avatar;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    /**
     * Generate Unique CustomerID
    */
    private function generateCustomerID($userId, $email)
    {
        $hash = substr(md5($email), 0, 5);
        return 'CUST' . $userId . strtoupper($hash);
    }

    /**
     * Generate Unique BusinessID
    */
    private function generateBusinessID($userId, $email)
    {
        $hash = substr(md5($email), 0, 5);
        return 'BUS' . $userId . strtoupper($hash);
    }

    /**
     * Handle Invitation in Registration (Customer & Business)
    */
    private function handleInviteReward($email, $role, $userId)
    {
        $invite = Invite::where('email', $email)->where('status', 'pending')->where('role', $role)->first();

        if ($invite) {
            $invite->update(['status' => 'accepted']);

            if ($role === 'customer') {
                // Reward inviter for customer
                $inviter = CustomerProfile::where('customer_id', $invite->customer_id)->first();
                if ($inviter) {
                    $inviter->increment('points_balance', 100);
                }
            } elseif ($role === 'business') {
                // Reward inviter (who sent the invite)
                $inviter = BusinessProfile::where('business_id', $invite->business_id)->first();
                if ($inviter) {
                    $inviter->increment('monthly_points_available', 100);
                }

                // Reward invitee (who registered using the invite)
                $invitee = BusinessProfile::where('business_id', $userId)->first();
                if ($invitee) {
                    $invitee->increment('monthly_points_available', 100);
                }
            }
        }
    }

    /**
     * Common Function : Generate AnonymousName for Customer
    */
    private function generateAnonymousName()
    {
        $adjectives = [
            'Cool', 'Fast', 'Happy', 'Chill', 'Brave', 'Smart', 'Sneaky', 'Wild', 'Funky', 'Gentle',
            'Zany', 'Quirky', 'Noble', 'Bouncy', 'Shiny', 'Clever', 'Bold', 'Goofy', 'Witty', 'Kind',
            'Jolly', 'Lively', 'Calm', 'Swift', 'Witty', 'Mighty', 'Lazy', 'Curious', 'Peppy', 'Sunny'
        ];

        $animals = [
            'Tiger', 'Lion', 'Panda', 'Eagle', 'Shark', 'Bear', 'Wolf', 'Otter', 'Falcon', 'Fox',
            'Hawk', 'Cheetah', 'Panther', 'Whale', 'Dolphin', 'Rabbit', 'Koala', 'Turtle', 'Penguin', 'Giraffe',
            'Moose', 'Horse', 'Badger', 'Lynx', 'Owl', 'Camel', 'Jaguar', 'Sloth', 'Raccoon', 'Buffalo'
        ];

        $name = $adjectives[array_rand($adjectives)] . $animals[array_rand($animals)] . rand(100, 999);
        while (CustomerProfile::where('anonymous_name', $name)->exists()) {
            $name = $adjectives[array_rand($adjectives)] . $animals[array_rand($animals)] . rand(100, 999);
        }

        return $name;
    }

    /**
     * Common Function : Generate AnonymousImage for Customer
    */
    private function generateAnonymousImage($anonymousName)
    {
        // Generate SVG avatar and save to public storage
        $avatarSvg = Avatar::create($anonymousName)->toSvg();
        $avatarFileName = 'avatars/customers/' . $anonymousName . '.svg';
        Storage::disk('public')->put($avatarFileName, $avatarSvg);
        $avatarUrl = asset('storage/' . $avatarFileName);
        return $avatarUrl;
    }

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
                'providers' => 'email',
            ]);

            Log::info('Customer user created with ID:', ['user_id' => $user->id]);

            $anonymousName = $this->generateAnonymousName();
            $anonymousImage = $this->generateAnonymousImage($anonymousName);
            $customer = CustomerProfile::create([
                'customer_id' => $user->id,
                'birthday' => $request->dob,
                'anonymous_name' => $anonymousName,
                'anonymous_image' => $anonymousImage,
            ]);

            // Create the customer address
            CustomerAddress::create([
                'customer_id' => $user->id,
            ]);

            Log::info('Customer profile created for user_id:', ['user_id' => $user->id]);

            // Generate Unique Customer ID (Short UPI-style ID)
            $customerId = $this->generateCustomerID($user->id, $user->email);
            $customer->customer_uid = $customerId;
            $customer->save();

            // Generate QR Code if not exists
            if (!$customer->qr_code_hash || !Storage::disk('public')->exists($customer->qr_code_hash)) {
                $payload = [
                    "type" => "customer_checkin",
                    "customer_uid" => $customerId
                ];
                $encryptedPayload = Crypt::encryptString(json_encode($payload));

                // Create a safe filename for the QR code
                $safeFilename = hash('sha256', $encryptedPayload);
                $path = 'qrcodes/customers/' . $safeFilename . '.svg';
                Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
                $customer->qr_code_hash = asset('storage/' . $path);
                $customer->save();
                
                Log::info('Customer QR created for user_id:', ['user_id' => $user->id]);
            }

            $this->handleInviteReward($request->email, 'customer', $customer->customer_id);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Customer registered successfully.',
                'token' => $token,
                'user' => $user,
                'customer_uid' => $customerId,
                'qr_code_url' => $customer->qr_code_hash
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
                'name' => $request->business_name,
                'email' => $request->business_email,
                'password' => Hash::make($request->password),
                'role' => 'business',
                'providers' => 'email',
            ]);

            // Create business profile
            $business = BusinessProfile::create([
                'business_id' => $user->id,
                'business_name' => $request->business_name,
                'category_id' => $request->category_id,
                'child_category_id' => $request->child_category_id,
                'owner_name' => $request->owner_name,
                'owner_email' => $request->owner_email,
                'owner_phone' => $request->owner_phone,
                'website' => $request->website,
            ]);

            // Generate Unique Business ID
            $businessId = $this->generateBusinessID($user->id, $user->email);
            $business->business_uid = $businessId;
            $business->save();

            // Generate QR Code if not exists
            if (!$business->qr_code_hash || !Storage::disk('public')->exists($business->qr_code_hash)) {
                $payload = [
                    "type" => "business_checkin",
                    "business_uid" => $businessId
                ];
                $encryptedPayload = Crypt::encryptString(json_encode($payload));

                // Create a safe filename for the QR code
                $safeFilename = hash('sha256', $encryptedPayload);
                $path = 'qrcodes/business/' . $safeFilename . '.svg';
                Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
                $business->qr_code_hash = asset('storage/' . $path);
                $business->save();
                
                Log::info('Business QR created for user_id:', ['user_id' => $user->id]);
            }

            $logoPath = null;
            if($request->hasFile('logo')) {
                $businessLogoFolder = 'logos/business';
                Storage::disk('public')->makeDirectory($businessLogoFolder);
                $file = $request->file('logo');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $logoPath = $file->storeAs($businessLogoFolder, $fileName, 'public');
            }

            // Create the Business address
            BusinessAddress::create([
                'business_id' => $user->id,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
                'address_line1' => $request->address_line1,
                'address_line2' => $request->address_line1,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'zip_code' => $request->zip_code,
            ]);

            $this->handleInviteReward($request->business_email, 'business', $user->id);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Business registered successfully.',
                'token' => $token,
                'user' => $user,
                'business_uid' => $businessId,
                'qr_code_url' => $business->qr_code_hash
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

            if ($user->status !== 'active') {
                Auth::logout();
                return response()->json([
                    'message' => 'Your account is not active. Please contact support.'
                ], 403);
            }

            $token = $user->createToken('auth_token')->plainTextToken;
            $user->last_login_at = now();
            $user->save();

            // Load profile and badges only for customer/business
            switch ($user->role) {
                case 'customer':
                    $user->load(['customerProfile', 'customerBadges.badge']);
                    $badges = $user->customerBadges;
                    break;

                case 'business':
                    $user->load('businessProfile');
                    $badges = null;
                    break;

                case 'manager':
                case 'employee':
                    $badges = null;
                    break;

                default:
                    $badges = null;
                    break;
            }

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
                'role' => $user->role,
                // 'badges' => '',
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

    /**
     * API Funcation : Social Login//Register
    */
    public function socialLoginRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:google,facebook,apple',
            'provider_id' => 'required',
            'name' => 'required',
            'email' => 'nullable|email',
            'role' => 'required|in:customer,business',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $user = User::where('provider_id', $request->provider_id)->where('providers', $request->provider)->first();
        if (!$user && !empty($request->email)) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->providers = $request->provider;
                $user->provider_id = $request->provider_id;
                $user->save();

                $token = $user->createToken('authToken')->plainTextToken;

                if ($request->role == "customer") {
                    $user->load(['customerProfile', 'customerBadges.badge']);
                    $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();

                    return response()->json([
                        'message' => 'Customer login successful.',
                        'token' => $token,
                        'user' => $user,
                        'customer_uid' => $customerProfile->customer_id ?? null,
                        'qr_code_url' => $customerProfile->qr_code_hash ?? null,
                    ]);
                }

                if ($request->role == "business") {
                    $user->load('businessProfile');
                    $businessProfile = BusinessProfile::where('business_id', $user->id)->first();

                    return response()->json([
                        'message' => 'Business login successful.',
                        'token' => $token,
                        'user' => $user,
                        'business_uid' => $businessProfile->business_id ?? null,
                        'qr_code_url' => $businessProfile->qr_code_hash ?? null,
                    ]);
                }
            }
        }

        if (!$user) {
            // No user found, so create new
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email ?? Str::random(10) . '@example.com',
                'providers' => $request->provider,
                'provider_id' => $request->provider_id,
                'phone' => $request->phone,
                'profile_image' => $request->profile_image,
                'role' => $request->role,
                'status' => 'active',
                'created_at' => now(),
            ]);

            if ($request->role == "customer") {
                $anonymousName = $this->generateAnonymousName();
                $anonymousImage = $this->generateAnonymousImage($anonymousName);
                $customer = CustomerProfile::create([
                    'customer_id' => $user->id,
                    'anonymous_name' => $anonymousName,
                    'anonymous_image' => $anonymousImage,
                ]);

                CustomerAddress::create([
                    'customer_id' => $user->id,
                ]);

                Log::info('Customer coming from ' . $request->provider . ' :: created for user_id:', ['user_id' => $user->id]);

                $customerId = $this->generateCustomerID($user->id, $user->email);
                $customer->customer_uid = $customerId;
                $customer->save();
            }

            if ($request->role == "business") {
                $business = BusinessProfile::create([
                    'business_id' => $user->id,
                ]);

                $businessId = $this->generateBusinessID($user->id, $user->email);
                $business->business_uid = $businessId;
                $business->save();

                if (!$business->qr_code_hash || !Storage::disk('public')->exists($business->qr_code_hash)) {
                    $payload = [
                        "type" => "business_checkin",
                        "business_uid" => $businessId,
                    ];
                    $encryptedPayload = Crypt::encryptString(json_encode($payload));
                    $safeFilename = hash('sha256', $encryptedPayload);
                    $path = 'qrcodes/business/' . $safeFilename . '.svg';
                    Storage::disk('public')->put($path, QrCode::format('svg')->size(300)->generate($encryptedPayload));
                    $business->qr_code_hash = asset('storage/' . $path);
                    $business->save();

                    Log::info('Business QR created for user_id:', ['user_id' => $user->id]);
                }

                BusinessAddress::create([
                    'business_id' => $user->id,
                ]);

                Log::info('Business coming from ' . $request->provider . ' :: created for user_id:', ['user_id' => $user->id]);
            }
        }

        // Finally after either update or create, generate token
        $token = $user->createToken('authToken')->plainTextToken;

        switch ($request->role) {
            case 'customer':
                $user->load(['customerProfile', 'customerBadges.badge']);
                $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();

                return response()->json([
                    'message' => 'Customer registered successfully.',
                    'token' => $token,
                    'user' => $user,
                    'customer_uid' => $customerProfile->customer_id ?? null,
                    'qr_code_url' => $customerProfile->qr_code_hash ?? null,
                ]);

            case 'business':
                $user->load('businessProfile');
                $businessProfile = BusinessProfile::where('business_id', $user->id)->first();

                return response()->json([
                    'message' => 'Business registered successfully.',
                    'token' => $token,
                    'user' => $user,
                    'business_uid' => $businessProfile->business_id ?? null,
                    'qr_code_url' => $businessProfile->qr_code_hash ?? null,
                ]);

            default:
                return response()->json([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'user' => $user,
                ]);
        }
    }

    /**
     * API Function for getting Badges
    */
    public function fetchBadges(){
        try {
            $badges = DB::table('badges')->get();
            return response()->json([
                'badges' => $badges,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Fetching profile failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred while retrieving user information.'], 500);
        }
    }

}

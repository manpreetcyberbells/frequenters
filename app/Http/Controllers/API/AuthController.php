<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterBusinessRequest;
use App\Http\Requests\RegisterCustomerRequest;
use App\Models\BusinessAddress;
use App\Models\BusinessProfile;
use App\Models\CustomerAddress;
use App\Models\CustomerProfile;
use App\Models\Invite;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class AuthController extends Controller
{
    public function __construct(
        private readonly QrService $qrService
    ) {}

    // Registration
    public function registerCustomer(RegisterCustomerRequest $request)
    {
        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'phone'     => $request->phone,
                'password'  => Hash::make($request->password),
                'role'      => 'customer',
                'providers' => 'email',
            ]);

            $customer = $this->createCustomerProfile(
                $user->id,
                $request->dob ?? null,
                [
                    'address'   => $request->address,
                    'city'      => $request->city,
                    'state'     => $request->state,
                    'country'   => $request->country,
                    'zip_code'  => $request->zip_code,
                    'latitude'  => $request->latitude,
                    'longitude' => $request->longitude,
                ]
            );
            $this->handleInviteReward($request->email, 'customer', $user->id);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message'      => 'Customer registered successfully.',
                'token'        => $token,
                'user'         => $user,
                'customer_uid' => $customer->customer_uid,
                'qr_code_url'  => $customer->qr_code_hash,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Customer registration failed:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during registration.'], 500);
        }
    }

    public function registerBusiness(RegisterBusinessRequest $request)
    {
        try {
            $user = User::create([
                'name'      => $request->business_name,
                'email'     => $request->business_email,
                'phone'     => $request->business_phone,
                'password'  => Hash::make($request->password),
                'role'      => 'business',
                'providers' => 'email',
            ]);

            $business = $this->createBusinessProfile($user->id, $request->all());
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->storeAs(
                    'logos/business',
                    time() . '_' . $request->file('logo')->getClientOriginalName(),
                    'public'
                );
                $user->update(['profile_image' => asset('storage/' . $logoPath)]);
            }

            BusinessAddress::create([
                'business_id'  => $user->id,
                'latitude'     => $request->latitude,
                'longitude'    => $request->longitude,
                'address_line1'=> $request->address_line1,
                'address_line2'=> $request->address_line2,
                'city'         => $request->city,
                'state'        => $request->state,
                'country'      => $request->country,
                'zip_code'     => $request->zip_code,
            ]);

            $this->handleInviteReward($request->business_email, 'business', $user->id);
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message'      => 'Business registered successfully.',
                'token'        => $token,
                'user'         => $user,
                'business_uid' => $business->business_uid,
                'qr_code_url'  => $business->qr_code_hash,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Business registration failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during registration.'], 500);
        }
    }

    // Login
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();
        if ($user->status !== 'active') {
            Auth::logout();
            return response()->json(['message' => 'Your account is not active. Please contact support.'], 403);
        }

        $user->update(['last_login_at' => now()]);
        $businessId = $user->effectiveBusinessId();
        if ($businessId && $businessId !== $user->id) {
            $parentBusiness = User::with(['businessProfile', 'businessAddress'])->find($businessId);
        }

        match ($user->role) {
            'customer' => $user->load(['customerProfile', 'customerBadges.badge']),
            'business' => $user->load('businessProfile'),
            'manager', 'employee' => null,
            default => null,
        };

        return response()->json([
            'message' => 'Login successful.',
            'token' => $user->createToken('auth_token')->plainTextToken,
            'user' => $user,
            'role' => $user->role,
            'parent_business' => @$parentBusiness,
        ]);
    }

    // Logout
    public function logout()
    {
        try {
            Auth::user()->tokens()->delete();
            return response()->json(['message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred during logout.'], 500);
        }
    }

    // Profile
    public function profile()
    {
        return response()->json(['user' => Auth::user()]);
    }

    // Password
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6|confirmed',
        ]);

        $user = Auth::user();
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        return response()->json(['message' => 'Password changed successfully']);
    }

    // Social Login
    public function socialLoginRegister(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider'    => 'required|in:google,facebook,apple',
            'provider_id' => 'required',
            'name'        => 'required',
            'email'       => 'nullable|email',
            'role'        => 'required|in:customer,business',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Find existing user by provider or email
        $user = User::where('provider_id', $request->provider_id)->where('providers', $request->provider)->first();
        if (!$user && !empty($request->email)) {
            $user = User::where('email', $request->email)->first();
            if ($user) {
                $user->update([
                    'providers'   => $request->provider,
                    'provider_id' => $request->provider_id,
                ]);
            }
        }

        // New user — create
        if (!$user) {
            $user = User::create([
                'name'          => $request->name,
                'email'         => $request->email ?? Str::random(10) . '@example.com',
                'providers'     => $request->provider,
                'provider_id'   => $request->provider_id,
                'phone'         => $request->phone,
                'profile_image' => $request->profile_image,
                'role'          => $request->role,
                'status'        => 'active',
            ]);

            if ($request->role === 'customer') {
                $this->createCustomerProfile($user->id);
                Log::info("Customer from {$request->provider} created:", ['user_id' => $user->id]);
            }

            if ($request->role === 'business') {
                $this->createBusinessProfile($user->id);
                BusinessAddress::create(['business_id' => $user->id]);
                Log::info("Business from {$request->provider} created:", ['user_id' => $user->id]);
            }
        }

        $token = $user->createToken('authToken')->plainTextToken;
        return match ($request->role) {
            'customer' => $this->customerLoginResponse($user, $token, 'Customer'),
            'business' => $this->businessLoginResponse($user, $token, 'Business'),
            default    => response()->json(['message' => 'Login successful.', 'token' => $token, 'user' => $user]),
        };
    }

    // Badges
    public function fetchBadges()
    {
        try {
            return response()->json(['badges' => DB::table('badges')->get()]);
        } catch (\Exception $e) {
            Log::error('Fetching badges failed: ' . $e->getMessage());
            return response()->json(['message' => 'An error occurred.'], 500);
        }
    }

    // Private Helpers
    private function createCustomerProfile(int $userId, ?string $birthday = null, array $addressData = []): CustomerProfile
    {
        $anonymousName  = $this->generateAnonymousName();
        $anonymousImage = $this->generateAnonymousImage($anonymousName);
        $customerId     = $this->generateCustomerID($userId, User::find($userId)->email);

        $qrPath = $this->qrService->generateAndStore([
            'type'        => 'customer_qr',
            'customer_uid'=> $customerId,
        ], 'qrcodes/customers');

        CustomerAddress::updateOrCreate(
            ['customer_id' => $userId],
            [
                'address_line1' => $addressData['address'] ?? null,
                'city'          => $addressData['city'] ?? null,
                'state'         => $addressData['state'] ?? null,
                'country'       => $addressData['country'] ?? null,
                'zip_code'      => $addressData['zip_code'] ?? null,
                'latitude'      => $addressData['latitude'] ?? null,
                'longitude'     => $addressData['longitude'] ?? null,
            ]
        );

        return CustomerProfile::create([
            'customer_id'    => $userId,
            'birthday'       => $birthday,
            'anonymous_name' => $anonymousName,
            'anonymous_image'=> $anonymousImage,
            'customer_uid'   => $customerId,
            'qr_code_hash'   => asset('storage/' . $qrPath),
        ]);
    }

    private function createBusinessProfile(int $userId, array $data = []): BusinessProfile
    {
        $businessId = $this->generateBusinessID($userId, User::find($userId)->email);
        $qrPath = $this->qrService->generateAndStore([
            'type'         => 'business_qr',
            'business_uid' => $businessId,
        ], 'qrcodes/business');

        return BusinessProfile::create([
            'business_id'       => $userId,
            'business_name'     => $data['business_name'] ?? null,
            'category_id'       => $data['category_id'] ?? null,
            'child_category_id' => $data['child_category_id'] ?? null,
            'owner_name'        => $data['owner_name'] ?? null,
            'owner_email'       => $data['owner_email'] ?? null,
            'owner_phone'       => $data['owner_phone'] ?? null,
            'website'           => $data['website'] ?? null,
            'business_uid'      => $businessId,
            'qr_code_hash'      => asset('storage/' . $qrPath),
            'points_per_checkin' => 10,
            'monthly_points_available' => 500
        ]);
    }

    private function handleInviteReward(string $email, string $role, int $userId): void
    {
        $invite = Invite::where('email', $email)->where('status', 'pending')->where('role', $role)->first();
        if (!$invite) return;

        $invite->update(['status' => 'accepted']);
        if ($role === 'customer') {
            CustomerProfile::where('customer_id', $invite->customer_id)->first()?->increment('points_balance', 100);
        }

        if ($role === 'business') {
            BusinessProfile::where('business_id', $invite->business_id)->first()?->increment('monthly_points_available', 100);
            BusinessProfile::where('business_id', $userId)->first()?->increment('monthly_points_available', 100);
        }
    }

    private function generateCustomerID(int $userId, string $email): string
    {
        return 'CUST' . $userId . strtoupper(substr(md5($email), 0, 5));
    }

    private function generateBusinessID(int $userId, string $email): string
    {
        return 'BUS' . $userId . strtoupper(substr(md5($email), 0, 5));
    }

    private function generateAnonymousName(): string
    {
        $adjectives = ['Cool','Fast','Happy','Chill','Brave','Smart','Sneaky','Wild','Funky','Gentle', 'Zany','Quirky','Noble','Bouncy','Shiny','Clever','Bold','Goofy','Witty','Kind'];
        $animals    = ['Tiger','Lion','Panda','Eagle','Shark','Bear','Wolf','Otter','Falcon','Fox', 'Hawk','Cheetah','Panther','Whale','Dolphin','Rabbit','Koala','Turtle','Penguin','Giraffe'];

        do {
            $name = $adjectives[array_rand($adjectives)] . $animals[array_rand($animals)] . rand(100, 999);
        } while (CustomerProfile::where('anonymous_name', $name)->exists());

        return $name;
    }

    private function generateAnonymousImage(string $anonymousName): string
    {
        $avatarSvg      = Avatar::create($anonymousName)->toSvg();
        $avatarFileName = 'avatars/customers/' . $anonymousName . '.svg';
        Storage::disk('public')->put($avatarFileName, $avatarSvg);
        return asset('storage/' . $avatarFileName);
    }

    private function customerLoginResponse(User $user, string $token, string $label)
    {
        $user->load(['customerProfile', 'customerBadges.badge']);
        $profile = CustomerProfile::where('customer_id', $user->id)->first();

        return response()->json([
            'message'      => "$label registered successfully.",
            'token'        => $token,
            'user'         => $user,
            'customer_uid' => $profile?->customer_uid,
            'qr_code_url'  => $profile?->qr_code_hash,
        ]);
    }

    private function businessLoginResponse(User $user, string $token, string $label)
    {
        $user->load('businessProfile');
        $profile = BusinessProfile::where('business_id', $user->id)->first();

        return response()->json([
            'message'      => "$label registered successfully.",
            'token'        => $token,
            'user'         => $user,
            'business_uid' => $profile?->business_uid,
            'qr_code_url'  => $profile?->qr_code_hash,
        ]);
    }
}

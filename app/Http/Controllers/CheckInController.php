<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Import DB Facade
use App\Models\CheckIn;
use App\Models\CustomerBusinessRelationship;
use Illuminate\Support\Facades\Log;

class CheckInController extends Controller
{
    public function customerCheckIn(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required', // Encrypted QR data
            'business_id' => 'required|exists:business_profiles,business_id'
        ]);

        try {
            // Decrypt the QR Data
            $decryptedPayload = json_decode(Crypt::decryptString($validated['qr_data']), true);

            // Validate the payload structure
            if ($decryptedPayload['type'] !== 'customer_checkin') {
                throw new \Exception('Invalid QR Code type');
            }

            $customerId = $decryptedPayload['customer_id'];

            // Log Check-In
            CheckIn::create([
                'customer_id' => $customerId,
                'business_id' => $validated['business_id'],
                'check_in_time' => now()
            ]);

            // Update Interaction Count
            CustomerBusinessRelationship::updateOrCreate(
                ['customer_id' => $customerId, 'business_id' => $validated['business_id']],
                ['interaction_count' => DB::raw('interaction_count + 1')]
            );

            return response()->json(['message' => 'Check-in successful!'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }
    }

    public function businessCheckIn(Request $request)
    {
        $validated = $request->validate([
            'qr_data' => 'required', // Encrypted QR code data
            'customer_id' => 'required|exists:customer_profiles,customer_id'
        ]);

        try {
            // Decrypt QR payload
            $decryptedPayload = json_decode(Crypt::decryptString($validated['qr_data']), true);

            // Validate payload type
            if ($decryptedPayload['type'] !== 'business_checkin') {
                throw new \Exception('Invalid QR Code type');
            }

            $businessId = $decryptedPayload['business_id'];
            $customerId = $validated['customer_id'];

            // Log Check-In
            CheckIn::create([
                'customer_id' => $customerId,
                'business_id' => $businessId,
                'check_in_time' => now()
            ]);

            // Update customer-business relationship
            CustomerBusinessRelationship::updateOrCreate(
                ['customer_id' => $customerId, 'business_id' => $businessId],
                [
                    'interaction_count' => DB::raw('interaction_count + 1'),
                    'last_interaction_date' => now()
                ]
            );

            return response()->json(['message' => 'Customer check-in successful!'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid or tampered QR Code'], 400);
        }
    }

    public function checkInWithTextCode(Request $request)
    {
        $validated = $request->validate([
            'text_code' => 'required|string',
            'customer_id' => 'required_if:type,customer_checkin',
            'business_id' => 'required_if:type,business_checkin'
        ]);

        try {
            // Decode and decrypt the text code
            $encryptedPayload = base64_decode($validated['text_code']);
            $decryptedPayload = json_decode(Crypt::decryptString($encryptedPayload), true);

            if ($decryptedPayload['type'] === 'customer_checkin') {
                $customerId = $decryptedPayload['customer_id'];
                $businessId = $validated['business_id'];

                // Log Check-In for Customer
                CheckIn::create([
                    'customer_id' => $customerId,
                    'business_id' => $businessId,
                    'check_in_time' => now()
                ]);

            } elseif ($decryptedPayload['type'] === 'business_checkin') {
                $businessId = $decryptedPayload['business_id'];
                $customerId = $validated['customer_id'];

                // Log Check-In for Business
                CheckIn::create([
                    'customer_id' => $customerId,
                    'business_id' => $businessId,
                    'check_in_time' => now()
                ]);
            }

            return response()->json(['message' => 'Check-in successful!'], 200);

        } catch (\Exception $e) {
            return response()->json(['message' => 'Invalid text code'], 400);
        }
    }

    public function checkIn(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customer_profiles,customer_id',
            'business_id' => 'required|exists:business_profiles,business_id',
        ]);

        $customerId = $validated['customer_id'];
        $businessId = $validated['business_id'];

        try {
            // Fetch business and validate points availability
            $business = DB::table('business_profiles')->where('business_id', $businessId)->first();

            if ($business->points_per_checkin > $business->monthly_points_available) {
                return response()->json(['error' => 'Business does not have enough points available.'], 400);
            }

            // Fetch or create customer-business relationship
            $relationship = DB::table('customer_business_relationships')
                ->where('customer_id', $customerId)
                ->where('business_id', $businessId)
                ->first();

            if (!$relationship) {
                // Create new relationship
                DB::table('customer_business_relationships')->insert([
                    'customer_id' => $customerId,
                    'business_id' => $businessId,
                    'interaction_count' => 1,
                    'last_interaction_date' => now(),
                    'total_points_earned' => $business->points_per_checkin,
                    'customer_tier' => 'new', // Default to 'new'
                    'created_at' => now(),
                ]);
            } else {
                // Update existing relationship
                DB::table('customer_business_relationships')
                    ->where('relationship_id', $relationship->relationship_id)
                    ->update([
                        'interaction_count' => $relationship->interaction_count + 1,
                        'last_interaction_date' => now(),
                        'total_points_earned' => $relationship->total_points_earned + $business->points_per_checkin,
                        'customer_tier' => $this->determineCustomerTier($relationship->interaction_count + 1),
                    ]);
            }

            // Insert points transaction
            DB::table('points')->insert([
                'customer_id' => $customerId,
                'business_id' => $businessId,
                'points_earned' => $business->points_per_checkin,
                'description' => 'Check-in reward',
                'created_at' => now(),
            ]);

            // Update points_balance in customer_profiles
            DB::table('customer_profiles')
                ->where('customer_id', $customerId)
                ->increment('points_balance', $business->points_per_checkin);

            // Update total_rewards_earned in customer_profiles
            DB::table('customer_profiles')
                ->where('customer_id', $customerId)
                ->increment('total_rewards_earned', $business->points_per_checkin);

            // Update business metrics
            DB::table('business_profiles')
                ->where('business_id', $businessId)
                ->update([
                    'monthly_points_available' => $business->monthly_points_available - $business->points_per_checkin,
                    'points_given' => $business->points_given + $business->points_per_checkin,
                    'total_checkins' => $business->total_checkins + 1,
                ]);

            // Update customer business interaction
            $interaction = DB::table('customer_business_interactions')
                ->where('customer_id', $customerId)
                ->where('business_id', $businessId)
                ->where('interaction_type', 'check_in')
                ->first();

            if ($interaction) {
                DB::table('customer_business_interactions')
                    ->where('interaction_id', $interaction->interaction_id)
                    ->update([
                        'interaction_count' => $interaction->interaction_count + 1,
                        'last_interaction_date' => now(),
                    ]);
            } else {
                DB::table('customer_business_interactions')->insert([
                    'customer_id' => $customerId,
                    'business_id' => $businessId,
                    'interaction_type' => 'check_in',
                    'points_allocated' => $business->points_per_checkin,
                    'interaction_count' => 1,
                    'last_interaction_date' => now(),
                    'created_at' => now(),
                ]);
            }

            return response()->json(['message' => 'Check-in successful.'], 200);
        } catch (\Exception $e) {
            Log::error('Error during check-in: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to process check-in.'], 500);
        }
    }

    private function determineCustomerTier($interactionCount)
    {
        if ($interactionCount >= 10) {
            return 'regular';
        } elseif ($interactionCount >= 3) {
            return 'repeat';
        } else {
            return 'new';
        }
    }
}

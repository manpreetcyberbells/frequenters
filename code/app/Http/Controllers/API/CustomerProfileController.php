<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CustomerAddress;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Traits\ManagesCurrentAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerProfileController extends Controller
{
    use ManagesCurrentAddress;

    // Profile
    public function updateProfile(Request $request)
    {
        try {
            $request->validate([
                'full_name'     => 'required|string|max:255',
                'email'         => 'required|email|unique:users,email,' . Auth::id(),
                'phone_number'  => 'nullable|string|max:15|unique:users,phone,' . Auth::id(),
                'gender'        => 'nullable|in:male,female,other',
                'dob'           => 'nullable|date|before:today',
                'anniversary'   => 'nullable|date|after:dob',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            ]);

            $user = Auth::user();

            if ($request->hasFile('profile_image')) {
                $image     = $request->file('profile_image');
                $imageName = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                $image->storeAs('profile_images', $imageName);
                $user->profile_image = asset('storage/profile_images/' . $imageName);
            }

            $user->update([
                'name'   => $request->full_name,
                'email'  => $request->email,
                'phone'  => $request->phone_number,
                'gender' => $request->gender,
            ]);

            CustomerProfile::updateOrCreate(
                ['customer_id' => $user->id],
                ['birthday' => $request->dob, 'anniversary' => $request->anniversary]
            );

            return response()->json([
                'message' => 'Profile updated successfully',
                'user'    => $user->load('customerProfile'),
            ]);

        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    public function updateStatus(int $user_id, Request $request)
    {
        try {
            $user = User::findOrFail($user_id);

            $validated = $request->validate([
                'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
            ]);

            if ($user->status === $validated['status']) {
                return response()->json(['message' => 'User status is already ' . $user->status], 400);
            }

            $user->update(['status' => $validated['status']]);

            return response()->json(['message' => 'User status updated successfully', 'user' => $user]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

    // Settings (push, location, language, personal info)
    public function pushNotificationUpdate(Request $request)
    {
        $request->validate([
            'enabled'   => 'required|boolean',
            'fcm_token' => 'required|string|max:255',
        ]);

        return $this->updateCustomerProfileFields(Auth::id(), [
            'fcm_token'          => $request->fcm_token,
            'push_notification'  => $request->boolean('enabled') ? '1' : '0',
        ], 'Push notification setting updated successfully', 'push_notification');
    }

    public function updateLocation(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);

        return $this->updateCustomerProfileFields(Auth::id(), [
            'location' => $request->boolean('enabled') ? '1' : '0',
        ], 'Location updated successfully', 'location');
    }

    public function updateCustomerLanguage(Request $request)
    {
        $request->validate(['language' => 'required|in:en,es']);

        return $this->updateCustomerProfileFields(Auth::id(), [
            'preferred_language' => $request->language,
        ], 'Language updated successfully', 'preferred_language');
    }

    public function allowPersonalInformation(Request $request)
    {
        $request->validate(['enabled' => 'required|boolean']);

        return $this->updateCustomerProfileFields(Auth::id(), [
            'show_personal_info' => $request->boolean('enabled') ? '1' : '0',
        ], 'Personal information setting updated successfully', 'show_personal_info');
    }

    // Address
    public function createCustomerAddress(Request $request)
    {
        try {
            $validated = $this->validateAddressRequest($request);
            $isCurrent = (bool) ($validated['is_current'] ?? false);

            // Trait handles duplicate is_current logic
            $this->unsetCurrentAddressIfNeeded(Auth::id(), $isCurrent);

            $address = CustomerAddress::create(array_merge(
                $validated,
                ['customer_id' => Auth::id(), 'radius' => $validated['radius'] ?? 5, 'is_current' => $isCurrent]
            ));

            return response()->json(['message' => 'Customer address saved successfully', 'address' => $address]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while saving address', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateCustomerAddress(Request $request, int $id)
    {
        try {
            $address = CustomerAddress::where('id', $id)->where('customer_id', Auth::id())->firstOrFail();
            $validated  = $this->validateAddressRequest($request);
            $isCurrent  = (bool) ($validated['is_current'] ?? false);

            $this->unsetCurrentAddressIfNeeded(Auth::id(), $isCurrent);

            $address->update($validated);

            return response()->json(['message' => 'Customer address updated successfully', 'address' => $address]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating address', 'error' => $e->getMessage()], 500);
        }
    }

    public function makeCurrentAddress(Request $request, int $id)
    {
        try {
            $address   = CustomerAddress::where('id', $id)->where('customer_id', Auth::id())->firstOrFail();
            $validated = $request->validate(['is_current' => 'nullable|boolean']);
            $isCurrent = (bool) ($validated['is_current'] ?? false);

            $this->unsetCurrentAddressIfNeeded(Auth::id(), $isCurrent);

            $address->update(['is_current' => $isCurrent]);

            return response()->json(['message' => 'Current address updated successfully', 'address' => $address]);

        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while updating address', 'error' => $e->getMessage()], 500);
        }
    }

    public function deleteCustomerAddress(int $id)
    {
        try {
            $address = CustomerAddress::where('id', $id)->where('customer_id', Auth::id())->firstOrFail();
            $address->delete();
            return response()->json(['message' => 'Customer address deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting address', 'error' => $e->getMessage()], 500);
        }
    }

    public function getCustomerAddress()
    {
        return response()->json([
            'message' => 'Customer addresses retrieved successfully',
            'address' => CustomerAddress::where('customer_id', Auth::id())->get(),
        ]);
    }

    // Helpers

    /**
     * Generic customer profile field updater — avoids 4 near-identical try/catch blocks
     */
    private function updateCustomerProfileFields(int $userId, array $fields, string $successMsg, string $returnField)
    {
        try {
            $profile = CustomerProfile::where('customer_id', $userId)->firstOrFail();
            $profile->update($fields);

            return response()->json([
                'message'      => $successMsg,
                $returnField   => $profile->$returnField,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }

    private function validateAddressRequest(Request $request): array
    {
        return $request->validate([
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city'          => 'nullable|string|max:255',
            'state'         => 'nullable|string|max:255',
            'country'       => 'nullable|string|max:255',
            'zip_code'      => 'nullable|string|max:20',
            'radius'        => 'nullable|numeric|min:1|max:100',
            'latitude'      => 'nullable|string|max:20',
            'longitude'     => 'nullable|string|max:20',
            'is_current'    => 'nullable|boolean',
        ]);
    }
}

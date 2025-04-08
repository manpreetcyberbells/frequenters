<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller; // Base controller
use Illuminate\Support\Facades\Auth; // Import Auth Facade
use App\Models\User;
use App\Models\CustomerProfile;
use App\Models\CustomerAddress;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class CustomerProfileController extends Controller
{
    /**
     *  API Function to update user profile. 
    */
    public function updateProfile(Request $request)
    {
        // echo"<pre>"; print_r($request->all()); die;
        try {
            $request->validate([
                'full_name'    => 'required|string|max:255',
                'email'        => 'required|email|unique:users,email,' . Auth::id(),
                'phone_number' => 'nullable|string|max:15|unique:users,phone,' . Auth::id(),
                'gender'       => 'nullable|in:male,female,other',
                'dob'          => 'nullable|date|before:today',
                'anniversary'  => 'nullable|date|after:dob',
                'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048', // Max size 2MB
            ]);

            $user = Auth::user();
            $oldImage = $user->profile_image;

            // Handle profile image upload
            if ($request->hasFile('profile_image')) {
                $image = $request->file('profile_image');
                $imageName = 'profile_' . $user->id . '_' . time() . '.' . $image->getClientOriginalExtension();
                $imagePath = $image->storeAs('profile_images', $imageName);
                $imageUrl = asset('storage/profile_images/' . $imageName);
                \Log::info('Image Path: ' . $imagePath);
                $user->profile_image = $imageUrl;
            }

            $user->update([
                'name'    => $request->full_name,
                'email'        => $request->email,
                'phone' => $request->phone_number,
                'gender'       => $request->gender
            ]);

            // Update or create customer profile
            $customerProfile = CustomerProfile::updateOrCreate(
                ['customer_id' => $user->id],
                [
                    'birthday' => $request->dob,
                    'anniversary' => $request->anniversary,
                ]
            );

            return response()->json(['message' => 'Profile updated successfully', 'user' => $user->load('customerProfile')], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'details' => $e->errors()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'details' => $e->getMessage()], 500);
        }
    }

    /**
     * API Function for Update User status
     * 
    */
    public function updateStatus($user_id, Request $request)
    {
        try{
            $user = User::find($user_id);
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $validatedData = $request->validate([
                'status' => ['required', Rule::in(['pending', 'active', 'suspended'])],
            ]);

            // Check if status is actually changing
            if ($user->status === $validatedData['status']) {
                return response()->json(['message' => 'User status is already ' . $user->status], 400);
            }

            $user->status = $validatedData['status'];
            $user->save();

            return response()->json([
                'message' => 'User status updated successfully',
                'user' => $user
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     *  API Function for update Push Notification status of customer
    */
    public function pushNotificationUpdate(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $user = Auth::user();
            $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();
            if (!$customerProfile) {
                return response()->json([
                    'error' => 'Customer profile not found',
                ], 404);
            }
            $customerProfile->push_notification = $request->enabled ? '1' : '0';
            $customerProfile->save();

            return response()->json([
                'message' => 'Push notification setting updated successfully',
                'push_notification' => $customerProfile->push_notification,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update push notification setting',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for update Location 
    */
    public function updateLocation(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $user = Auth::user();
            $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();
            if (!$customerProfile) {
                return response()->json([
                    'error' => 'Customer profile not found',
                ], 404);
            }
            $customerProfile->location = $request->enabled ? '1' : '0';
            $customerProfile->save();

            return response()->json([
                'message' => 'Location updated successfully',
                'location' => $customerProfile->location,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update customer location',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for update Language 
    */
    public function updateCustomerLanguage(Request $request)
    {
        $request->validate([
            'language' => 'required',
        ]);

        try {
            $user = Auth::user();
            $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();
            if (!$customerProfile) {
                return response()->json([
                    'error' => 'Customer profile not found',
                ], 404);
            }
            $customerProfile->preferred_language = $request->language;
            $customerProfile->save();

            return response()->json([
                'message' => 'Language updated successfully',
                'language' => $customerProfile->preferred_language,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update customer location',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function Allow // Not Allow Show Personal Information 
    */
    public function allowPersonalInformation(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        try {
            $user = Auth::user();
            $customerProfile = CustomerProfile::where('customer_id', $user->id)->first();
            if (!$customerProfile) {
                return response()->json([
                    'error' => 'Customer profile not found',
                ], 404);
            }
            $customerProfile->show_personal_info = $request->enabled ? '1' : '0';
            $customerProfile->save();

            return response()->json([
                'message' => 'Personal information setting updated successfully',
                'show_personal_info' => $customerProfile->show_personal_info,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update Personal information setting',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for Create Customer Address 
    */
    public function createCustomerAddress(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $validated = $request->validate([
                'address_line1' => 'nullable|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:20',
                'radius' => 'nullable|numeric|min:1|max:100',
                'latitude' => 'nullable|string|max:20',
                'longitude' => 'nullable|string|max:20',
            ]);

            $customerAddress = CustomerAddress::create([
                'customer_id' => $user->id,
                'address_line1' => $validated['address_line1'] ?? null,
                'address_line2' => $validated['address_line2'] ?? null,
                'city' => $validated['city'] ?? null,
                'state' => $validated['state'] ?? null,
                'country' => $validated['country'] ?? null,
                'zip_code' => $validated['zip_code'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'radius' => $validated['radius'] ?? 5,
            ]);

            return response()->json([
                'message' => 'Customer address saved successfully',
                'address' => $customerAddress,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while saving address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for Update Customer Address 
    */
    public function updateCustomerAddress(Request $request, $id)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $customerAddress = CustomerAddress::where('id', $id)->where('customer_id', $user->id)->first();
            if (!$customerAddress) {
                return response()->json(['message' => 'Address not found'], 404);
            }

            $validated = $request->validate([
                'address_line1' => 'nullable|string|max:255',
                'address_line2' => 'nullable|string|max:255',
                'city' => 'nullable|string|max:255',
                'state' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'zip_code' => 'nullable|string|max:20',
                'radius' => 'nullable|numeric|min:1|max:100',
                'latitude' => 'nullable|string|max:20',
                'longitude' => 'nullable|string|max:20',
            ]);

            $customerAddress->update($validated);
            return response()->json([
                'message' => 'Customer address updated successfully',
                'address' => $customerAddress,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for Delete Customer Address 
    */
    public function deleteCustomerAddress($id)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $customerAddress = CustomerAddress::where('id', $id)->where('customer_id', $user->id)->first();
            if (!$customerAddress) {
                return response()->json(['message' => 'Address not found'], 404);
            }
            $customerAddress->delete();

            return response()->json(['message' => 'Customer address soft deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     *  API Function for Get Customer Address 
    */
    public function getCustomerAddress(Request $request)
    {
        try {
            $user = auth()->user();
            if (!$user) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $customerAddress = CustomerAddress::where('customer_id', $user->id)->get();

            return response()->json([
                'message' => 'Customer address getting successfully',
                'address' => $customerAddress,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while getting address',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\City;
use App\Models\Zip;
use Illuminate\Http\JsonResponse;

class CityController extends Controller
{
    // Fetch all city details
    public function getCityDetails()
    {
        $cities = City::all();
        return response()->json([
            'status' => true,
            'data' => $cities
        ], 200);
    }

    // Fetch states
    public function getStates()
    {
        $states = City::select('state')->distinct()->orderBy('state', 'asc')->get();
        return response()->json([
            'status' => true,
            'data' => $states
        ], 200);
    }

    // Fetch countries
    public function getCountries()
    {
        $countries = City::select('country')->distinct()->orderBy('state', 'asc')->get();
        return response()->json([
            'status' => true,
            'data' => $countries
        ], 200);
    }

    // Fetch cities by state or country (For dependent dropdown)
    public function getCitiesByStateOrCountry(Request $request)
    {
        $query = City::query();

        if ($request->has('state')) {
            $query->orderBy('city_name', 'asc')->where('state', $request->state);
        }

        if ($request->has('country')) {
            $query->orderBy('country', 'asc')->where('country', $request->country);
        }

        $cities = $query->get();

        return response()->json([
            'status' => true,
            'data' => $cities
        ], 200);
    }

    // API Function provide list of cities based on given zip_code
    public function getCitiesByZip($zip_code): JsonResponse
    {
        $zip = Zip::where('zip_code', $zip_code)->with('city')->get();
        if ($zip->isEmpty()) {
            return response()->json(['message' => 'No cities found for this zip code'], 404);
        }
        return response()->json($zip->map(function ($z) {
            return [
                'id' => $z->city->id,
                'name' => $z->city->city_name
            ];
        }));
    }

    // API Function return country, state, latitude, and longitude
    public function getStateAndCoordinates($city_id): JsonResponse
    {
        $city = City::find($city_id);
        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }
        return response()->json([
            'state' => $city->state,
            'state_code' => $city->state_code,
            'latitude' => $city->latitude,
            'longitude' => $city->longitude,
            'country' => $city->country,
        ]);
    }

    // API Function provide list of zip_code
    public function getZipCodes(Request $request): JsonResponse
    {
        try {
            // Get the search query from request
            $search = $request->query('zip_code');

            // Query zip codes with LIKE condition & paginate
            $zipCodes = Zip::when($search, function ($query, $search) {
                return $query->where('zip_code', 'like', "$search%");
            })->paginate(100);

            return response()->json([
                'status' => true,
                'message' => 'Zip codes retrieved successfully',
                'data' => $zipCodes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error retrieving zip codes',
                'error' => $e->getMessage()
            ], 500);
        }
    }


}

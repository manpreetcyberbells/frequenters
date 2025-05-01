<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class NearbyCustomerService
{
    /**
     * Get nearby unique customer IDs based on latitude, longitude and radius (km).
     */
    public function getNearbyCustomerIds(float $latitude, float $longitude, float $radius = 10): Collection
    {
        $customerAddresses = DB::table('customer_addresses')
            ->select('id', 'customer_id', 'latitude', 'longitude', 'is_current')
            ->orderBy('is_current', 'desc') // Prefer current addresses
            ->orderBy('id')                 // Then pick the oldest
            ->get()
            ->groupBy('customer_id')
            ->map(function ($addresses) {
                return $addresses->first();
            });

        $nearbyCustomerIds = collect();

        foreach ($customerAddresses as $address) {
            if (is_null($address->latitude) || is_null($address->longitude)) {
                continue;
            }

            $distance = $this->calculateDistance($latitude, $longitude, $address->latitude, $address->longitude);

            if ($distance < $radius) {
                $nearbyCustomerIds->push($address->customer_id);
            }
        }

        return $nearbyCustomerIds->unique()->values();
    }

    /**
     * Haversine formula to calculate distance between two lat/lng points.
    */
    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // in kilometers
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }
}

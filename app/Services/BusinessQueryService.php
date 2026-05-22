<?php

namespace App\Services;

use App\Models\BusinessAddress;
use App\Models\Menu;
use App\Models\Service;
use Illuminate\Support\Facades\DB;

class BusinessQueryService
{
    /**
     * Nearby, highestReward, frequentlyVisited.
     */
    private function baseSelect(int $customerId, float $lat, float $lng): array
    {
        return [
            "business_addresses.id AS business_address_id,
            business_addresses.business_id,
            business_addresses.address_line1,
            business_addresses.address_line2,
            business_addresses.city,
            business_addresses.state,
            business_addresses.country,
            business_addresses.zip_code,
            business_addresses.latitude,
            business_addresses.longitude,
            users.id AS user_id,
            users.status AS business_status,
            business_profiles.id AS business_profile_id,
            business_profiles.business_name,
            business_profiles.description,
            business_profiles.website,
            business_profiles.qr_code_hash,
            business_profiles.points_given,
            business_profiles.monthly_points_available,
            business_profiles.points_redeemed,
            business_profiles.points_per_checkin,
            business_profiles.conversion_rate,
            business_profiles.total_checkins,
            business_profiles.total_rewards_redeemed,
            business_profiles.logo,
            business_profiles.owner_name,
            business_profiles.owner_email,
            business_profiles.owner_phone,
            business_profiles.category_id,
            business_profiles.child_category_id,
            parent_category.name AS category_name,
            child_category.name AS child_category_name,
            (SELECT GROUP_CONCAT(business_images.photo)
                FROM business_images
                WHERE business_images.business_id = users.id) AS images,
            (SELECT GROUP_CONCAT(
                CONCAT(day_of_week, '|', open_time, '|', close_time, '|', is_closed)
            ) FROM business_hours
            WHERE business_hours.business_id = users.id) AS business_hours,
            EXISTS (
                SELECT 1 FROM wishlists
                WHERE wishlists.business_id = users.id
                AND wishlists.customer_id = ?
            ) AS is_wishlist,
            (SELECT COUNT(*) FROM reviews WHERE reviews.business_id = users.id) AS total_reviews,
            (SELECT COALESCE(AVG(reviews.rating), 0) FROM reviews WHERE reviews.business_id = users.id) AS average_rating,
            ROUND((6371 * acos(
                LEAST(1.0, 
                    cos(radians(?)) * cos(radians(business_addresses.latitude))
                    * cos(radians(business_addresses.longitude) - radians(?))
                    + sin(radians(?)) * sin(radians(business_addresses.latitude))
                )
            )), 2) AS distance",
            [$customerId, $lat, $lng, $lat],
        ];
    }

    /**
     * Base query builder with joins applied.
     */
    private function baseQuery(int $customerId, float $lat, float $lng, ?int $categoryId)
    {
        [$columns, $bindings] = $this->baseSelect($customerId, $lat, $lng);

        $query = BusinessAddress::selectRaw($columns, $bindings)
            ->join('users', 'users.id', '=', 'business_addresses.business_id')
            ->leftJoin('business_profiles', 'business_profiles.business_id', '=', 'users.id')
            ->leftJoin('categories AS parent_category', 'parent_category.id', '=', 'business_profiles.category_id')
            ->leftJoin('categories AS child_category', 'child_category.id', '=', 'business_profiles.child_category_id');

        if ($categoryId) {
            $query->where('business_profiles.category_id', $categoryId);
        }

        return $query;
    }

    /**
     * bulk-fetch has_menu and has_service for all business IDs at once.
     */
    private function attachMenuAndServiceFlags($businesses)
    {
        $userIds = $businesses->getCollection()->pluck('user_id')->unique()->toArray();

        $hasMenuIds    = Menu::whereIn('business_id', $userIds)->pluck('business_id')->flip();
        $hasServiceIds = Service::whereIn('business_id', $userIds)->pluck('business_id')->flip();

        $businesses->getCollection()->transform(function ($business) use ($hasMenuIds, $hasServiceIds) {
            $business->has_menu    = isset($hasMenuIds[$business->user_id]);
            $business->has_service = isset($hasServiceIds[$business->user_id]);
            return $business;
        });

        return $businesses;
    }

    // Public Methods
    public function nearbyBusinessesOLDV1(int $customerId, float $lat, float $lng, ?int $categoryId)
    {
        $result = $this->baseQuery($customerId, $lat, $lng, $categoryId)
            ->having('distance', '<=', 10000)
            ->orderByDesc('distance')
            ->paginate(10);

        return $this->attachMenuAndServiceFlags($result);
    }

    public function nearbyBusinesses(int $customerId, float $lat, float $lng, ?int $categoryId)
    {
        $result = $this->baseQuery($customerId, $lat, $lng, $categoryId)->where('users.status', 'active')->having('distance', '<=', 10)->orderBy('distance', 'ASC')->paginate(10);
        return $this->attachMenuAndServiceFlags($result);
    }

    public function highestRewardBusinesses(int $customerId, float $lat, float $lng, ?int $categoryId)
    {
        $result = $this->baseQuery($customerId, $lat, $lng, $categoryId)
            ->orderByDesc('business_profiles.points_per_checkin')
            ->orderBy('distance', 'ASC')
            ->paginate(10);

        return $this->attachMenuAndServiceFlags($result);
    }

    public function frequentlyVisited(int $customerId, float $lat, float $lng, ?int $categoryId)
    {
        $result = $this->baseQuery($customerId, $lat, $lng, $categoryId)
            ->join('customer_business_relationships AS cbr', function ($join) use ($customerId) {
                $join->on('cbr.business_id', '=', 'users.id')->where('cbr.customer_id', '=', $customerId);
            })
            ->addSelect(DB::raw('cbr.customer_tier'))
            ->where('cbr.customer_tier', 'daily')
            ->orderBy('distance', 'ASC')
            ->paginate(10);

        return $this->attachMenuAndServiceFlags($result);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureBusinessAccess
{
    /**
     * Allows business owners AND their staff (manager/employee)
     * to access endpoints that operate on the business context.
     *
     * Usage:
     *   Route::middleware(['auth:sanctum', 'business.access'])
     *   Route::middleware(['auth:sanctum', 'business.access:owner_only'])
     */
    public function handle(Request $request, Closure $next, string $mode = 'any')
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($mode === 'owner_only') {
            if (!$user->isBusinessOwner()) {
                return response()->json([
                    'message' => 'This action is restricted to the business owner.',
                ], 403);
            }
        } else {
            if (!$user->canActAsBusiness()) {
                return response()->json([
                    'message' => 'Business access required.',
                ], 403);
            }
        }

        if (!$user->effectiveBusinessId()) {
            return response()->json([
                'message' => 'No business account linked to this user.',
            ], 403);
        }

        // Attach for easy controller access
        $request->attributes->set('business_id', $user->effectiveBusinessId());

        return $next($request);
    }
}
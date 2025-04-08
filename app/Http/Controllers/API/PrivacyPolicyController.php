<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PrivacyPolicy;

class PrivacyPolicyController extends Controller
{
    public function show(Request $request)
    {
        $audience = $request->query('audience'); // customer, business, both

        $policy = PrivacyPolicy::where('is_active', true)
                    ->when($audience, function ($query) use ($audience) {
                        $query->whereIn('audience', [$audience, 'both']);
                    })
                    ->latest()
                    ->first();

        return response()->json([
            'status' => true,
            'data' => $policy
        ]);
    }
}

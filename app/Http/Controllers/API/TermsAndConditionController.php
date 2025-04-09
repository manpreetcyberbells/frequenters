<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TermsAndCondition;
use Illuminate\Http\Request;

class TermsAndConditionController extends Controller
{
    public function show(Request $request)
    {
        $audience = $request->query('audience');

        $terms = TermsAndCondition::where('is_active', true)
                    ->when($audience, function ($query) use ($audience) {
                        $query->whereIn('audience', [$audience, 'both']);
                    })
                    ->latest()
                    ->first();

        return response()->json([
            'status' => true,
            'data' => $terms
        ]);
    }
}

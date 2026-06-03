<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Faq;

class FaqController extends Controller
{
    public function index(Request $request)
    {
        $audience = $request->query('audience'); // 'customer', 'business', or 'both'
        $faqs = Faq::where('is_active', true)
                    ->when($audience, function ($query) use ($audience) {
                        $query->whereIn('audience', [$audience, 'both']);
                    })
                    ->orderBy('id', 'asc')
                    ->get();

        return response()->json([
            'status' => true,
            'data' => $faqs
        ]);
    }
}

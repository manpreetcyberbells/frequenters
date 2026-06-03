<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CustomerSuggestion;
use Illuminate\Support\Facades\Mail;
use App\Mail\SuggestionMail;

class SuggestionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'suggestion' => 'required|string|max:1000',
        ]);

        $suggestion = CustomerSuggestion::create([
            'customer_id' => auth()->id(),
            'suggestion' => $request->suggestion,
        ]);

        // Send email to admin
        Mail::to('admin123321@yopmail.com')->send(new SuggestionMail($suggestion));

        return response()->json([
            'status' => true,
            'message' => 'Suggestion submitted successfully.',
        ]);
    }

}

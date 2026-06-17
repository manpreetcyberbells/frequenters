<?php

namespace App\Http\Controllers\Connect\Business;

use App\Http\Controllers\Controller;
use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectMessageTemplate;
use Illuminate\Http\Request;

class ConnectStatusController extends Controller
{
    /**
     * GET /business/connect
     *
     * Read-only status page for V1. Resolve the business from the logged-in
     * Frequenters user. Replace the resolution logic below with your real
     * auth/business association.
     */
    public function show(Request $request)
    {
        // =====================================================================
        // FREQUENTERS INTEGRATION POINT — authenticated user -> business_id
        // ---------------------------------------------------------------------
        // Replace the line below with however your app links a logged-in
        // Frequenters user to their business_id (a column, a pivot table, a
        // relationship, etc.). Until this is wired, the status page will show
        // the "not set up yet" state for everyone.
        //
        // Grep the codebase for "FREQUENTERS INTEGRATION POINT" to find every
        // spot that must be wired before launch.
        // =====================================================================
        $businessId = $request->user()->effectiveBusinessId();

        $business = $businessId
            ? ConnectBusinessSetting::where('business_id', $businessId)->first()
            : null;

        $sampleTemplate = $business
            ? ConnectMessageTemplate::where('business_id', $business->business_id)
                ->where('template_type', 'missed_call_open')
                ->value('body')
            : null;

        return view('connect.business.status', [
            'business'       => $business,
            'sampleTemplate' => $sampleTemplate,
        ]);
    }
}

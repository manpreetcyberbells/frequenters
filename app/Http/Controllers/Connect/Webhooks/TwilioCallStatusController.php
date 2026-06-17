<?php

namespace App\Http\Controllers\Connect\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Connect\ConnectCallLog;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TwilioCallStatusController extends Controller
{
    /**
     * POST /webhooks/frequenters-connect/twilio/call-status
     *
     * Optional. Stores Twilio call status callbacks against the call log.
     */
    public function __invoke(Request $request): Response
    {
        $sid    = $request->input('CallSid');
        $status = $request->input('CallStatus');

        if ($sid) {
            ConnectCallLog::where('twilio_call_sid', $sid)
                ->latest('id')
                ->first()
                ?->update(['call_status' => $status]);
        }

        return response('<Response></Response>', 200, ['Content-Type' => 'text/xml']);
    }
}

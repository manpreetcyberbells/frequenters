<?php

namespace App\Http\Controllers\Connect\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Connect\ConnectCallLog;
use App\Models\Connect\ConnectCustomer;
use App\Services\Connect\ConnectCommunicationService;
use App\Services\Connect\ConnectRoutingService;
use App\Support\Connect\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TwilioCallController extends Controller
{
    public function __construct(
        protected ConnectRoutingService $routing,
        protected ConnectCommunicationService $comms,
    ) {}

    /**
     * POST /webhooks/frequenters-connect/twilio/call
     *
     * A forwarded (missed/busy/unavailable) call has reached the business's
     * assigned Twilio number. We send the auto-text and respond with TwiML.
     */
    public function __invoke(Request $request): Response
    {
        $from    = PhoneNumber::toE164($request->input('From'));
        $to      = PhoneNumber::toE164($request->input('To'));
        $callSid = $request->input('CallSid');
        $status  = $request->input('CallStatus');

        $business = $this->routing->identifyBusinessByTwilioNumber($to);

        // Unknown number -> politely hang up, log nothing we cannot attribute.
        if (! $business) {
            return $this->twiml('<Response><Hangup/></Response>');
        }

        $log = ConnectCallLog::create([
            'business_id'     => $business->business_id,
            'from_number'     => $from,
            'to_number'       => $to,
            'twilio_call_sid' => $callSid,
            'call_status'     => $status,
            'auto_text_sent'  => false,
        ]);

        $skip = $this->skipReason($business, $from);

        if ($skip !== null) {
            $log->update(['auto_text_skipped_reason' => $skip]);
            return $this->twiml($this->voicePrompt($skip));
        }

        // Create customer + conversation so a reply lands in the same thread.
        $customer     = $this->routing->findOrCreateCustomer($business, $from);
        $conversation = $this->routing->findOrCreateConversation($business, $customer);
        $conversation->update(['started_from_call_log_id' => $log->id]);

        $autoText = $this->comms->sendAutoText($business, $from, $conversation->id);

        // Only flag the call as texted if the SMS did not fail. A failed send
        // is recorded like a skip so admins can see it in the logs, and the
        // voice prompt must not promise a text that never went out.
        if ($autoText->status === 'failed') {
            $log->update([
                'auto_text_sent'           => false,
                'auto_text_skipped_reason' => 'sms_failed',
            ]);

            return $this->twiml($this->voicePrompt('sms_failed'));
        }

        $log->update(['auto_text_sent' => true]);

        return $this->twiml($this->voicePrompt(null));
    }

    /**
     * Returns a skip reason string if the auto-text should NOT be sent,
     * or null if it should be sent.
     */
    protected function skipReason($business, ?string $from): ?string
    {
        if (! $business->isActive())          return 'business_inactive';
        if (! $business->connect_enabled)     return 'connect_disabled';
        if (! $business->auto_text_enabled)   return 'auto_text_disabled';
        if ($business->pause_mode_enabled)    return 'paused';

        // Honour opt-out.
        $customer = ConnectCustomer::where('business_id', $business->business_id)
            ->where('customer_phone', PhoneNumber::toE164($from))
            ->first();
        if ($customer && $customer->opted_out) return 'opted_out';

        // Duplicate prevention.
        $windowHours = $business->duplicate_text_window_hours
            ?: (int) config('connect.defaults.duplicate_window_hours');

        $recentlyTexted = ConnectCallLog::where('business_id', $business->business_id)
            ->where('from_number', PhoneNumber::toE164($from))
            ->where('auto_text_sent', true)
            ->where('created_at', '>=', now()->subHours($windowHours))
            ->exists();

        return $recentlyTexted ? 'duplicate' : null;
    }

    protected function voicePrompt(?string $skipReason): string
    {
        // Only promise a text when an auto-text is actually being sent.
        // When it is skipped (paused, opted out, duplicate, disabled, etc.)
        // we must NOT tell the caller to expect a message.
        if ($skipReason === null) {
            $say = "Thanks for calling. We'll send you a text message shortly so we can help.";
        } else {
            $say = "Thanks for calling. Sorry we couldn't take your call right now. Please try again later.";
        }

        return '<Response><Say voice="alice">' . $say . '</Say><Hangup/></Response>';
    }

    protected function twiml(string $xml): Response
    {
        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}

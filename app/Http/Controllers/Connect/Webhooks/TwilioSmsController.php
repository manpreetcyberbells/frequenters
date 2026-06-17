<?php

namespace App\Http\Controllers\Connect\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectConversation;
use App\Models\Connect\ConnectCustomer;
use App\Models\Connect\ConnectSmsMessage;
use App\Services\Connect\ConnectCommandService;
use App\Services\Connect\ConnectCommunicationService;
use App\Services\Connect\ConnectOptOutService;
use App\Services\Connect\ConnectRoutingService;
use App\Services\Connect\ConnectTemplateService;
use App\Support\Connect\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TwilioSmsController extends Controller
{
    public function __construct(
        protected ConnectRoutingService $routing,
        protected ConnectCommunicationService $comms,
        protected ConnectCommandService $commands,
        protected ConnectOptOutService $optOut,
        protected ConnectTemplateService $templates,
    ) {}

    /**
     * POST /webhooks/frequenters-connect/twilio/sms
     *
     * Handles BOTH customer replies and owner replies. We distinguish them
     * by checking whether "From" matches the business owner's cell phone.
     */
    public function __invoke(Request $request): Response
    {
        $from = PhoneNumber::toE164($request->input('From'));
        $to   = PhoneNumber::toE164($request->input('To'));
        $body = trim((string) $request->input('Body', ''));
        $sid  = $request->input('MessageSid');

        $business = $this->routing->identifyBusinessByTwilioNumber($to);

        if (! $business || ! $business->isActive() || ! $business->connect_enabled) {
            return $this->emptyTwiml();
        }

        // Is this the owner texting the relay number?
        if (PhoneNumber::matches($business->owner_cell_phone, $from)) {
            return $this->handleOwner($business, $from, $to, $body, $sid);
        }

        return $this->handleCustomer($business, $from, $to, $body, $sid);
    }

    // ---------------------------------------------------------------------
    // CUSTOMER -> SYSTEM
    // ---------------------------------------------------------------------
    protected function handleCustomer(ConnectBusinessSetting $business, string $from, string $to, string $body, ?string $sid): Response
    {
        $customer     = $this->routing->findOrCreateCustomer($business, $from);
        $conversation = $this->routing->findOrCreateConversation($business, $customer);

        // Always persist the inbound message first.
        $this->logMessage($business->business_id, $conversation->id, $from, $to, $body, 'inbound', 'customer', $sid);
        $customer->increment('total_messages');
        $conversation->update([
            'status'                   => 'active',
            'last_customer_message_at' => now(),
            'last_message_at'          => now(),
        ]);

        // Opt-out / opt-in keywords.
        if ($this->optOut->isStop($body)) {
            $this->optOut->optOut($customer);
            return $this->emptyTwiml(); // Twilio also auto-handles STOP at carrier level.
        }
        if ($this->optOut->isStart($body)) {
            $this->optOut->optIn($customer);
            return $this->emptyTwiml();
        }

        // Forward to owner unless paused / relay disabled / customer opted out.
        $canForward = $business->owner_sms_relay_enabled
            && $business->send_owner_notification_on_customer_reply
            && ! $business->pause_mode_enabled
            && ! $customer->opted_out;

        if ($canForward) {
            $this->comms->forwardCustomerMessageToOwner($conversation, $body);
        }

        return $this->emptyTwiml();
    }

    // ---------------------------------------------------------------------
    // OWNER -> SYSTEM
    // ---------------------------------------------------------------------
    protected function handleOwner(ConnectBusinessSetting $business, string $from, string $to, string $body, ?string $sid): Response
    {
        // 1) Control command? (PAUSE / RESUME / HELP / STATUS / LIST / CLOSE)
        if ($this->commands->isCommand($body)) {
            $reply = $this->commands->processCommand($business, $body);
            $this->comms->sendOwnerSystemMessage($business, $reply);
            return $this->emptyTwiml();
        }

        // 2) Explicit lead code? (e.g. "#1047 your message")
        if (preg_match('/^\s*#(\d+)\s*(.*)$/s', $body, $m)) {
            $conversation = $this->routing->findConversationByCode($business, $m[1]);
            $message      = trim($m[2]);

            if (! $conversation) {
                $this->comms->sendOwnerSystemMessage($business,
                    "We couldn't find lead #{$m[1]}. Reply with a valid lead number, e.g. #1047 your message.");
                return $this->emptyTwiml();
            }

            if ($message === '') {
                $this->comms->sendOwnerSystemMessage($business,
                    "Add your message after the lead number, e.g. #{$m[1]} Yes, we can help.");
                return $this->emptyTwiml();
            }

            return $this->deliverOwnerReply($business, $conversation, $message, $from, $to, $sid);
        }

        // 3) No code: branch on how many conversations are currently active.
        //    NOTE (V1): requiring a lead code when multiple are active is
        //    intentionally HARDCODED on for Version 1. The
        //    `require_code_when_multiple_active` setting is ignored here and
        //    reserved for a future version.
        $activeCount = $this->routing->activeConversations($business)->count();

        // 3a) Exactly one active -> route to it automatically (no code needed).
        if ($activeCount === 1) {
            $single = $this->routing->findSingleActiveConversation($business);
            return $this->deliverOwnerReply($business, $single, $body, $from, $to, $sid);
        }

        // 3b) Zero active -> there is nothing to reply to. Tell the owner that
        //     plainly (do NOT send the "multiple conversations" warning here).
        if ($activeCount === 0) {
            $this->comms->sendOwnerSystemMessage(
                $business,
                "There are no active customer conversations right now. When a customer messages you, it'll arrive here with a lead number to reply to. Text HELP for options."
            );
            return $this->emptyTwiml();
        }

        // 3c) Multiple active -> a lead code is required (V1 hardcoded).
        $this->comms->sendOwnerSystemMessage(
            $business,
            $this->templates->renderTemplate($business, 'multiple_conversation_warning')
        );

        return $this->emptyTwiml();
    }

    protected function deliverOwnerReply(ConnectBusinessSetting $business, ConnectConversation $conversation, string $message, string $from, string $to, ?string $sid): Response
    {
        // Log the owner's inbound text to us.
        $this->logMessage($business->business_id, $conversation->id, $from, $to, $message, 'inbound', 'owner', $sid);

        // Relay to the customer.
        $this->comms->relayOwnerMessageToCustomer($conversation, $message);

        $conversation->update([
            'status'                => 'active',
            'last_owner_message_at' => now(),
            'last_message_at'       => now(),
        ]);

        return $this->emptyTwiml();
    }

    protected function logMessage(int $businessId, ?int $conversationId, string $from, string $to, string $body, string $direction, string $senderType, ?string $sid): ConnectSmsMessage
    {
        return ConnectSmsMessage::create([
            'business_id'             => $businessId,
            'connect_conversation_id' => $conversationId,
            'from_number'             => $from,
            'to_number'               => $to,
            'body'                    => $body,
            'direction'               => $direction,
            'sender_type'             => $senderType,
            'twilio_message_sid'      => $sid,
            'status'                  => 'received',
        ]);
    }

    protected function emptyTwiml(): Response
    {
        // Empty TwiML tells Twilio "no automatic reply from this webhook";
        // all outbound texts are sent explicitly through the REST API.
        return response('<Response></Response>', 200, ['Content-Type' => 'text/xml']);
    }
}

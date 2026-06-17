<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectConversation;
use App\Models\Connect\ConnectSmsMessage;
use App\Support\Connect\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class ConnectCommunicationService
{
    public function __construct(
        protected ConnectTemplateService $templates
    ) {}

    /**
     * Low-level send. Logs every message to connect_sms_messages.
     *
     * @return ConnectSmsMessage the persisted message row
     */
    public function sendSms(
        string $to,
        string $from,
        string $body,
        ?int $businessId = null,
        ?int $conversationId = null,
        string $senderType = 'system'
    ): ConnectSmsMessage {
        $to   = PhoneNumber::toE164($to);
        $from = PhoneNumber::toE164($from);

        $message = new ConnectSmsMessage([
            'business_id'             => $businessId,
            'connect_conversation_id' => $conversationId,
            'from_number'             => $from,
            'to_number'               => $to,
            'body'                    => $body,
            'direction'               => 'outbound',
            'sender_type'             => $senderType,
            'status'                  => 'queued',
        ]);
        $message->save();

        try {
            $sid    = config('connect.twilio.account_sid');
            $token  = config('connect.twilio.auth_token');
            $client = new TwilioClient($sid, $token);

            $result = $client->messages->create($to, [
                'from' => $from,
                'body' => $body,
            ]);

            $message->update([
                'twilio_message_sid' => $result->sid,
                'status'             => $result->status ?? 'sent',
            ]);
        } catch (\Throwable $e) {
            // Never leak credentials into the log.
            Log::error('[Connect] SMS send failed', [
                'business_id' => $businessId,
                'to'          => $to,
                'error'       => $e->getMessage(),
            ]);

            $message->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
        }

        return $message;
    }

    /** Send the missed-call auto-text to a customer. */
    public function sendAutoText(ConnectBusinessSetting $business, string $customerPhone, ?int $conversationId = null): ConnectSmsMessage
    {
        $type = $this->templates->autoTextTypeForNow($business);

        $body = $this->templates->renderTemplate($business, $type, [
            'next_open_time' => $this->templates->getNextOpenTime($business),
        ]);

        if (config('connect.defaults.append_stop_footer')) {
            $body .= "\n\nReply STOP to opt out.";
        }

        return $this->sendSms(
            to: $customerPhone,
            from: $business->assigned_twilio_phone,
            body: $body,
            businessId: $business->business_id,
            conversationId: $conversationId,
            senderType: 'system'
        );
    }

    /** Forward an inbound customer message to the owner's cell phone. */
    public function forwardCustomerMessageToOwner(ConnectConversation $conversation, string $customerMessage): ConnectSmsMessage
    {
        $business = ConnectBusinessSetting::where('business_id', $conversation->business_id)->firstOrFail();

        $body = $this->templates->renderTemplate($business, 'forward_customer_reply_to_owner', [
            'conversation_id' => $conversation->leadNumber(),
            'customer_phone'  => $conversation->customer?->customer_phone,
            'customer_message' => $customerMessage,
        ]);

        return $this->sendSms(
            to: $business->owner_cell_phone,
            from: $business->assigned_twilio_phone,
            body: $body,
            businessId: $business->business_id,
            conversationId: $conversation->id,
            senderType: 'system'
        );
    }

    /** Relay the owner's reply to the customer. */
    public function relayOwnerMessageToCustomer(ConnectConversation $conversation, string $ownerMessage): ConnectSmsMessage
    {
        $business = ConnectBusinessSetting::where('business_id', $conversation->business_id)->firstOrFail();

        return $this->sendSms(
            to: $conversation->customer?->customer_phone,
            from: $business->assigned_twilio_phone,
            body: $ownerMessage,
            businessId: $business->business_id,
            conversationId: $conversation->id,
            senderType: 'owner'
        );
    }

    /** Send a system/control message back to the owner (PAUSE confirmations, warnings, etc.). */
    public function sendOwnerSystemMessage(ConnectBusinessSetting $business, string $message): ConnectSmsMessage
    {
        return $this->sendSms(
            to: $business->owner_cell_phone,
            from: $business->assigned_twilio_phone,
            body: $message,
            businessId: $business->business_id,
            conversationId: null,
            senderType: 'system'
        );
    }
}

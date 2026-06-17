<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectConversation;
use App\Models\Connect\ConnectCustomer;
use App\Support\Connect\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ConnectRoutingService
{
    /** Find the business that owns a given Twilio number (the SMS/voice "To"). */
    public function identifyBusinessByTwilioNumber(?string $number): ?ConnectBusinessSetting
    {
        $number = PhoneNumber::toE164($number);
        if (! $number) {
            return null;
        }

        return ConnectBusinessSetting::query()
            ->whereRaw("REPLACE(assigned_twilio_phone, ' ', '') = ?", [$number])
            ->orWhere('assigned_twilio_phone', $number)
            ->first();
    }

    /** Confirm an inbound SMS came from the owner of the business behind that Twilio number. */
    public function identifyBusinessByOwnerPhone(?string $ownerPhone, ?string $twilioNumber): ?ConnectBusinessSetting
    {
        $business = $this->identifyBusinessByTwilioNumber($twilioNumber);

        if ($business && PhoneNumber::matches($business->owner_cell_phone, $ownerPhone)) {
            return $business;
        }

        return null;
    }

    public function findOrCreateCustomer(ConnectBusinessSetting $business, string $customerPhone): ConnectCustomer
    {
        $phone = PhoneNumber::toE164($customerPhone);

        $customer = ConnectCustomer::firstOrCreate(
            ['business_id' => $business->business_id, 'customer_phone' => $phone],
            ['first_seen_at' => now(), 'last_seen_at' => now()]
        );

        $customer->forceFill(['last_seen_at' => now()])->save();

        return $customer;
    }

    /**
     * Reuse the most recent open conversation inside the active window,
     * otherwise start a new one.
     */
    public function findOrCreateConversation(ConnectBusinessSetting $business, ConnectCustomer $customer): ConnectConversation
    {
        $windowMinutes = $business->active_conversation_window_minutes
            ?: (int) config('connect.defaults.active_conversation_window_minutes');

        $existing = ConnectConversation::query()
            ->where('business_id', $business->business_id)
            ->where('connect_customer_id', $customer->id)
            ->whereIn('status', ['new', 'active'])
            ->where('last_message_at', '>=', now()->subMinutes($windowMinutes))
            ->latest('last_message_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        $conversation = ConnectConversation::create([
            'business_id'         => $business->business_id,
            'connect_customer_id' => $customer->id,
            'status'              => 'new',
            'last_message_at'     => now(),
        ]);

        // Lead number == conversation id by default (e.g. #1047).
        $conversation->update(['conversation_code' => (string) $conversation->id]);

        $customer->increment('total_conversations');

        return $conversation;
    }

    /** Look up a conversation by the lead code the owner typed (#1047). */
    public function findConversationByCode(ConnectBusinessSetting $business, string $code): ?ConnectConversation
    {
        $code = ltrim(trim($code), '#');

        return ConnectConversation::query()
            ->where('business_id', $business->business_id)
            ->where(function ($q) use ($code) {
                $q->where('conversation_code', $code)
                  ->orWhere('id', (int) $code);
            })
            ->first();
    }

    /** All open conversations for the business inside the active window. */
    public function activeConversations(ConnectBusinessSetting $business): Collection
    {
        $windowMinutes = $business->active_conversation_window_minutes
            ?: (int) config('connect.defaults.active_conversation_window_minutes');

        return ConnectConversation::query()
            ->with('customer')
            ->where('business_id', $business->business_id)
            ->whereIn('status', ['new', 'active'])
            ->where('last_message_at', '>=', now()->subMinutes($windowMinutes))
            ->latest('last_message_at')
            ->get();
    }

    /** Returns the single active conversation, or null when there are zero or many. */
    public function findSingleActiveConversation(ConnectBusinessSetting $business): ?ConnectConversation
    {
        $active = $this->activeConversations($business);

        return $active->count() === 1 ? $active->first() : null;
    }

    public function countOpenConversationsToday(ConnectBusinessSetting $business): int
    {
        $tz = $business->timezone ?: config('app.timezone');

        return ConnectConversation::query()
            ->where('business_id', $business->business_id)
            ->where('created_at', '>=', Carbon::now($tz)->startOfDay())
            ->count();
    }
}

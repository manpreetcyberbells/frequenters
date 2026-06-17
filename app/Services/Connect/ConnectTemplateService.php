<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectBusinessHour;
use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectMessageTemplate;
use Carbon\Carbon;

class ConnectTemplateService
{
    /** Hard-coded fallbacks used when a business has no custom template row. */
    public const DEFAULTS = [
        'missed_call_open' =>
            'Hi, this is {{business_name}}. Sorry we missed your call. How can we help?',

        'missed_call_closed' =>
            'Hi, this is {{business_name}}. We are currently closed. Our next business hours are {{next_open_time}}. Reply here and we’ll get back to you as soon as possible.',

        'forward_customer_reply_to_owner' =>
            "Frequenters Connect - {{business_name}}\n" .
            "Lead #{{conversation_id}}\n" .
            "Customer: {{customer_phone}}\n" .
            "Message: {{customer_message}}\n\n" .
            "Reply with:\n#{{conversation_id}} your message",

        'multiple_conversation_warning' =>
            'We found multiple active customer conversations. Please reply with the lead number, for example: #1047 Your message.',

        'pause_confirmation' =>
            'Frequenters Connect is now paused for {{business_name}}.',

        'resume_confirmation' =>
            'Frequenters Connect is now active for {{business_name}}.',

        'help' =>
            'Reply to a customer with #LeadNumber your message (e.g. #1047 Yes, we can help). Commands: LIST (see open chats), CLOSE #1047 (end a chat), PAUSE, RESUME, STATUS, HELP.',

        'status' =>
            'Frequenters Connect is {{service_state}}. Open conversations today: {{open_today}}. Auto-text enabled: {{auto_text_enabled}}.',
    ];

    /**
     * Render a template for a business, substituting {{variables}}.
     */
    public function renderTemplate(ConnectBusinessSetting $business, string $templateType, array $variables = []): string
    {
        $row = ConnectMessageTemplate::query()
            ->where('business_id', $business->business_id)
            ->where('template_type', $templateType)
            ->where('active', true)
            ->first();

        $body = $row?->body ?? (self::DEFAULTS[$templateType] ?? '');

        // Always-available variables.
        $variables = array_merge([
            'business_name' => $business->getBusinessName(),
        ], $variables);

        foreach ($variables as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        return $body;
    }

    /**
     * Decide which auto-text to send based on whether the business is open now.
     */
    public function autoTextTypeForNow(ConnectBusinessSetting $business): string
    {
        return $this->isOpenNow($business) ? 'missed_call_open' : 'missed_call_closed';
    }

    public function isOpenNow(ConnectBusinessSetting $business): bool
    {
        $now = Carbon::now($business->timezone ?: config('app.timezone'));

        $row = ConnectBusinessHour::query()
            ->where('business_id', $business->business_id)
            ->where('day_of_week', (int) $now->dayOfWeek)
            ->first();

        if (! $row || $row->is_closed || ! $row->open_time || ! $row->close_time) {
            return false;
        }

        $open  = $now->copy()->setTimeFromTimeString($row->open_time);
        $close = $now->copy()->setTimeFromTimeString($row->close_time);

        return $now->betweenIncluded($open, $close);
    }

    /**
     * Human-readable next opening time, e.g. "Monday at 9:00 AM".
     * Returns "soon" if no hours are configured.
     */
    public function getNextOpenTime(ConnectBusinessSetting $business): string
    {
        $tz  = $business->timezone ?: config('app.timezone');
        $now = Carbon::now($tz);

        for ($i = 0; $i <= 7; $i++) {
            $day = $now->copy()->addDays($i);

            $row = ConnectBusinessHour::query()
                ->where('business_id', $business->business_id)
                ->where('day_of_week', (int) $day->dayOfWeek)
                ->first();

            if (! $row || $row->is_closed || ! $row->open_time) {
                continue;
            }

            $openAt = $day->copy()->setTimeFromTimeString($row->open_time);

            // Skip today's slot if we are already past it.
            if ($i === 0 && $openAt->lessThanOrEqualTo($now)) {
                continue;
            }

            $label = $i === 0 ? 'today' : ($i === 1 ? 'tomorrow' : $openAt->format('l'));

            return $label . ' at ' . $openAt->format('g:i A');
        }

        return 'soon';
    }
}

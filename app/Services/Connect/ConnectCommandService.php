<?php

namespace App\Services\Connect;

use App\Models\Connect\ConnectBusinessSetting;
use App\Models\Connect\ConnectConversation;

class ConnectCommandService
{
    public const COMMANDS = ['PAUSE', 'RESUME', 'HELP', 'STATUS', 'LIST', 'CLOSE'];

    public function __construct(
        protected ConnectTemplateService $templates,
        protected ConnectRoutingService $routing,
    ) {}

    /**
     * Is the owner's message a recognised command?
     *
     * Word-only commands must be the WHOLE message (so a normal reply that
     * merely starts with "close" or "status" is NOT swallowed as a command).
     * CLOSE additionally accepts a lead number: "CLOSE #1047" / "CLOSE 1047".
     */
    public function isCommand(string $message): bool
    {
        $m = strtoupper(trim($message));

        if (in_array($m, self::COMMANDS, true)) {
            return true;
        }

        // CLOSE with an explicit lead number.
        return (bool) preg_match('/^CLOSE\s+#?\d+$/', $m);
    }

    /**
     * Process a command and return the reply text to send back to the owner.
     */
    public function processCommand(ConnectBusinessSetting $business, string $message): string
    {
        $trimmed = trim($message);
        $parts   = preg_split('/\s+/', $trimmed, 2);
        $keyword = strtoupper($parts[0] ?? '');
        $arg     = trim($parts[1] ?? '');

        return match ($keyword) {
            'PAUSE'  => $this->pause($business),
            'RESUME' => $this->resume($business),
            'STATUS' => $this->status($business),
            'LIST'   => $this->listConversations($business),
            'CLOSE'  => $this->close($business, $arg),
            'HELP'   => $this->templates->renderTemplate($business, 'help'),
            default  => $this->templates->renderTemplate($business, 'help'),
        };
    }

    protected function pause(ConnectBusinessSetting $business): string
    {
        $business->update(['pause_mode_enabled' => true]);

        return $this->templates->renderTemplate($business, 'pause_confirmation');
    }

    protected function resume(ConnectBusinessSetting $business): string
    {
        $business->update(['pause_mode_enabled' => false]);

        return $this->templates->renderTemplate($business, 'resume_confirmation');
    }

    protected function status(ConnectBusinessSetting $business): string
    {
        return $this->templates->renderTemplate($business, 'status', [
            'service_state'     => $business->pause_mode_enabled ? 'paused' : 'active',
            'open_today'        => $this->routing->countOpenConversationsToday($business),
            'auto_text_enabled' => $business->auto_text_enabled ? 'Yes' : 'No',
        ]);
    }

    /**
     * LIST — show the owner their currently active conversations and lead codes.
     */
    protected function listConversations(ConnectBusinessSetting $business): string
    {
        $active = $this->routing->activeConversations($business);

        if ($active->isEmpty()) {
            return 'No active customer conversations right now.';
        }

        $lines = $active->take(10)->map(function (ConnectConversation $c) {
            $phone = $c->customer?->customer_phone ?? 'unknown';
            return "#{$c->leadNumber()} {$phone}";
        })->implode("\n");

        $extra = $active->count() > 10
            ? "\n…and " . ($active->count() - 10) . ' more.'
            : '';

        return "Active conversations:\n{$lines}{$extra}\n\nReply with #LeadNumber your message.";
    }

    /**
     * CLOSE — end a conversation.
     *   "CLOSE #1047" closes that lead.
     *   "CLOSE" closes the single active conversation, or asks for a lead
     *   number when zero or multiple are active.
     */
    protected function close(ConnectBusinessSetting $business, string $arg): string
    {
        if ($arg !== '') {
            $conversation = $this->routing->findConversationByCode($business, $arg);

            if (! $conversation) {
                $code = ltrim($arg, '#');
                return "We couldn't find lead #{$code}. Text LIST to see open conversations.";
            }

            return $this->closeConversation($conversation);
        }

        $active = $this->routing->activeConversations($business);

        if ($active->count() === 1) {
            return $this->closeConversation($active->first());
        }

        if ($active->isEmpty()) {
            return 'There are no active conversations to close.';
        }

        return 'You have multiple active conversations. Close one with: CLOSE #1047';
    }

    protected function closeConversation(ConnectConversation $conversation): string
    {
        $conversation->update([
            'status'    => 'closed',
            'closed_at' => now(),
        ]);

        return "Lead #{$conversation->leadNumber()} is now closed.";
    }
}

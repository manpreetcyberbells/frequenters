<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification
{
    use Queueable;
    public $token;
    public $email;

    /**
     * Create a new notification instance.
     */
    public function __construct($token, $email)
    {
        $this->token = $token;
        $this->email = $email;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
    */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Funcation to Send notification on Mail.
    */
    public function toMail($notifiable)
    {
        $frontendUrl = env('FRONTEND_URL', 'http://frequenters_app.local/');
        $resetLink = "{$frontendUrl}/reset-password?token={$this->token}&email={$this->email}";

        $user = \App\Models\User::where('email', $this->email)->first();
        $name = $user?->name ?? 'there';

        return (new \Illuminate\Notifications\Messages\MailMessage)
        ->subject('Reset Your Password')
        ->view('emails.reset-password', [
            'resetLink' => $resetLink,
            'name' => $name,
        ]);
    }


    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}

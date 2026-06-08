<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class HntPasswordResetNotification extends Notification
{
    public function __construct(
        protected string $token
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $resetUrl = $this->resetUrl($notifiable);
        $expireMinutes = $this->expireMinutes();

        return (new MailMessage)
            ->subject(__('ui.password_reset_mail_subject'))
            ->markdown('mail.auth.password-reset', [
                'user' => $notifiable,
                'resetUrl' => $resetUrl,
                'expireMinutes' => $expireMinutes,
            ]);
    }

    protected function resetUrl(object $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    protected function expireMinutes(): int
    {
        $broker = Config::get('auth.defaults.passwords', 'users');

        return (int) Config::get("auth.passwords.{$broker}.expire", 60);
    }
}

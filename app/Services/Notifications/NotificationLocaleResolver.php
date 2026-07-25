<?php

namespace App\Services\Notifications;

use App\Models\UserNotification;
use Illuminate\Support\Facades\App;
use Throwable;

class NotificationLocaleResolver
{
    public const SUPPORTED_LOCALES = ['de', 'en'];

    public function messages(UserNotification $notification): array
    {
        $originalLocale = App::currentLocale();
        $messages = [];

        try {
            foreach (self::SUPPORTED_LOCALES as $locale) {
                App::setLocale($locale);
                $messages[$locale] = $this->currentMessage($notification, $locale);
            }
        } finally {
            App::setLocale($originalLocale);
        }

        return $messages;
    }

    public function message(UserNotification $notification, ?string $locale): array
    {
        $normalized = $this->normalize($locale);
        $originalLocale = App::currentLocale();

        try {
            App::setLocale($normalized);

            return $this->currentMessage($notification, $normalized);
        } finally {
            App::setLocale($originalLocale);
        }
    }

    public function normalize(?string $locale): string
    {
        $value = strtolower(trim((string) $locale));
        $language = explode('-', str_replace('_', '-', $value), 2)[0];

        if (in_array($language, self::SUPPORTED_LOCALES, true)) {
            return $language;
        }

        $fallback = strtolower((string) config('app.locale', 'de'));

        return in_array($fallback, self::SUPPORTED_LOCALES, true) ? $fallback : 'de';
    }

    private function currentMessage(UserNotification $notification, string $locale): array
    {
        try {
            $title = trim($notification->displayTitle());
        } catch (Throwable) {
            $title = trim((string) $notification->title);
        }

        try {
            $body = trim((string) $notification->displayBody());
        } catch (Throwable) {
            $body = trim((string) $notification->body);
        }

        return [
            'title' => $title !== '' ? $title : 'hnt.rocks',
            'body' => $body !== ''
                ? $body
                : ($locale === 'en'
                    ? 'You have a new notification.'
                    : 'Du hast eine neue Benachrichtigung.'),
        ];
    }
}

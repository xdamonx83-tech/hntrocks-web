<?php

namespace App\Services;

use App\Models\LiveLobby;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LiveLobbyNotificationService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    public function announce(LiveLobby $lobby): void
    {
        User::query()
            ->where('id', '!=', $lobby->creator_id)
            ->whereHas('pushDevices', fn ($query) => $query->active())
            ->where(function ($query): void {
                $query->whereDoesntHave('notificationSettings')
                    ->orWhereHas('notificationSettings', fn ($settings) => $settings->where('lfg', true));
            })
            ->whereHas('profile', function ($query) use ($lobby): void {
                $platforms = $lobby->crossplay_pool === 'pc' ? ['pc'] : ['playstation', 'xbox'];
                $query->whereIn('platform', $platforms)
                    ->when($lobby->region, fn ($profile) => $profile->where('region', $lobby->region))
                    ->when($lobby->language, fn ($profile) => $profile->where('language', $lobby->language));
            })
            ->with('profile')
            ->chunkById(100, function ($users) use ($lobby): void {
                foreach ($users as $recipient) {
                    $cooldownKey = 'live_lobby_ready_push:'.$recipient->id;
                    if (! Cache::add($cooldownKey, true, now()->addMinutes(10))) {
                        continue;
                    }

                    $locale = $recipient->profile?->language === 'en' ? 'en' : 'de';
                    $missing = max(0, (int) $lobby->slots_total - 1);
                    $title = $locale === 'en' ? 'A Hunter is ready' : 'Ein Hunter ist ready';
                    $body = $locale === 'en'
                        ? ($lobby->mode === 'duo' ? 'Duo needs 1 more Hunter.' : 'Trio needs '.$missing.' more Hunters.')
                        : ($lobby->mode === 'duo' ? 'Duo sucht noch 1 Hunter.' : 'Trio sucht noch '.$missing.' Hunter.');

                    $this->notifications->send(
                        $recipient,
                        $lobby->creator,
                        'lfg_live_lobby_ready',
                        $title,
                        $body,
                        '/ready-lobbies/'.$lobby->public_id
                    );
                }
            });
    }

    public function joined(LiveLobby $lobby, User $member): void
    {
        $creator = $lobby->creator;
        $english = $creator?->profile?->language === 'en';

        $this->notifications->send(
            $creator,
            $member,
            'lfg_live_lobby_joined',
            $english ? 'Hunter joined' : 'Hunter beigetreten',
            $english ? $member->name.' joined your Ready Lobby.' : $member->name.' ist deiner Ready Lobby beigetreten.',
            '/ready-lobbies/'.$lobby->public_id
        );
    }

    public function full(LiveLobby $lobby, User $actor): void
    {
        foreach ($lobby->activeMembers as $member) {
            $recipient = $member->user;
            $english = $recipient?->profile?->language === 'en';
            $this->notifications->send(
                $recipient,
                $actor,
                'lfg_live_lobby_full',
                $english ? 'Ready Lobby is full' : 'Ready Lobby ist voll',
                $english ? 'Your team is ready to hunt.' : 'Euer Team ist bereit für die Jagd.',
                '/ready-lobbies/'.$lobby->public_id
            );
        }
    }
}

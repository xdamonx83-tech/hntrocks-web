<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ApiAccessToken;
use App\Models\UserNotificationSetting;
use App\Services\Auth\TwoFactorService;
use App\Services\SecurityLogService;
use App\Support\NotificationSettingsGroups;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request, TwoFactorService $twoFactor): View
    {
        $user = $request->user();
        $settings = $user->notificationSettings()->firstOrCreate([]);
        $privacySettings = $user->privacySettings()->firstOrCreate([]);
        $blockedUsers = $user->blockedUsers()
            ->with('blockedUser')
            ->latest()
            ->get();
        $user->loadMissing('accountDeletionRequest');

        $twoFactorSetupSecret = (string) $request->session()->get('two_factor_setup_secret', '');
        $twoFactorRecoveryCodes = $request->session()->pull('two_factor_recovery_codes', []);
        $securitySettings = [
            'user' => $user,
            'events' => $user->securityEvents()->latest()->limit(12)->get(),
            'deletion_request' => $user->accountDeletionRequest,
            'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            'two_factor_recovery_count' => $twoFactor->recoveryCodeCount($user),
            'two_factor_setup_secret' => $twoFactorSetupSecret,
            'two_factor_setup_uri' => $twoFactorSetupSecret !== '' ? $twoFactor->keyUri($user, $twoFactorSetupSecret) : null,
            'two_factor_recovery_codes' => is_array($twoFactorRecoveryCodes) ? $twoFactorRecoveryCodes : [],
        ];

        $view = $request->boolean('classic_settings')
            ? 'account.settings'
            : 'themes.hnt_preview.account.settings';

        return view($view, [
            'settings' => $settings,
            'notificationGroups' => NotificationSettingsGroups::all(),
            'privacySettings' => $privacySettings,
            'blockedUsers' => $blockedUsers,
            'securitySettings' => $securitySettings,
            'generalSettings' => [
                'locale' => app()->getLocale(),
                'user' => $user,
            ],
            'securityStatus' => $this->securityStatus($request),
        ]);
    }

    public function update(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $request->validate($this->rules());

        $data = [];
        foreach (UserNotificationSetting::FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        $request->user()->notificationSettings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        $securityLog->record($request->user(), 'notification_settings_updated', $request, $data);

        if ($request->input('settings_section') === 'notifications') {
            return redirect()
                ->to(route('account.settings.edit').'#notifications')
                ->with('status', __('ui.notification_settings_saved'));
        }

        return back()->with('status', __('ui.notification_settings_saved'));
    }

    private function rules(): array
    {
        return array_fill_keys(UserNotificationSetting::FIELDS, ['nullable', 'boolean']);
    }

    private function securityStatus(Request $request): array
    {
        $user = $request->user()->loadMissing(['privacySettings', 'profile']);
        $isEnglish = app()->getLocale() === 'en';
        $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;

        $twoFactorEnabled = $user->hasTwoFactorEnabled();
        $profileVisibility = (string) ($user->privacySettings?->profile_visibility
            ?? $user->profile?->profile_visibility
            ?? 'public');
        $messagesFrom = (string) ($user->privacySettings?->allow_messages_from ?? 'registered');

        $passwordChangedAt = $user->securityEvents()
            ->where('event', 'password_changed')
            ->latest()
            ->first()
            ?->created_at;

        $passwordActive = filled($user->getAuthPassword());
        $messagesRestricted = $messagesFrom !== 'everyone';
        $profileRestricted = $profileVisibility !== 'public';

        $protectionChecks = [
            $passwordActive,
            $twoFactorEnabled,
            $profileRestricted,
            $messagesRestricted,
        ];
        $score = count(array_filter($protectionChecks)) * 25;

        $scoreLabel = match (true) {
            $score >= 90 => $t('Sehr gut geschützt', 'Very well protected'),
            $score >= 75 => $t('Gut geschützt', 'Well protected'),
            $score >= 55 => $t('Solider Schutz', 'Solid protection'),
            default => $t('Schutz verbessern', 'Improve protection'),
        };

        $profileLabel = match ($profileVisibility) {
            'private' => $t('Privat', 'Private'),
            'registered' => $t('Nur registrierte Nutzer', 'Registered users only'),
            default => $t('Öffentlich', 'Public'),
        };

        $messagesLabel = match ($messagesFrom) {
            'nobody' => $t('Niemand', 'Nobody'),
            'everyone' => $t('Alle', 'Everyone'),
            'following' => $t('Nur von „Folge ich“', 'Following only'),
            default => $t('Registrierte Nutzer', 'Registered users'),
        };

        $sessions = [[
            'type' => 'web',
            'title' => $this->browserSessionTitle((string) $request->userAgent(), $t),
            'subtitle' => $t('Diese Sitzung · jetzt aktiv', 'This session · active now'),
            'current' => true,
        ]];

        $activeAppSessionCount = 0;
        if (Schema::hasTable('api_access_tokens')) {
            $activeTokens = ApiAccessToken::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->where(function ($query): void {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                });

            $activeAppSessionCount = (clone $activeTokens)->count();

            $tokens = $activeTokens
                ->orderByRaw('last_used_at is null')
                ->orderByDesc('last_used_at')
                ->orderByDesc('created_at')
                ->limit(3)
                ->get();

            foreach ($tokens as $token) {
                $activityAt = $token->last_used_at ?: $token->created_at;
                $sessions[] = [
                    'type' => 'app',
                    'title' => 'HNT.rocks App',
                    'subtitle' => $activityAt
                        ? $t('App · ', 'App · ').$activityAt->diffForHumans()
                        : $t('App-Zugang aktiv', 'App access active'),
                    'current' => false,
                ];
            }
        }

        return [
            'score' => $score,
            'score_label' => $scoreLabel,
            'password' => [
                'good' => $passwordActive,
                'text' => ! $passwordActive
                    ? $t('Kein Passwort eingerichtet', 'No password set')
                    : ($passwordChangedAt
                        ? $t('Aktiv · ', 'Active · ').$passwordChangedAt->diffForHumans()
                        : $t('Aktiv · Änderungsdatum nicht erfasst', 'Active · change date unavailable')),
                'badge' => $passwordActive ? 'OK' : $t('Offen', 'Open'),
            ],
            'two_factor' => [
                'good' => $twoFactorEnabled,
                'text' => $twoFactorEnabled ? $t('Aktiviert', 'Enabled') : $t('Noch nicht aktiviert', 'Not enabled yet'),
                'badge' => $twoFactorEnabled ? $t('Aktiv', 'Active') : $t('Offen', 'Open'),
            ],
            'profile' => [
                'good' => $profileRestricted,
                'text' => $profileLabel,
                'badge' => $profileRestricted
                    ? $t('Begrenzt', 'Limited')
                    : $t('Offen', 'Open'),
            ],
            'messages' => [
                'good' => $messagesRestricted,
                'text' => $messagesLabel,
                'badge' => $messagesFrom === 'everyone' ? $t('Offen', 'Open') : $t('Begrenzt', 'Limited'),
            ],
            'sessions' => $sessions,
            'session_count' => 1 + $activeAppSessionCount,
            'labels' => [
                'eyebrow' => $t('KONTOSTATUS', 'ACCOUNT STATUS'),
                'title' => $t('Sicherheitsstatus', 'Security status'),
                'password' => $t('Passwort', 'Password'),
                'two_factor' => $t('Zwei-Faktor-Schutz', 'Two-factor protection'),
                'profile' => $t('Profil-Sichtbarkeit', 'Profile visibility'),
                'messages' => $t('Nachrichten', 'Messages'),
                'sessions' => $t('AKTIVE ZUGÄNGE', 'ACTIVE ACCESS'),
                'open_security' => $t('Sicherheit öffnen', 'Open security'),
            ],
        ];
    }

    private function browserSessionTitle(string $userAgent, callable $t): string
    {
        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone'), str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Macintosh') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => $t('Gerät', 'Device'),
        };

        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => $t('Browser', 'Browser'),
        };

        return $os.' · '.$browser;
    }
}

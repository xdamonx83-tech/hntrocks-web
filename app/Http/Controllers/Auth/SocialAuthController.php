<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\MobileSocialLoginCode;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SocialIdentity;
use App\Services\Auth\SocialProviderService;
use App\Services\GamificationService;
use App\Services\ReferralService;
use App\Services\SecurityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SocialAuthController extends Controller
{
    public function redirect(string $provider, Request $request, SocialProviderService $social): RedirectResponse
    {
        try {
            $provider = $social->assertUsable($provider);
        } catch (RuntimeException $exception) {
            if ($this->wantsMobileSocialLogin($request)) {
                return $this->mobileSocialRedirect([
                    'status' => 'error',
                    'error' => 'provider_unavailable',
                    'message' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('login')->withErrors(['social' => $exception->getMessage()]);
        }

        $state = Str::random(40);
        $request->session()->put($social->stateSessionKey($provider), $state);

        if ($this->wantsMobileSocialLogin($request)) {
            $request->session()->put($this->mobileStateSessionKey($provider, $state), [
                'device_name' => Str::limit($request->string('device_name')->trim()->value() ?: 'Android App', 80, ''),
            ]);
        }

        return redirect()->away($social->redirectUrl($provider, $state));
    }

    public function callback(
        string $provider,
        Request $request,
        SocialProviderService $social,
        SecurityLogService $securityLog,
        GamificationService $gamification,
        ReferralService $referrals,
    ): RedirectResponse {
        $mobileContext = null;

        try {
            $provider = $social->assertUsable($provider);
            $this->validateState($provider, $request, $social);

            $mobileContext = $this->pullMobileContext($provider, $this->receivedState($provider, $request));

            if ($request->query('error')) {
                throw new RuntimeException('Der Social-Login wurde abgebrochen oder abgelehnt.');
            }

            $identity = $social->identity($provider, $request);
            $user = $this->resolveUser($identity, $request, $gamification, $referrals);

            if ($user->isSuspended()) {
                $securityLog->record($user, 'social_login_blocked_suspended', $request, ['provider' => $provider]);

                if ($mobileContext !== null) {
                    return $this->mobileSocialRedirect([
                        'status' => 'error',
                        'error' => 'account_suspended',
                        'message' => 'Dieses Konto ist aktuell gesperrt.',
                    ]);
                }

                return redirect()->route('login')->withErrors(['social' => 'Dieses Konto ist aktuell gesperrt.']);
            }

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $securityLog->record($user, 'social_login_success', $request, [
                'provider' => $provider,
                'provider_user_id' => $identity->providerUserId,
                'target' => $mobileContext !== null ? 'mobile' : 'web',
            ]);

            if ($mobileContext !== null) {
                $codeData = MobileSocialLoginCode::createForUser(
                    $user,
                    $provider,
                    $mobileContext['device_name'] ?? 'Android App',
                    $request->ip(),
                    (string) $request->userAgent(),
                );

                return $this->mobileSocialRedirect([
                    'status' => 'ok',
                    'code' => $codeData['plain_code'],
                    'provider' => $provider,
                    'expires_in' => $codeData['ttl_seconds'],
                ]);
            }

            if ($user->hasTwoFactorEnabled()) {
                $request->session()->put('two_factor_login', [
                    'user_id' => $user->id,
                    'remember' => true,
                    'created_at' => now()->timestamp,
                ]);

                $securityLog->record($user, 'social_login_two_factor_required', $request, [
                    'provider' => $provider,
                    'provider_user_id' => $identity->providerUserId,
                ]);

                return redirect()->route('login.two-factor');
            }

            Auth::login($user, true);
            $request->session()->regenerate();

            return redirect()->intended(route('feed.index'));
        } catch (RuntimeException $exception) {
            $securityLog->record(null, 'social_login_failed', $request, [
                'provider' => $provider,
                'message' => $exception->getMessage(),
                'target' => $mobileContext !== null ? 'mobile' : 'web',
            ]);

            if ($mobileContext !== null) {
                return $this->mobileSocialRedirect([
                    'status' => 'error',
                    'error' => 'social_login_failed',
                    'message' => $exception->getMessage(),
                ]);
            }

            return redirect()->route('login')->withErrors(['social' => $exception->getMessage()]);
        }
    }

    private function validateState(string $provider, Request $request, SocialProviderService $social): void
    {
        $expected = $request->session()->pull($social->stateSessionKey($provider));
        $received = $provider === 'steam'
            ? (string) $request->query('hh_state', '')
            : (string) $request->query('state', '');

        if (! is_string($expected) || $expected === '' || $received === '' || ! hash_equals($expected, $received)) {
            throw new RuntimeException('Die Social-Login-Sitzung ist abgelaufen. Bitte erneut versuchen.');
        }
    }

    private function wantsMobileSocialLogin(Request $request): bool
    {
        return $request->boolean('mobile')
            || $request->boolean('app')
            || $request->query('target') === 'mobile';
    }

    private function receivedState(string $provider, Request $request): string
    {
        return $provider === 'steam'
            ? (string) $request->query('hh_state', '')
            : (string) $request->query('state', '');
    }

    private function mobileStateSessionKey(string $provider, string $state): string
    {
        return 'hunthub_social_mobile.' . $provider . '.' . $state;
    }

    private function pullMobileContext(string $provider, string $state): ?array
    {
        if ($state === '') {
            return null;
        }

        $context = request()->session()->pull($this->mobileStateSessionKey($provider, $state));

        return is_array($context) ? $context : null;
    }

    private function mobileSocialRedirect(array $query): RedirectResponse
    {
        $deepLink = trim((string) config('social.mobile.deep_link_url', 'hntrocks://auth/social'));
        $deepLink = $deepLink !== '' ? $deepLink : 'hntrocks://auth/social';
        $separator = str_contains($deepLink, '?') ? '&' : '?';

        return redirect()->away($deepLink . $separator . http_build_query($query, '', '&', PHP_QUERY_RFC3986));
    }

    private function resolveUser(
        SocialIdentity $identity,
        Request $request,
        GamificationService $gamification,
        ReferralService $referrals,
    ): User {
        if ($identity->providerUserId === '') {
            throw new RuntimeException('Der Social-Login hat keine gültige Benutzer-ID geliefert.');
        }

        $account = SocialAccount::query()
            ->where('provider', $identity->provider)
            ->where('provider_user_id', $identity->providerUserId)
            ->first();

        if ($account) {
            $this->updateSocialAccount($account, $identity);

            return $account->user;
        }

        $user = $this->findUserByExistingEmail($identity);

        if (! $user) {
            $user = $this->createUserFromIdentity($identity, $request, $gamification, $referrals);
        }

        $this->createSocialAccount($user, $identity);

        return $user;
    }

    private function findUserByExistingEmail(SocialIdentity $identity): ?User
    {
        if (! $identity->email) {
            return null;
        }

        $allowedProviders = (array) config('social.auto_link_email_providers', []);
        $canLinkBySameEmail = (bool) config('social.auto_link_existing_email', true)
            && in_array($identity->provider, $allowedProviders, true);

        $canLinkByVerifiedEmail = $identity->emailVerified
            && (bool) config('social.auto_link_verified_email', true);

        if (! $canLinkBySameEmail && ! $canLinkByVerifiedEmail) {
            return null;
        }

        return User::query()->where('email', $identity->email)->first();
    }

    private function createUserFromIdentity(
        SocialIdentity $identity,
        Request $request,
        GamificationService $gamification,
        ReferralService $referrals,
    ): User {
        $email = $identity->email ?: $this->syntheticEmail($identity);

        $existingEmailUser = User::query()->where('email', $email)->first();

        if ($existingEmailUser) {
            throw new RuntimeException('Zu dieser E-Mail gibt es bereits ein hnt.rocks-Konto. Bitte zuerst normal einloggen.');
        }

        $user = User::create([
            'name' => Str::limit($identity->name ?: $identity->nickname ?: ucfirst($identity->provider) . ' Hunter', 80, ''),
            'username' => $this->uniqueUsername($identity),
            'email' => $email,
            'email_verified_at' => $identity->emailVerified ? now() : null,
            'password' => Hash::make(Str::random(64)),
        ]);

        $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $user->privacySettings()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $referrals->attachSignup($user, $request->session()->pull('referral_code'), [
            'registered_via' => 'social_' . $identity->provider,
        ]);

        $gamification->award($user, 'account_created', source: $user, description: 'Account per Social Login erstellt');

        return $user;
    }

    private function createSocialAccount(User $user, SocialIdentity $identity): SocialAccount
    {
        return SocialAccount::create([
            'user_id' => $user->id,
            'provider' => $identity->provider,
            'provider_user_id' => $identity->providerUserId,
            'provider_email' => $identity->email,
            'provider_name' => $identity->name,
            'provider_nickname' => $identity->nickname,
            'avatar_url' => $identity->avatarUrl,
            'raw_profile' => $identity->raw,
            'last_login_at' => now(),
        ]);
    }

    private function updateSocialAccount(SocialAccount $account, SocialIdentity $identity): void
    {
        $account->forceFill([
            'provider_email' => $identity->email,
            'provider_name' => $identity->name,
            'provider_nickname' => $identity->nickname,
            'avatar_url' => $identity->avatarUrl,
            'raw_profile' => $identity->raw,
            'last_login_at' => now(),
        ])->save();
    }

    private function uniqueUsername(SocialIdentity $identity): string
    {
        $source = $identity->nickname ?: $identity->name ?: $identity->provider . '-' . Str::substr($identity->providerUserId, -8);
        $base = Str::of($source)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9_.-]+/', '-')
            ->trim('-_.')
            ->limit(24, '')
            ->toString();

        if (strlen($base) < 3) {
            $base = $identity->provider . '-' . Str::substr($identity->providerUserId, -8);
        }

        $base = Str::substr($base, 0, 28);
        $candidate = $base;
        $counter = 1;

        while (User::query()->where('username', $candidate)->exists()) {
            $suffix = '-' . $counter;
            $candidate = Str::substr($base, 0, 32 - strlen($suffix)) . $suffix;
            $counter++;
        }

        return $candidate;
    }

    private function syntheticEmail(SocialIdentity $identity): string
    {
        $domain = strtolower(trim((string) config('social.synthetic_email_domain', 'social-login.hnt.rocks')));
        $domain = preg_replace('/[^a-z0-9.-]/', '', $domain) ?: 'social-login.hnt.rocks';

        return sprintf('%s-%s@%s', $identity->provider, strtolower($identity->providerUserId), $domain);
    }
}

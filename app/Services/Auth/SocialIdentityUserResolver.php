<?php

namespace App\Services\Auth;

use App\Models\SocialAccount;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class SocialIdentityUserResolver
{
    public function __construct(
        private readonly GamificationService $gamification,
        private readonly ReferralService $referrals,
    ) {
    }

    public function resolve(SocialIdentity $identity, Request $request, ?string $referralCode = null): User
    {
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
            $user = $this->createUserFromIdentity($identity, $request, $referralCode);
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

    private function createUserFromIdentity(SocialIdentity $identity, Request $request, ?string $referralCode): User
    {
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
            'status' => 'active',
        ]);

        $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $user->privacySettings()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $referralCode ??= $this->pullReferralCode($request);
        $this->referrals->attachSignup($user, $referralCode, [
            'registered_via' => 'social_' . $identity->provider,
            'target' => $request->expectsJson() ? 'api' : 'web',
        ]);

        $this->gamification->award($user, 'account_created', source: $user, description: 'Account per Social Login erstellt');

        return $user;
    }

    private function pullReferralCode(Request $request): ?string
    {
        if (! $request->hasSession()) {
            return null;
        }

        $code = $request->session()->pull('referral_code');

        return is_string($code) && trim($code) !== '' ? trim($code) : null;
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

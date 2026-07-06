<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\AccountDeletionRequest;
use App\Models\ApiAccessToken;
use App\Models\Friendship;
use App\Models\MobileSocialLoginCode;
use App\Models\Quest;
use App\Models\ReferralLink;
use App\Models\User;
use App\Models\UserTwoFactorChallenge;
use App\Services\Auth\NativeGoogleTokenVerifier;
use App\Services\Auth\SocialIdentityUserResolver;
use App\Services\Auth\TwoFactorService;
use App\Services\GamificationService;
use App\Services\SecurityLogService;
use App\Services\UserDataExportService;
use App\Services\MediaService;
use App\Services\ReferralService;
use App\Support\HunterDna;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class ApiAuthController extends Controller
{
    public function register(Request $request, GamificationService $gamification, ReferralService $referrals): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:80'],
            'username' => ['required', 'string', 'alpha_dash', 'min:3', 'max:32', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:160', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'referral_code' => ['nullable', 'string', 'max:64'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = DB::transaction(function () use ($request, $gamification, $referrals): User {
            $user = User::query()->create([
                'name' => $request->string('name'),
                'username' => $request->string('username'),
                'email' => $request->string('email'),
                'password' => $request->string('password'),
                'status' => 'active',
            ]);

            $user->profile()->create([
                'profile_visibility' => 'public',
            ]);

            $user->privacySettings()->firstOrCreate([], [
                'profile_visibility' => 'public',
            ]);

            $gamification->award($user, 'account_created', source: $user, description: 'Account erstellt');

            $code = $request->string('referral_code')->trim()->value();
            if ($code !== '') {
                $link = ReferralLink::query()->where('code', $code)->first();
                if ($link) {
                    $referrals->recordSignup($link, $user, $request->ip(), (string) $request->userAgent());
                }
            }

            return $user;
        });

        $tokenData = ApiAccessToken::createForUser($user, $request->string('device_name')->trim()->value() ?: 'Android App');

        return response()->json([
            'message' => 'Registered.',
            'access_token' => $tokenData['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $tokenData['token']->expires_at?->toISOString(),
            'user' => new UserResource($user->load('profile')),
        ], 201);
    }

    public function login(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'login' => ['required', 'string', 'max:160'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $login = $request->string('login')->lower()->trim()->value();
        $user = User::query()
            ->whereRaw('LOWER(email) = ?', [$login])
            ->orWhereRaw('LOWER(username) = ?', [$login])
            ->first();

        if (! $user || ! Hash::check($request->string('password')->value(), (string) $user->password)) {
            $securityLog->record($user, 'api_login_failed', $request, ['login' => $login]);

            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        if ($user->status !== 'active') {
            $securityLog->record($user, 'api_login_blocked_suspended', $request);

            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $deviceName = $request->string('device_name')->trim()->value() ?: 'Android App';

        if ($user->hasTwoFactorEnabled()) {
            return $this->twoFactorRequiredResponse($user, $request, $securityLog, 'api_login', $deviceName);
        }

        return $this->issueApiTokenResponse($user, $request, $deviceName, $securityLog, 'api_login_success');
    }

    public function exchangeSocialLoginCode(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string', 'min:40', 'max:160'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $codeHash = hash('sha256', trim((string) $request->string('code')));

        $handoff = DB::transaction(function () use ($codeHash): ?MobileSocialLoginCode {
            $handoff = MobileSocialLoginCode::query()
                ->with(['user.profile'])
                ->where('code_hash', $codeHash)
                ->whereNull('used_at')
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if (! $handoff) {
                return null;
            }

            $handoff->forceFill(['used_at' => now()])->save();

            return $handoff;
        });

        if (! $handoff || ! $handoff->user) {
            return response()->json(['message' => 'Invalid or expired social login code.'], 422);
        }

        $user = $handoff->user;

        if ($user->status !== 'active') {
            return response()->json(['message' => 'Account suspended.'], 403);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $deviceName = $request->string('device_name')->trim()->value()
            ?: ($handoff->device_name ?: 'Android App');

        if ($user->hasTwoFactorEnabled()) {
            return $this->twoFactorRequiredResponse($user, $request, $securityLog, 'mobile_social_login', $deviceName, [
                'provider' => $handoff->provider,
            ]);
        }

        $tokenData = ApiAccessToken::createForUser($user, $deviceName);

        $securityLog->record($user, 'mobile_social_login_success', $request, [
            'provider' => $handoff->provider,
            'token_id' => $tokenData['token']->id,
        ]);

        return response()->json([
            'message' => 'Logged in.',
            'access_token' => $tokenData['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $tokenData['token']->expires_at?->toISOString(),
            'user' => new UserResource($user->load('profile')),
        ]);
    }

    public function nativeGoogleLogin(
        Request $request,
        NativeGoogleTokenVerifier $google,
        SocialIdentityUserResolver $resolver,
        SecurityLogService $securityLog,
    ): JsonResponse {
        $validator = Validator::make($request->all(), [
            'id_token' => ['required', 'string', 'min:100', 'max:5000'],
            'device_name' => ['nullable', 'string', 'max:80'],
            'referral_code' => ['nullable', 'string', 'max:64'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $identity = $google->verify($request->string('id_token')->value());
            $user = $resolver->resolve(
                $identity,
                $request,
                $request->string('referral_code')->trim()->value() ?: null,
            );

            if ($user->status !== 'active') {
                $securityLog->record($user, 'native_google_login_blocked_suspended', $request, [
                    'provider' => 'google',
                    'provider_user_id' => $identity->providerUserId,
                ]);

                return response()->json(['message' => 'Account suspended.'], 403);
            }

            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => $request->ip(),
            ])->save();

            $deviceName = $request->string('device_name')->trim()->value() ?: 'Android App';

            if ($user->hasTwoFactorEnabled()) {
                return $this->twoFactorRequiredResponse($user, $request, $securityLog, 'native_google_login', $deviceName, [
                    'provider' => 'google',
                    'provider_user_id' => $identity->providerUserId,
                ]);
            }

            $tokenData = ApiAccessToken::createForUser(
                $user,
                $deviceName
            );

            $securityLog->record($user, 'native_google_login_success', $request, [
                'provider' => 'google',
                'provider_user_id' => $identity->providerUserId,
                'token_id' => $tokenData['token']->id,
            ]);

            return response()->json([
                'message' => 'Logged in.',
                'access_token' => $tokenData['access_token'],
                'token_type' => 'Bearer',
                'expires_at' => $tokenData['token']->expires_at?->toISOString(),
                'user' => new UserResource($user->load('profile')),
            ]);
        } catch (RuntimeException $exception) {
            $securityLog->record(null, 'native_google_login_failed', $request, [
                'provider' => 'google',
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function completeTwoFactorChallenge(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'two_factor_token' => ['required', 'string', 'min:40', 'max:160'],
            'code' => ['required', 'string', 'max:32'],
            'device_name' => ['nullable', 'string', 'max:80'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $challenge = UserTwoFactorChallenge::findValidPlainToken(
            $request->string('two_factor_token')->value()
        );

        if (! $challenge || ! in_array($challenge->purpose, ['api_login', 'mobile_social_login', 'native_google_login'], true)) {
            return response()->json(['message' => '2FA challenge expired.'], 422);
        }

        $user = $challenge->user;

        if (! $user || $user->status !== 'active' || ! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA challenge expired.'], 422);
        }

        $code = $request->string('code')->value();
        $valid = $twoFactor->verifyCode($user->two_factor_secret, $code)
            || $twoFactor->verifyAndConsumeRecoveryCode($user, $code);

        if (! $valid) {
            $securityLog->record($user, 'api_two_factor_failed', $request, [
                'purpose' => $challenge->purpose,
            ]);

            return response()->json([
                'message' => 'Invalid 2FA code.',
                'errors' => [
                    'code' => ['Invalid 2FA code.'],
                ],
            ], 422);
        }

        $challenge->markUsed();

        $deviceName = $request->string('device_name')->trim()->value()
            ?: ($challenge->device_name ?: 'Android App');

        return $this->issueApiTokenResponse($user, $request, $deviceName, $securityLog, 'api_two_factor_success', [
            'purpose' => $challenge->purpose,
            'challenge_id' => $challenge->id,
        ]);
    }

    public function twoFactorStatus(Request $request, TwoFactorService $twoFactor): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'message' => '2FA status loaded.',
            'two_factor' => $this->twoFactorPayload($user, $twoFactor),
        ]);
    }

    public function startTwoFactorSetup(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->string('current_password')->value(), (string) $user->password)) {
            return response()->json([
                'message' => 'Current password is invalid.',
                'errors' => [
                    'current_password' => ['The current password is invalid.'],
                ],
            ], 422);
        }

        $secret = $twoFactor->generateSecret();
        $challengeData = UserTwoFactorChallenge::createForUser(
            $user,
            'api_two_factor_setup',
            'Android App',
            $request->ip(),
            (string) $request->userAgent(),
            $secret,
            15,
        );

        $securityLog->record($user, 'api_two_factor_setup_started', $request, [
            'challenge_id' => $challengeData['challenge']->id,
        ]);

        return response()->json([
            'message' => '2FA setup started.',
            'two_factor' => $this->twoFactorPayload($user, $twoFactor),
            'setup' => [
                'setup_token' => $challengeData['plain_token'],
                'secret' => $secret,
                'otpauth_url' => $twoFactor->keyUri($user, $secret),
                'expires_at' => $challengeData['challenge']->expires_at?->toISOString(),
            ],
        ]);
    }

    public function enableTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'setup_token' => ['required', 'string', 'min:40', 'max:160'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $challenge = UserTwoFactorChallenge::findValidPlainToken(
            $request->string('setup_token')->value(),
            'api_two_factor_setup'
        );

        if (! $challenge || (int) $challenge->user_id !== (int) $user->id || ! $challenge->secret) {
            return response()->json(['message' => '2FA setup expired.'], 422);
        }

        if (! $twoFactor->verifyCode($challenge->secret, $request->string('code')->value())) {
            return response()->json([
                'message' => 'Invalid 2FA code.',
                'errors' => [
                    'code' => ['Invalid 2FA code.'],
                ],
            ], 422);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $challenge->secret,
            'two_factor_recovery_codes' => $twoFactor->recoveryHashes($recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $challenge->markUsed();

        $securityLog->record($user, 'api_two_factor_enabled', $request, [
            'challenge_id' => $challenge->id,
        ]);

        return response()->json([
            'message' => '2FA enabled.',
            'two_factor' => $this->twoFactorPayload($user->fresh(), $twoFactor),
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! Hash::check($request->string('current_password')->value(), (string) $user->password)) {
            return response()->json([
                'message' => 'Current password is invalid.',
                'errors' => [
                    'current_password' => ['The current password is invalid.'],
                ],
            ], 422);
        }

        $valid = $twoFactor->verifyCode($user->two_factor_secret, $request->string('code')->value())
            || $twoFactor->verifyAndConsumeRecoveryCode($user, $request->string('code')->value());

        if (! $valid) {
            return response()->json([
                'message' => 'Invalid 2FA code.',
                'errors' => [
                    'code' => ['Invalid 2FA code.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $securityLog->record($user, 'api_two_factor_disabled', $request);

        return response()->json([
            'message' => '2FA disabled.',
            'two_factor' => $this->twoFactorPayload($user->fresh(), $twoFactor),
        ]);
    }

    public function regenerateTwoFactorRecoveryCodes(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return response()->json(['message' => '2FA is not enabled.'], 422);
        }

        if (! Hash::check($request->string('current_password')->value(), (string) $user->password)) {
            return response()->json([
                'message' => 'Current password is invalid.',
                'errors' => [
                    'current_password' => ['The current password is invalid.'],
                ],
            ], 422);
        }

        $valid = $twoFactor->verifyCode($user->two_factor_secret, $request->string('code')->value())
            || $twoFactor->verifyAndConsumeRecoveryCode($user, $request->string('code')->value());

        if (! $valid) {
            return response()->json([
                'message' => 'Invalid 2FA code.',
                'errors' => [
                    'code' => ['Invalid 2FA code.'],
                ],
            ], 422);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $twoFactor->recoveryHashes($recoveryCodes),
        ])->save();

        $securityLog->record($user, 'api_two_factor_recovery_codes_regenerated', $request);

        return response()->json([
            'message' => 'Recovery codes regenerated.',
            'two_factor' => $this->twoFactorPayload($user->fresh(), $twoFactor),
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return $this->mobileProfileResponse($request->user());
    }


    public function privacy(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing(['privacySettings', 'profile']);
        $settings = $user->privacySettings ?: $user->privacySettings()->create();

        return response()->json([
            'settings' => $this->mobilePrivacySettings($settings),
        ]);
    }


    public function updatePrivacy(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_visibility' => ['required', Rule::in(['public', 'registered', 'private'])],
            'allow_messages_from' => ['required', Rule::in(['everyone', 'registered', 'following', 'nobody'])],
            'allow_team_invites' => ['nullable', 'boolean'],
            'allow_lfg_invites' => ['nullable', 'boolean'],
            'show_online_status' => ['nullable', 'boolean'],
            'show_activity_feed' => ['nullable', 'boolean'],
            'show_gamification' => ['nullable', 'boolean'],
            'data_usage_consent' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $user = $request->user();

        $settings = $user->privacySettings()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'profile_visibility' => $validated['profile_visibility'],
                'allow_messages_from' => $validated['allow_messages_from'],
                'allow_team_invites' => $request->boolean('allow_team_invites'),
                'allow_lfg_invites' => $request->boolean('allow_lfg_invites'),
                'show_online_status' => $request->boolean('show_online_status'),
                'show_activity_feed' => $request->boolean('show_activity_feed'),
                'show_gamification' => $request->boolean('show_gamification'),
                'data_usage_consent' => $request->boolean('data_usage_consent'),
            ]
        );

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['profile_visibility' => $validated['profile_visibility']]
        );

        return response()->json([
            'message' => 'Privacy settings saved.',
            'settings' => $this->mobilePrivacySettings($settings),
        ]);
    }


    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $validated = $validator->validated();

        if (! Hash::check((string) $validated['current_password'], (string) $user->password)) {
            return response()->json([
                'message' => 'Current password is invalid.',
                'errors' => [
                    'current_password' => ['The current password is invalid.'],
                ],
            ], 422);
        }

        $user->forceFill([
            'password' => Hash::make((string) $validated['password']),
        ])->save();

        return response()->json([
            'message' => 'Password updated.',
        ]);
    }


    public function updateProfile(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:80'],
            'headline' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'hunt_role' => ['nullable', 'string', 'max:60'],
            'discord_name' => ['nullable', 'string', 'max:80'],
            'is_lfg_available' => ['nullable', 'boolean'],
            'hunter_dna' => ['nullable', 'array:voice,preferred_mode,experience,temper,goals,mentor'],
            'hunter_dna.voice' => ['nullable', Rule::in(['yes', 'no', 'optional'])],
            'hunter_dna.preferred_mode' => ['nullable', Rule::in(['solo', 'duo', 'trio', 'flexible'])],
            'hunter_dna.experience' => ['nullable', Rule::in(['new', 'casual', 'experienced', 'veteran'])],
            'hunter_dna.temper' => ['nullable', Rule::in(['chill', 'focused', 'tryhard', 'chaotic'])],
            'hunter_dna.goals' => ['nullable', 'array', 'max:5'],
            'hunter_dna.goals.*' => ['nullable', Rule::in(['pvp', 'bounty', 'boss', 'extract', 'events', 'quests', 'teach', 'learn', 'memes'])],
            'hunter_dna.mentor' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $data = $validator->validated();

        if (array_key_exists('name', $data)) {
            $name = trim((string) ($data['name'] ?? ''));
            if ($name !== '') {
                $user->forceFill(['name' => $name])->save();
            }
        }

        $profile = $user->profile()->firstOrCreate([], [
            'profile_visibility' => 'public',
        ]);

        $profileFields = [
            'headline',
            'bio',
            'platform',
            'playstyle',
            'region',
            'language',
            'hunt_role',
            'discord_name',
        ];

        $updates = [];
        foreach ($profileFields as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }

            $value = trim((string) ($data[$field] ?? ''));
            $updates[$field] = $value === '' ? null : $value;
        }

        if (array_key_exists('is_lfg_available', $data)) {
            $updates['is_lfg_available'] = (bool) $data['is_lfg_available'];
        }

        if (array_key_exists('hunter_dna', $data)) {
            $hunterDna = HunterDna::normalize($data['hunter_dna'] ?? []);
            $updates['hunter_dna'] = $hunterDna === [] ? null : $hunterDna;
            $updates['hunter_dna_completed_at'] = HunterDna::isComplete($hunterDna) ? now() : null;
        }

        if ($updates !== []) {
            $profile->fill($updates)->save();
        }

        $user->load('profile');

        return $this->mobileProfileResponse($user, 'Profile updated.');
    }


    public function updateAvatar(Request $request, MediaService $mediaService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.profile_avatar_kb', 2048)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('avatar');

        if (! $file) {
            return response()->json([
                'message' => 'Avatar file is missing.',
            ], 422);
        }

        $mediaService->assertAllowed($file, $user, 'profile_avatar');

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $avatarAsset = $mediaService->store($file, $user, 'profile_avatar', [
            'attachable' => $user,
            'visibility' => 'public',
        ]);

        $user->forceFill([
            'avatar_path' => $avatarAsset->path,
        ])->save();

        $user->load('profile');

        return $this->mobileProfileResponse($user, 'Avatar updated.');
    }


    public function updateCover(Request $request, MediaService $mediaService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cover' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.config('hunthub.upload_limits.profile_cover_kb', 4096)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('cover');

        if (! $file) {
            return response()->json([
                'message' => 'Cover file is missing.',
            ], 422);
        }

        $mediaService->assertAllowed($file, $user, 'profile_cover');

        if ($user->cover_path) {
            Storage::disk('public')->delete($user->cover_path);
        }

        $coverAsset = $mediaService->store($file, $user, 'profile_cover', [
            'attachable' => $user,
            'visibility' => 'public',
        ]);

        $user->forceFill([
            'cover_path' => $coverAsset->path,
        ])->save();

        $user->load('profile');

        return $this->mobileProfileResponse($user, 'Cover updated.');
    }

    private function twoFactorRequiredResponse(User $user, Request $request, SecurityLogService $securityLog, string $purpose, string $deviceName, array $meta = []): JsonResponse
    {
        $challengeData = UserTwoFactorChallenge::createForUser(
            $user,
            $purpose,
            $deviceName,
            $request->ip(),
            (string) $request->userAgent(),
        );

        $securityLog->record($user, 'api_two_factor_required', $request, [
            'purpose' => $purpose,
            'challenge_id' => $challengeData['challenge']->id,
        ] + $meta);

        return response()->json([
            'message' => '2FA required.',
            'two_factor_required' => true,
            'two_factor_token' => $challengeData['plain_token'],
            'expires_at' => $challengeData['challenge']->expires_at?->toISOString(),
            'methods' => ['totp', 'recovery_code'],
            'user_hint' => [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'name' => (string) $user->name,
            ],
        ], 202);
    }

    private function issueApiTokenResponse(User $user, Request $request, string $deviceName, SecurityLogService $securityLog, string $event, array $meta = []): JsonResponse
    {
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $tokenData = ApiAccessToken::createForUser($user, $deviceName);

        $securityLog->record($user, $event, $request, [
            'token_id' => $tokenData['token']->id,
        ] + $meta);

        return response()->json([
            'message' => 'Logged in.',
            'access_token' => $tokenData['access_token'],
            'token_type' => 'Bearer',
            'expires_at' => $tokenData['token']->expires_at?->toISOString(),
            'user' => new UserResource($user->load('profile')),
        ]);
    }

    private function twoFactorPayload(User $user, TwoFactorService $twoFactor): array
    {
        return [
            'enabled' => $user->hasTwoFactorEnabled(),
            'confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
            'recovery_code_count' => $twoFactor->recoveryCodeCount($user),
        ];
    }

    private function mobilePrivacySettings($settings): array
    {
        return [
            'profile_visibility' => (string) ($settings->profile_visibility ?? 'public'),
            'allow_messages_from' => (string) ($settings->allow_messages_from ?? 'registered'),
            'allow_team_invites' => (bool) ($settings->allow_team_invites ?? true),
            'allow_lfg_invites' => (bool) ($settings->allow_lfg_invites ?? true),
            'show_online_status' => (bool) ($settings->show_online_status ?? true),
            'show_activity_feed' => (bool) ($settings->show_activity_feed ?? true),
            'show_gamification' => (bool) ($settings->show_gamification ?? true),
            'data_usage_consent' => (bool) ($settings->data_usage_consent ?? false),
        ];
    }


    private function mobileProfileResponse(User $user, ?string $message = null): JsonResponse
    {
        $user->loadMissing('profile');

        $payload = [
            'user' => new UserResource($user),
            'counts' => $this->mobileCounts($user),
            'profile_summary' => $this->mobileProfileSummary($user),
        ];

        if ($message) {
            $payload = ['message' => $message] + $payload;
        }

        return response()->json($payload);
    }

    private function mobileCounts(User $user): array
    {
        return [
            'unread_messages' => $user->unreadMessagesCount(),
            'unread_notifications' => $user->notificationItems()->standard()->whereNull('read_at')->count(),
        ];
    }

    private function mobileProfileSummary(User $user): array
    {
        $profile = $user->profile;
        $level = max(1, (int) ($user->level ?: 1));
        $xpTotal = max(0, (int) ($user->xp_total ?: 0));
        $nextLevelXp = max(250, $level * 250);
        $progressPercent = $nextLevelXp > 0 ? min(100, (int) floor(($xpTotal / $nextLevelXp) * 100)) : 0;

        $badgeCount = $user->badges()->count();
        $activeQuestCount = Quest::query()->where('is_active', true)->count();
        $completedQuestCount = $user->questProgress()->whereNotNull('completed_at')->count();
        $friendsCount = $user->friendsCount();

        $latestBadges = $user->badges()
            ->orderByPivot('awarded_at', 'desc')
            ->orderBy('badges.sort_order')
            ->limit(12)
            ->get()
            ->map(fn ($badge): array => [
                'id' => (int) $badge->id,
                'slug' => (string) $badge->slug,
                'name' => (string) $badge->name,
                'category' => (string) ($badge->category ?? ''),
                'rarity' => (string) ($badge->rarity ?? 'common'),
                'rarity_label' => method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ucfirst((string) ($badge->rarity ?? 'common')),
                'icon' => (string) ($badge->icon ?? ''),
                'icon_url' => $badge->iconUrl(),
                'description' => (string) ($badge->description ?? ''),
                'xp_reward' => (int) ($badge->xp_reward ?? 0),
                'awarded_at' => $badge->pivot?->awarded_at ? (string) $badge->pivot->awarded_at : null,
                'award_reason' => $badge->pivot?->award_reason,
            ])
            ->values();

        $quests = Quest::query()
            ->where('is_active', true)
            ->with(['progress' => fn ($query) => $query->where('user_id', $user->id)])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit(8)
            ->get()
            ->map(function (Quest $quest): array {
                $progress = $quest->progress->first();
                $target = max(1, (int) $quest->target_count);
                $current = min($target, max(0, (int) ($progress?->progress_count ?? 0)));
                $completed = $progress?->completed_at !== null;

                return [
                    'id' => (int) $quest->id,
                    'slug' => (string) $quest->slug,
                    'name' => (string) $quest->name,
                    'category' => (string) ($quest->category ?? ''),
                    'description' => (string) ($quest->description ?? ''),
                    'period' => (string) ($quest->period ?? ''),
                    'period_label' => method_exists($quest, 'periodLabel') ? $quest->periodLabel() : ucfirst((string) ($quest->period ?? 'once')),
                    'target_count' => $target,
                    'progress_count' => $current,
                    'progress_percent' => min(100, (int) floor(($current / $target) * 100)),
                    'completed' => $completed,
                    'completed_at' => $progress?->completed_at?->toISOString(),
                    'xp_reward' => (int) ($quest->xp_reward ?? 0),
                    'badge_slug' => $quest->badge_slug,
                    'icon' => (string) ($quest->icon ?? ''),
                    'icon_url' => $quest->iconUrl(),
                ];
            })
            ->values();

        $friendsPreview = Friendship::query()
            ->forUser($user)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->with(['userOne.profile', 'userTwo.profile'])
            ->latest('accepted_at')
            ->limit(6)
            ->get()
            ->map(function (Friendship $friendship) use ($user): ?array {
                $friend = $friendship->otherUser($user);

                if (! $friend) {
                    return null;
                }

                return [
                    'id' => (int) $friend->id,
                    'name' => (string) $friend->name,
                    'username' => (string) $friend->username,
                    'avatar_url' => $friend->avatar_path ? Storage::disk('public')->url($friend->avatar_path) : asset('assets/vikinger/img/default-avatar.svg'),
                    'headline' => (string) ($friend->profile?->headline ?? ''),
                    'level' => (int) ($friend->level ?? 1),
                    'accepted_at' => $friendship->accepted_at?->toISOString(),
                ];
            })
            ->filter()
            ->values();

        $recentPosts = $user->feedPosts()
            ->with(['media.mediaAsset'])
            ->withCount(['comments', 'reactions'])
            ->where('status', 'published')
            ->whereNull('team_id')
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($post): array => $this->profilePostActivityItem($post))
            ->values();

        $recentComments = $user->feedComments()
            ->with(['post.user.profile'])
            ->whereHas('post', fn ($postQuery) => $postQuery
                ->where('status', 'published')
                ->whereNull('team_id'))
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn ($comment): array => [
                'id' => (int) $comment->id,
                'post_id' => (int) $comment->feed_post_id,
                'body' => str((string) $comment->body)->stripTags()->limit(180)->toString(),
                'post_excerpt' => $comment->post ? $comment->post->excerpt(120) : '',
                'post_author' => $comment->post?->user ? [
                    'id' => (int) $comment->post->user->id,
                    'name' => (string) $comment->post->user->name,
                    'username' => (string) $comment->post->user->username,
                ] : null,
                'created_at' => $comment->created_at?->toISOString(),
            ])
            ->values();

        $completionLabels = [
            'name' => 'Name',
            'username' => 'Username',
            'bio' => 'Bio',
            'platform' => 'Plattform',
            'playstyle' => 'Spielstil',
        ];

        $completionItems = collect(\App\Support\ProfileCompletion::checks($user))
            ->map(fn (bool $done, string $key): array => [
                'key' => $key,
                'label' => $completionLabels[$key] ?? $key,
                'done' => $done,
            ])
            ->values();

        $completedCompletionItems = $completionItems->where('done', true)->count();
        $totalCompletionItems = $completionItems->count();

        return [
            'progress' => [
                'level' => $level,
                'xp_total' => $xpTotal,
                'next_level' => $level + 1,
                'next_level_xp' => $nextLevelXp,
                'xp_to_next_level' => max(0, $nextLevelXp - $xpTotal),
                'progress_percent' => $progressPercent,
                'trust_score' => (int) ($user->trust_score ?? 0),
                'last_xp_at' => $user->last_xp_at?->toISOString(),
            ],
            'counts' => [
                'badges' => $badgeCount,
                'active_quests' => $activeQuestCount,
                'completed_quests' => $completedQuestCount,
                'friends' => $friendsCount,
                'teams' => $user->activeTeams()->count(),
                'posts' => $user->feedPosts()->where('status', 'published')->count(),
                'comments' => $user->feedComments()->count(),
                'moments' => $user->moments()->count(),
            ],
            'latest_badges' => $latestBadges,
            'quests' => $quests,
            'friends_preview' => $friendsPreview,
            'recent_posts' => $recentPosts,
            'recent_comments' => $recentComments,
            'completion' => [
                'completed' => $completedCompletionItems,
                'total' => $totalCompletionItems,
                'percent' => $totalCompletionItems > 0 ? (int) floor(($completedCompletionItems / $totalCompletionItems) * 100) : 0,
                'items' => $completionItems,
            ],
        ];
    }


    private function profilePostActivityItem($post): array
    {
        $firstMedia = $post->media->first();
        $mimeType = (string) ($firstMedia?->mime_type ?? $firstMedia?->mediaAsset?->mime_type ?? '');
        $mediaType = str_starts_with($mimeType, 'video/') ? 'video' : (str_starts_with($mimeType, 'image/') ? 'image' : '');

        return [
            'id' => (int) $post->id,
            'body' => $post->excerpt(220),
            'visibility' => (string) $post->visibility,
            'comments_count' => (int) ($post->comments_count ?? 0),
            'reactions_count' => (int) ($post->reactions_count ?? 0),
            'media_count' => (int) $post->media->count(),
            'first_media_type' => $mediaType,
            'first_media_url' => $firstMedia ? $firstMedia->url() : null,
            'created_at' => $post->created_at?->toISOString(),
        ];
    }


    public function dataProtection(Request $request): JsonResponse
    {
        $user = $request->user()->loadMissing('accountDeletionRequest');
        $deletionRequest = $user->accountDeletionRequest;

        return response()->json([
            'message' => 'Data protection status loaded.',
            'export' => [
                'available' => true,
                'format' => 'json',
                'note' => 'Der Export enthält strukturierte Account-, Profil-, Inhalts-, Aktivitäts-, Sicherheits- und Moderationsdaten. Hochgeladene Dateien werden als Referenzen exportiert.',
            ],
            'deletion' => $this->apiDeletionPayload($deletionRequest),
            'user' => [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'is_admin' => (bool) $user->isAdmin(),
            ],
        ]);
    }

    public function dataExport(Request $request, SecurityLogService $securityLog, UserDataExportService $exportService): JsonResponse
    {
        $user = $request->user();
        $payload = $exportService->build($user, $request);

        $securityLog->record($user, 'api_data_export_downloaded', $request, [
            'export_version' => $payload['meta']['export_version'] ?? null,
        ]);

        return response()->json([
            'message' => 'Data export generated.',
            'filename' => $exportService->filenameFor($user),
            'export' => $payload,
        ]);
    }

    public function requestDeletion(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json([
                'message' => 'Admin accounts cannot request deletion through the app.',
            ], 422);
        }

        $validator = Validator::make($request->all(), [
            'password' => ['nullable', 'string'],
            'delete_confirmation' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        if (! hash_equals((string) $user->username, trim((string) $validated['delete_confirmation']))) {
            return response()->json([
                'message' => 'Der eingegebene Benutzername stimmt nicht mit deinem Account überein.',
                'errors' => [
                    'delete_confirmation' => ['Der eingegebene Benutzername stimmt nicht mit deinem Account überein.'],
                ],
            ], 422);
        }

        $password = trim((string) ($validated['password'] ?? ''));
        if ($password !== '' && ! Hash::check($password, (string) $user->password)) {
            return response()->json([
                'message' => 'Das Passwort ist nicht korrekt.',
                'errors' => [
                    'password' => ['Das Passwort ist nicht korrekt.'],
                ],
            ], 422);
        }

        $deletionRequest = AccountDeletionRequest::updateOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'pending',
                'reason' => $validated['reason'] ?? null,
                'requested_at' => now(),
                'scheduled_for' => now()->addDays(14),
                'cancelled_at' => null,
                'processed_at' => null,
            ]
        );

        $securityLog->record($user, 'api_account_deletion_requested', $request, [
            'scheduled_for' => $deletionRequest->scheduled_for?->toIso8601String(),
        ]);

        return response()->json([
            'message' => 'Account deletion requested.',
            'deletion' => $this->apiDeletionPayload($deletionRequest),
        ]);
    }

    public function cancelDeletion(Request $request, SecurityLogService $securityLog): JsonResponse
    {
        $deletionRequest = $request->user()->accountDeletionRequest;

        if (! $deletionRequest || ! $deletionRequest->isPending()) {
            return response()->json([
                'message' => 'Es gibt keinen aktiven Löschantrag.',
                'deletion' => $this->apiDeletionPayload($deletionRequest),
            ]);
        }

        $deletionRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $securityLog->record($request->user(), 'api_account_deletion_cancelled', $request);

        return response()->json([
            'message' => 'Account deletion cancelled.',
            'deletion' => $this->apiDeletionPayload($deletionRequest->fresh()),
        ]);
    }

    private function apiDeletionPayload(?AccountDeletionRequest $deletionRequest): array
    {
        if (! $deletionRequest) {
            return [
                'status' => 'none',
                'is_pending' => false,
                'requested_at' => null,
                'requested_at_human' => null,
                'scheduled_for' => null,
                'scheduled_for_human' => null,
                'cancelled_at' => null,
                'processed_at' => null,
                'reason' => null,
            ];
        }

        return [
            'status' => (string) $deletionRequest->status,
            'is_pending' => $deletionRequest->isPending(),
            'requested_at' => $deletionRequest->requested_at?->toISOString(),
            'requested_at_human' => $deletionRequest->requested_at?->diffForHumans(),
            'scheduled_for' => $deletionRequest->scheduled_for?->toISOString(),
            'scheduled_for_human' => $deletionRequest->scheduled_for?->diffForHumans(),
            'cancelled_at' => $deletionRequest->cancelled_at?->toISOString(),
            'processed_at' => $deletionRequest->processed_at?->toISOString(),
            'reason' => $deletionRequest->reason,
        ];
    }


    public function sessions(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->attributes->get('api_access_token');
        $currentTokenId = $currentToken instanceof ApiAccessToken ? (int) $currentToken->id : 0;

        $sessions = ApiAccessToken::query()
            ->where('user_id', $user->id)
            ->whereNull('revoked_at')
            ->orderByRaw('id = ? desc', [$currentTokenId])
            ->orderByDesc('last_used_at')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get()
            ->map(fn (ApiAccessToken $token): array => $this->apiSessionPayload($token, $currentTokenId))
            ->values();

        return response()->json([
            'message' => 'Sessions loaded.',
            'sessions' => $sessions,
            'current_token_id' => $currentTokenId,
        ]);
    }

    public function revokeSession(Request $request, ApiAccessToken $token): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->attributes->get('api_access_token');
        $currentTokenId = $currentToken instanceof ApiAccessToken ? (int) $currentToken->id : 0;

        if ((int) $token->user_id !== (int) $user->id) {
            return response()->json(['message' => 'Session not found.'], 404);
        }

        if ((int) $token->id === $currentTokenId) {
            return response()->json([
                'message' => 'Die aktuelle App-Sitzung kann hier nicht beendet werden. Nutze dafür Logout.',
            ], 422);
        }

        if ($token->revoked_at === null) {
            $token->revoke();
        }

        return $this->sessions($request);
    }

    private function apiSessionPayload(ApiAccessToken $token, int $currentTokenId): array
    {
        return [
            'id' => (int) $token->id,
            'name' => (string) ($token->name ?: 'Android App'),
            'current' => (int) $token->id === $currentTokenId,
            'type' => str_contains(strtolower((string) $token->name), 'android') ? 'android' : 'api',
            'created_at' => $token->created_at?->toISOString(),
            'created_at_human' => $token->created_at?->diffForHumans(),
            'last_used_at' => $token->last_used_at?->toISOString(),
            'last_used_at_human' => $token->last_used_at?->diffForHumans(),
            'expires_at' => $token->expires_at?->toISOString(),
            'expires_at_human' => $token->expires_at?->diffForHumans(),
        ];
    }


    public function logout(Request $request): JsonResponse
    {
        $token = $request->attributes->get('api_access_token');

        if ($token instanceof ApiAccessToken) {
            $token->revoke();
        }

        return response()->json(['message' => 'Logged out.']);
    }
}

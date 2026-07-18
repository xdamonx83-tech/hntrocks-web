<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AccountDeletionRequest;
use App\Services\Auth\TwoFactorService;
use App\Services\SecurityLogService;
use App\Services\UserDataExportService;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function index(Request $request, TwoFactorService $twoFactor): View
    {
        $user = $request->user()->loadMissing(['securityEvents', 'accountDeletionRequest']);
        $setupSecret = (string) $request->session()->get('two_factor_setup_secret', '');
        $recoveryCodes = $request->session()->pull('two_factor_recovery_codes', []);

        $view = HntTheme::settingsEnabled() && ! $request->boolean('classic_settings')
            ? HntTheme::resolve('settings.security.index')
            : 'settings.security.index';

        return view($view, [
            'user' => $user,
            'events' => $user->securityEvents()->latest()->limit(30)->get(),
            'deletionRequest' => $user->accountDeletionRequest,
            'twoFactorEnabled' => $user->hasTwoFactorEnabled(),
            'twoFactorRecoveryCount' => $twoFactor->recoveryCodeCount($user),
            'twoFactorSetupSecret' => $setupSecret,
            'twoFactorSetupUri' => $setupSecret !== '' ? $twoFactor->keyUri($user, $setupSecret) : null,
            'twoFactorRecoveryCodes' => is_array($recoveryCodes) ? $recoveryCodes : [],
        ]);
    }

    public function events(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'before' => ['nullable', 'integer', 'min:1'],
        ]);

        $query = $request->user()->securityEvents()->latest('id');

        if (! empty($validated['before'])) {
            $query->where('id', '<', (int) $validated['before']);
        }

        $events = $query->limit(13)->get();
        $page = $events->take(12)->values();

        return response()->json([
            'html' => view('themes.hnt_preview.account.partials.security-events', [
                'events' => $page,
            ])->render(),
            'has_more' => $events->count() > 12,
            'next_cursor' => $page->last()?->id,
        ]);
    }

    public function updatePassword(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $this->validateSecurityRequest($request, [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        $request->user()->forceFill([
            'password' => Hash::make($validated['password']),
        ])->save();

        $securityLog->record($request->user(), 'password_changed', $request);

        return $this->securityRedirect($request)->with('status', __('ui.security_password_updated'));
    }

    public function startTwoFactorSetup(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): RedirectResponse
    {
        $this->validateSecurityRequest($request, [
            'current_password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $secret = $twoFactor->generateSecret();

        $request->session()->put('two_factor_setup_secret', $secret);
        $securityLog->record($user, 'two_factor_setup_started', $request);

        return $this->securityRedirect($request)->with('status', __('ui.two_factor_setup_started'));
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $this->validateSecurityRequest($request, [
            'code' => ['required', 'string', 'max:32'],
        ]);

        $secret = (string) $request->session()->get('two_factor_setup_secret', '');

        if ($secret === '') {
            throw $this->securityValidationException($request, [
                'code' => __('ui.two_factor_setup_missing'),
            ]);
        }

        if (! $twoFactor->verifyCode($secret, (string) $validated['code'])) {
            throw $this->securityValidationException($request, [
                'code' => __('ui.two_factor_code_invalid'),
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $request->user()->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $twoFactor->recoveryHashes($recoveryCodes),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $request->session()->forget('two_factor_setup_secret');
        $request->session()->flash('two_factor_recovery_codes', $recoveryCodes);

        $securityLog->record($request->user(), 'two_factor_enabled', $request);

        return $this->securityRedirect($request)->with('status', __('ui.two_factor_enabled_status'));
    }

    public function disableTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $this->validateSecurityRequest($request, [
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();
        $valid = $twoFactor->verifyCode($user->two_factor_secret, (string) $validated['code'])
            || $twoFactor->verifyAndConsumeRecoveryCode($user, (string) $validated['code']);

        if (! $valid) {
            throw $this->securityValidationException($request, [
                'code' => __('ui.two_factor_code_invalid'),
            ]);
        }

        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        $securityLog->record($user, 'two_factor_disabled', $request);

        return $this->securityRedirect($request)->with('status', __('ui.two_factor_disabled_status'));
    }

    public function regenerateTwoFactorRecoveryCodes(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $this->validateSecurityRequest($request, [
            'current_password' => ['required', 'current_password'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return $this->securityRedirect($request)->with('status', __('ui.two_factor_not_enabled'));
        }

        $valid = $twoFactor->verifyCode($user->two_factor_secret, (string) $validated['code'])
            || $twoFactor->verifyAndConsumeRecoveryCode($user, (string) $validated['code']);

        if (! $valid) {
            throw $this->securityValidationException($request, [
                'code' => __('ui.two_factor_code_invalid'),
            ]);
        }

        $recoveryCodes = $twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_recovery_codes' => $twoFactor->recoveryHashes($recoveryCodes),
        ])->save();

        $request->session()->flash('two_factor_recovery_codes', $recoveryCodes);
        $securityLog->record($user, 'two_factor_recovery_codes_regenerated', $request);

        return $this->securityRedirect($request)->with('status', __('ui.two_factor_recovery_codes_regenerated'));
    }

    public function export(Request $request, SecurityLogService $securityLog, UserDataExportService $exportService)
    {
        $user = $request->user();
        $payload = $exportService->build($user, $request);

        $securityLog->record($user, 'data_export_downloaded', $request, [
            'export_version' => $payload['meta']['export_version'] ?? null,
        ]);

        return response()->streamDownload(function () use ($payload): void {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }, $exportService->filenameFor($user), [
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function requestDeletion(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return $this->securityRedirect($request)->with('status', __('ui.account_deletion_admin_blocked_status'));
        }

        $validated = $this->validateSecurityRequest($request, [
            'password' => ['nullable', 'current_password'],
            'delete_confirmation' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        if (! hash_equals((string) $user->username, trim((string) $validated['delete_confirmation']))) {
            throw $this->securityValidationException($request, [
                'delete_confirmation' => __('ui.account_deletion_confirmation_mismatch'),
            ]);
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

        $securityLog->record($user, 'account_deletion_requested', $request, [
            'scheduled_for' => $deletionRequest->scheduled_for?->toIso8601String(),
        ]);

        return $this->securityRedirect($request)->with('status', __('ui.account_deletion_requested_status'));
    }

    public function cancelDeletion(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $deletionRequest = $request->user()->accountDeletionRequest;

        if (! $deletionRequest || ! $deletionRequest->isPending()) {
            return $this->securityRedirect($request)->with('status', __('ui.no_active_deletion_request'));
        }

        $deletionRequest->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);

        $securityLog->record($request->user(), 'account_deletion_cancelled', $request);

        return $this->securityRedirect($request)->with('status', __('ui.account_deletion_cancelled_status'));
    }

    private function validateSecurityRequest(Request $request, array $rules): array
    {
        if ($request->input('settings_section') !== 'security') {
            return $request->validate($rules);
        }

        $validator = validator($request->all(), $rules);

        if ($validator->fails()) {
            throw $this->securityValidationException($request, $validator->errors()->toArray());
        }

        return $validator->validated();
    }

    private function securityValidationException(Request $request, array $messages): ValidationException
    {
        $exception = ValidationException::withMessages($messages);

        if ($request->input('settings_section') === 'security') {
            $exception->errorBag((string) $request->input('settings_action', 'security'));
            $exception->redirectTo(route('account.settings.edit').'#security');
        }

        return $exception;
    }

    private function securityRedirect(Request $request): RedirectResponse
    {
        if ($request->input('settings_section') === 'security') {
            return redirect()->to(route('account.settings.edit').'#security');
        }

        return back();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthShowcaseStatsService;
use App\Services\Auth\TwoFactorService;
use App\Services\SecurityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(AuthShowcaseStatsService $showcaseStats): View
    {
        return view('auth.login', [
            'authShowcaseStats' => $showcaseStats->get(),
        ]);
    }

    public function store(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $login = strtolower(trim($validated['login']));
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $attemptedUser = User::where($field, $login)->first();

        if (! Auth::attempt([
            $field => $login,
            'password' => $validated['password'],
        ], $request->boolean('remember'))) {
            $securityLog->record($attemptedUser, 'login_failed', $request, [
                'login' => $login,
                'field' => $field,
            ]);

            throw ValidationException::withMessages([
                'login' => __('ui.login_credentials_invalid'),
            ]);
        }

        $user = Auth::user();

        if ($user?->isSuspended()) {
            $securityLog->record($user, 'login_blocked_suspended', $request);
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            throw ValidationException::withMessages([
                'login' => __('ui.login_account_suspended'),
            ]);
        }

        if ($user?->hasTwoFactorEnabled()) {
            Auth::guard('web')->logout();

            $request->session()->put('two_factor_login', [
                'user_id' => $user->id,
                'remember' => $request->boolean('remember'),
                'created_at' => now()->timestamp,
            ]);

            $securityLog->record($user, 'login_two_factor_required', $request);

            return redirect()->route('login.two-factor');
        }

        $this->finalizeLogin($request, $user, $securityLog, 'login_success');

        return redirect()->intended(route('feed.index'));
    }

    public function twoFactorChallenge(Request $request): View|RedirectResponse
    {
        $pending = $request->session()->get('two_factor_login');

        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        return view('auth.two-factor-challenge');
    }

    public function confirmTwoFactor(Request $request, TwoFactorService $twoFactor, SecurityLogService $securityLog): RedirectResponse
    {
        $pending = $request->session()->get('two_factor_login');

        if (! is_array($pending) || empty($pending['user_id'])) {
            return redirect()->route('login');
        }

        if (((int) ($pending['created_at'] ?? 0)) < now()->subMinutes(10)->timestamp) {
            $request->session()->forget('two_factor_login');

            return redirect()->route('login')->withErrors([
                'login' => __('ui.two_factor_challenge_expired'),
            ]);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $user = User::query()->find((int) $pending['user_id']);

        if (! $user || ! $user->hasTwoFactorEnabled() || $user->isSuspended()) {
            $request->session()->forget('two_factor_login');

            return redirect()->route('login')->withErrors([
                'login' => __('ui.two_factor_challenge_expired'),
            ]);
        }

        $code = (string) $validated['code'];
        $valid = $twoFactor->verifyCode($user->two_factor_secret, $code)
            || $twoFactor->verifyAndConsumeRecoveryCode($user, $code);

        if (! $valid) {
            $securityLog->record($user, 'login_two_factor_failed', $request);

            throw ValidationException::withMessages([
                'code' => __('ui.two_factor_code_invalid'),
            ]);
        }

        Auth::login($user, (bool) ($pending['remember'] ?? false));
        $request->session()->forget('two_factor_login');

        $this->finalizeLogin($request, $user, $securityLog, 'login_two_factor_success');

        return redirect()->intended(route('feed.index'));
    }

    public function destroy(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $securityLog->record($user, 'logout', $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function finalizeLogin(Request $request, ?User $user, SecurityLogService $securityLog, string $event): void
    {
        if (! $user) {
            return;
        }

        $request->session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ])->save();

        $securityLog->record($user, $event, $request);
    }
}

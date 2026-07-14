<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AuthShowcaseStatsService;
use App\Services\GamificationService;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(AuthShowcaseStatsService $showcaseStats): View
    {
        return view('auth.register', [
            'authShowcaseStats' => $showcaseStats->get(),
        ]);
    }

    public function store(Request $request, GamificationService $gamification, ReferralService $referrals): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[A-Za-z0-9_.-]+$/', 'unique:users,username'],
            'email' => ['required', 'string', 'lowercase', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'legal_terms' => ['accepted'],
        ], [
            'username.regex' => __('ui.username_validation_regex'),
            'legal_terms.accepted' => __('ui.register_legal_terms_required'),
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('register')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $displayName = trim($validated['username']);
        $username = strtolower($displayName);

        $user = User::create([
            'name' => $displayName,
            'username' => $username,
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
        ]);

        $user->profile()->create([
            'profile_visibility' => 'public',
        ]);

        $user->privacySettings()->create([
            'profile_visibility' => 'public',
        ]);

        $referrals->attachSignup($user, $request->session()->pull('referral_code'), [
            'registered_via' => 'web',
        ]);

        $gamification->award($user, 'account_created', source: $user, description: 'Account erstellt');

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route('feed.index');
    }
}

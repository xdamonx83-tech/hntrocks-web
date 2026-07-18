<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBlock;
use App\Services\SecurityLogService;
use App\Services\UserBlockService;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PrivacyController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user()->loadMissing(['privacySettings', 'profile']);
        $settings = $user->privacySettings ?: $user->privacySettings()->create();

        $view = HntTheme::settingsEnabled() && ! $request->boolean('classic_settings')
            ? HntTheme::resolve('settings.privacy.edit')
            : 'settings.privacy.edit';

        return view($view, [
            'user' => $user,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        if ($request->input('settings_section') === 'privacy') {
            return $this->updateIntegratedSettingsTab($request, $securityLog);
        }

        $validated = $request->validate([
            'profile_visibility' => ['required', Rule::in(['public', 'registered', 'private'])],
            'allow_messages_from' => ['required', Rule::in(['everyone', 'registered', 'following', 'nobody'])],
            'allow_team_invites' => ['nullable', 'boolean'],
            'allow_lfg_invites' => ['nullable', 'boolean'],
            'show_online_status' => ['nullable', 'boolean'],
            'show_activity_feed' => ['nullable', 'boolean'],
            'show_gamification' => ['nullable', 'boolean'],
            'data_usage_consent' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();

        $user->privacySettings()->updateOrCreate(
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

        $securityLog->record($user, 'privacy_settings_updated', $request, [
            'profile_visibility' => $validated['profile_visibility'],
            'allow_messages_from' => $validated['allow_messages_from'],
        ]);

        return back()->with('status', __('ui.privacy_settings_saved'));
    }

    private function updateIntegratedSettingsTab(Request $request, SecurityLogService $securityLog): RedirectResponse
    {
        $validated = $request->validate([
            'profile_visibility' => ['required', Rule::in(['public', 'registered', 'private'])],
            'allow_messages_from' => ['required', Rule::in(['everyone', 'registered', 'following', 'nobody'])],
            'show_online_status' => ['nullable', 'boolean'],
            'show_activity_feed' => ['nullable', 'boolean'],
            'show_gamification' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $data = [
            'profile_visibility' => $validated['profile_visibility'],
            'allow_messages_from' => $validated['allow_messages_from'],
            'show_online_status' => $request->boolean('show_online_status'),
            'show_activity_feed' => $request->boolean('show_activity_feed'),
            'show_gamification' => $request->boolean('show_gamification'),
        ];

        $user->privacySettings()->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            ['profile_visibility' => $validated['profile_visibility']]
        );

        $securityLog->record($user, 'privacy_settings_updated', $request, $data);

        return redirect()
            ->to(route('account.settings.edit').'#privacy')
            ->with('status', __('ui.privacy_settings_saved'));
    }

    public function blocks(Request $request): View
    {
        $blocks = $request->user()
            ->blockedUsers()
            ->with('blockedUser')
            ->latest()
            ->paginate(20);

        $view = HntTheme::settingsEnabled() && ! $request->boolean('classic_settings')
            ? HntTheme::resolve('settings.privacy.blocks')
            : 'settings.privacy.blocks';

        return view($view, [
            'blocks' => $blocks,
        ]);
    }

    public function block(Request $request, SecurityLogService $securityLog, UserBlockService $blocks): RedirectResponse
    {
        $rules = [
            'username' => ['required', 'string', 'max:32'],
            'reason' => ['nullable', 'string', 'max:120'],
        ];

        if ($request->input('settings_section') === 'blocked') {
            $validator = validator($request->all(), $rules);

            if ($validator->fails()) {
                return $this->blockedUsersRedirect($request)
                    ->withErrors($validator)
                    ->withInput();
            }

            $validated = $validator->validated();
        } else {
            $validated = $request->validate($rules);
        }

        $username = ltrim(strtolower(trim($validated['username'])), '@');
        $target = User::where('username', $username)->first();

        if (! $target) {
            return $this->blockedUsersRedirect($request)
                ->withErrors(['username' => __('ui.privacy_user_not_found')])
                ->withInput();
        }

        if ((int) $target->id === (int) $request->user()->id) {
            return $this->blockedUsersRedirect($request)
                ->withErrors(['username' => __('ui.privacy_cannot_block_self')])
                ->withInput();
        }

        UserBlock::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'blocked_user_id' => $target->id,
            ],
            [
                'reason' => $validated['reason'] ?? null,
            ]
        );

        Friendship::query()->between($request->user(), $target)->delete();
        $blocks->forget($request->user());

        $securityLog->record($request->user(), 'user_blocked', $request, [
            'blocked_user_id' => $target->id,
            'blocked_username' => $target->username,
        ]);

        return $this->blockedUsersRedirect($request)
            ->with('status', __('ui.user_blocked_status'));
    }

    public function unblock(Request $request, UserBlock $block, SecurityLogService $securityLog, UserBlockService $blocks): RedirectResponse
    {
        abort_unless((int) $block->user_id === (int) $request->user()->id, 403);

        $blockedUsername = $block->blockedUser?->username;
        $block->delete();
        $blocks->forget($request->user());

        $securityLog->record($request->user(), 'user_unblocked', $request, [
            'blocked_username' => $blockedUsername,
        ]);

        return $this->blockedUsersRedirect($request)
            ->with('status', __('ui.user_unblocked_status'));
    }

    private function blockedUsersRedirect(Request $request): RedirectResponse
    {
        if ($request->input('settings_section') === 'blocked') {
            return redirect()->to(route('account.settings.edit').'#blocked');
        }

        return back();
    }
}

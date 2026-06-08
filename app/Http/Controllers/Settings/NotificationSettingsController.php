<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationSetting;
use App\Support\HntTheme;
use App\Services\SecurityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationSettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $settings = $request->user()->notificationSettings()->firstOrCreate([]);

        $view = HntTheme::settingsEnabled() && ! $request->boolean('classic_settings')
            ? HntTheme::resolve('account.settings')
            : 'account.settings';

        return view($view, [
            'settings' => $settings,
            'notificationGroups' => $this->notificationGroups(),
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

        return back()->with('status', __('ui.notification_settings_saved'));
    }

    private function rules(): array
    {
        return array_fill_keys(UserNotificationSetting::FIELDS, ['nullable', 'boolean']);
    }

    private function notificationGroups(): array
    {
        return [
            'feed_comments' => ['title' => __('ui.notification_feed_comments'), 'text' => __('ui.notification_feed_comments_text')],
            'feed_reactions' => ['title' => __('ui.notification_feed_reactions'), 'text' => __('ui.notification_feed_reactions_text')],
            'friends' => ['title' => __('ui.notification_friends'), 'text' => __('ui.notification_friends_text')],
            'teams' => ['title' => __('ui.notification_teams'), 'text' => __('ui.notification_teams_text')],
            'lfg' => ['title' => __('ui.notification_lfg'), 'text' => __('ui.notification_lfg_text')],
            'gamification' => ['title' => __('ui.notification_gamification'), 'text' => __('ui.notification_gamification_text')],
            'moments' => ['title' => __('ui.notification_moments'), 'text' => __('ui.notification_moments_text')],
            'cups' => ['title' => __('ui.notification_cups'), 'text' => __('ui.notification_cups_text')],
            'referrals' => ['title' => __('ui.notification_referrals'), 'text' => __('ui.notification_referrals_text')],
        ];
    }
}

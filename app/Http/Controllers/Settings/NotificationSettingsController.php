<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\UserNotificationSetting;
use App\Support\HntTheme;
use App\Support\NotificationSettingsGroups;
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
            'notificationGroups' => NotificationSettingsGroups::all(),
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

}

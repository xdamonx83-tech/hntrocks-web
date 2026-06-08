<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Models\UserNotification;
use App\Models\UserNotificationSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ApiNotificationController extends Controller
{

    public function settings(Request $request): JsonResponse
    {
        $settings = $request->user()->notificationSettings()->firstOrCreate([]);

        return response()->json([
            'message' => 'Notification settings loaded.',
            'settings' => $this->settingsPayload($settings),
            'groups' => $this->settingsGroups(),
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->settingsRules());

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = [];
        foreach (UserNotificationSetting::FIELDS as $field) {
            $data[$field] = $request->boolean($field);
        }

        $settings = $request->user()->notificationSettings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json([
            'message' => 'Notification settings saved.',
            'settings' => $this->settingsPayload($settings),
            'groups' => $this->settingsGroups(),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $notifications = $request->user()
            ->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->latest()
            ->paginate(30);

        return NotificationResource::collection($notifications);
    }

    public function read(Request $request, UserNotification $notification): JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 404);

        $notification->markAsRead();
        $notification->loadMissing('actor.profile');

        return response()->json([
            'message' => 'Notification marked as read.',
            'notification' => new NotificationResource($notification),
            'counts' => [
                'unread_notifications' => $request->user()->notificationItems()->standard()->whereNull('read_at')->count(),
            ],
        ]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()
            ->notificationItems()
            ->standard()
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Notifications marked as read.',
            'counts' => [
                'unread_notifications' => 0,
            ],
        ]);
    }

    private function settingsRules(): array
    {
        return array_fill_keys(UserNotificationSetting::FIELDS, ['nullable', 'boolean']);
    }

    private function settingsPayload(UserNotificationSetting $settings): array
    {
        $payload = [];
        foreach (UserNotificationSetting::FIELDS as $field) {
            $payload[$field] = (bool) $settings->{$field};
        }

        return $payload;
    }

    private function settingsGroups(): array
    {
        return [
            'feed_comments' => [
                'title' => 'Feed-Kommentare',
                'text' => 'Kommentare, Antworten und Erwähnungen in Feed-Beiträgen.',
            ],
            'feed_reactions' => [
                'title' => 'Feed-Reaktionen',
                'text' => 'Likes und Reaktionen auf deine Feed-Beiträge oder Kommentare.',
            ],
            'friends' => [
                'title' => 'Freunde',
                'text' => 'Freundschaftsanfragen und angenommene Verbindungen.',
            ],
            'teams' => [
                'title' => 'Teams',
                'text' => 'Team-Anfragen, Bewerbungen und Team-Aktivität.',
            ],
            'lfg' => [
                'title' => 'LFG',
                'text' => 'Bewerbungen, Zusagen und Ablehnungen für LFG-Beiträge.',
            ],
            'gamification' => [
                'title' => 'Badges & Quests',
                'text' => 'Freigeschaltete Badges, Quests und Fortschritt.',
            ],
            'moments' => [
                'title' => 'Moments',
                'text' => 'Kommentare und Reaktionen auf Moments.',
            ],
            'cups' => [
                'title' => 'Cups',
                'text' => 'Cup-Anmeldungen, Einreichungen, Wertungen und Prüfungen.',
            ],
            'referrals' => [
                'title' => 'Referrals',
                'text' => 'Registrierungen und Fortschritt über deine Referral-Links.',
            ],
        ];
    }

}
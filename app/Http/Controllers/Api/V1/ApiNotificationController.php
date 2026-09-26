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
        $filter = strtolower((string) $request->query('filter', 'all'));
        $allowedFilters = ['all', 'unread', 'interactions', 'teams', 'cups', 'system'];

        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        $cupCondition = "LEFT(type, 4) = 'cup_'";
        $teamCondition = "(LEFT(type, 5) = 'team_' OR LEFT(type, 4) = 'lfg_')";
        $interactionCondition = "(LEFT(type, 5) = 'feed_' OR LEFT(type, 7) = 'moment_' OR LEFT(type, 7) = 'friend_' OR type LIKE '%mention%')";

        $counts = $request->user()
            ->notificationItems()
            ->standard()
            ->selectRaw(
                "COUNT(*) AS total_count,
                SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) AS unread_count,
                SUM(CASE WHEN type LIKE '%mention%' THEN 1 ELSE 0 END) AS mentions_count,
                SUM(CASE WHEN NOT ({$cupCondition}) AND NOT ({$teamCondition}) AND {$interactionCondition} THEN 1 ELSE 0 END) AS interactions_count,
                SUM(CASE WHEN {$teamCondition} THEN 1 ELSE 0 END) AS teams_count,
                SUM(CASE WHEN {$cupCondition} THEN 1 ELSE 0 END) AS cups_count,
                SUM(CASE WHEN NOT ({$cupCondition}) AND NOT ({$teamCondition}) AND NOT ({$interactionCondition}) THEN 1 ELSE 0 END) AS system_count"
            )
            ->first();

        $notificationsQuery = $request->user()
            ->notificationItems()
            ->standard()
            ->with('actor.profile');

        if ($filter === 'unread') {
            $notificationsQuery->whereNull('read_at');
        } elseif ($filter === 'interactions') {
            $notificationsQuery
                ->whereRaw("NOT ({$cupCondition})")
                ->whereRaw("NOT ({$teamCondition})")
                ->whereRaw($interactionCondition);
        } elseif ($filter === 'teams') {
            $notificationsQuery->whereRaw($teamCondition);
        } elseif ($filter === 'cups') {
            $notificationsQuery->whereRaw($cupCondition);
        } elseif ($filter === 'system') {
            $notificationsQuery
                ->whereRaw("NOT ({$cupCondition})")
                ->whereRaw("NOT ({$teamCondition})")
                ->whereRaw("NOT ({$interactionCondition})");
        }

        $notifications = $notificationsQuery
            ->latest()
            ->paginate(30);

        return NotificationResource::collection($notifications)->additional([
            'filter' => $filter,
            'counts' => [
                'total' => (int) ($counts->total_count ?? 0),
                'unread' => (int) ($counts->unread_count ?? 0),
                'mentions' => (int) ($counts->mentions_count ?? 0),
                'interactions' => (int) ($counts->interactions_count ?? 0),
                'teams' => (int) ($counts->teams_count ?? 0),
                'cups' => (int) ($counts->cups_count ?? 0),
                'system' => (int) ($counts->system_count ?? 0),
            ],
        ]);
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
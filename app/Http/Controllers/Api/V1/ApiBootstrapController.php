<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiBootstrapController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load('profile');

        return response()->json([
            'app' => [
                'name' => config('app.name'),
                'url' => config('app.url'),
                'api_version' => 'v1',
                'locale' => app()->getLocale(),
                'supported_locales' => ['de', 'en'],
            ],
            'user' => new UserResource($user),
            'navigation' => [
                'feed',
                'members',
                'teams',
                'lfg',
                'team-lfg',
                'moments',
                'cups',
                'referrals',
                'gamification',
                'messages',
                'notifications',
            ],
            'api' => [
                'feed' => [
                    'list' => '/api/v1/feed',
                    'create' => '/api/v1/feed',
                    'detail' => '/api/v1/feed/{id}',
                ],
                'cups' => [
                    'list' => '/api/v1/cups',
                    'detail' => '/api/v1/cups/{slug}',
                    'register' => '/api/v1/cups/{slug}/register',
                    'submit' => '/api/v1/cups/{slug}/submissions',
                ],
                'notifications' => [
                    'list' => '/api/v1/notifications',
                    'read' => '/api/v1/notifications/{id}/read',
                    'read_all' => '/api/v1/notifications/read-all',
                ],
            ],
            'limits' => [
                'feed_media_max_mb' => (int) round(((int) config('hunthub.upload_limits.feed_media_kb', 102400)) / 1024),
                'feed_media_max_files' => (int) config('hunthub.upload_limits.feed_media_count', 12),
                'cup_submission_screenshot_max_mb' => (int) round(((int) config('hunthub.upload_limits.cup_submission_screenshot_kb', 10240)) / 1024),
                'cup_submission_cooldown_minutes' => max(0, (int) config('hunthub.cups.submission_cooldown_minutes', 30)),
            ],
            'counts' => [
                'unread_messages' => $user->unreadMessagesCount(),
                'unread_notifications' => $user->notificationItems()->standard()->whereNull('read_at')->count(),
            ],
        ]);
    }
}

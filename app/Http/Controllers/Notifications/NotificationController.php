<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\UserNotification;
use App\Support\HntTheme;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $perPage = 15;
        $page = (int) $request->query('page', 1);
        $page = max(1, min(50, $page));
        $limit = $perPage * $page;

        $user = $request->user();
        $totalNotifications = $user->notificationItems()->standard()->count();
        $unreadCount = $user->notificationItems()->standard()->unread()->count();

        $notifications = $user
            ->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->latest()
            ->limit($limit)
            ->get();

        $hasMoreNotifications = $notifications->count() < $totalNotifications;

        return HntTheme::view('notifications.index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalNotifications' => $totalNotifications,
            'hasMoreNotifications' => $hasMoreNotifications,
            'nextNotificationsUrl' => $hasMoreNotifications ? route('notifications.index', ['page' => $page + 1]) : null,
        ]);
    }

    public function markRead(Request $request, UserNotification $notification): RedirectResponse|JsonResponse
    {
        abort_unless((int) $notification->user_id === (int) $request->user()->id, 403);

        $notification->markAsRead();

        $actionUrl = $notification->actionUrl();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.notification_marked_read'),
                'unread_count' => $request->user()->notificationItems()->standard()->unread()->count(),
                'action_url' => $actionUrl,
            ]);
        }

        if ($actionUrl) {
            return redirect($actionUrl);
        }

        return back()->with('status', __('ui.notification_marked_read'));
    }

    public function markAllRead(Request $request): RedirectResponse|JsonResponse
    {
        $request->user()
            ->notificationItems()
            ->standard()
            ->unread()
            ->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'message' => __('ui.notifications_marked_read'),
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', __('ui.notifications_marked_read'));
    }
}

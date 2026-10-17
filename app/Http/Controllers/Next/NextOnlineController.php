<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class NextOnlineController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $viewer */
        $viewer = $request->user();
        $viewerId = (int) $viewer->id;
        $tab = (string) $request->query('tab', 'friends');
        if (! in_array($tab, ['friends', 'all', 'team'], true)) {
            $tab = 'friends';
        }
        $search = mb_substr(trim((string) $request->query('q', '')), 0, 70);

        $friends = Friendship::query()
            ->forUser($viewer)->where('status', Friendship::STATUS_ACCEPTED)
            ->get(['user_one_id', 'user_two_id'])
            ->map(fn (Friendship $friend) => (int) $friend->user_one_id === $viewerId
                ? (int) $friend->user_two_id : (int) $friend->user_one_id)
            ->unique()->values()->all();

        $teamIds = TeamMember::query()
            ->where('user_id', $viewerId)->where('status', 'active')->pluck('team_id');
        $teammates = $teamIds->isEmpty()
            ? []
            : TeamMember::query()->whereIn('team_id', $teamIds)
                ->where('status', 'active')->where('user_id', '!=', $viewerId)
                ->distinct()->pluck('user_id')->map(fn ($id) => (int) $id)->all();

        $visible = User::query()
            ->where('last_seen_at', '>=', now()->subSeconds(User::ONLINE_WINDOW_SECONDS))
            ->where('status', '!=', 'suspended')
            ->whereDoesntHave('privacySettings', fn (Builder $query) =>
                $query->where('show_online_status', false));

        $counts = [
            'all' => (clone $visible)->count(),
            'friends' => empty($friends) ? 0 : (clone $visible)->whereIn('id', $friends)->count(),
            'team' => empty($teammates) ? 0 : (clone $visible)->whereIn('id', $teammates)->count(),
        ];

        $query = clone $visible;
        if ($tab === 'friends') {
            $query->whereIn('id', $friends);
        } elseif ($tab === 'team') {
            $query->whereIn('id', $teammates);
        }
        if ($search !== '') {
            $query->where('name', 'like', '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%');
        }

        $items = $query->orderByDesc('last_seen_at')->limit(40)->get();
        return response()->json([
            'ok' => true,
            'counts' => $counts,
            'tab' => $tab,
            'items' => $items->map(fn (User $user) => [
                'id' => (int) $user->id,
                'name' => $user->name,
                'avatar' => $user->avatarUrl(),
                'url' => route('profile.public', $user),
                'friend' => in_array((int) $user->id, $friends, true),
                'team' => in_array((int) $user->id, $teammates, true),
                'self' => (int) $user->id === $viewerId,
            ])->values(),
            'more' => $query->count() > $items->count(),
        ]);
    }
}

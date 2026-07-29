<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApiProfileBadgeEarnersController extends Controller
{
    public function __invoke(Request $request, User $user): JsonResponse
    {
        abort_unless($user->status === 'active', 404);

        $viewer = $request->user();
        $user->loadMissing('profile');
        $isOwnProfile = (int) $viewer->id === (int) $user->id;

        if (! $isOwnProfile && $user->profile?->profile_visibility === 'private') {
            abort(404);
        }

        $badgeIds = $user->badges()->pluck('badges.id');

        $data = Badge::query()
            ->whereIn('id', $badgeIds)
            ->orderBy('sort_order')
            ->get()
            ->mapWithKeys(function (Badge $badge): array {
                $earnersQuery = $badge->users()
                    ->where('users.status', 'active')
                    ->orderByPivot('awarded_at', 'desc');

                $total = (clone $earnersQuery)->count();
                $earners = $earnersQuery
                    ->limit(5)
                    ->get(['users.id', 'users.name', 'users.username', 'users.avatar_path'])
                    ->map(fn (User $earner): array => [
                        'id' => (int) $earner->id,
                        'name' => (string) $earner->name,
                        'username' => (string) $earner->username,
                        'avatar_url' => $earner->avatar_path
                            ? Storage::disk('public')->url($earner->avatar_path)
                            : asset('assets/vikinger/img/default-avatar.svg'),
                    ])
                    ->values();

                return [(string) $badge->id => [
                    'total' => $total,
                    'users' => $earners,
                ]];
            });

        return response()->json(['data' => $data]);
    }
}

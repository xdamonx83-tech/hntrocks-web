<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class LandingSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $visibleUsers = static function (Builder $query): void {
            $query->where('status', 'active')
                ->where(function (Builder $privacy): void {
                    $privacy->whereDoesntHave('privacySettings')
                        ->orWhereHas('privacySettings', function (Builder $settings): void {
                            $settings->where('profile_visibility', 'public')
                                ->where('show_activity_feed', true);
                        });
                });
        };

        $publicPosts = FeedPost::query()
            ->where('status', 'published')
            ->where('visibility', 'public')
            ->whereNull('team_id')
            ->whereHas('user', $visibleUsers);

        $topPost = (clone $publicPosts)
            ->whereNotNull('body')
            ->where('body', '!=', '')
            ->with(['user.profile'])
            ->withCount(['comments', 'reactions'])
            ->withCount('sharedByPosts as shares_count')
            ->orderByDesc('reactions_count')
            ->orderByDesc('comments_count')
            ->orderByDesc('shares_count')
            ->latest()
            ->first();

        $publicMembers = User::query()
            ->where('status', 'active')
            ->where(function (Builder $privacy): void {
                $privacy->whereDoesntHave('privacySettings')
                    ->orWhereHas('privacySettings', function (Builder $settings): void {
                        $settings->where('profile_visibility', 'public');
                    });
            })
            ->count();

        $membersPreview = User::query()
            ->where('status', 'active')
            ->whereNotNull('avatar_path')
            ->where('avatar_path', '!=', '')
            ->where(function (Builder $privacy): void {
                $privacy->whereDoesntHave('privacySettings')
                    ->orWhereHas('privacySettings', function (Builder $settings): void {
                        $settings->where('profile_visibility', 'public');
                    });
            })
            ->where(function (Builder $profile): void {
                $profile->whereDoesntHave('profile')
                    ->orWhereHas('profile', function (Builder $details): void {
                        $details->where('profile_visibility', 'public');
                    });
            })
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id')
            ->limit(18)
            ->get(['id', 'name', 'username', 'avatar_path'])
            ->map(static fn (User $user): array => [
                'name' => $user->name ?: $user->username,
                'username' => $user->username,
                'avatar_url' => $user->avatarUrl(),
            ])
            ->all();

        return response()->json([
            'stats' => [
                'public_members' => $publicMembers,
                'public_posts' => (clone $publicPosts)->count(),
            ],
            'top_post' => $topPost ? [
                'id' => $topPost->id,
                'body' => Str::limit(trim(strip_tags((string) $topPost->body)), 320),
                'created_at' => $topPost->created_at?->toIso8601String(),
                'author' => [
                    'name' => $topPost->user->name ?: $topPost->user->username,
                    'username' => $topPost->user->username,
                    'avatar_url' => $topPost->user->avatarUrl(),
                ],
                'stats' => [
                    'reactions' => (int) $topPost->reactions_count,
                    'comments' => (int) $topPost->comments_count,
                    'shares' => (int) $topPost->shares_count,
                ],
            ] : null,
            'members_preview' => $membersPreview,
            'privacy' => [
                'public_posts_only' => true,
                'public_profiles_only' => true,
                'activity_visibility_respected' => true,
            ],
        ]);
    }
}

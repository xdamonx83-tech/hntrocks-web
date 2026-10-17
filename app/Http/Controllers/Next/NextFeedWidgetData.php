<?php

namespace App\Http\Controllers\Next;

use App\Models\Cup;
use App\Models\FeedPost;
use App\Models\LiveLobby;
use App\Models\Moment;
use App\Models\MomentSpotlight;
use App\Models\User;
use App\Models\UserLoadout;
use Illuminate\Support\Collection;

final class NextFeedWidgetData
{
    public static function forViewer(User $viewer): array
    {
        // Public-only community activity. No private feeds or unreleased Moments.
        $posts = FeedPost::query()->where('status', 'published')
            ->where('visibility', 'public')->whereNull('team_id')
            ->with('user.profile')->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'))
            ->latest('created_at')->limit(5)->get();

        $moments = Moment::query()->published()->where('visibility', 'public')
            ->with('user.profile')->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'))
            ->latest('published_at')->limit(4)->get();

        // New registrations are visible only when the profile and activity privacy permit it.
        $newUsers = User::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query->whereDoesntHave('privacySettings')
                    ->orWhereHas('privacySettings', function ($privacy): void {
                        $privacy->where('profile_visibility', 'public')
                            ->where('show_activity_feed', true);
                    });
            })
            ->where(function ($query): void {
                $query->whereDoesntHave('profile')
                    ->orWhereHas('profile', fn ($profile) => $profile->where('profile_visibility', 'public'));
            })
            ->with('profile')->latest('created_at')->limit(8)->get();

        $activity = collect();
        foreach ($posts as $post) {
            $activity->push([
                'type' => 'post', 'name' => $post->user?->name ?: 'Hunter',
                'avatar' => $post->user?->avatarUrl(),
                'url' => url('/feed?post=' . $post->id),
                'time' => $post->created_at?->diffForHumans(),
                'sort' => $post->created_at?->timestamp ?? 0,
            ]);
        }
        foreach ($moments as $moment) {
            $activity->push([
                'type' => 'moment', 'name' => $moment->user?->name ?: 'Hunter',
                'avatar' => $moment->user?->avatarUrl(),
                'url' => route('moments.show', $moment),
                'time' => ($moment->published_at ?: $moment->created_at)?->diffForHumans(),
                'sort' => ($moment->published_at ?: $moment->created_at)?->timestamp ?? 0,
            ]);
        }
        foreach ($newUsers as $member) {
            $activity->push([
                'type' => 'joined', 'name' => $member->name,
                'avatar' => $member->avatarUrl(),
                'url' => route('profile.public', $member),
                'time' => $member->created_at?->diffForHumans(),
                'sort' => $member->created_at?->timestamp ?? 0,
            ]);
        }

        $spotlight = MomentSpotlight::query()->published()
            ->with(['moment.user.profile', 'moment.media', 'moment.cover'])
            ->whereHas('moment', fn ($q) => $q->published()->where('visibility', 'public'))
            ->latest('week_starts_at')->latest('id')->first();

        $cup = Cup::query()->visible()->whereIn('status', ['active','planned'])
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('starts_at')->first();

        $loadouts = UserLoadout::query()->where('visibility', 'public')
            ->where('is_active', true)
            ->with('user.profile')
            ->whereHas('user', fn ($q) => $q->where('status', '!=', 'suspended'))
            ->latest('updated_at')->limit(4)->get();

        return [
            'activity' => $activity->sortByDesc('sort')->take(9)->values(),
            'spotlight' => $spotlight,
            'cup' => $cup,
            'loadouts' => $loadouts,
            'openLobbies' => LiveLobby::query()->where('status', 'open')->where('expires_at', '>', now())->count(),
        ];
    }
}

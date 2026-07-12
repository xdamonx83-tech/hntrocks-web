<?php

namespace App\Http\Controllers\Members;

use App\Http\Controllers\Controller;
use App\Http\Middleware\PreviewDashboardHeader;
use App\Models\Friendship;
use App\Models\User;
use App\Support\HntTheme;
use App\Support\ReworkFeedSidebar;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MembersController extends Controller
{
    public function index(Request $request): View|JsonResponse|StreamedResponse
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'platform' => ['nullable', 'string', 'max:40'],
            'playstyle' => ['nullable', 'string', 'max:60'],
            'region' => ['nullable', 'string', 'max:60'],
            'language' => ['nullable', 'string', 'max:40'],
            'lfg' => ['nullable', 'in:1'],
            'relationship' => ['nullable', 'in:all,friends,pending'],
            'export' => ['nullable', 'in:csv'],
        ]);

        $viewer = $request->user();

        if ($request->boolean('dashboard_header')) {
            $method = new ReflectionMethod(PreviewDashboardHeader::class, 'payload');
            $method->setAccessible(true);

            return response()->json([
                'header' => $method->invoke(app(PreviewDashboardHeader::class), $viewer),
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        }

        $relationshipFilter = $filters['relationship'] ?? 'all';
        $redesignLive = (bool) config('members.redesign_live', false);

        $query = User::query()
            ->with([
                'profile',
                'privacySettings',
                'badges' => fn ($badgeQuery) => $badgeQuery->orderBy('badges.sort_order'),
            ])
            ->withCount([
                'badges',
                'activeTeams',
                'feedPosts as visible_feed_posts_count' => function ($postQuery): void {
                    $postQuery->where('status', 'published')->where('visibility', '!=', 'private');
                },
                'moments as visible_moments_count' => function ($momentQuery): void {
                    $momentQuery->where('status', 'published')->where('visibility', '!=', 'private');
                },
            ])
            ->whereHas('profile', function ($profileQuery) use ($request): void {
                $profileQuery->where(function ($visibilityQuery) use ($request): void {
                    $visibilityQuery
                        ->whereIn('profile_visibility', ['public', 'registered'])
                        ->orWhere('user_id', $request->user()->id);
                });
            });

        if (filled($filters['q'] ?? null)) {
            $term = trim($filters['q']);

            $query->where(function ($searchQuery) use ($term): void {
                $searchQuery
                    ->where('name', 'like', '%' . $term . '%')
                    ->orWhere('username', 'like', '%' . $term . '%')
                    ->orWhereHas('profile', function ($profileQuery) use ($term): void {
                        $profileQuery
                            ->where('headline', 'like', '%' . $term . '%')
                            ->orWhere('bio', 'like', '%' . $term . '%')
                            ->orWhere('discord_name', 'like', '%' . $term . '%');
                    });
            });
        }

        foreach (['platform', 'playstyle', 'region', 'language'] as $field) {
            if (filled($filters[$field] ?? null)) {
                $query->whereHas('profile', function ($profileQuery) use ($field, $filters): void {
                    $profileQuery->where($field, $filters[$field]);
                });
            }
        }

        if (($filters['lfg'] ?? null) === '1') {
            $query->whereHas('profile', function ($profileQuery): void {
                $profileQuery->where('is_lfg_available', true);
            });
        }

        if ($relationshipFilter !== 'all') {
            $relationshipIds = Friendship::query()
                ->forUser($viewer)
                ->when($relationshipFilter === 'friends', fn ($friendshipQuery) => $friendshipQuery->where('status', Friendship::STATUS_ACCEPTED))
                ->when($relationshipFilter === 'pending', fn ($friendshipQuery) => $friendshipQuery->where('status', Friendship::STATUS_PENDING))
                ->get(['user_one_id', 'user_two_id'])
                ->map(fn (Friendship $friendship) => (int) $friendship->user_one_id === (int) $viewer->id ? (int) $friendship->user_two_id : (int) $friendship->user_one_id)
                ->unique()
                ->values();

            $query->whereIn('users.id', $relationshipIds->isNotEmpty() ? $relationshipIds->all() : [-1]);
        }

        if (($filters['export'] ?? null) === 'csv') {
            return $this->exportCsv(clone $query);
        }

        $members = $query
            ->latest('users.created_at')
            ->paginate(12)
            ->withQueryString();

        $memberIds = $members->getCollection()->pluck('id')->map(fn ($id) => (int) $id)->all();

        $friendshipMap = Friendship::query()
            ->forUser($viewer)
            ->where(function ($friendshipQuery) use ($memberIds): void {
                $friendshipQuery->whereIn('user_one_id', $memberIds)->orWhereIn('user_two_id', $memberIds);
            })
            ->get()
            ->keyBy(fn (Friendship $friendship) => (int) $friendship->user_one_id === (int) $viewer->id ? (int) $friendship->user_two_id : (int) $friendship->user_one_id);

        $friendCounts = array_fill_keys($memberIds, 0);

        Friendship::query()
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->where(function ($friendshipQuery) use ($memberIds): void {
                $friendshipQuery->whereIn('user_one_id', $memberIds)->orWhereIn('user_two_id', $memberIds);
            })
            ->get(['user_one_id', 'user_two_id'])
            ->each(function (Friendship $friendship) use (&$friendCounts): void {
                if (array_key_exists((int) $friendship->user_one_id, $friendCounts)) {
                    $friendCounts[(int) $friendship->user_one_id]++;
                }

                if (array_key_exists((int) $friendship->user_two_id, $friendCounts)) {
                    $friendCounts[(int) $friendship->user_two_id]++;
                }
            });

        $filterOptions = [
            'platforms' => $this->profileOptions('platform'),
            'playstyles' => $this->profileOptions('playstyle'),
            'regions' => $this->profileOptions('region'),
            'languages' => $this->profileOptions('language'),
        ];

        $relationshipCounts = [
            'friends' => Friendship::query()->forUser($viewer)->where('status', Friendship::STATUS_ACCEPTED)->count(),
            'pending' => Friendship::query()->forUser($viewer)->where('status', Friendship::STATUS_PENDING)->count(),
        ];

        $visibleMembers = User::query()->whereHas('profile', function ($profileQuery) use ($viewer): void {
            $profileQuery->where(function ($visibilityQuery) use ($viewer): void {
                $visibilityQuery
                    ->whereIn('profile_visibility', ['public', 'registered'])
                    ->orWhere('user_id', $viewer->id);
            });
        });

        $membersStats = [
            'total' => (clone $visibleMembers)->count(),
            'filtered' => $members->total(),
            'lfg' => (clone $visibleMembers)->whereHas('profile', fn ($profileQuery) => $profileQuery->where('is_lfg_available', true))->count(),
            'friends' => $relationshipCounts['friends'],
            'pending' => $relationshipCounts['pending'],
            'new_this_week' => (clone $visibleMembers)->where('users.created_at', '>=', now()->startOfWeek())->count(),
            'online' => (clone $visibleMembers)
                ->where('users.last_seen_at', '>=', now()->subSeconds(User::ONLINE_WINDOW_SECONDS))
                ->where(function ($onlineQuery): void {
                    $onlineQuery
                        ->whereDoesntHave('privacySettings')
                        ->orWhereHas('privacySettings', fn ($privacyQuery) => $privacyQuery->where('show_online_status', true));
                })
                ->count(),
        ];

        $itemsView = $redesignLive
            ? 'themes.hnt_preview.members.partials.member-items'
            : HntTheme::resolve('members.partials.member-items');

        if ($request->boolean('fragment')) {
            return response()->json([
                'html' => view($itemsView, [
                    'members' => $members,
                    'filters' => $filters,
                    'filterOptions' => $filterOptions,
                    'friendshipMap' => $friendshipMap,
                    'friendCounts' => $friendCounts,
                    'relationshipCounts' => $relationshipCounts,
                ])->render(),
                'nextPageUrl' => $members->nextPageUrl(),
                'hasMorePages' => $members->hasMorePages(),
            ]);
        }

        $sidebarData = ReworkFeedSidebar::forViewer($viewer);
        $indexView = $redesignLive
            ? 'themes.hnt_preview.members.index'
            : HntTheme::resolve('members.index');

        return view($indexView, [
            'members' => $members,
            'hasMoreMembers' => $members->hasMorePages(),
            'nextMembersPageUrl' => $members->nextPageUrl(),
            'socialiteMembers' => $sidebarData['members'],
            'socialiteProfileStats' => $sidebarData['profileStats'],
            'socialiteCrownsSummary' => $sidebarData['crownsSummary'],
            'socialiteHighlightTopPost' => $sidebarData['highlightTopPost'],
            'socialiteHighlightLfg' => $sidebarData['highlightLfg'],
            'socialiteHighlightCup' => $sidebarData['highlightCup'],
            'filters' => $filters,
            'filterOptions' => $filterOptions,
            'friendshipMap' => $friendshipMap,
            'friendCounts' => $friendCounts,
            'relationshipCounts' => $relationshipCounts,
            'membersStats' => $membersStats,
        ]);
    }

    private function exportCsv(Builder $query): StreamedResponse
    {
        $filename = 'hnt-members-' . now()->format('Y-m-d-His') . '.csv';

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['Name', 'Username', 'Plattform', 'Region', 'Spielstil', 'Sprache', 'LFG'], ';');

            $query
                ->orderBy('users.id')
                ->chunkById(200, function ($users) use ($output): void {
                    foreach ($users as $user) {
                        fputcsv($output, [
                            $user->name,
                            $user->username,
                            $user->profile?->platform,
                            $user->profile?->region,
                            $user->profile?->playstyle,
                            $user->profile?->language,
                            $user->profile?->is_lfg_available ? 'Ja' : 'Nein',
                        ], ';');
                    }
                }, 'users.id', 'id');

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function profileOptions(string $field): array
    {
        return User::query()
            ->join('user_profiles', 'users.id', '=', 'user_profiles.user_id')
            ->whereNotNull('user_profiles.' . $field)
            ->where('user_profiles.' . $field, '!=', '')
            ->whereIn('user_profiles.profile_visibility', ['public', 'registered'])
            ->distinct()
            ->orderBy('user_profiles.' . $field)
            ->pluck('user_profiles.' . $field)
            ->all();
    }
}

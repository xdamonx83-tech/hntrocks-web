<?php

namespace App\Http\Controllers\TrophyRoom;

use App\Http\Controllers\Controller;
use App\Models\CupSubmission;
use App\Models\CupTeam;
use App\Models\Moment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TrophyRoomController extends Controller
{
    private const POSITIONS = [
        [-3.45, 1.05, -4.18],
        [-1.72, 0.98, -4.28],
        [0.0, 1.04, -4.34],
        [1.72, 0.98, -4.28],
        [3.45, 1.05, -4.18],
    ];

    public function index(Request $request): View
    {
        $viewer = $request->user();
        $realTrophies = $viewer ? $this->realTrophiesFor($viewer) : [];
        $trophies = count($realTrophies) > 0 ? $realTrophies : $this->demoTrophies();

        return view('themes.hnt_preview.trophy-room.index', [
            'demoTrophies' => $this->positionedTrophies($trophies),
            'viewer' => $viewer,
            'usesRealTrophies' => count($realTrophies) > 0,
        ]);
    }

    public function propLab(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return view('themes.hnt_preview.trophy-room.prop-lab', [
            'models' => $this->propLabModels(),
            'maxUploadMegabytes' => 60,
        ]);
    }

    public function uploadPropModel(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $file = $request->file('glb_model');

        if (! $file || ! $file->isValid()) {
            return back()->withErrors(['glb_model' => __('ui.trophy_prop_lab_upload_error_missing')]);
        }

        $maxBytes = 60 * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            return back()->withErrors(['glb_model' => __('ui.trophy_prop_lab_upload_error_size', ['size' => '60 MB'])]);
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());

        if ($extension !== 'glb') {
            return back()->withErrors(['glb_model' => __('ui.trophy_prop_lab_upload_error_type')]);
        }

        $targetDirectory = public_path('assets/themes/hnt_preview/models/custom');

        if (! is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $originalName = (string) pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeName = Str::slug($originalName) ?: 'model';
        $filename = $safeName . '.glb';
        $counter = 2;

        while (is_file($targetDirectory . DIRECTORY_SEPARATOR . $filename)) {
            $filename = $safeName . '-' . $counter . '.glb';
            $counter++;
        }

        $file->move($targetDirectory, $filename);

        return redirect()
            ->route('trophy-room.prop-lab')
            ->with('trophy_prop_lab_success', __('ui.trophy_prop_lab_upload_success', ['file' => $filename]));
    }

    private function propLabModels(): array
    {
        $baseDirectory = public_path('assets/themes/hnt_preview/models');
        $baseUrl = asset('assets/themes/hnt_preview/models');

        $modelRoots = [
            ['directory' => $baseDirectory, 'url' => $baseUrl, 'group' => __('ui.trophy_prop_lab_group_project')],
            ['directory' => $baseDirectory . DIRECTORY_SEPARATOR . 'custom', 'url' => $baseUrl . '/custom', 'group' => __('ui.trophy_prop_lab_group_custom')],
        ];

        $models = [];

        foreach ($modelRoots as $root) {
            $directory = (string) $root['directory'];

            if (! is_dir($directory)) {
                continue;
            }

            $entries = scandir($directory) ?: [];

            foreach ($entries as $entry) {
                if (! preg_match('/\.glb$/i', $entry)) {
                    continue;
                }

                $path = $directory . DIRECTORY_SEPARATOR . $entry;

                if (! is_file($path)) {
                    continue;
                }

                $relativeFile = $directory === $baseDirectory ? $entry : 'custom/' . $entry;
                $size = (int) (filesize($path) ?: 0);

                $models[] = [
                    'file' => $relativeFile,
                    'label' => str((string) pathinfo($entry, PATHINFO_FILENAME))->replace(['-', '_'], ' ')->title()->toString(),
                    'group' => (string) $root['group'],
                    'url' => ((string) $root['url']) . '/' . rawurlencode($entry),
                    'size' => $size,
                    'size_label' => $this->formatBytes($size),
                ];
            }
        }

        usort($models, function (array $left, array $right): int {
            $groupCompare = strcasecmp((string) $left['group'], (string) $right['group']);

            return $groupCompare !== 0 ? $groupCompare : strcasecmp($left['label'], $right['label']);
        });

        return $models;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 KB';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, '.', '') . ' ' . $units[$power];
    }

    private function realTrophiesFor(User $user): array
    {
        $trophies = [];

        $bestSubmission = $user->cupSubmissions()
            ->whereIn('status', CupSubmission::scoredStatuses())
            ->with('cup')
            ->orderByDesc('points')
            ->orderByDesc('bounty_tokens')
            ->orderByDesc('kills')
            ->first();

        if ($bestSubmission) {
            $cupTitle = (string) ($bestSubmission->cup?->title ?: __('ui.trophy_room_real_unknown_cup'));
            $trophies[] = [
                'id' => 'best-cup-run-' . $bestSubmission->id,
                'type' => 'medal',
                'title' => __('ui.trophy_room_real_best_run_title'),
                'subtitle' => __('ui.trophy_room_real_best_run_subtitle'),
                'description' => __('ui.trophy_room_real_best_run_description', [
                    'cup' => $cupTitle,
                    'points' => (int) $bestSubmission->points,
                    'kills' => (int) $bestSubmission->kills,
                    'bounty' => (int) $bestSubmission->bounty_tokens,
                ]),
                'meta' => __('ui.trophy_room_real_best_run_meta', [
                    'points' => (int) $bestSubmission->points,
                    'kills' => (int) $bestSubmission->kills,
                    'bounty' => (int) $bestSubmission->bounty_tokens,
                ]),
                'rarity' => __('ui.trophy_room_rarity_gold'),
                'accent' => '#D6A84F',
            ];
        }

        foreach ($this->cupPlacementsFor($user) as $placement) {
            if (count($trophies) >= 3) {
                break;
            }

            $rank = (int) $placement['rank'];
            $trophies[] = [
                'id' => 'cup-placement-' . $placement['team_id'],
                'type' => $rank <= 3 ? 'cup' : 'medal',
                'title' => __('ui.trophy_room_real_cup_rank_title', ['rank' => $rank]),
                'subtitle' => (string) $placement['cup_title'],
                'description' => __('ui.trophy_room_real_cup_rank_description', [
                    'cup' => (string) $placement['cup_title'],
                    'rank' => $rank,
                    'points' => (int) $placement['points'],
                ]),
                'meta' => __('ui.trophy_room_real_cup_rank_meta', [
                    'points' => (int) $placement['points'],
                    'kills' => (int) $placement['kills'],
                    'bounty' => (int) $placement['bounty_tokens'],
                ]),
                'rarity' => $rank <= 3 ? __('ui.trophy_room_rarity_gold') : __('ui.trophy_room_rarity_rare'),
                'accent' => $rank === 1 ? '#D6A84F' : ($rank <= 3 ? '#CFA149' : '#9BE447'),
            ];
        }

        $badges = $user->badges()
            ->latest('badge_user.awarded_at')
            ->limit(2)
            ->get();

        foreach ($badges as $badge) {
            if (count($trophies) >= 5) {
                break;
            }

            $name = trim((string) ($badge->name ?: $badge->slug));
            $description = trim((string) ($badge->description ?: __('ui.trophy_room_real_badge_description_fallback')));

            $trophies[] = [
                'id' => 'badge-' . $badge->id,
                'type' => 'badge',
                'title' => $name !== '' ? $name : __('ui.trophy_room_real_badge_title_fallback'),
                'subtitle' => __('ui.trophy_room_real_badge_subtitle'),
                'description' => $description,
                'meta' => __('ui.trophy_room_real_badge_meta', [
                    'rarity' => method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : ((string) $badge->rarity ?: __('ui.trophy_room_rarity_unlocked')),
                ]),
                'rarity' => method_exists($badge, 'rarityLabel') ? $badge->rarityLabel() : __('ui.trophy_room_rarity_unlocked'),
                'accent' => match ((string) $badge->rarity) {
                    'legendary' => '#D6A84F',
                    'epic' => '#CFA149',
                    'rare' => '#9BE447',
                    default => '#7FB069',
                },
            ];
        }

        if (count($trophies) < 5) {
            $bestMoment = $user->moments()
                ->published()
                ->orderByDesc('likes_count')
                ->orderByDesc('views_count')
                ->orderByDesc('comments_count')
                ->first();

            if ($bestMoment instanceof Moment) {
                $caption = trim((string) ($bestMoment->caption ?: __('ui.trophy_room_real_moment_title_fallback')));
                $trophies[] = [
                    'id' => 'moment-' . $bestMoment->id,
                    'type' => 'crystal',
                    'title' => __('ui.trophy_room_real_moment_title'),
                    'subtitle' => $caption,
                    'description' => __('ui.trophy_room_real_moment_description'),
                    'meta' => __('ui.trophy_room_real_moment_meta', [
                        'likes' => (int) $bestMoment->likes_count,
                        'views' => (int) $bestMoment->views_count,
                        'comments' => (int) $bestMoment->comments_count,
                    ]),
                    'rarity' => __('ui.trophy_room_rarity_epic'),
                    'accent' => '#7FB069',
                ];
            }
        }

        if (count($trophies) > 0 && count($trophies) < 5) {
            $trophies[] = $this->lockedPlaceholder();
        }

        return array_slice($trophies, 0, 5);
    }

    private function cupPlacementsFor(User $user): array
    {
        return CupTeam::query()
            ->with('cup')
            ->where('status', 'active')
            ->where(function ($query) use ($user): void {
                $query->where('owner_id', $user->id)
                    ->orWhereHas('members', function ($memberQuery) use ($user): void {
                        $memberQuery
                            ->where('user_id', $user->id)
                            ->where('status', 'active');
                    });
            })
            ->latest('updated_at')
            ->limit(10)
            ->get()
            ->map(function (CupTeam $team): ?array {
                $cup = $team->cup;

                if (! $cup) {
                    return null;
                }

                $leaderboard = $cup->teams()
                    ->where('status', 'active')
                    ->orderByDesc('points_total')
                    ->orderByDesc('bounty_tokens_total')
                    ->orderByDesc('kills_total')
                    ->orderBy('name')
                    ->get(['id', 'points_total', 'bounty_tokens_total', 'kills_total']);

                $rankIndex = $leaderboard->search(fn (CupTeam $entry): bool => (int) $entry->id === (int) $team->id);

                if ($rankIndex === false) {
                    return null;
                }

                return [
                    'rank' => ((int) $rankIndex) + 1,
                    'team_id' => (int) $team->id,
                    'cup_title' => (string) ($cup->title ?: __('ui.trophy_room_real_unknown_cup')),
                    'points' => (int) $team->points_total,
                    'kills' => (int) $team->kills_total,
                    'bounty_tokens' => (int) $team->bounty_tokens_total,
                ];
            })
            ->filter()
            ->sortBy([
                ['rank', 'asc'],
                ['points', 'desc'],
            ])
            ->take(2)
            ->values()
            ->all();
    }

    private function demoTrophies(): array
    {
        return [
            [
                'id' => 'bayou-cup',
                'type' => 'cup',
                'title' => __('ui.trophy_room_demo_bayou_title'),
                'subtitle' => __('ui.trophy_room_demo_bayou_subtitle'),
                'description' => __('ui.trophy_room_demo_bayou_description'),
                'meta' => __('ui.trophy_room_demo_bayou_meta'),
                'rarity' => __('ui.trophy_room_rarity_gold'),
                'accent' => '#D6A84F',
            ],
            [
                'id' => 'perfect-run',
                'type' => 'medal',
                'title' => __('ui.trophy_room_demo_run_title'),
                'subtitle' => __('ui.trophy_room_demo_run_subtitle'),
                'description' => __('ui.trophy_room_demo_run_description'),
                'meta' => __('ui.trophy_room_demo_run_meta'),
                'rarity' => __('ui.trophy_room_rarity_rare'),
                'accent' => '#9BE447',
            ],
            [
                'id' => 'moment-maker',
                'type' => 'crystal',
                'title' => __('ui.trophy_room_demo_moment_title'),
                'subtitle' => __('ui.trophy_room_demo_moment_subtitle'),
                'description' => __('ui.trophy_room_demo_moment_description'),
                'meta' => __('ui.trophy_room_demo_moment_meta'),
                'rarity' => __('ui.trophy_room_rarity_epic'),
                'accent' => '#7FB069',
            ],
            [
                'id' => 'early-hunter',
                'type' => 'badge',
                'title' => __('ui.trophy_room_demo_hunter_title'),
                'subtitle' => __('ui.trophy_room_demo_hunter_subtitle'),
                'description' => __('ui.trophy_room_demo_hunter_description'),
                'meta' => __('ui.trophy_room_demo_hunter_meta'),
                'rarity' => __('ui.trophy_room_rarity_unlocked'),
                'accent' => '#CFA149',
            ],
            $this->lockedPlaceholder(),
        ];
    }

    private function lockedPlaceholder(): array
    {
        return [
            'id' => 'locked-crown',
            'type' => 'locked',
            'title' => __('ui.trophy_room_demo_locked_title'),
            'subtitle' => __('ui.trophy_room_demo_locked_subtitle'),
            'description' => __('ui.trophy_room_demo_locked_description'),
            'meta' => __('ui.trophy_room_demo_locked_meta'),
            'rarity' => __('ui.trophy_room_rarity_locked'),
            'accent' => '#5B5A50',
        ];
    }

    private function positionedTrophies(array $trophies): array
    {
        return array_values(array_map(function (array $trophy, int $index): array {
            $trophy['position'] = self::POSITIONS[$index] ?? [$index - 2, 1, -4.2];

            return $trophy;
        }, $trophies, array_keys($trophies)));
    }
}

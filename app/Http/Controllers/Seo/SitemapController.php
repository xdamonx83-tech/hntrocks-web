<?php

namespace App\Http\Controllers\Seo;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Models\CupIdea;
use App\Models\HntMap;
use App\Models\LoadoutChallenge;
use App\Models\MomentSpotlight;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SitemapController extends Controller
{
    private const MAP_SLUGS = [
        'stillwater-bayou',
        'lawson-delta',
        'desalle',
        'mammons-gulch',
    ];

    public function __invoke(): Response
    {
        $mapLastModified = $this->mapLastModified();
        $mapsLastModified = $mapLastModified->max() ?: now();

        $urls = collect([
            $this->url(route('home'), now(), 'daily', '1.0'),
            $this->url(route('app-beta.index'), now(), 'monthly', '0.7'),
            $this->url(route('cups.index'), now(), 'weekly', '0.8'),
            $this->url(route('hall-of-fame.index'), now(), 'weekly', '0.7'),
            $this->url(route('loadout-challenges.index'), now(), 'weekly', '0.8'),
            $this->url(route('cup-ideas.index'), now(), 'weekly', '0.7'),
            $this->url(route('moment-of-week.index'), now(), 'weekly', '0.7'),
            $this->url(route('maps.index'), $mapsLastModified, 'weekly', '0.9'),
            $this->url(route('legal.impressum'), now(), 'yearly', '0.3'),
            $this->url(route('legal.datenschutz'), now(), 'yearly', '0.3'),
            $this->url(route('legal.nutzungsbedingungen'), now(), 'yearly', '0.3'),
            $this->url(route('legal.netiquette'), now(), 'yearly', '0.3'),
            $this->url(route('legal.account_deletion'), now(), 'yearly', '0.3'),
            $this->url(route('legal.child_safety'), now(), 'yearly', '0.3'),
        ]);

        foreach (self::MAP_SLUGS as $slug) {
            $urls->push($this->url(route('maps.show', $slug), $mapLastModified->get($slug, now()), 'weekly', '0.8'));
        }

        if (Schema::hasTable('cups')) {
            Cup::query()
                ->visible()
                ->whereIn('status', ['planned', 'active', 'finished', 'archived'])
                ->orderByDesc('updated_at')
                ->limit(250)
                ->get(['id', 'slug', 'updated_at', 'created_at'])
                ->each(function (Cup $cup) use ($urls): void {
                    $urls->push($this->url(route('cups.show', $cup), $cup->updated_at ?: $cup->created_at, 'weekly', '0.8'));
                });
        }

        if (Schema::hasTable('loadout_challenges')) {
            LoadoutChallenge::query()
                ->publicVisible()
                ->orderByDesc('updated_at')
                ->limit(250)
                ->get(['id', 'slug', 'updated_at', 'created_at'])
                ->each(function (LoadoutChallenge $challenge) use ($urls): void {
                    $urls->push($this->url(route('loadout-challenges.show', $challenge), $challenge->updated_at ?: $challenge->created_at, 'weekly', '0.7'));
                });
        }

        if (Schema::hasTable('cup_ideas')) {
            $lastCupIdea = CupIdea::query()
                ->publicVisible()
                ->latest('updated_at')
                ->first(['id', 'updated_at', 'created_at']);

            if ($lastCupIdea) {
                $urls->push($this->url(route('cup-ideas.index'), $lastCupIdea->updated_at ?: $lastCupIdea->created_at, 'weekly', '0.7'));
            }
        }

        if (Schema::hasTable('moment_spotlights')) {
            $lastSpotlight = MomentSpotlight::query()
                ->whereIn('status', [MomentSpotlight::STATUS_ACTIVE, MomentSpotlight::STATUS_ARCHIVED])
                ->latest('updated_at')
                ->first(['id', 'updated_at', 'created_at']);

            if ($lastSpotlight) {
                $urls->push($this->url(route('moment-of-week.index'), $lastSpotlight->updated_at ?: $lastSpotlight->created_at, 'weekly', '0.7'));
            }
        }

        if (Schema::hasTable('users') && Schema::hasTable('user_profiles')) {
            User::query()
                ->select(['users.id', 'users.username', 'users.updated_at', 'users.created_at'])
                ->join('user_profiles', 'user_profiles.user_id', '=', 'users.id')
                ->where('users.status', 'active')
                ->whereNotNull('users.username')
                ->where('users.username', '!=', '')
                ->where('user_profiles.profile_visibility', 'public')
                ->orderByDesc('users.updated_at')
                ->limit(500)
                ->get()
                ->each(function (User $user) use ($urls): void {
                    $urls->push($this->url(route('profile.public', $user), $user->updated_at ?: $user->created_at, 'weekly', '0.6'));
                });
        }

        $uniqueUrls = $urls
            ->unique('loc')
            ->sortBy('loc')
            ->values();

        $xml = view('seo.sitemap', ['urls' => $uniqueUrls])->render();

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    private function url(string $loc, CarbonInterface|string|null $lastmod = null, string $changefreq = 'weekly', string $priority = '0.5'): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod instanceof CarbonInterface ? $lastmod->toDateString() : ($lastmod ?: now()->toDateString()),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }

    private function mapLastModified(): Collection
    {
        $lastModified = collect(self::MAP_SLUGS)->mapWithKeys(fn (string $slug): array => [$slug => now()]);

        try {
            if (! Schema::hasTable('hnt_maps')) {
                return $lastModified;
            }

            $query = HntMap::query()->whereIn('slug', self::MAP_SLUGS);

            if (Schema::hasTable('hnt_map_markers')) {
                $query->withMax('markers', 'updated_at');
            }

            $query->get()->each(function (HntMap $map) use ($lastModified): void {
                $timestamps = collect([
                    $map->updated_at,
                    $map->created_at,
                    $map->getAttribute('markers_max_updated_at'),
                ])->filter()->map(fn ($timestamp) => $timestamp instanceof CarbonInterface ? $timestamp : Carbon::parse($timestamp));

                $lastModified->put($map->slug, $timestamps->max() ?: now());
            });
        } catch (Throwable) {
            // Keep the public sitemap available while map tables are unavailable.
        }

        return $lastModified;
    }
}

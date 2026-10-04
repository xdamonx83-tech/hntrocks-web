<?php

namespace App\Services\Marketing;

use App\Models\EquipmentItem;
use App\Models\NewsArticle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LandingDiscoveryService
{
    /**
     * Read-only, public content for the first page and its SEO HTML.
     * Do not include unpublished articles or inactive equipment.
     *
     * @return array{latest_news: array<int, array<string, mixed>>, arsenal_preview: array<int, array<string, mixed>>}
     */
    public function forLocale(string $locale): array
    {
        $locale = in_array($locale, NewsArticle::LOCALES, true) ? $locale : 'en';

        return Cache::remember('landing.discovery.v1.'.$locale, now()->addSeconds(90), function () use ($locale): array {
            $articles = NewsArticle::query()
                ->published()
                ->whereHas('translations', fn ($query) => $query->where('locale', $locale)->publishable())
                ->with(['translations' => fn ($query) => $query->where('locale', $locale), 'heroMedia'])
                ->orderByDesc('published_at')
                ->orderByDesc('id')
                ->limit(3)
                ->get();

            $news = $articles->map(function (NewsArticle $article) use ($locale): array {
                $translation = $article->translationForLocale($locale);
                $hero = $article->heroMedia;
                $imageUrl = null;

                if ($hero && $hero->context === 'news' && $hero->status === 'ready'
                    && $hero->visibility === 'private') {
                    $variant = $hero->thumbnail_path ? 'thumbnail'
                        : (str_starts_with((string) $hero->mime_type, 'image/') ? 'original' : null);
                    if ($variant !== null) {
                        $imageUrl = route('news.media', [
                            'article' => $article->id,
                            'asset' => $hero->id,
                            'variant' => $variant,
                        ]);
                    }
                }

                return [
                    'title' => (string) $translation->title,
                    'excerpt' => Str::limit(trim(strip_tags((string) ($translation->excerpt ?? ''))), 170),
                    'url' => route('news.article', ['locale' => $locale, 'slug' => $translation->slug]),
                    'published_at' => $article->published_at?->toIso8601String(),
                    'image_url' => $imageUrl,
                    'category' => (string) ($article->category_key ?? ''),
                ];
            })->all();

            // One representative from four distinct weapon categories, not four variants
            // of the alphabetically first gun.
            $slugs = ['1865-carbine', 'auto-5', 'bornheim-no-3', 'crossbow'];
            $weapons = EquipmentItem::query()
                ->where('source_status', 'active')
                ->where('item_type', 'weapon')
                ->whereIn('slug', $slugs)
                ->with('translations')
                ->get()
                ->keyBy('slug');

            $arsenal = collect($slugs)
                ->map(fn (string $slug) => $weapons->get($slug))
                ->filter()
                ->map(function (EquipmentItem $item) use ($locale): array {
                    $translation = $item->translations->firstWhere('locale', $locale)
                        ?? $item->translations->firstWhere('locale', 'en');

                    return [
                        'name' => (string) ($translation?->name ?: $item->name),
                        'slug' => (string) $item->slug,
                        'url' => url('/arsenal/'.$item->slug),
                        'category' => (string) ($item->category ?? ''),
                        'ammo_type' => (string) ($item->ammo_type ?? ''),
                        'image_url' => $item->imageUrl(),
                    ];
                })
                ->values()
                ->all();

            return ['latest_news' => $news, 'arsenal_preview' => $arsenal];
        });
    }
}

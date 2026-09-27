<?php

namespace App\Http\Controllers\News;

use App\Http\Controllers\Controller;
use App\Models\MediaAsset;
use App\Models\NewsArticle;
use App\Models\NewsArticleTranslation;
use App\Support\NewsContentDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

class NewsPublicController extends Controller
{
    private const LOCALES = ['de', 'en', 'es', 'ru'];

    public function apiIndex(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'locale' => ['nullable', Rule::in(self::LOCALES)],
            'featured' => ['nullable', 'boolean'],
            'category' => ['nullable', 'string', 'max:80'],
            'tag' => ['nullable', 'string', 'max:40', 'regex:/^[a-z0-9][a-z0-9-]*$/'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $locale = $this->locale($request, $filters['locale'] ?? null);
        $query = $this->publishedForLocale($locale)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale), 'heroMedia']);

        if (isset($filters['featured'])) $query->where('featured', (bool) $filters['featured']);
        if (! empty($filters['category'])) $query->where('category_key', $filters['category']);
        if (! empty($filters['tag'])) $query->whereJsonContains('tags', $filters['tag']);

        $articles = $query->orderByDesc('published_at')->orderByDesc('id')
            ->paginate((int) ($filters['per_page'] ?? 12))->withQueryString();
        $categories = $this->publishedForLocale($locale)->whereNotNull('category_key')
            ->distinct()->orderBy('category_key')->pluck('category_key')->values();
        $tags = $this->publishedForLocale($locale)->pluck('tags')->flatMap(fn ($values) => $values ?? [])
            ->filter(fn ($tag) => is_string($tag) && $tag !== '')->unique()->sort()->values();
        $featured = $this->publishedForLocale($locale)->where('featured', true)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale), 'heroMedia'])
            ->orderByDesc('published_at')->first();

        return response()->json([
            'data' => collect($articles->items())->map(fn (NewsArticle $article) => $this->publicArticle($article, $locale))->values(),
            'featured_article' => $featured ? $this->publicArticle($featured, $locale) : null,
            'meta' => [
                'current_page' => $articles->currentPage(), 'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(), 'total' => $articles->total(),
                'from' => $articles->firstItem(), 'to' => $articles->lastItem(),
            ],
            'facets' => ['categories' => $categories, 'tags' => $tags],
        ]);
    }

    public function apiShow(string $locale, string $slug): JsonResponse
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);
        $article = $this->publishedForLocale($locale)
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('slug', $slug))
            ->with(['translations', 'heroMedia'])->firstOrFail();
        $relatedQuery = $this->publishedForLocale($locale)->where('news_articles.id', '!=', $article->id);
        $related = $relatedQuery->with(['translations' => fn ($q) => $q->where('locale', $locale), 'heroMedia'])
            ->orderByDesc('published_at')->limit(50)->get()
            ->sort(function (NewsArticle $left, NewsArticle $right) use ($article): int {
                $category = (int) ($right->category_key === $article->category_key && $article->category_key !== null)
                    <=> (int) ($left->category_key === $article->category_key && $article->category_key !== null);
                if ($category !== 0) return $category;
                $tags = count(array_intersect($right->tags ?? [], $article->tags ?? []))
                    <=> count(array_intersect($left->tags ?? [], $article->tags ?? []));
                return $tags !== 0 ? $tags : $right->published_at <=> $left->published_at;
            })->take(4)->values();

        return response()->json([
            'data' => $this->publicArticle($article, $locale),
            'alternates' => $this->alternateMap($article),
            'related' => $related->map(fn (NewsArticle $item) => $this->publicArticle($item, $locale))->values(),
        ]);
    }

    public function apiMedia(NewsArticle $article, MediaAsset $asset, string $variant): Response
    {
        abort_unless(in_array($variant, ['original', 'thumbnail'], true), 404);
        abort_unless($article->status === NewsArticle::STATUS_PUBLISHED && $article->published_at?->lte(now()), 404);
        abort_unless($article->translations()->publishable()->exists(), 404);
        abort_unless($asset->context === 'news' && $asset->visibility === 'private' && $asset->status === 'ready', 404);
        abort_unless($this->articleUsesAsset($article, (int) $asset->id), 404);
        $path = $variant === 'original' ? $asset->path : $asset->thumbnail_path;
        abort_unless(is_string($path) && $path !== '' && Storage::disk($asset->disk)->exists($path), 404);

        return Storage::disk($asset->disk)->response($path, $asset->original_name, [
            'Content-Type' => (string) $asset->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'public, max-age=3600, s-maxage=86400',
        ], 'inline');
    }

    public function overview(Request $request, ?string $locale = null): Response
    {
        $locale = $this->locale($request, $locale);
        app()->setLocale($locale);
        $articles = $this->publishedForLocale($locale)
            ->with(['translations' => fn ($q) => $q->where('locale', $locale), 'heroMedia'])
            ->orderByDesc('published_at')->limit(24)->get()
            ->map(fn (NewsArticle $article) => $this->publicArticle($article, $locale));
        $title = $this->overviewTitle($locale);
        $description = $this->overviewDescription($locale);
        $canonical = route('news.overview.locale', ['locale' => $locale]);
        $head = view('react.news-seo', [
            'mode' => 'head', 'locale' => $locale, 'title' => $title, 'description' => $description,
            'canonical' => $canonical, 'image' => $this->defaultImageUrl(),
            'alternates' => $this->overviewAlternates(),
            'structuredData' => $this->overviewStructuredData($canonical, $title, $description, $locale, $articles),
        ])->render();
        $fallback = view('react.news-seo', [
            'mode' => 'overview', 'locale' => $locale, 'title' => $title,
            'description' => $description, 'articles' => $articles,
        ])->render();

        return $this->reactResponse($locale, $title, $head, $fallback);
    }

    public function article(Request $request, string $locale, string $slug): Response
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);
        app()->setLocale($locale);
        $article = $this->publishedForLocale($locale)
            ->whereHas('translations', fn ($q) => $q->where('locale', $locale)->where('slug', $slug))
            ->with(['translations', 'heroMedia'])->firstOrFail();
        $translation = $article->translationForLocale($locale);
        abort_unless($translation, 404);
        $canonical = route('news.article', ['locale' => $locale, 'slug' => $translation->slug]);
        $title = $translation->seo_title ?: $translation->title;
        $description = $translation->seo_description ?: $translation->excerpt ?: $this->overviewDescription($locale);
        $media = $this->mediaForArticle($article, $translation);
        $heroAsset = $media[(int) ($article->hero_media_asset_id ?? 0)] ?? null;
        $image = $heroAsset && str_starts_with((string) ($heroAsset['mime_type'] ?? ''), 'image/')
            ? $heroAsset['url']
            : ($heroAsset['thumbnail_url'] ?? $this->defaultImageUrl());
        $modified = collect([$translation->updated_at, $article->updated_at])->filter()->max();
        $structuredData = [
            '@context' => 'https://schema.org', '@type' => 'NewsArticle',
            'headline' => $translation->title, 'description' => $description, 'image' => [$image],
            'datePublished' => $article->published_at?->toIso8601String(), 'dateModified' => $modified?->toIso8601String(),
            'publisher' => [
                '@type' => 'Organization', 'name' => 'HNT.ROCKS', 'url' => 'https://hnt.rocks/',
                'logo' => ['@type' => 'ImageObject', 'url' => 'https://hnt.rocks/assets/socialite/images/logo-light.png'],
            ],
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $canonical], 'inLanguage' => $locale,
        ];
        $head = view('react.news-seo', [
            'mode' => 'head', 'locale' => $locale, 'title' => $title, 'description' => $description,
            'canonical' => $canonical, 'image' => $image, 'alternates' => $this->alternateMap($article),
            'publishedAt' => $article->published_at?->toIso8601String(), 'modifiedAt' => $modified?->toIso8601String(),
            'structuredData' => $structuredData,
        ])->render();
        $fallback = view('react.news-seo', [
            'mode' => 'article', 'locale' => $locale, 'article' => $this->publicArticle($article, $locale),
            'translation' => $translation, 'media' => $media,
        ])->render();

        return $this->reactResponse($locale, $title, $head, $fallback);
    }

    private function publishedForLocale(string $locale): Builder
    {
        return NewsArticle::query()->published()->whereHas('translations', fn ($q) => $q
            ->where('locale', $locale)->publishable());
    }

    private function publicArticle(NewsArticle $article, string $locale): array
    {
        $translation = $article->translationForLocale($locale);
        if (! $translation) return [];
        $media = $this->mediaForArticle($article, $translation);

        return [
            'id' => (int) $article->id, 'locale' => $locale, 'slug' => $translation->slug,
            'url' => route('news.article', ['locale' => $locale, 'slug' => $translation->slug]),
            'title' => $translation->title, 'excerpt' => $translation->excerpt,
            'content_json' => $translation->content_json ?? [], 'category_key' => $article->category_key,
            'tags' => $article->tags ?? [], 'featured' => (bool) $article->featured,
            'comments_enabled' => (bool) $article->comments_enabled,
            'hero_media' => $media[(int) ($article->hero_media_asset_id ?? 0)] ?? null, 'media' => $media,
            'published_at' => $article->published_at?->toIso8601String(),
            'updated_at' => $translation->updated_at?->toIso8601String(),
        ];
    }

    private function mediaForArticle(NewsArticle $article, NewsArticleTranslation $translation): array
    {
        $ids = $article->hero_media_asset_id ? [(int) $article->hero_media_asset_id] : [];
        $ids = array_merge($ids, NewsContentDocument::mediaIds($translation->content_json ?? []));
        return MediaAsset::query()->where('context', 'news')->where('visibility', 'private')->where('status', 'ready')
            ->whereIn('id', array_values(array_unique($ids)))->get()->mapWithKeys(fn (MediaAsset $asset) => [
                (int) $asset->id => [
                    'id' => (int) $asset->id, 'type' => $asset->type, 'mime_type' => $asset->mime_type,
                    'url' => route('news.media', ['article' => $article->id, 'asset' => $asset->id, 'variant' => 'original']),
                    'thumbnail_url' => $asset->thumbnail_path ? route('news.media', ['article' => $article->id, 'asset' => $asset->id, 'variant' => 'thumbnail']) : null,
                    'width' => $asset->width, 'height' => $asset->height,
                    'duration_seconds' => $asset->duration_seconds, 'alt_text' => $asset->alt_text,
                ],
            ])->all();
    }

    private function articleUsesAsset(NewsArticle $article, int $assetId): bool
    {
        if ((int) $article->hero_media_asset_id === $assetId) return true;
        foreach ($article->translations()->get() as $translation) {
            if (! $translation->isPublishable()) continue;
            if (in_array($assetId, NewsContentDocument::mediaIds($translation->content_json ?? []), true)) return true;
        }
        return false;
    }

    private function alternateMap(NewsArticle $article): array
    {
        $translations = $article->translations->keyBy('locale');
        return collect(self::LOCALES)->filter(fn ($locale) => $translations->get($locale)?->isPublishable())
            ->mapWithKeys(fn ($locale) => [$locale => route('news.article', ['locale' => $locale, 'slug' => $translations->get($locale)->slug])])->all();
    }

    private function overviewAlternates(): array
    {
        return collect(self::LOCALES)->mapWithKeys(fn ($locale) => [$locale => route('news.overview.locale', ['locale' => $locale])])->all();
    }

    private function overviewStructuredData(string $url, string $title, string $description, string $locale, $articles): array
    {
        return [
            '@context' => 'https://schema.org', '@type' => 'CollectionPage', '@id' => $url.'#news',
            'url' => $url, 'name' => $title, 'description' => $description, 'inLanguage' => $locale,
            'mainEntity' => ['@type' => 'ItemList', 'itemListElement' => $articles->values()->map(fn ($item, $index) => [
                '@type' => 'ListItem', 'position' => $index + 1, 'url' => $item['url'], 'name' => $item['title'],
            ])->all()],
        ];
    }

    private function reactResponse(string $locale, string $title, string $head, string $fallback): Response
    {
        $reactIndex = public_path('app/index.html');
        abort_unless(File::isFile($reactIndex), 503, 'The React application bundle is unavailable.');
        $html = File::get($reactIndex);
        $html = preg_replace('~<html\s+lang="[^"]*"~i', '<html lang="'.e($locale).'"', $html, 1) ?? $html;
        $html = preg_replace('~<title>.*?</title>~is', '<title>'.e($title).'</title>', $html, 1) ?? $html;
        $html = preg_replace('~<meta\s+(?:name|property)="(?:description|robots|og:[^"]+|twitter:[^"]+)"[^>]*>~i', '', $html) ?? $html;
        $html = preg_replace('~<link\s+rel="canonical"[^>]*>~i', '', $html) ?? $html;
        abort_unless(str_contains($html, '</head>'), 503, 'The React application head could not be prepared.');
        $html = str_replace('</head>', $head."\n</head>", $html);
        $rootPattern = '~<div\s+id="root"\s*></div>~i';
        abort_unless(preg_match($rootPattern, $html) === 1, 503, 'The React application root could not be prepared.');
        $html = preg_replace($rootPattern, '<div id="root">'.$fallback.'</div>', $html, 1) ?? $html;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8', 'Content-Language' => $locale,
            'Cache-Control' => 'no-cache, private', 'Vary' => 'Accept-Language, X-HNT-Locale',
        ]);
    }

    private function locale(Request $request, ?string $explicit = null): string
    {
        if (is_string($explicit) && in_array($explicit, self::LOCALES, true)) return $explicit;
        $requested = strtolower((string) ($request->query('locale') ?: $request->header('X-HNT-Locale', '')));
        if (in_array($requested, self::LOCALES, true)) return $requested;
        $cookie = strtolower((string) ($request->cookie('hnt-locale') ?: $request->cookie('hnt-next-locale') ?: $request->cookie('locale')));
        if (in_array($cookie, self::LOCALES, true)) return $cookie;
        $language = strtolower((string) $request->header('Accept-Language', ''));
        foreach (explode(',', $language) as $part) {
            $candidate = strtolower(trim(explode(';', $part, 2)[0] ?? ''));
            $candidate = explode('-', $candidate, 2)[0];
            if (in_array($candidate, self::LOCALES, true)) return $candidate;
        }
        return 'en';
    }

    private function overviewTitle(string $locale): string
    {
        return ['de' => 'News | HNT.ROCKS', 'en' => 'News | HNT.ROCKS', 'es' => 'Noticias | HNT.ROCKS', 'ru' => 'Новости | HNT.ROCKS'][$locale];
    }

    private function overviewDescription(string $locale): string
    {
        return [
            'de' => 'Aktuelle Updates, Community-Geschichten, Events und Einblicke aus der Welt von HNT.ROCKS.',
            'en' => 'The latest HNT.ROCKS updates, community stories, events, and behind-the-scenes news.',
            'es' => 'Las últimas novedades de HNT.ROCKS, historias de la comunidad, eventos y noticias entre bastidores.',
            'ru' => 'Последние новости HNT.ROCKS, истории сообщества, события и закулисные материалы.',
        ][$locale];
    }

    private function defaultImageUrl(): string
    {
        return 'https://hnt.rocks/assets/socialite/images/seo/hnt-og-default.png';
    }
}

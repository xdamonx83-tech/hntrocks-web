<?php

namespace App\Services\HuntNews;

use App\Models\ApprovedOutboundLink;
use App\Models\FeedPost;
use App\Models\HuntNewsItem;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class OfficialHuntNewsImporter
{
    public function sync(bool $autoPublish = true, ?CarbonInterface $autoPublishFrom = null, int $limit = 16): array
    {
        $limit = max(1, min(40, $limit));
        $indexUrl = (string) config('hunthub.hunt_news.source_url', 'https://www.huntshowdown.com/news');
        $autoPublishFrom ??= $this->autoPublishFrom();

        $stats = [
            'seen' => 0,
            'created' => 0,
            'updated' => 0,
            'posted' => 0,
            'skipped' => 0,
            'errors' => 0,
        ];

        $indexHtml = $this->fetch($indexUrl);
        $links = array_slice($this->extractArticleLinks($indexHtml, $indexUrl), 0, $limit);
        $stats['seen'] = count($links);

        foreach ($links as $link) {
            try {
                $article = $this->hydrateArticle($link);
                $publishedAt = $article['published_at'];
                $eligible = $publishedAt instanceof CarbonInterface && $publishedAt->greaterThanOrEqualTo($autoPublishFrom);

                $item = HuntNewsItem::query()->firstOrNew(['source_hash' => sha1($article['url'])]);
                $isNew = ! $item->exists;
                $alreadyPosted = $item->isPosted();

                $item->fill([
                    'source_url' => $article['url'],
                    'source_slug' => $article['slug'],
                    'source_domain' => $article['domain'],
                    'title' => $article['title'],
                    'excerpt' => $article['excerpt'],
                    'category' => $article['category'],
                    'source_published_at' => $publishedAt,
                    'discovered_at' => $item->discovered_at ?: now(),
                    'auto_publish_eligible' => $eligible,
                    'raw_meta' => $article['raw_meta'],
                ]);

                if (! $item->exists) {
                    $item->status = HuntNewsItem::STATUS_DISCOVERED;
                }

                if ($item->status === HuntNewsItem::STATUS_ERROR && ! $alreadyPosted) {
                    $item->status = HuntNewsItem::STATUS_DISCOVERED;
                    $item->error_message = null;
                }

                $item->save();
                $stats[$isNew ? 'created' : 'updated']++;

                if ($alreadyPosted && $item->isPosted()) {
                    $this->refreshPublishedPost($item);
                }

                if ($autoPublish && $eligible && ! $alreadyPosted && $item->status === HuntNewsItem::STATUS_DISCOVERED) {
                    $this->publish($item);
                    $stats['posted']++;
                }
            } catch (Throwable $exception) {
                $stats['errors']++;
            }
        }

        return $stats;
    }

    public function publish(HuntNewsItem $item, ?int $postedByUserId = null): FeedPost
    {
        if ($item->isPosted() && $item->feedPost) {
            return $item->feedPost;
        }

        $userId = (int) config('hunthub.hunt_news.user_id', 1);
        $user = User::query()->find($userId);

        if (! $user) {
            throw new \RuntimeException('HuntNews user #'.$userId.' wurde nicht gefunden.');
        }

        return DB::transaction(function () use ($item, $user, $postedByUserId): FeedPost {
            $outbound = $this->approvedOutboundLinkFor($item, (int) $user->id);
            $body = $this->postBody($item, $outbound);

            $post = FeedPost::create([
                'user_id' => $user->id,
                'body' => $body,
                'source_language' => 'en',
                'visibility' => 'public',
                'status' => 'published',
            ]);

            $item->forceFill([
                'status' => HuntNewsItem::STATUS_POSTED,
                'feed_post_id' => $post->id,
                'outbound_link_id' => $outbound->id,
                'posted_at' => now(),
                'posted_by_user_id' => $postedByUserId,
                'error_message' => null,
            ])->save();

            return $post;
        });
    }

    public function markSkipped(HuntNewsItem $item): void
    {
        if ($item->isPosted()) {
            return;
        }

        $item->forceFill([
            'status' => HuntNewsItem::STATUS_SKIPPED,
            'error_message' => null,
        ])->save();
    }

    private function autoPublishFrom(): CarbonInterface
    {
        $configured = config('hunthub.hunt_news.auto_publish_from');

        if (is_string($configured) && trim($configured) !== '') {
            try {
                return Carbon::parse($configured)->startOfDay();
            } catch (Throwable) {
                // Fall back to today if the optional config value is invalid.
            }
        }

        return now()->startOfDay();
    }

    private function fetch(string $url): string
    {
        $response = Http::timeout((int) config('hunthub.hunt_news.timeout', 15))
            ->retry(2, 400)
            ->withHeaders([
                'User-Agent' => 'HNT.rocks HuntNews importer (+https://hnt.rocks)',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            ])
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('Quelle nicht erreichbar: '.$url.' · HTTP '.$response->status());
        }

        return (string) $response->body();
    }

    private function extractArticleLinks(string $html, string $indexUrl): array
    {
        preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);

        $items = [];
        $seen = [];

        foreach ($matches as $match) {
            $url = $this->absoluteUrl($match[1], $indexUrl);

            if (! $this->isAllowedNewsUrl($url) || isset($seen[$url])) {
                continue;
            }

            $title = $this->cleanText(strip_tags($match[2] ?? ''));
            $seen[$url] = true;
            $items[] = [
                'url' => $url,
                'title' => $title,
            ];
        }

        return $items;
    }

    private function hydrateArticle(array $link): array
    {
        $url = (string) $link['url'];
        $html = $this->fetch($url);
        $title = $this->extractTitle($html) ?: $this->cleanText((string) ($link['title'] ?? ''));
        $excerpt = $this->extractArticleExcerpt($html) ?: $this->extractDescription($html);
        $publishedAt = $this->extractPublishedAt($html);
        $category = $this->extractCategory($html);
        $parts = parse_url($url) ?: [];
        $path = (string) ($parts['path'] ?? '');

        return [
            'url' => $url,
            'slug' => $this->sourceSlug($url),
            'domain' => Str::lower((string) ($parts['host'] ?? 'huntshowdown.com')),
            'title' => Str::limit($title ?: 'Official Hunt: Showdown News', 240, ''),
            'excerpt' => $excerpt ? Str::limit($excerpt, 480) : null,
            'category' => $category,
            'published_at' => $publishedAt,
            'raw_meta' => [
                'path' => $path,
                'parsed_title' => $title,
                'parsed_excerpt' => $excerpt,
                'parsed_category' => $category,
                'parsed_published_at' => $publishedAt?->toIso8601String(),
            ],
        ];
    }

    private function extractTitle(string $html): ?string
    {
        foreach ([
            '/<meta\s+property=["\']og:title["\']\s+content=["\']([^"\']+)["\']/i',
            '/<meta\s+name=["\']twitter:title["\']\s+content=["\']([^"\']+)["\']/i',
            '/<h1\b[^>]*>(.*?)<\/h1>/is',
            '/<title\b[^>]*>(.*?)<\/title>/is',
        ] as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $title = $this->cleanText(html_entity_decode(strip_tags($match[1]), ENT_QUOTES | ENT_HTML5));
                $title = preg_replace('/\s+[-|·]\s+Hunt.*$/i', '', $title) ?: $title;
                if ($title !== '') {
                    return $title;
                }
            }
        }

        return null;
    }

    private function extractArticleExcerpt(string $html): ?string
    {
        $body = $this->extractOfficialNewsArticleBody($html);

        $body = preg_replace('/<(script|style|svg|nav|header|footer|form|button|noscript|iframe)\b[^>]*>.*?<\/\1>/is', ' ', $body) ?? $body;
        $body = preg_replace('/<[^>]+class=["\'][^"\']*(cookie|newsletter|share|social|footer|header|nav|menu|support|captcha)[^"\']*["\'][^>]*>.*?<\/[^>]+>/is', ' ', $body) ?? $body;

        preg_match_all('/<p\b[^>]*>(.*?)<\/p>/is', $body, $paragraphMatches);
        $paragraphs = $paragraphMatches[1] ?? [];

        if (count($paragraphs) === 0) {
            preg_match_all('/<(h2|h3|li)\b[^>]*>(.*?)<\/\1>/is', $body, $fallbackMatches);
            $paragraphs = $fallbackMatches[2] ?? [];
        }

        $chunks = [];

        foreach ($paragraphs as $paragraph) {
            $text = $this->stripNonArticleBoilerplate($this->cleanText(strip_tags($paragraph)));

            if (! $this->isUsableArticleExcerptChunk($text)) {
                continue;
            }

            $chunks[] = $text;

            if (mb_strlen(implode(' ', $chunks)) >= 260 || count($chunks) >= 3) {
                break;
            }
        }

        $excerpt = $this->stripNonArticleBoilerplate($this->cleanText(implode(' ', $chunks)));

        if (! $this->isUsableArticleExcerptChunk($excerpt)) {
            return null;
        }

        return Str::limit($excerpt, 480);
    }

    private function extractOfficialNewsArticleBody(string $html): string
    {
        // The official Hunt pages currently keep the real article copy in
        // <div class="row news-article-text">. Prefer that block because
        // generic meta descriptions and browser warning text appear earlier in
        // the document and are not article excerpts.
        if (preg_match('/<div\b[^>]*class=["\'][^"\']*news-article-text[^"\']*["\'][^>]*>(.*?)(?:<h2\b[^>]*>\s*Share\s+ON\s*:?\s*<\/h2>|Share\s+ON\s*:|See more News|Subscribe to get our latest updates|<footer\b)/is', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/<main\b[^>]*>(.*?)<\/main>/is', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/<article\b[^>]*>(.*?)<\/article>/is', $html, $match)) {
            return $match[1];
        }

        if (preg_match('/<h1\b[^>]*>.*?<\/h1>(.*?)(?:<h2\b[^>]*>\s*Share\s+ON\s*:?\s*<\/h2>|Share\s+ON\s*:|See more News|Subscribe to get our latest updates|<footer\b)/is', $html, $match)) {
            return $match[1];
        }

        return $html;
    }

    private function stripNonArticleBoilerplate(string $text): string
    {
        $text = $this->cleanText($text);

        $text = preg_replace('/^You are using an outdated browser\.?\s*Please upgrade your browser or activate Google Chrome Frame to improve your experience\.?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/^Your browser is out of date\.?\s*/i', '', $text) ?? $text;
        $text = preg_replace('/^Hunters,\s*/i', '', $text) ?? $text;

        return $this->cleanText($text);
    }

    private function isUsableArticleExcerptChunk(?string $text): bool
    {
        $text = $this->stripNonArticleBoilerplate($this->cleanText((string) $text));

        if ($text === '' || mb_strlen($text) < 24) {
            return false;
        }

        $lower = Str::lower($text);

        foreach ([
            'hunt: showdown is a competitive first-person pvp bounty hunting game',
            'hunt packs the thrill of survival games into a match-based format',
            'you are using an outdated browser',
            'please upgrade your browser',
            'google chrome frame to improve your experience',
            'your browser is out of date',
            'watch the event trailer now',
            'hunters,',
            'sie verwenden einen veralteten browser',
            'crytek gmbh uses cookies',
            'you may adjust your cookie preferences',
            'youtube player uses cookies',
            'please log in for support',
            'subscribe to get our latest updates',
            'you agree to receive our hunt newsletter',
            'all rights reserved',
            'terms of service',
            'privacy policy',
            'cookie policy',
            'buy now',
            'read more',
            'share on:',
        ] as $blocked) {
            if (Str::contains($lower, $blocked)) {
                return false;
            }
        }

        return true;
    }

    private function refreshPublishedPost(HuntNewsItem $item): void
    {
        $item->loadMissing(['feedPost', 'outboundLink']);

        if ($item->outboundLink) {
            $item->outboundLink->forceFill([
                'title' => 'Official Hunt News: '.$item->displayTitle(),
                'description' => $item->excerpt ?: 'Offizielle Hunt: Showdown News. Der vollständige Artikel öffnet auf huntshowdown.com.',
            ])->save();
        }

        if ($item->feedPost) {
            $outbound = $item->outboundLink ?: $this->approvedOutboundLinkFor($item, (int) config('hunthub.hunt_news.user_id', 1));
            $item->feedPost->forceFill([
                'body' => $this->postBody($item, $outbound),
            ])->save();
        }
    }

    private function extractDescription(string $html): ?string
    {
        foreach ([
            '/<meta\s+property=["\']og:description["\']\s+content=["\']([^"\']+)["\']/i',
            '/<meta\s+name=["\']description["\']\s+content=["\']([^"\']+)["\']/i',
            '/<meta\s+name=["\']twitter:description["\']\s+content=["\']([^"\']+)["\']/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                $description = $this->cleanText(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5));
                if ($this->isUsableArticleExcerptChunk($description)) {
                    return Str::limit($description, 480);
                }
            }
        }

        return null;
    }

    private function extractPublishedAt(string $html): ?CarbonInterface
    {
        foreach ([
            '/<meta\s+property=["\']article:published_time["\']\s+content=["\']([^"\']+)["\']/i',
            '/<meta\s+name=["\']pubdate["\']\s+content=["\']([^"\']+)["\']/i',
            '/<time\b[^>]*datetime=["\']([^"\']+)["\']/i',
        ] as $pattern) {
            if (preg_match($pattern, $html, $match)) {
                try {
                    return Carbon::parse($match[1]);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        if (preg_match('/(20\d{2}-\d{2}-\d{2})/', $html, $match)) {
            try {
                return Carbon::parse($match[1]);
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    private function extractCategory(string $html): ?string
    {
        if (preg_match('/<meta\s+property=["\']article:section["\']\s+content=["\']([^"\']+)["\']/i', $html, $match)) {
            $category = Str::lower($this->cleanText($match[1]));
            return $category !== '' ? Str::limit($category, 80, '') : null;
        }

        return null;
    }

    private function approvedOutboundLinkFor(HuntNewsItem $item, int $userId): ApprovedOutboundLink
    {
        if ($item->outboundLink) {
            return $item->outboundLink;
        }

        $slug = $this->uniqueOutboundSlug('hunt-news-'.$item->source_slug);

        return ApprovedOutboundLink::create([
            'title' => 'Official Hunt News: '.$item->displayTitle(),
            'slug' => $slug,
            'description' => $item->excerpt ?: 'Offizielle Hunt: Showdown News. Der vollständige Artikel öffnet auf huntshowdown.com.',
            'target_url' => $item->source_url,
            'target_domain' => parse_url($item->source_url, PHP_URL_HOST) ?: 'huntshowdown.com',
            'is_active' => true,
            'created_by' => $userId,
            'admin_note' => 'Automatisch vom HuntNews-Importer angelegt.',
        ]);
    }

    private function postBody(HuntNewsItem $item, ApprovedOutboundLink $outbound): string
    {
        $lines = [
            'Offizielle Hunt: Showdown News: '.$item->displayTitle(),
            '',
        ];

        if (filled($item->excerpt)) {
            $lines[] = 'Kurzer Auszug: '.Str::limit(trim((string) $item->excerpt), 260);
            $lines[] = '';
        }

        $lines[] = 'Quelle: Offizielle Hunt: Showdown Website';
        $lines[] = 'Vollständige News lesen: '.$outbound->publicUrl();

        return implode("\n", $lines);
    }

    private function uniqueOutboundSlug(string $base): string
    {
        $base = Str::slug($base) ?: 'hunt-news';
        $slug = Str::limit($base, 130, '');
        $candidate = $slug;
        $counter = 2;

        while (ApprovedOutboundLink::query()->where('slug', $candidate)->exists()) {
            $candidate = Str::limit($slug, 125, '').'-'.$counter;
            $counter++;
        }

        return $candidate;
    }

    private function sourceSlug(string $url): string
    {
        $path = trim((string) (parse_url($url, PHP_URL_PATH) ?: ''), '/');
        $last = collect(explode('/', $path))->filter()->last();

        return Str::slug((string) $last) ?: sha1($url);
    }

    private function absoluteUrl(string $href, string $baseUrl): string
    {
        $href = html_entity_decode(trim($href), ENT_QUOTES | ENT_HTML5);

        if (Str::startsWith($href, ['http://', 'https://'])) {
            return $this->canonicalUrl($href);
        }

        if (Str::startsWith($href, '//')) {
            return $this->canonicalUrl('https:'.$href);
        }

        $base = parse_url($baseUrl) ?: [];
        $scheme = (string) ($base['scheme'] ?? 'https');
        $host = (string) ($base['host'] ?? 'www.huntshowdown.com');

        if (Str::startsWith($href, '/')) {
            return $this->canonicalUrl($scheme.'://'.$host.$href);
        }

        $basePath = rtrim(dirname((string) ($base['path'] ?? '/')), '/');

        return $this->canonicalUrl($scheme.'://'.$host.($basePath ? '/'.$basePath : '').'/'.$href);
    }

    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = (string) ($parts['path'] ?? '/');

        return $scheme.'://'.$host.$path;
    }

    private function isAllowedNewsUrl(string $url): bool
    {
        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        $path = '/'.trim((string) ($parts['path'] ?? ''), '/');

        if (! in_array($host, ['huntshowdown.com', 'www.huntshowdown.com'], true)) {
            return false;
        }

        if (! Str::startsWith($path, '/news/')) {
            return false;
        }

        if (preg_match('~/news/(tagged|category|page)(/|$)~i', $path)) {
            return false;
        }

        return strlen(trim(substr($path, strlen('/news/')), '/')) > 2;
    }

    private function cleanText(string $value): string
    {
        // Hunt's article HTML can contain double-encoded entities such as
        // &amp;#039; which become &#039; after a single decode. Decode a few
        // times so feed excerpts and link previews show normal punctuation.
        for ($i = 0; $i < 4; $i++) {
            $decoded = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if ($decoded === $value) {
                break;
            }

            $value = $decoded;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}

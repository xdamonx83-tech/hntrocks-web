<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsArticleTranslation;
use App\Models\User;
use App\Services\NewsArticleEditorService;
use App\Services\Translation\FeedTranslationService;
use Illuminate\Console\Command;
use Throwable;

class TranslateNewsArticle extends Command
{
    protected $signature = 'hnt:news-translate
        {slug : Slug der Quell-News}
        {--from=de : Quellsprache}
        {--to=en,es,ru : Kommagetrennte Zielsprachen}
        {--force : Vorhandene Zielübersetzungen überschreiben}';

    protected $description = 'Übersetzt eine News samt Inhalt und SEO-Feldern automatisch in weitere HNT.ROCKS-Sprachen.';

    private const TEXT_KEYS = [
        'text',
        'caption',
        'alt',
        'attribution',
        'before_alt',
        'after_alt',
        'before_label',
        'after_label',
        'title',
    ];

    /** @var array<string, string> */
    private array $translationCache = [];

    public function handle(
        FeedTranslationService $translator,
        NewsArticleEditorService $editorService,
    ): int {
        $slug = trim((string) $this->argument('slug'));
        $from = strtolower(trim((string) $this->option('from')));
        $targets = collect(explode(',', (string) $this->option('to')))
            ->map(fn ($locale) => strtolower(trim((string) $locale)))
            ->filter()
            ->unique()
            ->values();

        if (! in_array($from, NewsArticle::LOCALES, true)) {
            $this->error('Ungültige Quellsprache: '.$from);

            return self::FAILURE;
        }

        $invalidTargets = $targets->reject(fn ($locale) => in_array($locale, NewsArticle::LOCALES, true));

        if ($invalidTargets->isNotEmpty()) {
            $this->error('Ungültige Zielsprachen: '.$invalidTargets->implode(', '));

            return self::FAILURE;
        }

        $targets = $targets->reject(fn ($locale) => $locale === $from)->values();

        if ($targets->isEmpty()) {
            $this->error('Keine gültigen Zielsprachen übrig.');

            return self::FAILURE;
        }

        $source = NewsArticleTranslation::query()
            ->where('locale', $from)
            ->where('slug', $slug)
            ->first();

        if (! $source) {
            $this->error("News nicht gefunden: {$from}/{$slug}");

            return self::FAILURE;
        }

        if (! $source->isPublishable()) {
            $this->error('Die Quellübersetzung ist nicht vollständig/publishable.');

            return self::FAILURE;
        }

        $article = NewsArticle::query()
            ->with(['translations', 'updatedBy', 'createdBy'])
            ->find($source->news_article_id);

        if (! $article) {
            $this->error('Zugehöriger News-Artikel wurde nicht gefunden.');

            return self::FAILURE;
        }

        $editor = collect([$article->updatedBy, $article->createdBy])
            ->first(fn ($user) => $user instanceof User && $user->isAdmin());

        if (! $editor instanceof User) {
            $this->error('Kein Admin-Editor am Artikel gefunden; Übersetzung wird nicht gespeichert.');

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $payload = [];

        try {
            foreach ($targets as $target) {
                $existing = $article->translations->firstWhere('locale', $target);

                if ($existing?->isPublishable() && ! $force) {
                    $this->warn(strtoupper($target).': bereits vollständig vorhanden – übersprungen.');

                    continue;
                }

                $this->info('Übersetze '.strtoupper($from).' → '.strtoupper($target).' …');

                $translatedTitle = $this->translateNullable(
                    $source->title,
                    $from,
                    $target,
                    $translator,
                );

                $payload[$target] = [
                    'title' => $translatedTitle,
                    'excerpt' => $this->translateNullable(
                        $source->excerpt,
                        $from,
                        $target,
                        $translator,
                    ),
                    'content_json' => $this->translateContent(
                        $source->content_json,
                        $from,
                        $target,
                        $translator,
                    ),
                    'seo_title' => $this->translateNullable(
                        $source->seo_title,
                        $from,
                        $target,
                        $translator,
                    ),
                    'seo_description' => $this->translateNullable(
                        $source->seo_description,
                        $from,
                        $target,
                        $translator,
                    ),
                ];
            }

            if ($payload === []) {
                $this->info('Nichts zu ändern.');

                return self::SUCCESS;
            }

            $updated = $editorService->autosave(
                $article,
                $editor,
                (int) $article->lock_version,
                ['translations' => $payload],
            )->load('translations');

            foreach (array_keys($payload) as $locale) {
                $translation = $updated->translations->firstWhere('locale', $locale);

                if (! $translation) {
                    continue;
                }

                $url = rtrim((string) config('app.url', 'https://hnt.rocks'), '/')
                    .'/news/'.$locale.'/'.$translation->slug;

                $this->line(strtoupper($locale).': '.$url);
            }

            $this->info('News-Übersetzungen gespeichert. Revision/Lock-Version wurden sauber aktualisiert.');

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Übersetzung fehlgeschlagen: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    private function translateNullable(
        ?string $value,
        string $from,
        string $to,
        FeedTranslationService $translator,
    ): ?string {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        return $this->translateCached($value, $from, $to, $translator);
    }

    private function translateContent(
        mixed $value,
        string $from,
        string $to,
        FeedTranslationService $translator,
        ?string $parentKey = null,
    ): mixed {
        if (is_array($value)) {
            if ($parentKey === 'items') {
                return array_map(
                    fn ($item) => is_string($item)
                        ? $this->translateCached($item, $from, $to, $translator)
                        : $this->translateContent($item, $from, $to, $translator),
                    $value,
                );
            }

            $translated = [];

            foreach ($value as $key => $item) {
                $translated[$key] = $this->translateContent(
                    $item,
                    $from,
                    $to,
                    $translator,
                    is_string($key) ? $key : null,
                );
            }

            return $translated;
        }

        if (is_string($value) && $parentKey !== null && in_array($parentKey, self::TEXT_KEYS, true)) {
            $trimmed = trim($value);

            if ($trimmed === '') {
                return $value;
            }

            return $this->translateCached($value, $from, $to, $translator);
        }

        return $value;
    }

    private function translateCached(
        string $text,
        string $from,
        string $to,
        FeedTranslationService $translator,
    ): string {
        $cacheKey = $from.'|'.$to.'|'.$text;

        if (array_key_exists($cacheKey, $this->translationCache)) {
            return $this->translationCache[$cacheKey];
        }

        return $this->translationCache[$cacheKey] = $translator->translateText($text, $from, $to);
    }
}

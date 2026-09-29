<?php

namespace App\Console\Commands;

use App\Models\NewsArticle;
use App\Models\NewsArticleTranslation;
use App\Models\User;
use App\Services\Translation\NewsArticleTranslationService;
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

    public function handle(NewsArticleTranslationService $translationService): int
    {
        $slug = trim((string) $this->argument('slug'));
        $from = strtolower(trim((string) $this->option('from')));
        $targets = collect(explode(',', (string) $this->option('to')))
            ->map(fn ($locale) => strtolower(trim((string) $locale)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $source = NewsArticleTranslation::query()
            ->where('locale', $from)
            ->where('slug', $slug)
            ->first();

        if (! $source) {
            $this->error("News nicht gefunden: {$from}/{$slug}");

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

        try {
            $result = $translationService->translateArticle(
                $article,
                $editor,
                (int) $article->lock_version,
                $from,
                $targets,
                (bool) $this->option('force'),
            );

            foreach ($result['skipped_locales'] as $locale) {
                $this->warn(strtoupper($locale).': bereits Inhalt vorhanden – übersprungen.');
            }

            foreach ($result['translated_locales'] as $locale) {
                $translation = $result['article']->translations->firstWhere('locale', $locale);

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
}

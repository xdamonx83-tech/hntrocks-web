<?php

namespace App\Http\Controllers\Next;

use App\Http\Controllers\Controller;
use App\Http\Controllers\News\NewsPublicController;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class NextNewsController extends Controller
{
    private const LOCALES = ['de', 'en', 'es', 'ru'];

    /** Public design preview; the canonical news URLs remain unchanged. */
    public function overview(Request $request, ?string $locale = null): View
    {
        $preview = $request->is('next/news*');
        $locale = $this->locale($request, $locale);
        // News routes bypass NextLocaleMiddleware: load the HNTv3 shell translations explicitly.
        app('translation.loader')->addPath(base_path('lang'));
        app()->setLocale($locale);
        $request->merge([
            'locale' => $locale,
            'per_page' => 24,
        ]);
        $raw = app(NewsPublicController::class)->apiIndex($request)->getData(true);
        $articles = collect($raw['data'] ?? [])->filter(fn ($a) => is_array($a) && ! empty($a['url']))->values();
        $articles = $articles->map(fn ($a) => $this->displayArticle($a, $preview));
        $featured = $articles->firstWhere('featured', true) ?: $articles->first();
        $briefs = $articles->where('id', '!=', $featured['id'] ?? 0)->take(3)->values();
        $tags = collect($raw['facets']['tags'] ?? [])->filter(fn ($tag) => is_string($tag))->take(12)->values();
        $categories = collect($raw['facets']['categories'] ?? [])->filter(fn ($c) => is_string($c))->values();
        $canonical = route('news.overview.locale', ['locale' => $locale]);
        $labels = $this->labels($locale);
        $title = $labels['title'].' | HNT.ROCKS';
        $description = $labels['description'];

        return view('next.news-public', [
            'mode' => 'overview', 'preview' => $preview, 'viewer' => auth()->user(),
            'locale' => $locale, 'labels' => $labels,
            'pageTitle' => $title, 'pageDescription' => $description,
            'canonical' => $canonical, 'alternates' => $this->overviewAlternates(),
            'image' => asset('assets/socialite/images/seo/hnt-og-default.png'),
            'schema' => [
                '@context' => 'https://schema.org', '@type' => 'CollectionPage',
                '@id' => $canonical.'#news', 'url' => $canonical, 'name' => $title,
                'inLanguage' => $locale, 'description' => $description,
                'mainEntity' => ['@type'=>'ItemList', 'itemListElement'=>$articles->map(
                    fn ($a,$i) => ['@type'=>'ListItem','position'=>$i+1,'url'=>$a['canonical_url'],'name'=>$a['title']]
                )->all()],
            ],
            'articles' => $articles, 'featured' => $featured, 'briefs' => $briefs,
            'tags' => $tags, 'categories' => $categories,
            'meta' => $raw['meta'] ?? [],
        ]);
    }

    public function article(Request $request, string $locale, string $slug): View
    {
        abort_unless(in_array($locale, self::LOCALES, true), 404);
        $preview = $request->is('next/news*');
        // News routes bypass NextLocaleMiddleware: load the HNTv3 shell translations explicitly.
        app('translation.loader')->addPath(base_path('lang'));
        app()->setLocale($locale);
        $raw = app(NewsPublicController::class)->apiShow($locale, $slug)->getData(true);
        $article = $this->displayArticle($raw['data'] ?? [], $preview);
        abort_unless(! empty($article['id']), 404);
        $related = collect($raw['related'] ?? [])->map(fn ($a) => $this->displayArticle($a, $preview))->take(4)->values();
        $labels = $this->labels($locale);
        $title = $article['title'];
        $description = $article['excerpt'] ?: $labels['description'];
        $canonical = $article['canonical_url'];
        $image = $article['hero_media']['url'] ?? asset('assets/socialite/images/seo/hnt-og-default.png');
        $words = str_word_count(strip_tags(json_encode($article['content_json'] ?? [], JSON_UNESCAPED_UNICODE) ?: ''));
        $minutes = max(1, (int) ceil($words/200));
        $alternates = $raw['alternates'] ?? [];
        $schema = [
            '@context' => 'https://schema.org', '@type' => 'NewsArticle',
            '@id' => $canonical.'#article', 'mainEntityOfPage' => $canonical,
            'headline' => $title, 'description' => $description,
            'image' => [$image], 'url' => $canonical,
            'datePublished' => $article['published_at'], 'dateModified' => $article['updated_at'] ?? $article['published_at'],
            'inLanguage' => $locale,
            'publisher' => ['@type'=>'Organization','name'=>'HNT.ROCKS','url'=>'https://hnt.rocks/'],
            'author' => ['@type'=>'Organization','name'=>'HNT.ROCKS'],
        ];

        return view('next.news-public', [
            'mode' => 'article', 'preview' => $preview, 'viewer' => auth()->user(),
            'locale' => $locale, 'labels' => $labels,
            'pageTitle' => $title.' | HNT.ROCKS', 'pageDescription' => $description,
            'canonical' => $canonical, 'alternates' => $alternates,
            'image' => $image, 'schema' => $schema,
            'article' => $article, 'related' => $related, 'readingMinutes' => $minutes,
        ]);
    }

    private function displayArticle(array $article, bool $preview): array
    {
        $article['canonical_url'] = $article['url'] ?? '';
        if ($preview && ! empty($article['slug']) && ! empty($article['locale'])) {
            $article['url'] = url('/next/news/'.$article['locale'].'/'.$article['slug']);
        }
        $article['excerpt'] = Str::limit(trim(strip_tags((string)($article['excerpt'] ?? ''))), 290);
        $article['read_minutes'] = max(1, (int)ceil(Str::wordCount(
            ($article['excerpt'] ?? '').' '.json_encode($article['content_json'] ?? [])
        ) / 200));
        return $article;
    }

    private function overviewAlternates(): array
    {
        return collect(self::LOCALES)->mapWithKeys(
            fn ($l) => [$l => route('news.overview.locale', ['locale' => $l])]
        )->all();
    }

    private function locale(Request $request, ?string $explicit): string
    {
        if (in_array($explicit, self::LOCALES, true)) return $explicit;
        $session = $request->session()->get('hnt_next_locale');
        if ($request->is('next/news*') && in_array($session, self::LOCALES, true)) return $session;
        foreach (['locale', 'hnt-locale', 'hnt-next-locale'] as $cookie) {
            $choice = $cookie === 'locale' ? $request->query('locale') : $request->cookie($cookie);
            if (in_array($choice, self::LOCALES, true)) return $choice;
        }
        foreach (explode(',', (string) $request->header('Accept-Language', '')) as $part) {
            $candidate = explode('-', strtolower(trim(explode(';', $part)[0])), 2)[0];
            if (in_array($candidate, self::LOCALES, true)) return $candidate;
        }
        return 'de';
    }

    private function labels(string $locale): array
    {
        return [
            'de' => [
                'title'=>'News','description'=>'Updates, Hintergründe und Geschichten direkt aus dem Bayou.',
                'recent'=>'Aktuelle Meldungen','featured'=>'EMPFOHLENE STORY','read'=>'Artikel lesen',
                'archive'=>'Alle News','archiveIntro'=>'Weitere Berichte, Patchnotes und Community-News.',
                'trending'=>'Im Gespräch','redaction'=>'HNT.ROCKS Redaktion','catAll'=>'Alle News',
                'search'=>'News durchsuchen','empty'=>'Keine Artikel für diesen Filter gefunden.',
                'back'=>'Zur News-Übersicht','reading'=>'Lesezeit','minutes'=>'Min.',
                'article'=>'ARTIKEL','related'=>'Weitere News','share'=>'Link teilen',
                'comments'=>'Kommentare','login'=>'Zum Kommentieren anmelden',
            ],
            'en'=>[
                'title'=>'News','description'=>'Updates, background reports and stories straight from the Bayou.',
                'recent'=>'Latest stories','featured'=>'EDITOR’S PICK','read'=>'Read article',
                'archive'=>'All news','archiveIntro'=>'More updates, patch notes and community news.',
                'trending'=>'Trending','redaction'=>'HNT.ROCKS Editorial','catAll'=>'All news',
                'search'=>'Search news','empty'=>'No articles match your filters.',
                'back'=>'Back to news','reading'=>'Reading time','minutes'=>'min.',
                'article'=>'ARTICLE','related'=>'More news','share'=>'Share link',
                'comments'=>'Comments','login'=>'Sign in to comment',
            ],
            'es'=>[
                'title'=>'Noticias','description'=>'Actualizaciones, análisis e historias del Bayou.',
                'recent'=>'Últimas noticias','featured'=>'DESTACADO','read'=>'Leer artículo',
                'archive'=>'Todas las noticias','archiveIntro'=>'Más novedades y noticias de la comunidad.',
                'trending'=>'Tendencias','redaction'=>'Redacción HNT.ROCKS','catAll'=>'Todas',
                'search'=>'Buscar noticias','empty'=>'No hay noticias para estos filtros.',
                'back'=>'Volver a noticias','reading'=>'Tiempo de lectura','minutes'=>'min.',
                'article'=>'ARTÍCULO','related'=>'Más noticias','share'=>'Compartir',
                'comments'=>'Comentarios','login'=>'Inicia sesión para comentar',
            ],
            'ru'=>[
                'title'=>'Новости','description'=>'Обновления и истории прямо из Байу.',
                'recent'=>'Последние новости','featured'=>'ВЫБОР РЕДАКЦИИ','read'=>'Читать статью',
                'archive'=>'Все новости','archiveIntro'=>'Патчноуты и новости сообщества.',
                'trending'=>'Популярные темы','redaction'=>'Редакция HNT.ROCKS','catAll'=>'Все',
                'search'=>'Поиск новостей','empty'=>'Нет новостей по этому запросу.',
                'back'=>'К новостям','reading'=>'Время чтения','minutes'=>'мин.',
                'article'=>'СТАТЬЯ','related'=>'Другие новости','share'=>'Поделиться',
                'comments'=>'Комментарии','login'=>'Войти, чтобы комментировать',
            ],
        ][$locale];
    }
}

<?php

namespace App\Http\Controllers\React;

use App\Http\Controllers\Controller;
use App\Models\Moment;
use App\Models\Cup;
use App\Models\LoadoutChallenge;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ReactAppController extends Controller
{
    public function __invoke(): Response
    {
        $reactIndex = public_path('app/index.html');

        abort_unless(
            File::isFile($reactIndex),
            503,
            'The React application bundle is unavailable.',
        );

        // Preserve the actual SPA bundle and its behaviour. Only supply
        // request-specific <head> information for crawlable public permalinks.
        $html = $this->withPublicPageMetadata(File::get($reactIndex));

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    private function withPublicPageMetadata(string $html): string
    {
        $route = request()->route();
        // The challenges listing is a public SEO landing page. It uses the
        // exact same React application markup as before.
        if (request()->is('loadout-challenges')) {
            return $this->replaceHead(
                $html,
                'Hunt: Showdown Loadout Challenges | HNT.ROCKS',
                'Hunt: Showdown Loadout-Challenges entdecken, Regeln ansehen und eigene Runs einreichen. Die Community-Challenges auf HNT.ROCKS.',
                true
            );
        }

        $challenge = $route?->parameter('challenge');
        if (is_string($challenge) && request()->is('loadout-challenges/*')) {
            // Implicit model binding does not resolve controller parameters that
            // are absent from __invoke(); look up public SPA permalinks here.
            $challenge = LoadoutChallenge::query()->where('slug', $challenge)->firstOrFail();
        }
        if ($challenge instanceof LoadoutChallenge && request()->is('loadout-challenges/*')) {
            if ($challenge->status !== LoadoutChallenge::STATUS_ACTIVE) {
                return $this->replaceHead($html, 'Loadout Challenge | HNT.ROCKS',
                    'Diese Challenge ist nicht öffentlich verfügbar.', false);
            }

            $title = $this->plainText((string) $challenge->title, 100);
            $description = $this->plainText((string) ($challenge->summary
                ?: $challenge->description ?: 'Hunt: Showdown Loadout-Challenge der HNT.ROCKS Community.'), 160);
            $url = url()->current();
            return $this->replaceHead($html, $title.' – Loadout Challenge | HNT.ROCKS',
                $description, true, 'article', [
                    '@context' => 'https://schema.org',
                    '@type' => 'CreativeWork',
                    '@id' => $url.'#challenge',
                    'url' => $url,
                    'name' => $title,
                    'description' => $description,
                    'about' => ['@type' => 'VideoGame', 'name' => 'Hunt: Showdown'],
                ]);
        }

        $cup = $route?->parameter('cup');
        if (is_string($cup) && request()->is('cups/*')) {
            $cup = Cup::query()->where('slug', $cup)->firstOrFail();
        }
        if ($cup instanceof Cup && request()->is('cups/*')) {
            if ($cup->visibility !== 'public' ||
                ! in_array($cup->status, ['planned', 'active', 'finished', 'archived'], true)) {
                return $this->replaceHead($html, 'Cup | HNT.ROCKS',
                    'Dieser Cup ist nicht öffentlich verfügbar.', false);
            }

            $title = $this->plainText((string) $cup->title, 100);
            $description = $this->plainText((string) ($cup->summary
                ?: 'Hunt: Showdown Community-Cup auf HNT.ROCKS. Regeln, Ergebnisse und Teilnehmer entdecken.'), 160);
            $url = url()->current();
            return $this->replaceHead($html, $title.' – Hunt: Showdown Cup | HNT.ROCKS',
                $description, true, 'article', [
                    '@context' => 'https://schema.org',
                    '@type' => 'CreativeWork',
                    '@id' => $url.'#cup',
                    'url' => $url,
                    'name' => $title,
                    'description' => $description,
                    'about' => ['@type' => 'VideoGame', 'name' => 'Hunt: Showdown'],
                ]);
        }

        $profile = $route?->parameter('user');
        if (is_string($profile) && request()->is('u/*')) {
            $profile = User::query()->where('username', $profile)->firstOrFail();
        }

        if ($profile instanceof User && request()->is('u/*')) {
            $profile->loadMissing('profile');
            $isPublic = $profile->status === 'active'
                && $profile->profile?->profile_visibility === 'public';

            if (! $isPublic) {
                return $this->replaceHead($html, 'Hunter-Profil | HNT.ROCKS',
                    'Dieses Hunter-Profil ist nicht öffentlich sichtbar.', false);
            }

            $name = $this->plainText((string) ($profile->name ?: $profile->username), 75);
            $headline = $this->plainText((string) ($profile->profile?->headline
                ?: $profile->profile?->bio ?: ''), 145);
            $title = Str::limit($name.' (@'.$profile->username.') – Hunt: Showdown | HNT.ROCKS', 115, '');
            $description = $headline !== ''
                ? $headline
                : $name.' – öffentliches Hunter-Profil mit Hunt: Showdown Aktivitäten auf HNT.ROCKS.';
            $url = url()->current();

            return $this->replaceHead($html, $title, $description, true, 'profile', [
                '@context' => 'https://schema.org',
                '@type' => 'ProfilePage',
                '@id' => $url.'#profile',
                'url' => $url,
                'mainEntity' => [
                    '@type' => 'Person',
                    'name' => $name,
                    'alternateName' => (string) $profile->username,
                    'url' => $url,
                ],
            ]);
        }

        $moment = $route?->parameter('moment');

        if ($moment instanceof Moment && request()->is('moments/r/*')) {
            $isPublic = $moment->visibility === 'public' && $moment->isPublishedNow();

            if (! $isPublic) {
                // Registered/private/unpublished Moments must never leak captions
                // or other metadata into publicly readable HTML source.
                return $this->replaceHead($html, 'Moment | HNT.ROCKS',
                    'Dieser Moment ist nicht öffentlich verfügbar.', false);
            }

            $moment->loadMissing('user');
            $name = $this->plainText((string) ($moment->user?->name
                ?: $moment->user?->username ?: 'Hunter'), 70);
            $caption = $this->plainText((string) ($moment->caption
                ?: $moment->description ?: ''), 150);
            $title = $caption !== ''
                ? Str::limit($caption, 77, '').' | HNT.ROCKS Moments'
                : 'Hunt: Showdown Moment von '.$name.' | HNT.ROCKS';
            $description = $caption !== ''
                ? $caption
                : 'Ein öffentlicher Hunt: Showdown Moment von '.$name.' auf HNT.ROCKS.';
            $url = url()->current();
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'SocialMediaPosting',
                '@id' => $url.'#post',
                'url' => $url,
                'headline' => $title,
                'description' => $description,
                'author' => [
                    '@type' => 'Person',
                    'name' => $name,
                ],
            ];

            if ($moment->published_at) {
                $schema['datePublished'] = $moment->published_at->toIso8601String();
            }

            return $this->replaceHead($html, $title, $description, true, 'article', $schema);
        }

        return $html;
    }

    private function plainText(string $text, int $length): string
    {
        $text = trim(preg_replace('/\s+/u', ' ', strip_tags($text)) ?? '');

        return Str::limit($text, $length, '');
    }

    /**
     * Add metadata only to the original React document head: no DOM changes,
     * scripts, application assets, client routing or interactive content.
     */
    private function replaceHead(
        string $html,
        string $title,
        string $description,
        bool $indexable,
        string $type = 'website',
        ?array $structuredData = null,
    ): string {
        $html = preg_replace('~<title\b[^>]*>.*?</title>~is', '', $html, 1) ?? $html;
        $html = preg_replace('~<meta\b(?=[^>]*\bname\s*=\s*["\']description["\'])[^>]*>~is', '', $html, 1) ?? $html;

        $canonical = url()->current();
        $meta = [
            '<title>'.e($title).'</title>',
            '<meta name="description" content="'.e($this->plainText($description, 160)).'">',
            '<meta name="robots" content="'.($indexable ? 'index,follow' : 'noindex,nofollow').'">',
            '<link rel="canonical" href="'.e($canonical).'">',
            '<meta property="og:site_name" content="HNT.ROCKS">',
            '<meta property="og:type" content="'.e($type).'">',
            '<meta property="og:title" content="'.e($title).'">',
            '<meta property="og:description" content="'.e($this->plainText($description, 160)).'">',
            '<meta property="og:url" content="'.e($canonical).'">',
            '<meta name="twitter:card" content="summary">',
            '<meta name="twitter:title" content="'.e($title).'">',
            '<meta name="twitter:description" content="'.e($this->plainText($description, 160)).'">',
        ];

        if ($indexable && $structuredData !== null) {
            $meta[] = '<script type="application/ld+json">'
                .json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)
                .'</script>';
        }

        return str_replace('</head>', implode("\n    ", $meta)."\n  </head>", $html);
    }
}

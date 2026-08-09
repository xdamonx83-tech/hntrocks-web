<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;

class LegalPageController extends Controller
{
    private const PAGES = [
        'impressum' => [
            'path' => '/impressum',
            'view' => 'legal.impressum',
        ],
        'datenschutz' => [
            'path' => '/datenschutz',
            'view' => 'legal.datenschutz',
        ],
        'nutzungsbedingungen' => [
            'path' => '/nutzungsbedingungen',
            'view' => 'legal.nutzungsbedingungen',
        ],
        'netiquette' => [
            'path' => '/netiquette',
            'view' => 'legal.netiquette',
        ],
        'account-deletion' => [
            'path' => '/account-deletion',
            'view' => 'legal.account-deletion',
        ],
        'child-safety-standards' => [
            'path' => '/child-safety-standards',
            'view' => 'legal.child-safety',
        ],
    ];

    public function show(Request $request): Response
    {
        $slug = (string) $request->route('legalSlug');
        $page = $this->page($slug);
        $payload = $this->payload($slug, $page);

        $reactIndex = public_path('app/index.html');

        abort_unless(
            File::isFile($reactIndex),
            503,
            'The React application bundle is unavailable.',
        );

        $html = File::get($reactIndex);
        $canonical = url($page['path']);
        $title = $payload['title'].' | HNT.rocks';
        $description = $payload['summary'];

        $html = preg_replace(
            '~<html\s+lang="[^"]*"~i',
            '<html lang="de"',
            $html,
            1,
        ) ?? $html;

        $html = preg_replace(
            '~<title>.*?</title>~is',
            '<title>'.e($title).'</title>',
            $html,
            1,
        ) ?? $html;

        $html = preg_replace(
            '~<meta\s+name="description"[^>]*>~i',
            '',
            $html,
            1,
        ) ?? $html;

        $head = implode("\n", [
            '<meta name="description" content="'.e($description).'">',
            '<meta name="robots" content="index,follow">',
            '<link rel="canonical" href="'.e($canonical).'">',
            '<meta property="og:type" content="article">',
            '<meta property="og:title" content="'.e($title).'">',
            '<meta property="og:description" content="'.e($description).'">',
            '<meta property="og:url" content="'.e($canonical).'">',
        ]);

        $json = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT,
        );

        abort_unless($json !== false, 500, 'Legal page data could not be encoded.');

        $initialData = '<script id="hnt-legal-page-data" type="application/json">'.$json.'</script>';

        abort_unless(
            str_contains($html, '</head>'),
            503,
            'The React application head could not be prepared.',
        );

        $html = str_replace(
            '</head>',
            $head."\n".$initialData."\n</head>",
            $html,
        );

        $rootPattern = '~<div\s+id="root"\s*></div>~i';

        abort_unless(
            preg_match($rootPattern, $html) === 1,
            503,
            'The React application root could not be prepared.',
        );

        $fallback = sprintf(
            '<main data-hnt-legal-fallback><h1>%s</h1><p>%s</p><article>%s</article></main>',
            e($payload['title']),
            e($payload['summary']),
            $payload['html'],
        );

        $html = preg_replace(
            $rootPattern,
            '<div id="root">'.$fallback.'</div>',
            $html,
            1,
        ) ?? $html;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Language' => 'de',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    private function page(string $slug): array
    {
        abort_unless(isset(self::PAGES[$slug]), 404);

        return self::PAGES[$slug];
    }

    private function payload(string $slug, array $page): array
    {
        $rendered = view($page['view'])->render();

        $title = $this->extractText(
            $rendered,
            '~<header class="hh-legal-hero">.*?<h1>(.*?)</h1>~s',
            'Rechtliches',
        );

        $pretitle = $this->extractText(
            $rendered,
            '~<div class="hh-legal-hero-kicker">(.*?)</div>~s',
            'Rechtliches',
        );

        $summary = $this->extractText(
            $rendered,
            '~<header class="hh-legal-hero">.*?<h1>.*?</h1>\s*<p>(.*?)</p>~s',
            'Rechtliche Informationen zu HNT.rocks.',
        );

        $updated = $this->extractText(
            $rendered,
            '~<div class="hh-legal-meta">.*?<span>Stand:\s*(.*?)</span>~s',
            '',
        );

        $contentPattern = '~<div class="hh-legal-content">(.*?)</div>\s*</article>\s*<aside class="hh-legal-sidebar">~s';

        abort_unless(
            preg_match($contentPattern, $rendered, $contentMatch) === 1,
            500,
            'The legal page content could not be extracted.',
        );

        return [
            'slug' => $slug,
            'path' => $page['path'],
            'title' => $title,
            'pretitle' => $pretitle,
            'summary' => $summary,
            'updated' => $updated,
            'html' => trim($contentMatch[1]),
        ];
    }

    private function extractText(
        string $html,
        string $pattern,
        string $fallback,
    ): string {
        if (preg_match($pattern, $html, $match) !== 1) {
            return $fallback;
        }

        return trim(
            html_entity_decode(
                strip_tags($match[1]),
                ENT_QUOTES | ENT_HTML5,
                'UTF-8',
            ),
        );
    }
}

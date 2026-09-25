<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(Request $request): View|Response|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route('feed.index');
        }

        $reactIndex = public_path('app/index.html');
        if (File::isFile($reactIndex)) {
            return $this->reactLandingResponse($request, $reactIndex);
        }

        return view('themes.socialite.landing.index');
    }

    private function reactLandingResponse(Request $request, string $reactIndex): Response
    {
        $requestedLocale = strtolower((string) $request->header('X-HNT-Locale', ''));
        if (in_array($requestedLocale, ['de', 'en', 'es', 'ru'], true)) {
            app()->setLocale($requestedLocale);
        }

        $html = File::get($reactIndex);
        $locale = app()->getLocale();
        $title = __('ui.landing_meta_title');

        $head = view('react.landing-seo', [
            'mode' => 'head',
        ])->render();

        $fallback = view('react.landing-seo', [
            'mode' => 'fallback',
        ])->render();

        $html = preg_replace(
            '~<html\s+lang="[^"]*"~i',
            '<html lang="'.e($locale).'"',
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

        $html = preg_replace(
            '~<link\s+rel="canonical"[^>]*>~i',
            '',
            $html,
            1,
        ) ?? $html;

        abort_unless(
            str_contains($html, '</head>'),
            503,
            'The React application head could not be prepared.',
        );

        $html = str_replace('</head>', $head."\n</head>", $html);
        $rootPattern = '~<div\s+id="root"\s*></div>~i';

        abort_unless(
            preg_match($rootPattern, $html) === 1,
            503,
            'The React application root could not be prepared.',
        );

        $html = preg_replace(
            $rootPattern,
            '<div id="root">'.$fallback.'</div>',
            $html,
            1,
        ) ?? $html;

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=UTF-8',
            'Content-Language' => $locale,
            'Cache-Control' => 'no-cache, private',
        ]);
    }
}

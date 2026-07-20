<?php

namespace App\Http\Controllers\Presentation;

use App\Http\Controllers\Controller;
use App\Support\HntViewMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViewModeController extends Controller
{
    public function __invoke(Request $request, string $mode): RedirectResponse
    {
        abort_unless(in_array($mode, HntViewMode::validModes(), true), 404);

        $target = $this->safeReturnTarget($request);
        $cookie = cookie(
            (string) config('hnt_view.cookie_name', 'hnt_view_mode'),
            $mode,
            (int) config('hnt_view.cookie_minutes', 259200),
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        );

        return redirect()->to($target)->withCookie($cookie);
    }

    private function safeReturnTarget(Request $request): string
    {
        $requested = trim((string) $request->query('return', ''));
        if ($requested !== '' && str_starts_with($requested, '/') && ! str_starts_with($requested, '//')) {
            return $requested;
        }

        $referer = trim((string) $request->headers->get('referer', ''));
        if ($referer !== '') {
            $parts = parse_url($referer);
            $host = strtolower((string) ($parts['host'] ?? ''));
            if ($host !== '' && $host === strtolower($request->getHost())) {
                $path = (string) ($parts['path'] ?? '/');
                $query = isset($parts['query']) ? '?'.$parts['query'] : '';
                $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

                return $path.$query.$fragment;
            }
        }

        return route('feed.index');
    }
}

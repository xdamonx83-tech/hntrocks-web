<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;

class LocaleController extends Controller
{
    public function switch(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, ['de', 'en'], true), 404);

        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return back()
            ->with('status', __('ui.language_changed'))
            ->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365, null, null, $request->isSecure(), true, false, 'lax'));
    }
}

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

        return $this->withLocale($request, $locale, back());
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:de,en'],
            'theme_preference' => ['nullable', 'string', 'in:light,dark,system'],
        ]);

        $user = $request->user();
        $themePreference = $validated['theme_preference']
            ?? (in_array($user->theme_preference, ['light', 'dark', 'system'], true)
                ? $user->theme_preference
                : 'light');

        $user->theme_preference = $themePreference;
        $user->save();

        $request->session()->put('locale', $validated['locale']);
        App::setLocale($validated['locale']);

        return redirect()
            ->to(route('account.settings.edit').'#general')
            ->with('status', __('theme.general_saved'))
            ->withCookie(Cookie::make(
                'locale',
                $validated['locale'],
                60 * 24 * 365,
                null,
                null,
                $request->isSecure(),
                true,
                false,
                'lax'
            ))
            ->withCookie(Cookie::make(
                'hnt_theme_preference',
                $themePreference,
                60 * 24 * 365,
                null,
                null,
                $request->isSecure(),
                false,
                false,
                'lax'
            ));
    }

    private function withLocale(Request $request, string $locale, RedirectResponse $response): RedirectResponse
    {
        $request->session()->put('locale', $locale);
        App::setLocale($locale);

        return $response
            ->with('status', __('ui.language_changed'))
            ->withCookie(Cookie::make('locale', $locale, 60 * 24 * 365, null, null, $request->isSecure(), true, false, 'lax'));
    }
}

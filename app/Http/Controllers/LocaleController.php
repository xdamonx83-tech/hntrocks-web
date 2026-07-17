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
        ]);

        return $this->withLocale(
            $request,
            $validated['locale'],
            redirect()->to(route('account.settings.edit').'#general')
        );
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

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\HntTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminThemePreviewController extends Controller
{
    public function index(Request $request): View
    {
        $this->guardAdmin($request);

        $user = $request->user();
        $previewTheme = HntTheme::previewTheme();
        $fallbackTheme = HntTheme::previewFallbackTheme();

        return view('admin.theme-preview.index', [
            'previewTheme' => $previewTheme,
            'fallbackTheme' => $fallbackTheme,
            'activeTheme' => HntTheme::active(),
            'previewActive' => HntTheme::previewActive($user),
            'previewAvailable' => HntTheme::previewAvailableFor($user),
            'previewRestrictionConfigured' => HntTheme::previewRestrictionConfigured(),
            'allowedUserIds' => HntTheme::previewAllowedUserIds(),
            'allowedEmails' => HntTheme::previewAllowedEmails(),
            'allowAnyAdmin' => (bool) config('hunthub.theme.preview.allow_any_admin', false),
            'sessionKey' => HntTheme::PREVIEW_SESSION_KEY,
            'sampleResolutions' => $this->sampleResolutions(),
            'templateReferences' => $this->templateReferences(),
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);

        if (! HntTheme::previewAvailableFor($request->user())) {
            return back()->with('error', 'Theme-Preview ist für diesen Admin nicht freigeschaltet. Bitte HH_THEME_PREVIEW_USER_IDS oder HH_THEME_PREVIEW_USER_EMAILS setzen.');
        }

        HntTheme::activatePreview();

        return back()->with('status', 'Theme-Preview wurde für deine aktuelle Admin-Session aktiviert.');
    }

    public function stop(Request $request): RedirectResponse
    {
        $this->guardAdmin($request);
        HntTheme::deactivatePreview();

        return back()->with('status', 'Theme-Preview wurde für deine aktuelle Session beendet.');
    }

    public function shell(Request $request): View
    {
        $this->guardAdmin($request);

        abort_unless(HntTheme::previewActive($request->user()), 403);

        return view('themes.hnt_preview.preview.shell');
    }

    private function guardAdmin(Request $request): void
    {
        abort_unless($request->user()?->isAdmin(), 403);
    }

    private function sampleResolutions(): array
    {
        $samples = [
            'feed.live',
            'feed.show',
            'profile.show',
            'profile.edit',
            'members.index',
            'cups.index',
            'cups.show',
            'messages.index',
            'notifications.index',
            'gamification.index',
        ];

        return collect($samples)
            ->map(fn (string $view): array => [
                'view' => $view,
                'resolved' => HntTheme::resolve($view),
            ])
            ->all();
    }

    private function templateReferences(): array
    {
        return [
            'index.html' => 'Rework Feed Preview / globale Shell',
            'profile.html' => 'Profil',
            'profile-edit.html' => 'Profil bearbeiten',
            'members.html' => 'Mitglieder',
            'cups.html' => 'Cup-Übersicht',
            'cup-detail.html' => 'Cup-Detail',
            'cup-feedback.html' => 'Cup-Feedback',
            'gamification.html' => 'Badges / Gamification',
            'crowns.html' => 'Bounty Marks Wallet',
            'crowns-shop.html' => 'Marks Shop',
            'crowns-inventory.html' => 'Marks Inventar',
            'hall-of-fame.html' => 'Hall of Fame',
            'contracts.html' => 'HNT-Aufträge',
            'lfg.html' => 'LFG',
            'lfg-detail.html' => 'LFG Detail',
        ];
    }
}

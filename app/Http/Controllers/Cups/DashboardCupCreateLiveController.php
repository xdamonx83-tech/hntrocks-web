<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use App\Support\CupOrganizerAccess;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardCupCreateLiveController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user(), 401);

        if ($request->boolean('capabilities')) {
            $canUseAi = CupOrganizerAccess::canUseAi($request->user());

            return response()->json([
                'can_ai_review' => $canUseAi,
                'default_verification_mode' => CupOrganizerAccess::VERIFICATION_MANUAL,
                'labels' => [
                    'verification_method' => app()->getLocale() === 'en' ? 'Review method' : 'Prüfmethode',
                    'manual' => app()->getLocale() === 'en' ? 'Manual review' : 'Manuelle Prüfung',
                    'manual_help' => app()->getLocale() === 'en'
                        ? 'The cup organizer reviews screenshots and enters the score.'
                        : 'Der Cup-Ersteller prüft Screenshots und trägt die Wertung ein.',
                    'ai' => app()->getLocale() === 'en' ? 'AI review' : 'KI-Prüfung',
                    'ai_help' => app()->getLocale() === 'en'
                        ? 'Available only to the HNT.ROCKS AI manager.'
                        : 'Nur für den HNT.ROCKS-KI-Manager verfügbar.',
                ],
            ]);
        }

        return response()
            ->view('themes.hnt_preview.cups.create-live', [
                'cup' => new Cup(),
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

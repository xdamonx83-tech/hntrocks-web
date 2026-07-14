<?php

namespace App\Http\Controllers\Cups;

use App\Http\Controllers\Controller;
use App\Models\Cup;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardCupCreateLiveController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()?->isAdmin(), 403);

        return response()
            ->view('themes.hnt_preview.cups.create-live', [
                'cup' => new Cup(),
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

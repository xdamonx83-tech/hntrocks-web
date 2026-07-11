<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Profile\ProfileController;
use App\Http\Middleware\PreviewDashboardHeader;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

class ActivateProfileRedesign
{
    private const PROFILE_ROUTES = [
        'profile.show',
        'profile.about',
        'profile.friends',
        'profile.badges',
        'profile.trophies',
        'profile.teams',
        'profile.contact',
        'profile.public',
        'profile.about.public',
        'profile.friends.public',
        'profile.badges.public',
        'profile.trophies.public',
        'profile.teams.public',
        'profile.contact.public',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('hunthub.theme.profile_redesign_live', false)
            || ! $request->isMethod('GET')
            || ! $request->routeIs(...self::PROFILE_ROUTES)
            || $request->boolean('classic_profile')) {
            return $next($request);
        }

        $viewer = $request->user();

        if ($request->boolean('dashboard_header')) {
            abort_unless($viewer instanceof User, 401);

            $method = new ReflectionMethod(PreviewDashboardHeader::class, 'payload');
            $method->setAccessible(true);

            return response()->json([
                'header' => $method->invoke(app(PreviewDashboardHeader::class), $viewer),
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        }

        $profileUser = $request->route('user');
        $profileUser = $profileUser instanceof User ? $profileUser : null;

        /** @var View $source */
        $source = app(ProfileController::class)->show($request, $profileUser);

        return response()
            ->view('themes.hnt_preview.profile.live', $source->getData())
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

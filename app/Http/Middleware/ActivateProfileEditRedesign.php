<?php

namespace App\Http\Middleware;

use App\Http\Controllers\Profile\ProfileController;
use App\Models\Friendship;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\View\View;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;

class ActivateProfileEditRedesign
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET')
            && $request->routeIs('profile.edit')
            && $request->boolean('classic_profile_edit')) {
            $request->query->set('classic_profile', '1');

            return $next($request);
        }

        if (! (bool) config('hunthub.theme.profile_edit_redesign_live', false)
            || ! $request->isMethod('GET')
            || ! $request->routeIs('profile.edit')) {
            return $next($request);
        }

        $viewer = $request->user();
        abort_unless($viewer instanceof User, 401);

        if ($request->boolean('dashboard_header')) {
            $method = new ReflectionMethod(PreviewDashboardHeader::class, 'payload');
            $method->setAccessible(true);

            return response()->json([
                'header' => $method->invoke(app(PreviewDashboardHeader::class), $viewer),
            ])->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0');
        }

        /** @var View $source */
        $source = app(ProfileController::class)->edit($request);
        $user = $source->getData()['user'];
        $user->loadMissing(['profile', 'crownWallet']);
        $user->loadCount([
            'feedPosts as profile_edit_posts_count' => fn ($query) => $query->where('status', 'published'),
            'moments as profile_edit_moments_count' => fn ($query) => $query->where('status', 'published'),
        ]);

        $friendsCount = Friendship::query()
            ->forUser($user)
            ->where('status', Friendship::STATUS_ACCEPTED)
            ->count();

        return response()
            ->view('themes.hnt_preview.profile.edit-live', [
                ...$source->getData(),
                'profileEditFriendsCount' => $friendsCount,
            ])
            ->header('Cache-Control', 'private, no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }
}

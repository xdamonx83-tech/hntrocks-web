<?php

use App\Http\Controllers\Next\NextFeedController;
use App\Http\Controllers\Next\NextMembersController;
use App\Http\Controllers\Next\NextMessengerController;
use App\Http\Controllers\Next\NextSavedController;
use App\Http\Controllers\Next\NextCupsController;
use App\Http\Controllers\Next\NextHallOfFameController;
use App\Http\Controllers\Next\NextLoadoutGeneratorController;
use App\Http\Controllers\Next\NextRocksController;
use App\Http\Controllers\Next\NextShopController;
use App\Http\Controllers\Next\NextRocksStreakController;
use App\Http\Controllers\Next\NextCupDetailController;
use App\Http\Controllers\Next\NextPrestigeController;
use App\Http\Controllers\Next\NextTopbarController;
use App\Http\Controllers\Next\NextOnlineController;
use App\Http\Controllers\Next\NextMomentsController;
use App\Http\Controllers\Next\NextProfileController;
use App\Http\Controllers\Next\NextXboxConnectionController;
use App\Http\Controllers\Next\NextXboxDirectProbeController;
use App\Http\Controllers\Next\NextMapsController;
use App\Http\Controllers\Next\NextMapDetailController;
use App\Http\Controllers\Next\NextLocaleMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Next\NextSettingsChildController;

// Editorial News workspace: session-authenticated and admin-only, no search indexing.
Route::middleware(['auth', \App\Http\Controllers\Next\NextLocaleMiddleware::class])->group(function (): void {
    Route::get('/next/news/editor', \App\Http\Controllers\Next\NextNewsEditorController::class)
        ->name('next.news.editor');
    Route::get('/next/news/editor/{articleId}', \App\Http\Controllers\Next\NextNewsEditorController::class)
        ->whereNumber('articleId')->name('next.news.editor.edit');
});

// HNTv3 News: public, read-only SEO preview; production News URLs stay unchanged.
Route::get('/next/news', [\App\Http\Controllers\Next\NextNewsController::class, 'overview'])->name('next.news.hntv3.overview');
Route::get('/next/news/{locale}', [\App\Http\Controllers\Next\NextNewsController::class, 'overview'])
    ->where('locale', 'de|en|es|ru')->name('next.news.hntv3.locale');
Route::get('/next/news/{locale}/{slug}', [\App\Http\Controllers\Next\NextNewsController::class, 'article'])
    ->where(['locale' => 'de|en|es|ru', 'slug' => '[a-z0-9-]+'])->name('next.news.hntv3.article');

// Web-session News engagement preserves the existing backend interactions (CSRF + login required).
Route::middleware(['auth'])->group(function (): void {
    Route::get('/news/{article}/engagement/viewer', [\App\Http\Controllers\News\NewsEngagementController::class, 'viewer'])
        ->whereNumber('article')->name('next.news.engagement.viewer');
    Route::post('/news/{article}/like', [\App\Http\Controllers\News\NewsEngagementController::class, 'toggleLike'])
        ->whereNumber('article')->middleware('throttle:30,1')->name('next.news.engagement.like');
    Route::post('/news/{article}/save', [\App\Http\Controllers\News\NewsEngagementController::class, 'toggleSave'])
        ->whereNumber('article')->middleware('throttle:30,1')->name('next.news.engagement.save');
    Route::post('/news/{article}/comments', [\App\Http\Controllers\News\NewsEngagementController::class, 'store'])
        ->whereNumber('article')->middleware('throttle:12,1')->name('next.news.comments.store');
});

// Public HNTv3 landing preview. Keep the production / route unchanged until cutover.
Route::get('/next/landing', \App\Http\Controllers\Next\NextLandingController::class)->name('next.landing');


// Preview: exact HNTv3 auth templates, wired to Laravel without replacing current public pages.
// GET previews remain readable when the owner is already signed in.
Route::get('/next/auth/{page}', \App\Http\Controllers\Next\NextAuthPage::class)
    ->whereIn('page', ['welcome','login','register','forgot','reset','twofa'])
    ->name('next.auth.page');

Route::middleware('guest')->group(function (): void {
    Route::post('/next/auth/login', function (\Illuminate\Http\Request $request,
        \App\Services\SecurityLogService $log): \Illuminate\Http\RedirectResponse {
        $response = app()->call(
            [app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class), 'store'],
            ['request' => $request, 'securityLog' => $log]
        );
        // Preserve the existing authentication and 2FA checks, change only where the browser lands.
        if (str_contains($response->getTargetUrl(), '/login/2fa')) {
            $response->setTargetUrl(route('login.two-factor'));
        } elseif (auth()->check()) {
            // Old saved intended URLs may point into the legacy React
            // dashboard. After successful first-party login always enter
            // the active production HNTv3 feed, never the old theme.
            $response->setTargetUrl(route('feed.index'));
        }
        return $response;
    })->middleware('throttle:10,1')->name('next.auth.login.submit');

    Route::post('/next/auth/register', function (\Illuminate\Http\Request $request,
        \App\Services\GamificationService $gamification,
        \App\Services\ReferralService $referrals): \Illuminate\Http\RedirectResponse {
        $request->validate(['name' => ['required','string','max:80']]);
        $response = app()->call(
            [app(\App\Http\Controllers\Auth\RegisteredUserController::class), 'store'],
            ['request' => $request, 'gamification' => $gamification, 'referrals' => $referrals]
        );
        if (auth()->check()) {
            // Display name field exists in the original template, but not in the legacy form.
            $request->user()->forceFill(['name' => trim((string) $request->input('name'))])->save();
            $response->setTargetUrl(route('feed.index'));
        } else {
            $response->setTargetUrl(route('next.auth.page', ['page'=>'register']));
        }
        return $response;
    })->middleware('throttle:5,1')->name('next.auth.register.submit');

    Route::post('/next/auth/twofa', function (\Illuminate\Http\Request $request,
        \App\Services\Auth\TwoFactorService $twoFactor,
        \App\Services\SecurityLogService $log): \Illuminate\Http\RedirectResponse {
        $response = app()->call(
            [app(\App\Http\Controllers\Auth\AuthenticatedSessionController::class), 'confirmTwoFactor'],
            ['request' => $request, 'twoFactor' => $twoFactor, 'securityLog' => $log]
        );
        if (auth()->check()) $response->setTargetUrl(route('feed.index'));
        else if ($response->getTargetUrl() === route('login')) $response->setTargetUrl(route('next.auth.page', ['page'=>'login']));
        return $response;
    })->middleware('throttle:12,1')->name('next.auth.twofa.submit');
});

// HNTv3 maps: public read-only overview/detail, same approved-marker data
// as existing public /maps. Mutations retain their own auth/CSRF/throttles.
Route::middleware(NextLocaleMiddleware::class)->group(function (): void {
    // These public preview URLs now permanently redirect to the only
    // indexable canonical maps; preserve legacy route names for old links.
    Route::get('/next/maps', fn () => redirect()->route('maps.index', [], 301))->name('next.maps');
    Route::get('/next/maps/{slug}', fn (string $slug) => redirect()->route('maps.show', $slug, 301))
        ->where('slug', '[a-z0-9-]+')->name('next.maps.show');
});

Route::middleware(['auth', NextLocaleMiddleware::class])->group(function (): void {
    Route::redirect('/next', '/next/feed')->name('next.index');
    Route::get('/next/settings-child/{section}', NextSettingsChildController::class)->whereIn('section', ['password','twofactor','blocks'])->name('next.settings.child');
    Route::get('/next/topbar', [NextTopbarController::class, 'show'])->name('next.topbar');
    Route::get('/next/online', NextOnlineController::class)->name('next.online');
    Route::post('/next/language/{locale}', function (Request $request, string $locale) {
        abort_unless(in_array($locale, ['de','en','es','ru'], true), 404);
        $request->session()->put('hnt_next_locale', $locale);
        return response()->json(['ok'=>true, 'locale'=>$locale]);
    })->name('next.language');
    Route::get('/next/feed', NextFeedController::class)->name('next.feed');
    Route::get('/next/members', NextMembersController::class)->name('next.members');
    Route::get('/next/feed/latest', [NextFeedController::class, 'latest'])->middleware('throttle:15,1')->name('next.feed.latest');
    // HNTv3 Messenger: exact messenger.html workspace, production conversations.
    Route::get('/next/messages', [NextMessengerController::class, 'index'])->name('next.messages');
    Route::get('/next/messages/data', [NextMessengerController::class, 'list'])->name('next.messages.data');
    Route::get('/next/messages/users', [NextMessengerController::class, 'users'])->name('next.messages.users');
    Route::get('/next/messages/conversations/{conversation}', [NextMessengerController::class, 'messages'])
        ->whereNumber('conversation')->name('next.messages.conversation');
    Route::get('/next/messages/conversations/{conversation}/sync', [NextMessengerController::class, 'sync'])
        ->middleware('throttle:45,1')->whereNumber('conversation')->name('next.messages.sync');
    Route::post('/next/messages/conversations/{conversation}/typing', [NextMessengerController::class, 'typing'])
        ->middleware('throttle:25,1')->whereNumber('conversation')->name('next.messages.typing');
    Route::patch('/next/messages/conversations/{conversation}/mute', [NextMessengerController::class, 'mute'])
        ->middleware('throttle:20,1')->whereNumber('conversation')->name('next.messages.mute');
    Route::post('/next/messages/conversations/{conversation}/send', [NextMessengerController::class, 'send'])
        ->middleware('throttle:120,1')->whereNumber('conversation')->name('next.messages.send');
    Route::patch('/next/messages/conversations/{conversation}/messages/{message}', [NextMessengerController::class, 'edit'])
        ->middleware('throttle:25,1')->whereNumber('conversation')->whereNumber('message')->name('next.messages.edit');
    Route::delete('/next/messages/conversations/{conversation}/messages/{message}', [NextMessengerController::class, 'delete'])
        ->middleware('throttle:25,1')->whereNumber('conversation')->whereNumber('message')->name('next.messages.delete');
    Route::post('/next/messages/with/{user}', [NextMessengerController::class, 'withUser'])
        ->middleware('throttle:15,1')->whereNumber('user')->name('next.messages.with-user');
    Route::get('/next/moments', [NextMomentsController::class, 'index'])->name('next.moments');
    Route::get('/next/moments/create', [NextMomentsController::class, 'create'])->name('next.moments.create');
    Route::get('/next/moments/feed', [NextMomentsController::class, 'feedPage'])->name('next.moments.feed');
    Route::get('/next/moments/{moment}/comments', [NextMomentsController::class, 'comments'])->name('next.moments.comments');
    Route::post('/next/moments/{moment}/like', [NextMomentsController::class, 'like'])->middleware('throttle:40,1')->name('next.moments.like');
    Route::post('/next/moments/{moment}/bookmark', [NextMomentsController::class, 'bookmark'])->middleware('throttle:40,1')->name('next.moments.bookmark');
    Route::post('/next/moments/{moment}/comments', [NextMomentsController::class, 'commentStore'])->middleware('throttle:18,1')->name('next.moments.comment.store');
    Route::post('/next/moments/comments/{comment}/like', [NextMomentsController::class, 'commentLike'])->middleware('throttle:40,1')->name('next.moments.comment.like');
    Route::patch('/next/moments/comments/{comment}', [NextMomentsController::class, 'commentUpdate'])->middleware('throttle:40,1')->name('next.moments.comment.update');
    Route::delete('/next/moments/comments/{comment}', [NextMomentsController::class, 'commentDelete'])->middleware('throttle:40,1')->name('next.moments.comment.delete');

    // Original HNTv3 Ready-Lobbies HTML/CSS preview. Canonical React page
    // stays available until actual member create/join/leave QA is complete.
    Route::get('/next/ready-lobbies', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'index'])->name('next.ready.index');
    Route::post('/next/ready-lobbies', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'store'])->middleware('throttle:5,1')->name('next.ready.store');
    Route::get('/next/ready-lobbies/{lobby}', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'show'])->name('next.ready.detail');
    Route::post('/next/ready-lobbies/{lobby}/join', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'join'])->middleware('throttle:10,1')->name('next.ready.join');
    Route::post('/next/ready-lobbies/{lobby}/leave', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'leave'])->middleware('throttle:10,1')->name('next.ready.leave');
    Route::post('/next/ready-lobbies/{lobby}/close', [\App\Http\Controllers\Next\NextReadyLobbyController::class,'close'])->middleware('throttle:5,1')->name('next.ready.close');
    Route::get('/next/cups', NextCupsController::class)->name('next.cups');
    Route::get('/next/hall-of-fame', NextHallOfFameController::class)->name('next.hall-of-fame');
    Route::get('/next/loadout-generator', NextLoadoutGeneratorController::class)->name('next.loadout-generator');
    Route::get('/next/rocks', NextRocksController::class)->name('next.rocks');
    Route::get('/next/shop', [NextShopController::class, 'index'])->name('next.shop');
    Route::post('/next/shop/{item}/purchase', [NextShopController::class, 'purchase'])->middleware('throttle:20,1')->name('next.shop.purchase');
    Route::get('/next/rocks/streak', [NextRocksStreakController::class, 'show'])->name('next.rocks.streak');
    Route::post('/next/rocks/streak/claim', [NextRocksStreakController::class, 'claim'])->middleware('throttle:6,1')->name('next.rocks.streak.claim');

    Route::get('/next/profile', NextProfileController::class)->name('next.profile');
    // First-party HNTv3 web-session routes, reusing the already verified
    // OpenXBL controller; all writes require the normal Laravel CSRF token.
    Route::get('/next/xbox', [NextXboxConnectionController::class, 'status'])->name('next.xbox.status');
    // Owner-only opt-in Microsoft Xbox API probe. No account mutation or token storage.
    Route::get('/next/xbox/direct-probe', [NextXboxDirectProbeController::class, 'begin'])
        ->middleware('throttle:2,60')->name('next.xbox.direct.probe');
    Route::post('/next/xbox/start', [NextXboxConnectionController::class, 'start'])->middleware('throttle:5,1')->name('next.xbox.start');
    // Own Microsoft -> Xbox Live identity flow, separate from OpenXBL.
    Route::post('/next/xbox/direct/start', [\App\Http\Controllers\Api\V1\HuntGameAccountsController::class, 'startXbox'])
        ->middleware('throttle:5,1')->name('next.xbox.direct.start');
    Route::get('/next/xbox/pending', [NextXboxConnectionController::class, 'pending'])->middleware('throttle:20,1')->name('next.xbox.pending');
    Route::post('/next/xbox/confirm', [NextXboxConnectionController::class, 'confirm'])->middleware('throttle:5,1')->name('next.xbox.confirm');
    Route::post('/next/xbox/sync', [NextXboxConnectionController::class, 'sync'])->middleware('throttle:3,5')->name('next.xbox.sync');
    Route::delete('/next/xbox', [NextXboxConnectionController::class, 'disconnect'])->middleware('throttle:5,1')->name('next.xbox.disconnect');
    Route::get('/next/profile/{user:username}', NextProfileController::class)->name('next.profile.public');
    Route::get('/next/saved', NextSavedController::class)->name('next.saved');
    Route::get('/next/prestige', [NextPrestigeController::class, 'status'])->name('next.prestige.status');
    Route::post('/next/prestige', [NextPrestigeController::class, 'start'])->middleware('throttle:5,1')->name('next.prestige.start');
    Route::post('/next/prestige/equip', [NextPrestigeController::class, 'equip'])->middleware('throttle:10,1')->name('next.prestige.equip');
    Route::post('/next/feed', [NextFeedController::class, 'store'])->name('next.feed.store');
    Route::post('/next/feed/posts/{post}/media', [NextFeedController::class, 'attachMedia'])
        ->middleware('throttle:60,1')->name('next.feed.media');
    Route::get('/next/feed/posts/{post}/card', [NextFeedController::class, 'card'])->name('next.feed.card');
    Route::get('/next/feed/posts/{post}/poll/voters', [NextFeedController::class, 'pollVoters'])->name('next.feed.poll.voters');
});

// HNTv3 Cup Detail live on /cups/{slug}. This is deliberately registered
// after routes/web.php (which still contains the older root-owned React
// route). Do not duplicate the old SPA for signed-in visitors.
Route::get('/cups/{cup:slug}', \App\Http\Controllers\Next\CanonicalCupDetailController::class)
    ->middleware(NextLocaleMiddleware::class)
    ->name('cups.show');

// Preview URLs are historical. Redirect all visitors, including search bots,
// straight to the canonical URL, without going through auth/login.
Route::get('/next/cups/{cup:slug}', [\App\Http\Controllers\Next\CanonicalCupDetailController::class, 'redirectLegacy'])
    ->name('next.cups.detail');

// The root-owned web.php still contains the replaced SPA route. Refresh
// lookups so named URLs point to the active (latest) canonical route.
Route::getRoutes()->refreshNameLookups();

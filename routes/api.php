<?php

use App\Http\Controllers\Api\V1\ApiBootstrapController;
use App\Http\Controllers\Api\V1\ArsenalController;
use App\Http\Controllers\Api\V1\Arcade\ArcadeGameController;
use App\Http\Controllers\Api\V1\Arcade\ArcadeInvitationController;
use App\Http\Controllers\Api\V1\Arcade\ArcadeMatchController;
use App\Http\Controllers\Api\V1\Arcade\ArcadeBroadcastController;
use App\Http\Controllers\Api\V1\ApiCrownDailyStreakController;
use App\Http\Controllers\Api\V1\ApiCupChatController;
use App\Http\Controllers\Api\V1\ApiCupsController;
use App\Http\Controllers\Cups\CupController as CupManagementController;
use App\Http\Controllers\Api\V1\ApiCupRegistrationController;
use App\Http\Controllers\Api\V1\ApiCupTeamChatController;
use App\Http\Controllers\Api\V1\ApiCupTeamsController;
use App\Http\Controllers\Api\V1\ApiCrownsController;
use App\Http\Controllers\Api\V1\ApiCupFeedbackController;
use App\Http\Controllers\Api\V1\ApiContractsController;
use App\Http\Controllers\Api\V1\ApiCupIdeasController;
use App\Http\Controllers\Api\V1\ApiFeedController;
use App\Http\Controllers\Api\V1\ApiFeedEngagementController;
use App\Http\Controllers\Api\V1\ApiFeedbackTicketController;
use App\Http\Controllers\Api\V1\ApiGifController;
use App\Http\Controllers\Api\V1\ApiHallOfFameController;
use App\Http\Controllers\Api\V1\ApiHashtagController;
use App\Http\Controllers\Api\V1\ApiLfgController;
use App\Http\Controllers\Api\V1\ApiLiveLobbyController;
use App\Http\Controllers\Api\V1\ApiLiveLobbyFeedbackController;
use App\Http\Controllers\Api\V1\ApiLiveStreamController;
use App\Http\Controllers\Api\V1\ApiLoadoutChallengesController;
use App\Http\Controllers\Api\V1\ApiMapsController;
use App\Http\Controllers\Api\V1\ApiMembersController;
use App\Http\Controllers\Api\V1\ApiMessageController;
use App\Http\Controllers\Api\V1\ApiMomentOfWeekController;
use App\Http\Controllers\Api\V1\ApiMomentsController;
use App\Http\Controllers\Api\V1\ApiNotificationController;
use App\Http\Controllers\Api\V1\ApiPushDeviceController;
use App\Http\Controllers\Api\V1\ApiSavedFeedController;
use App\Http\Controllers\Api\V1\ApiSearchController;
use App\Http\Controllers\Api\V1\ApiTeamFeedController;
use App\Http\Controllers\Api\V1\ApiTeamLfgController;
use App\Http\Controllers\Api\V1\ApiTeamsController;
use App\Http\Controllers\Api\V1\ApiUserLoadoutController;
use App\Http\Controllers\Api\V1\AppRemoteConfigController;
use App\Http\Controllers\Api\V1\WebAppearanceController;
use App\Http\Controllers\Api\V1\Auth\ApiAuthController;
use App\Http\Controllers\Api\V1\Auth\ApiPasswordResetLinkController;
use App\Http\Controllers\Api\V1\MapCashSpotSubmissionApiController;
use App\Http\Controllers\Api\V1\MapMarkerInteractionController;
use App\Http\Controllers\Api\V1\NewsArticleAdminController;
use App\Http\Controllers\Api\V1\NewsArticleMediaController;
use App\Http\Controllers\Api\V1\NewsArticlePreviewController;
use App\Http\Controllers\News\NewsPublicController;
use App\Http\Controllers\News\NewsEngagementController;
use App\Http\Controllers\Feed\FeedBookmarkController;
use App\Http\Controllers\Feed\FeedTranslationController;
use App\Http\Controllers\Presence\PresenceHeartbeatController;
use App\Http\Controllers\Reports\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/v1/health', function (): array {
    return [
        'ok' => true,
        'app' => config('app.name'),
        'version' => 'v1',
    ];
})->name('api.health');

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::get('/arsenal', [ArsenalController::class, 'index'])->name('arsenal.index');
    Route::get('/arsenal/categories', [ArsenalController::class, 'categories'])->name('arsenal.categories');
    Route::get('/arsenal/classes', [ArsenalController::class, 'classes'])->name('arsenal.classes');
    Route::get('/arsenal/compare', [ArsenalController::class, 'compare'])->name('arsenal.compare');
    Route::get('/arsenal/stat-ranges', [ArsenalController::class, 'statRanges'])->name('arsenal.stat-ranges');
    Route::get('/arsenal/{slug}/related', [ArsenalController::class, 'related'])->name('arsenal.related');
    Route::get('/arsenal/{slug}/ballistics', [ArsenalController::class, 'ballistics'])->name('arsenal.ballistics');
    Route::get('/arsenal/{slug}', [ArsenalController::class, 'show'])->name('arsenal.show');
    Route::get('/appearance', WebAppearanceController::class)->name('appearance.show');
    Route::get('/news', [NewsPublicController::class, 'apiIndex'])->name('news.index');
    Route::get('/news/articles/{article}/engagement', [NewsEngagementController::class, 'index'])
        ->whereNumber('article')->name('news.engagement.index');
    Route::get('/news/{locale}/{slug}', [NewsPublicController::class, 'apiShow'])
        ->where(['locale' => 'de|en|es|ru', 'slug' => '[a-z0-9-]+'])->name('news.show');
    Route::get('/news/{article}/media/{asset}/{variant}', [NewsPublicController::class, 'apiMedia'])
        ->whereNumber('article')->whereNumber('asset')->where('variant', 'original|thumbnail')->name('news.media');
    Route::get('/maps', [ApiMapsController::class, 'index'])->name('maps.index');
    Route::get('/maps/{slug}', [ApiMapsController::class, 'show'])->name('maps.show');
    Route::post('/maps/{slug}/cash-spots', [MapCashSpotSubmissionApiController::class, 'store'])->name('maps.cash-spots.store');
    Route::get('/maps/markers/{marker}/comments', [MapMarkerInteractionController::class, 'comments'])->name('maps.markers.comments.index');

    // Public Moment permalinks use these read-only endpoints so shared links can
    // render the current React design without requiring an API token.
    Route::get('/public/moments', [ApiMomentsController::class, 'publicIndex'])
        ->middleware('throttle:60,1')
        ->name('moments.public.index');
    Route::get('/public/moments/{moment}', [ApiMomentsController::class, 'publicShow'])
        ->whereNumber('moment')
        ->name('moments.public.show');
    Route::get('/public/moments/{moment}/comments', [ApiMomentsController::class, 'publicComments'])
        ->whereNumber('moment')
        ->name('moments.public.comments.index');
    Route::post('/maps/markers/{marker}/vote', [MapMarkerInteractionController::class, 'vote'])->name('maps.markers.vote');

    Route::post('/auth/register', [ApiAuthController::class, 'register'])->name('auth.register');
    Route::post('/auth/login', [ApiAuthController::class, 'login'])->name('auth.login');
    Route::post('/auth/forgot-password', ApiPasswordResetLinkController::class)->middleware('throttle:6,1')->name('auth.password.forgot');
    Route::post('/auth/2fa/challenge', [ApiAuthController::class, 'completeTwoFactorChallenge'])->name('auth.two-factor.challenge');
    Route::post('/auth/social/exchange', [ApiAuthController::class, 'exchangeSocialLoginCode'])->name('auth.social.exchange');
    Route::post('/auth/google/native', [ApiAuthController::class, 'nativeGoogleLogin'])->name('auth.google.native');

    Route::get('/appearance', [AppRemoteConfigController::class, 'appearance'])->name('appearance.show');

    Route::middleware('api.token')->group(function (): void {
        Route::get('/news/articles/{article}/engagement/viewer', [NewsEngagementController::class, 'viewer'])->whereNumber('article')->name('news.engagement.viewer');
        Route::post('/news/articles/{article}/comments', [NewsEngagementController::class, 'store'])->whereNumber('article')->middleware('throttle:12,1')->name('news.comments.store');
        Route::post('/news/articles/{article}/like', [NewsEngagementController::class, 'toggleLike'])->whereNumber('article')->middleware('throttle:30,1')->name('news.like');
        Route::post('/news/articles/{article}/save', [NewsEngagementController::class, 'toggleSave'])->whereNumber('article')->middleware('throttle:30,1')->name('news.save');
        Route::post('/news/articles/{article}/comments/{comment}/like', [NewsEngagementController::class, 'toggleCommentLike'])->whereNumber('article')->whereNumber('comment')->middleware('throttle:30,1')->name('news.comments.like');
        require __DIR__.'/api-guides.php';

        Route::get('/arcade/games', [ArcadeGameController::class, 'index'])->name('arcade.games.index');
        Route::get('/arcade/games/{game}', [ArcadeGameController::class, 'show'])->where('game', '[A-Za-z0-9-]+')->name('arcade.games.show');
        Route::get('/arcade/invitations', [ArcadeInvitationController::class, 'index'])->name('arcade.invitations.index');
        Route::post('/arcade/games/{game}/invitations', [ArcadeInvitationController::class, 'store'])->middleware('throttle:10,1')->name('arcade.invitations.store');
        Route::post('/arcade/invitations/{invitation}/accept', [ArcadeInvitationController::class, 'accept'])->middleware('throttle:20,1')->name('arcade.invitations.accept');
        Route::post('/arcade/invitations/{invitation}/decline', [ArcadeInvitationController::class, 'decline'])->middleware('throttle:20,1')->name('arcade.invitations.decline');
        Route::post('/arcade/invitations/{invitation}/cancel', [ArcadeInvitationController::class, 'cancel'])->middleware('throttle:20,1')->name('arcade.invitations.cancel');
        Route::get('/arcade/matches', [ArcadeMatchController::class, 'index'])->name('arcade.matches.index');
        Route::get('/arcade/matches/{match}', [ArcadeMatchController::class, 'show'])->name('arcade.matches.show');
        Route::post('/arcade/matches/{match}/ready', [ArcadeMatchController::class, 'ready'])->middleware('throttle:30,1')->name('arcade.matches.ready');
        Route::post('/arcade/matches/{match}/moves', [ArcadeMatchController::class, 'move'])->middleware('throttle:120,1')->name('arcade.matches.moves.store');
        Route::get('/app/remote-config', [AppRemoteConfigController::class, 'show'])->name('app.remote-config.show');
        Route::post('/app/remote-feed-cards/{remote_id}/dismiss', [AppRemoteConfigController::class, 'dismiss'])
            ->where('remote_id', '[A-Za-z0-9_\-:]+')
            ->name('app.remote-feed-cards.dismiss');
        Route::post('/maps/markers/{marker}/comments', [MapMarkerInteractionController::class, 'storeComment'])->name('maps.markers.comments.store');
        Route::patch('/maps/marker-comments/{comment}', [MapMarkerInteractionController::class, 'updateComment'])->name('maps.marker-comments.update');
        Route::delete('/maps/marker-comments/{comment}', [MapMarkerInteractionController::class, 'destroyComment'])->name('maps.marker-comments.destroy');

        Route::post('/broadcasting/auth', [ArcadeBroadcastController::class, 'authenticate'])->name('broadcasting.auth');
        Route::post('/presence/heartbeat', PresenceHeartbeatController::class)->middleware('throttle:20,1')->name('presence.heartbeat');
        Route::get('/bootstrap', ApiBootstrapController::class)->name('bootstrap');
        Route::get('/me', [ApiAuthController::class, 'me'])->name('me');
        Route::get('/me/profile-sections/{section}', [ApiMembersController::class, 'meSection'])->name('me.profile-sections.show');
        Route::get('/me/loadouts', [ApiUserLoadoutController::class, 'index'])->name('me.loadouts.index');
        Route::post('/me/loadouts', [ApiUserLoadoutController::class, 'store'])->name('me.loadouts.store');
        Route::match(['put', 'patch'], '/me/loadouts/{loadout}', [ApiUserLoadoutController::class, 'update'])->name('me.loadouts.update');
        Route::delete('/me/loadouts/{loadout}', [ApiUserLoadoutController::class, 'destroy'])->name('me.loadouts.destroy');
        Route::post('/me/profile', [ApiAuthController::class, 'updateProfile'])->name('me.profile.update');
        Route::get('/me/privacy', [ApiAuthController::class, 'privacy'])->name('me.privacy.show');
        Route::post('/me/privacy', [ApiAuthController::class, 'updatePrivacy'])->name('me.privacy.update');
        Route::post('/me/password', [ApiAuthController::class, 'updatePassword'])->name('me.password.update');
        Route::get('/me/2fa', [ApiAuthController::class, 'twoFactorStatus'])->name('me.two-factor.show');
        Route::post('/me/2fa/setup', [ApiAuthController::class, 'startTwoFactorSetup'])->name('me.two-factor.setup');
        Route::post('/me/2fa/enable', [ApiAuthController::class, 'enableTwoFactor'])->name('me.two-factor.enable');
        Route::post('/me/2fa/disable', [ApiAuthController::class, 'disableTwoFactor'])->name('me.two-factor.disable');
        Route::post('/me/2fa/recovery-codes', [ApiAuthController::class, 'regenerateTwoFactorRecoveryCodes'])->name('me.two-factor.recovery-codes');
        Route::get('/me/data', [ApiAuthController::class, 'dataProtection'])->name('me.data.show');
        Route::get('/me/data/export', [ApiAuthController::class, 'dataExport'])->name('me.data.export');
        Route::post('/me/deletion/request', [ApiAuthController::class, 'requestDeletion'])->name('me.deletion.request');
        Route::post('/me/deletion/cancel', [ApiAuthController::class, 'cancelDeletion'])->name('me.deletion.cancel');
        Route::get('/me/sessions', [ApiAuthController::class, 'sessions'])->name('me.sessions.index');
        Route::post('/me/sessions/{token}/revoke', [ApiAuthController::class, 'revokeSession'])->name('me.sessions.revoke');
        Route::get('/me/notifications', [ApiNotificationController::class, 'settings'])->name('me.notifications.show');
        Route::post('/me/notifications', [ApiNotificationController::class, 'updateSettings'])->name('me.notifications.update');
        Route::get('/push/devices', [ApiPushDeviceController::class, 'index'])->name('push.devices.index');
        Route::post('/push/devices', [ApiPushDeviceController::class, 'store'])->name('push.devices.store');
        Route::post('/push/devices/remove', [ApiPushDeviceController::class, 'destroy'])->name('push.devices.remove');
        Route::post('/push/test', [ApiPushDeviceController::class, 'test'])->name('push.test');
        Route::post('/me/avatar', [ApiAuthController::class, 'updateAvatar'])->name('me.avatar.update');
        Route::post('/me/cover', [ApiAuthController::class, 'updateCover'])->name('me.cover.update');
        Route::post('/auth/logout', [ApiAuthController::class, 'logout'])->name('auth.logout');

        Route::get('/gifs/trending', [ApiGifController::class, 'trending'])->name('gifs.trending');
        Route::get('/gifs/search', [ApiGifController::class, 'search'])->name('gifs.search');
        Route::get('/search', ApiSearchController::class)->name('search');

        Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:8,1')->name('reports.store');
        Route::post('/feedback-tickets', [ApiFeedbackTicketController::class, 'store'])->middleware('throttle:6,1')->name('feedback-tickets.store');

        Route::get('/feed', [ApiFeedController::class, 'index'])->name('feed.index');
        Route::post('/feed', [ApiFeedController::class, 'store'])->name('feed.store');
        Route::get('/feed/saved', ApiSavedFeedController::class)->name('feed.saved.index');
        Route::get('/feed/{post}', [ApiFeedController::class, 'show'])->name('feed.show');
        Route::post('/feed/{post}/update', [ApiFeedController::class, 'update'])->name('feed.update');
        Route::post('/feed/{post}/delete', [ApiFeedController::class, 'destroy'])->name('feed.destroy');
        Route::post('/feed/{post}/share', [ApiFeedController::class, 'share'])->name('feed.share');
        Route::post('/feed/{post}/bookmark', [FeedBookmarkController::class, 'toggle'])->name('feed.bookmark.toggle');
        Route::post('/feed/{post}/translation', [FeedTranslationController::class, 'post'])->name('feed.translation.post');
        Route::get('/feed/{post}/comments', [ApiFeedEngagementController::class, 'comments'])->name('feed.comments.index');
        Route::post('/feed/{post}/comments', [ApiFeedEngagementController::class, 'storeComment'])->name('feed.comments.store');
        Route::post('/feed/{post}/reaction', [ApiFeedEngagementController::class, 'toggleReaction'])->name('feed.reactions.toggle');
        Route::get('/feed/{post}/reactions', [ApiFeedEngagementController::class, 'reactions'])->name('feed.reactions.index');
        Route::post('/feed/{post}/poll/vote', [ApiFeedController::class, 'votePoll'])->name('feed.poll.vote');
        Route::post('/feed/comments/{comment}/update', [ApiFeedEngagementController::class, 'updateComment'])->name('feed.comments.update');
        Route::post('/feed/comments/{comment}/delete', [ApiFeedEngagementController::class, 'destroyComment'])->name('feed.comments.destroy');
        Route::post('/feed/comments/{comment}/reaction', [ApiFeedEngagementController::class, 'toggleCommentReaction'])->name('feed.comments.reactions.toggle');
        Route::post('/feed/comments/{comment}/translation', [FeedTranslationController::class, 'comment'])->name('feed.comments.translation');

        Route::get('/hashtags/{tag}', [ApiHashtagController::class, 'show'])
            ->where('tag', '[A-Za-z0-9_\-]+')
            ->name('hashtags.show');

        Route::get('/members', [ApiMembersController::class, 'index'])->name('members.index');
        Route::get('/friends', [ApiMembersController::class, 'friends'])->name('friends.index');
        Route::get('/me/blocks', [ApiMembersController::class, 'blockedUsers'])->name('me.blocks.index');
        Route::get('/users/{user:username}', [ApiMembersController::class, 'show'])->name('users.show');
        Route::get('/users/{user:username}/live', [ApiLiveStreamController::class, 'show'])->name('users.live.show');
        Route::post('/users/{user:username}/live/comments', [ApiLiveStreamController::class, 'storeComment'])
            ->middleware('throttle:30,1')
            ->name('users.live.comments.store');
        Route::post('/users/{user:username}/live/hearts', [ApiLiveStreamController::class, 'storeHearts'])
            ->middleware('throttle:120,1')
            ->name('users.live.hearts.store');
        Route::get('/users/{user:username}/profile-sections/{section}', [ApiMembersController::class, 'userSection'])->name('users.profile-sections.show');
        Route::post('/users/{user:username}/friend', [ApiMembersController::class, 'requestFriend'])->name('users.friend.request');
        Route::post('/users/{user:username}/block', [ApiMembersController::class, 'blockUser'])->name('users.block');
        Route::post('/users/{user:username}/unblock', [ApiMembersController::class, 'unblockUser'])->name('users.unblock');
        Route::post('/friends/{friendship}/accept', [ApiMembersController::class, 'acceptFriend'])->name('friends.accept');
        Route::post('/friends/{friendship}/decline', [ApiMembersController::class, 'declineFriend'])->name('friends.decline');
        Route::post('/friends/{friendship}/remove', [ApiMembersController::class, 'removeFriend'])->name('friends.remove');
        Route::get('/teams', [ApiTeamsController::class, 'index'])->name('teams.index');
        Route::post('/teams', [ApiTeamsController::class, 'store'])->name('teams.store');
        Route::get('/teams/{team:slug}', [ApiTeamsController::class, 'show'])->name('teams.show');
        Route::get('/teams/{team:slug}/feed', [ApiTeamFeedController::class, 'index'])->name('teams.feed.index');
        Route::post('/teams/{team:slug}/feed', [ApiTeamFeedController::class, 'store'])->name('teams.feed.store');
        Route::post('/teams/{team:slug}', [ApiTeamsController::class, 'update'])->name('teams.update');
        Route::post('/teams/{team:slug}/join', [ApiTeamsController::class, 'join'])->name('teams.join');
        Route::post('/teams/{team:slug}/members/{member}/accept', [ApiTeamsController::class, 'acceptJoinRequest'])->name('teams.members.accept');
        Route::post('/teams/{team:slug}/members/{member}/reject', [ApiTeamsController::class, 'rejectJoinRequest'])->name('teams.members.reject');
        Route::post('/teams/{team:slug}/leave', [ApiTeamsController::class, 'leave'])->name('teams.leave');
        Route::post('/teams/{team:slug}/archive', [ApiTeamsController::class, 'archive'])->name('teams.archive');
        Route::post('/teams/{team:slug}/members/{member}/promote', [ApiTeamsController::class, 'promoteMember'])->name('teams.members.promote');
        Route::post('/teams/{team:slug}/members/{member}/demote', [ApiTeamsController::class, 'demoteMember'])->name('teams.members.demote');
        Route::post('/teams/{team:slug}/members/{member}/remove', [ApiTeamsController::class, 'removeMember'])->name('teams.members.remove');
        Route::post('/teams/{team:slug}/avatar', [ApiTeamsController::class, 'updateAvatar'])->name('teams.avatar.update');
        Route::post('/teams/{team:slug}/cover', [ApiTeamsController::class, 'updateCover'])->name('teams.cover.update');
        Route::get('/lfg', [ApiLfgController::class, 'index'])->name('lfg.index');
        Route::post('/lfg', [ApiLfgController::class, 'store'])->name('lfg.store');
        Route::get('/lfg/{post}', [ApiLfgController::class, 'show'])->name('lfg.show');
        Route::post('/lfg/{post}/update', [ApiLfgController::class, 'update'])->name('lfg.update');
        Route::post('/lfg/{post}/delete', [ApiLfgController::class, 'destroy'])->name('lfg.destroy');
        Route::post('/lfg/{post}/apply', [ApiLfgController::class, 'apply'])->name('lfg.apply');
        Route::post('/lfg/{post}/applications/{application}/accept', [ApiLfgController::class, 'acceptApplication'])->name('lfg.applications.accept');
        Route::post('/lfg/{post}/applications/{application}/reject', [ApiLfgController::class, 'rejectApplication'])->name('lfg.applications.reject');
        Route::get('/live-lobbies', [ApiLiveLobbyController::class, 'index'])->name('live-lobbies.index');
        Route::get('/live-lobbies/mine', [ApiLiveLobbyController::class, 'mine'])->name('live-lobbies.mine');
        Route::post('/live-lobbies', [ApiLiveLobbyController::class, 'store'])->name('live-lobbies.store');
        Route::get('/live-lobbies/{lobby}', [ApiLiveLobbyController::class, 'show'])->name('live-lobbies.show');
        Route::post('/live-lobbies/{lobby}/join', [ApiLiveLobbyController::class, 'join'])->name('live-lobbies.join');
        Route::post('/live-lobbies/{lobby}/leave', [ApiLiveLobbyController::class, 'leave'])->name('live-lobbies.leave');
        Route::post('/live-lobbies/{lobby}/close', [ApiLiveLobbyController::class, 'close'])->name('live-lobbies.close');
        Route::get('/ready-lobby-feedback/requests', [ApiLiveLobbyFeedbackController::class, 'index'])->name('ready-lobby-feedback.requests.index');
        Route::post('/ready-lobby-feedback/{feedbackRequest}/submit', [ApiLiveLobbyFeedbackController::class, 'submit'])->name('ready-lobby-feedback.submit');
        Route::post('/ready-lobby-feedback/{feedbackRequest}/dismiss', [ApiLiveLobbyFeedbackController::class, 'dismiss'])->name('ready-lobby-feedback.dismiss');
        Route::get('/team-lfg', [ApiTeamLfgController::class, 'index'])->name('team-lfg.index');
        Route::get('/team-lfg/manageable-teams', [ApiTeamLfgController::class, 'manageableTeams'])->name('team-lfg.manageable-teams');
        Route::post('/team-lfg', [ApiTeamLfgController::class, 'store'])->name('team-lfg.store');
        Route::get('/team-lfg/{post}', [ApiTeamLfgController::class, 'show'])->name('team-lfg.show');
        Route::post('/team-lfg/{post}/apply', [ApiTeamLfgController::class, 'apply'])->name('team-lfg.apply');
        Route::post('/team-lfg/{post}/applications/{application}/accept', [ApiTeamLfgController::class, 'acceptApplication'])->name('team-lfg.applications.accept');
        Route::post('/team-lfg/{post}/applications/{application}/reject', [ApiTeamLfgController::class, 'rejectApplication'])->name('team-lfg.applications.reject');
        Route::get('/messages', [ApiMessageController::class, 'index'])->name('messages.index');
        Route::post('/messages/with/{user:username}', [ApiMessageController::class, 'withUser'])->name('messages.with-user');
        Route::get('/messages/{conversation}', [ApiMessageController::class, 'show'])->name('messages.show');
        Route::post('/messages/{conversation}', [ApiMessageController::class, 'store'])->name('messages.store');
        Route::post('/messages/{conversation}/typing', [ApiMessageController::class, 'typing'])->middleware('throttle:30,1')->name('messages.typing');
        Route::post('/messages/{conversation}/read', [ApiMessageController::class, 'read'])->name('messages.read');
        Route::post('/messages/{conversation}/clear', [ApiMessageController::class, 'clear'])->name('messages.clear');
        Route::get('/moments', [ApiMomentsController::class, 'index'])->name('moments.index');
        Route::post('/moments', [ApiMomentsController::class, 'store'])->name('moments.store');
        Route::get('/moments/studio/{project}/status', [ApiMomentsController::class, 'studioStatus'])->name('moments.studio.status');
        Route::get('/moments/saved', [ApiMomentsController::class, 'saved'])->name('moments.saved.index');
        Route::get('/moments/{moment}', [ApiMomentsController::class, 'show'])->name('moments.show');
        Route::patch('/moments/{moment}', [ApiMomentsController::class, 'update'])->name('moments.update');
        Route::delete('/moments/{moment}', [ApiMomentsController::class, 'destroy'])->name('moments.destroy');
        Route::post('/moments/{moment}/like', [ApiMomentsController::class, 'toggleLike'])->name('moments.like.toggle');
        Route::post('/moments/{moment}/bookmark', [ApiMomentsController::class, 'toggleBookmark'])->name('moments.bookmark.toggle');
        Route::get('/moments/{moment}/comments', [ApiMomentsController::class, 'comments'])->name('moments.comments.index');
        Route::post('/moments/{moment}/comments', [ApiMomentsController::class, 'storeComment'])->name('moments.comments.store');
        Route::patch('/moments/comments/{comment}', [ApiMomentsController::class, 'updateComment'])->name('moments.comments.update');
        Route::post('/moments/comments/{comment}/like', [ApiMomentsController::class, 'toggleCommentLike'])->name('moments.comments.like.toggle');
        Route::delete('/moments/comments/{comment}', [ApiMomentsController::class, 'destroyComment'])->name('moments.comments.destroy');
        Route::get('/cup-feedback', [ApiCupFeedbackController::class, 'index'])->name('cup-feedback.index');
        Route::post('/cup-feedback', [ApiCupFeedbackController::class, 'store'])->middleware('throttle:6,1')->name('cup-feedback.store');
        Route::get('/cup-ideas', [ApiCupIdeasController::class, 'index'])->name('cup-ideas.index');
        Route::post('/cup-ideas', [ApiCupIdeasController::class, 'store'])->middleware('throttle:6,1')->name('cup-ideas.store');
        Route::post('/cup-ideas/{idea}/vote', [ApiCupIdeasController::class, 'vote'])->middleware('throttle:20,1')->name('cup-ideas.vote');
        Route::get('/loadout-challenges', [ApiLoadoutChallengesController::class, 'index'])->name('loadout-challenges.index');
        Route::get('/loadout-challenges/{challenge:slug}', [ApiLoadoutChallengesController::class, 'show'])->name('loadout-challenges.show');
        Route::post('/loadout-challenges/{challenge:slug}/submissions', [ApiLoadoutChallengesController::class, 'storeSubmission'])->middleware('throttle:6,1')->name('loadout-challenges.submissions.store');
        Route::get('/contracts', [ApiContractsController::class, 'index'])->name('contracts.index');
        Route::get('/hall-of-fame', [ApiHallOfFameController::class, 'index'])->name('hall-of-fame.index');
        Route::get('/moment-of-week', [ApiMomentOfWeekController::class, 'index'])->name('moment-of-week.index');
        Route::get('/crowns', [ApiCrownsController::class, 'index'])->name('crowns.index');
        Route::get('/crowns/daily-streak', [ApiCrownDailyStreakController::class, 'show'])->name('crowns.daily-streak.show');
        Route::post('/crowns/daily-streak/claim', [ApiCrownDailyStreakController::class, 'claim'])->middleware('throttle:20,1')->name('crowns.daily-streak.claim');
        Route::post('/crowns/daily-streak/dismiss', [ApiCrownDailyStreakController::class, 'dismiss'])->middleware('throttle:20,1')->name('crowns.daily-streak.dismiss');
        Route::post('/crowns/collect', [ApiCrownsController::class, 'collect'])->middleware('throttle:20,1')->name('crowns.collect');
        Route::post('/crowns/dismiss', [ApiCrownsController::class, 'dismiss'])->middleware('throttle:20,1')->name('crowns.dismiss');
        Route::post('/crowns/shop/{item:key}/purchase', [ApiCrownsController::class, 'purchase'])->middleware('throttle:12,1')->name('crowns.shop.purchase');
        Route::post('/crowns/inventory/{inventoryItem}/equip', [ApiCrownsController::class, 'equip'])->middleware('throttle:20,1')->name('crowns.inventory.equip');
        Route::post('/crowns/inventory/slots/{slot}/unequip', [ApiCrownsController::class, 'unequip'])->middleware('throttle:20,1')->name('crowns.inventory.unequip');
        Route::get('/cups/manage/options', [CupManagementController::class, 'apiOptions'])->name('cups.manage.options');
        Route::post('/cups', [CupManagementController::class, 'apiStore'])->name('cups.manage.store');
        Route::post('/cups/{cup:slug}', [CupManagementController::class, 'apiUpdate'])->name('cups.manage.update');
        Route::post('/cups/{cup:slug}/archive', [CupManagementController::class, 'apiArchive'])->name('cups.manage.archive');
        Route::get('/cups', [ApiCupsController::class, 'index'])->name('cups.index');
        Route::get('/cups/{cup:slug}', [ApiCupsController::class, 'show'])->name('cups.show');
        Route::get('/cups/{cup:slug}/chat', [ApiCupChatController::class, 'index'])->name('cups.chat.index');
        Route::post('/cups/{cup:slug}/chat', [ApiCupChatController::class, 'store'])->middleware('throttle:30,1')->name('cups.chat.store');
        Route::get('/cups/{cup:slug}/teams', [ApiCupTeamsController::class, 'index'])->name('cups.teams.index');
        Route::post('/cups/{cup:slug}/register', ApiCupRegistrationController::class)->name('cups.register');
        Route::get('/cups/{cup:slug}/teams/{team}/chat', [ApiCupTeamChatController::class, 'index'])->name('cups.teams.chat.index');
        Route::post('/cups/{cup:slug}/teams/{team}/chat', [ApiCupTeamChatController::class, 'store'])->name('cups.teams.chat.store');
        Route::post('/cups/{cup:slug}/teams/{team}/join', [ApiCupTeamsController::class, 'join'])->name('cups.teams.join');
        Route::post('/cups/{cup:slug}/team-finder', [ApiCupTeamsController::class, 'storeFinderPost'])->middleware('throttle:6,1')->name('cups.team-finder.store');
        Route::delete('/cups/{cup:slug}/team-finder', [ApiCupTeamsController::class, 'closeFinderPost'])->middleware('throttle:12,1')->name('cups.team-finder.close');
        Route::patch('/cups/{cup:slug}/teams/{team}/recruiting', [ApiCupTeamsController::class, 'updateRecruiting'])->name('cups.teams.recruiting');
        Route::post('/cups/{cup:slug}/teams/{team}/leave', [ApiCupTeamsController::class, 'leave'])->name('cups.teams.leave');
        Route::post('/cups/{cup:slug}/submissions', [ApiCupsController::class, 'submit'])->name('cups.submissions.store');
        Route::get('/cups/{cup:slug}/submissions/{submission}/screenshot', [ApiCupsController::class, 'screenshot'])->name('cups.submissions.screenshot');
        Route::get('/notifications', [ApiNotificationController::class, 'index'])->name('notifications.index');
        Route::post('/notifications/read-all', [ApiNotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::post('/notifications/{notification}/read', [ApiNotificationController::class, 'read'])->name('notifications.read');
    });
});

Route::middleware(['api.token', 'news.admin'])
    ->prefix('v1/admin/news')
    ->name('api.v1.admin.news.')
    ->group(function (): void {
        Route::get('/access', [NewsArticleAdminController::class, 'access'])->name('access');
        Route::get('/articles', [NewsArticleAdminController::class, 'index'])->name('articles.index');
        Route::post('/articles', [NewsArticleAdminController::class, 'store'])->name('articles.store');
        Route::get('/articles/{article}', [NewsArticleAdminController::class, 'show'])->name('articles.show');
        Route::patch('/articles/{article}', [NewsArticleAdminController::class, 'update'])->name('articles.update');
        Route::get('/articles/{article}/media', [NewsArticleMediaController::class, 'articleMedia'])->name('articles.media.index');
        Route::post('/articles/{article}/workflow', [NewsArticleAdminController::class, 'workflow'])->name('articles.workflow');
        Route::post('/articles/{article}/translate', [NewsArticleAdminController::class, 'translate'])
            ->middleware('throttle:6,1')
            ->name('articles.translate');
        Route::get('/articles/{article}/revisions', [NewsArticleAdminController::class, 'revisions'])->name('articles.revisions.index');
        Route::get('/articles/{article}/revisions/{revision}', [NewsArticleAdminController::class, 'showRevision'])->name('articles.revisions.show');
        Route::post('/articles/{article}/revisions/{revision}/restore', [NewsArticleAdminController::class, 'restoreRevision'])->name('articles.revisions.restore');
        Route::post('/articles/{article}/preview', [NewsArticlePreviewController::class, 'issue'])->name('articles.preview.issue');
        Route::post('/media', [NewsArticleMediaController::class, 'store'])->name('media.store');
        Route::get('/media/{asset}', [NewsArticleMediaController::class, 'showAdmin'])->name('media.show');
    });

Route::get('/v1/news/preview/{article}/{locale}', [NewsArticlePreviewController::class, 'show'])
    ->where('locale', 'de|en|es|ru')
    ->middleware(['signed', 'throttle:30,1'])
    ->name('api.v1.news.preview.show');

Route::get('/v1/news/media/{asset}/{variant}', [NewsArticleMediaController::class, 'show'])
    ->where('variant', 'original|thumbnail')
    ->middleware(['signed', 'throttle:60,1'])
    ->name('api.v1.news.media.show');

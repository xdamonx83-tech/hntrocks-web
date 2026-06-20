<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\SocialAuthController;
use App\Http\Controllers\Admin\AdminContentController;
use App\Http\Controllers\Admin\AdminCupFeedbackController;
use App\Http\Controllers\Admin\AdminCupIdeaController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOverviewController;
use App\Http\Controllers\Admin\AdminNavigationController;
use App\Http\Controllers\Admin\AdminMomentOfWeekController;
use App\Http\Controllers\Admin\AdminLoadoutChallengeController;
use App\Http\Controllers\Admin\AdminWeeklyContractController;
use App\Http\Controllers\Admin\AdminApprovedOutboundLinkController;
use App\Http\Controllers\Admin\AdminCampaignLinkController;
use App\Http\Controllers\Admin\AdminGamificationController;
use App\Http\Controllers\Admin\AdminHuntNewsController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminThemePreviewController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\VikingerMappingController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Seo\SitemapController;
use App\Http\Controllers\Settings\NotificationSettingsController;
use App\Http\Controllers\Settings\PrivacyController;
use App\Http\Controllers\Settings\SecurityController;
use App\Http\Controllers\Cups\CupController;
use App\Http\Controllers\Cups\CupChatMessageController;
use App\Http\Controllers\Cups\CupRandomizerDrawController;
use App\Http\Controllers\Cups\CupTeamChatMessageController;
use App\Http\Controllers\Cups\CupSubmissionController;
use App\Http\Controllers\Cups\CupTeamController;
use App\Http\Controllers\CupFeedback\CupFeedbackController;
use App\Http\Controllers\CupIdeas\CupIdeaController;
use App\Http\Controllers\Economy\CrownsController;
use App\Http\Controllers\Economy\CrownsShopController;
use App\Http\Controllers\Contracts\WeeklyContractController;
use App\Http\Controllers\LoadoutChallenges\LoadoutChallengeController;
use App\Http\Controllers\Feed\FeedBookmarkController;
use App\Http\Controllers\Feed\FeedCommentController;
use App\Http\Controllers\Feed\FeedCommentReactionController;
use App\Http\Controllers\Feed\FeedController;
use App\Http\Controllers\Feed\FeedGifController;
use App\Http\Controllers\Feed\FeedPollController;
use App\Http\Controllers\Feed\FeedReactionController;
use App\Http\Controllers\Feed\FeedTranslationController;
use App\Http\Controllers\Friends\FriendshipController;
use App\Http\Controllers\Gamification\GamificationController;
use App\Http\Controllers\Hashtags\HashtagController;
use App\Http\Controllers\LFG\LfgApplicationController;
use App\Http\Controllers\LFG\LfgController;
use App\Http\Controllers\Members\MembersController;
use App\Http\Controllers\Media\MediaController;
use App\Http\Controllers\Messages\MessageController;
use App\Http\Controllers\Mentions\MentionController;
use App\Http\Controllers\Moments\MomentBookmarkController;
use App\Http\Controllers\Moments\MomentCommentController;
use App\Http\Controllers\Moments\MomentController;
use App\Http\Controllers\Moments\MomentOfWeekController;
use App\Http\Controllers\Moments\MomentReactionController;
use App\Http\Controllers\Notifications\NotificationController;
use App\Http\Controllers\Outbound\ApprovedOutboundLinkController;
use App\Http\Controllers\Presence\PresenceHeartbeatController;
use App\Http\Controllers\Socialite\HeaderLiveController;
use App\Http\Controllers\Referrals\ReferralController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Teams\TeamController;
use App\Http\Controllers\TrophyRoom\TrophyRoomController;
use App\Http\Controllers\Teams\TeamFeedController;
use App\Http\Controllers\Teams\TeamMembershipController;
use App\Http\Controllers\TeamLFG\TeamLfgApplicationController;
use App\Http\Controllers\TeamLFG\TeamLfgController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\Marketing\CampaignRedirectController;
use App\Http\Controllers\Marketing\AppBetaController;
use App\Http\Controllers\Marketing\LandingPageController;
use App\Http\Controllers\Maps\MapController;
use App\Models\FeedPost;
use App\Models\Friendship;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    Route::get('/login/2fa', [AuthenticatedSessionController::class, 'twoFactorChallenge'])->name('login.two-factor');
    Route::post('/login/2fa', [AuthenticatedSessionController::class, 'confirmTwoFactor'])->name('login.two-factor.confirm');
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->whereIn('provider', ['google', 'discord', 'twitch', 'steam', 'microsoft', 'facebook'])
    ->name('social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->whereIn('provider', ['google', 'discord', 'twitch', 'steam', 'microsoft', 'facebook'])
    ->name('social.callback');

Route::get('/language/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');
Route::get('/ref/{code}', [ReferralController::class, 'accept'])->name('referrals.accept');
Route::get('/go/{slug}', CampaignRedirectController::class)->where('slug', '[A-Za-z0-9\-]+')->name('campaign.redirect');
Route::get('/out/{link:slug}', [ApprovedOutboundLinkController::class, 'show'])
    ->where('link', '[A-Za-z0-9\-]+')
    ->name('outbound.show');
Route::get('/out/{link:slug}/go', [ApprovedOutboundLinkController::class, 'go'])
    ->where('link', '[A-Za-z0-9\-]+')
    ->middleware('throttle:30,1')
    ->name('outbound.go');

Route::get('/sitemap.xml', SitemapController::class)->name('seo.sitemap');
Route::get('/app-beta', [AppBetaController::class, 'index'])->name('app-beta.index');
Route::post('/app-beta', [AppBetaController::class, 'store'])->middleware('throttle:6,1')->name('app-beta.store');
Route::get('/maps', [MapController::class, 'index'])->name('maps.index');
Route::get('/maps/{slug}', [MapController::class, 'show'])
    ->where('slug', '[a-z0-9-]+')
    ->name('maps.show');

Route::view('/impressum', 'legal.impressum')->name('legal.impressum');
Route::view('/datenschutz', 'legal.datenschutz')->name('legal.datenschutz');
Route::view('/nutzungsbedingungen', 'legal.nutzungsbedingungen')->name('legal.nutzungsbedingungen');
Route::view('/netiquette', 'legal.netiquette')->name('legal.netiquette');
Route::view('/account-deletion', 'legal.account-deletion')->name('legal.account_deletion');
Route::view('/child-safety-standards', 'legal.child-safety')->name('legal.child_safety');
Route::view('/design/socialite-feed', 'design.socialite-feed')->name('design.socialite.feed');
Route::view('/design/socialite-feed-live', 'design.socialite-feed-live')->name('design.socialite.feed.live');
Route::view('/design/socialite-theme-feed', 'themes.socialite.feed.index')->name('design.socialite.theme-feed');

Route::get('/design/socialite-theme-feed-data', function () {
    $allowedFilters = ['all', 'mentions', 'friends', 'teams', 'media'];
    $feedFilter = in_array(request()->query('filter'), $allowedFilters, true)
        ? (string) request()->query('filter')
        : 'all';

    $viewer = request()->user();
    $viewerId = (int) $viewer->id;

    $friendIds = Friendship::query()
        ->forUser($viewer)
        ->where('status', Friendship::STATUS_ACCEPTED)
        ->get(['user_one_id', 'user_two_id'])
        ->map(fn (Friendship $friendship): int => (int) ($friendship->user_one_id === $viewerId ? $friendship->user_two_id : $friendship->user_one_id))
        ->values();

    $posts = FeedPost::query()
        ->with([
            'user.profile',
            'team',
            'sharedPost.user.profile',
            'sharedPost.team',
            'sharedPost.media.mediaAsset',
            'media.mediaAsset',
            'comments.user.profile',
            'comments.reactions',
            'comments.viewerReaction',
            'reactions',
            'viewerReaction',
            'viewerBookmark',
            'poll.options.votes',
            'poll.votes',
        ])
        ->withCount(['comments', 'reactions', 'bookmarks', 'sharedByPosts as shares_count'])
        ->where('status', 'published')
        ->where(function ($query) use ($viewerId): void {
            $query->where(function ($normalPosts) use ($viewerId): void {
                $normalPosts->whereNull('team_id')
                    ->where(function ($visibility) use ($viewerId): void {
                        $visibility->where('visibility', '!=', 'private')
                            ->orWhere('user_id', $viewerId);
                    });
            })->orWhere(function ($teamPosts) use ($viewerId): void {
                $teamPosts->whereNotNull('team_id')
                    ->whereHas('team', function ($teamQuery) use ($viewerId): void {
                        $teamQuery->where('visibility', '!=', 'private')
                            ->orWhereHas('activeMembers', fn ($memberQuery) => $memberQuery->where('user_id', $viewerId));
                    });
            });
        })
        ->when($feedFilter === 'mentions', function ($query) use ($viewerId): void {
            $query->where(function ($mentionQuery) use ($viewerId): void {
                $mentionQuery
                    ->whereHas('mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId))
                    ->orWhereHas('comments.mentions', fn ($mentions) => $mentions->where('mentioned_user_id', $viewerId));
            });
        })
        ->when($feedFilter === 'friends', function ($query) use ($friendIds): void {
            $query->whereNull('team_id')
                ->whereIn('user_id', $friendIds->all());
        })
        ->when($feedFilter === 'teams', function ($query): void {
            $query->whereNotNull('team_id');
        })
        ->when($feedFilter === 'media', function ($query): void {
            $query->where(function ($mediaQuery): void {
                $mediaQuery->whereHas('media')
                    ->orWhereHas('sharedPost.media');
            });
        })
        ->orderByDesc('is_pinned')
        ->orderByDesc('pinned_at')
        ->latest()
        ->paginate(6)
        ->withQueryString();

    if (request()->boolean('fragment')) {
        return response()->json([
            'html' => view('themes.socialite.feed.partials.post-items', [
                'socialitePosts' => $posts,
            ])->render(),
            'nextPageUrl' => $posts->nextPageUrl(),
            'hasMorePages' => $posts->hasMorePages(),
        ]);
    }

    $members = User::query()
        ->with('profile')
        ->where('status', 'active')
        ->latest()
        ->limit(12)
        ->get();

    $teams = Team::query()
        ->withCount('activeMembers')
        ->where('visibility', 'public')
        ->where('status', 'active')
        ->latest()
        ->limit(5)
        ->get();

    return view('themes.socialite.feed.live', [
        'socialitePosts' => $posts,
        'socialiteMembers' => $members,
        'socialiteTeams' => $teams,
        'socialiteFeedFilter' => $feedFilter,
    ]);
})->middleware('auth')->name('design.socialite.theme-feed-data');


Route::get('/design/socialite-theme-feed-data/posts/{post}', function (FeedPost $post) {
    $post->loadMissing([
        'user.profile',
        'team',
        'sharedPost.user.profile',
        'sharedPost.team',
        'sharedPost.media.mediaAsset',
        'media.mediaAsset',
        'comments.user.profile',
        'comments.reactions',
        'comments.viewerReaction',
        'reactions',
        'viewerReaction',
        'viewerBookmark',
        'poll.options.votes',
        'poll.votes',
    ]);

    $post->loadCount(['comments', 'reactions', 'bookmarks']);
    $post->loadCount('sharedByPosts as shares_count');

    abort_unless($post->status === 'published' && $post->canBeViewedBy(request()->user()), 404);

    if (request()->boolean('socialite_comments')) {
        $comments = $post->comments;
        $html = $comments->isEmpty()
            ? '<div class="text-sm text-gray-500 dark:text-white/70" data-socialite-empty-comments>No comments yet.</div>'
            : view()->renderEach('themes.socialite.feed.partials.comment', $comments, 'comment');

        return response()->json([
            'html' => $html,
            'count' => $comments->count(),
        ]);
    }

    return view('themes.socialite.feed.show', [
        'post' => $post,
        'socialiteBackUrl' => route('design.socialite.theme-feed-data'),
        'socialiteBackLabel' => 'Back to preview feed',
        'socialiteModeLabel' => 'Preview post',
    ]);
})->middleware('auth')->name('design.socialite.theme-feed-data.show');


Route::get('/design/socialite-profile', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('design.socialite.profile');
Route::get('/design/socialite-profile/about', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'about')
    ->middleware('auth')
    ->name('design.socialite.profile.about');
Route::get('/design/socialite-profile/friends', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'friends')
    ->middleware('auth')
    ->name('design.socialite.profile.friends');
Route::get('/design/socialite-profile/badges', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'badges')
    ->middleware('auth')
    ->name('design.socialite.profile.badges');
Route::get('/design/socialite-profile/trophies', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'trophies')
    ->middleware('auth')
    ->name('design.socialite.profile.trophies');
Route::get('/design/socialite-profile/teams', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'teams')
    ->middleware('auth')
    ->name('design.socialite.profile.teams');
Route::get('/design/socialite-profile/contact', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'contact')
    ->middleware('auth')
    ->name('design.socialite.profile.contact');
Route::get('/design/socialite-profile/{user:username}/about', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'about')
    ->middleware('auth')
    ->name('design.socialite.profile.public.about');
Route::get('/design/socialite-profile/{user:username}/friends', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'friends')
    ->middleware('auth')
    ->name('design.socialite.profile.public.friends');
Route::get('/design/socialite-profile/{user:username}/badges', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'badges')
    ->middleware('auth')
    ->name('design.socialite.profile.public.badges');
Route::get('/design/socialite-profile/{user:username}/trophies', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'trophies')
    ->middleware('auth')
    ->name('design.socialite.profile.public.trophies');
Route::get('/design/socialite-profile/{user:username}/teams', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'teams')
    ->middleware('auth')
    ->name('design.socialite.profile.public.teams');
Route::get('/design/socialite-profile/{user:username}/contact', [ProfileController::class, 'show'])
    ->defaults('socialite_section', 'contact')
    ->middleware('auth')
    ->name('design.socialite.profile.public.contact');
Route::get('/design/socialite-profile/{user:username}', [ProfileController::class, 'show'])
    ->middleware('auth')
    ->name('design.socialite.profile.public');

Route::get('/hall-of-fame', [CupController::class, 'hallOfFame'])->name('hall-of-fame.index');
Route::get('/cups', [CupController::class, 'index'])->name('cups.index');
Route::get('/cups/{cup:slug}/chat', [CupChatMessageController::class, 'index'])->name('cups.chat.index');
Route::get('/loadout-challenges', [LoadoutChallengeController::class, 'index'])->name('loadout-challenges.index');
Route::get('/loadout-challenges/{challenge:slug}', [LoadoutChallengeController::class, 'show'])->name('loadout-challenges.show');
Route::get('/moment-of-week', [MomentOfWeekController::class, 'index'])->name('moment-of-week.index');
Route::get('/cup-ideas', [CupIdeaController::class, 'index'])->name('cup-ideas.index');
Route::get('/u/{user:username}/about', [ProfileController::class, 'about'])->name('profile.about.public');
Route::get('/u/{user:username}/badges', [ProfileController::class, 'badges'])->name('profile.badges.public');
Route::get('/u/{user:username}/trophies', [ProfileController::class, 'trophies'])->name('profile.trophies.public');
Route::get('/u/{user:username}/teams', [ProfileController::class, 'teams'])->name('profile.teams.public');
Route::get('/u/{user:username}', [ProfileController::class, 'show'])->name('profile.public');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::post('/presence/heartbeat', PresenceHeartbeatController::class)->middleware('throttle:20,1')->name('presence.heartbeat');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/mentions/search', [MentionController::class, 'search'])->name('mentions.search');
    Route::get('/socialite/header/live-badges', [HeaderLiveController::class, 'badges'])->name('socialite.header.live-badges');
    Route::get('/socialite/header/notifications', [HeaderLiveController::class, 'notifications'])->name('socialite.header.notifications');
    Route::get('/socialite/header/messages', [HeaderLiveController::class, 'messages'])->name('socialite.header.messages');
    Route::get('/socialite/header/friend-requests', [HeaderLiveController::class, 'friendRequests'])->name('socialite.header.friend-requests');
    Route::get('/socialite/header/search', [HeaderLiveController::class, 'search'])->name('socialite.header.search');
    Route::get('/trophy-room', [TrophyRoomController::class, 'index'])->name('trophy-room.index');
    Route::get('/trophy-room/prop-lab', [TrophyRoomController::class, 'propLab'])->name('trophy-room.prop-lab');
    Route::post('/trophy-room/prop-lab/models', [TrophyRoomController::class, 'uploadPropModel'])->name('trophy-room.prop-lab.models.upload');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/about', [ProfileController::class, 'about'])->name('profile.about');
    Route::get('/profile/friends', [ProfileController::class, 'friends'])->name('profile.friends');
    Route::get('/profile/badges', [ProfileController::class, 'badges'])->name('profile.badges');
    Route::get('/profile/trophies', [ProfileController::class, 'trophies'])->name('profile.trophies');
    Route::get('/profile/teams', [ProfileController::class, 'teams'])->name('profile.teams');
    Route::get('/profile/contact', [ProfileController::class, 'contact'])->name('profile.contact');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/media', [ProfileController::class, 'updateMedia'])->name('profile.media.update');
    Route::get('/u/{user:username}/friends', [ProfileController::class, 'friends'])->name('profile.friends.public');
    Route::get('/u/{user:username}/contact', [ProfileController::class, 'contact'])->name('profile.contact.public');
    Route::post('/friends/{user:username}', [FriendshipController::class, 'store'])->name('friends.store');
    Route::post('/friends/{friendship}/accept', [FriendshipController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/{friendship}/decline', [FriendshipController::class, 'decline'])->name('friends.decline');
    Route::delete('/friends/{friendship}', [FriendshipController::class, 'destroy'])->name('friends.destroy');

    Route::get('/overview', [AdminOverviewController::class, 'index'])->name('overview.index');
    Route::get('/feed/gifs/trending', [FeedGifController::class, 'trending'])->name('feed.gifs.trending');
    Route::get('/feed/gifs/search', [FeedGifController::class, 'search'])->name('feed.gifs.search');
    Route::get('/feed', [FeedController::class, 'index'])->name('feed.index');
    Route::get('/feed/posts/{post}', [FeedController::class, 'show'])->name('feed.show');
    Route::get('/hashtags/{tag}', [HashtagController::class, 'show'])->where('tag', '[A-Za-z0-9_\-]+')->name('hashtags.show');
    Route::post('/feed', [FeedController::class, 'store'])->name('feed.store');
    Route::put('/feed/{post}', [FeedController::class, 'update'])->name('feed.update');
    Route::post('/feed/{post}/pin', [FeedController::class, 'togglePin'])->name('feed.pin');
    Route::delete('/feed/{post}', [FeedController::class, 'destroy'])->name('feed.destroy');
    Route::post('/feed/{post}/comments', [FeedCommentController::class, 'store'])->name('feed.comments.store');
    Route::patch('/feed/comments/{comment}', [FeedCommentController::class, 'update'])->name('feed.comments.update');
    Route::delete('/feed/comments/{comment}', [FeedCommentController::class, 'destroy'])->name('feed.comments.destroy');
    Route::get('/feed/comments/{comment}/reactions', [FeedCommentReactionController::class, 'index'])->name('feed.comments.reactions.index');
    Route::post('/feed/comments/{comment}/reaction', [FeedCommentReactionController::class, 'toggle'])->name('feed.comments.reactions.toggle');
    Route::get('/feed/{post}/reactions', [FeedReactionController::class, 'index'])->name('feed.reactions.index');
    Route::post('/feed/{post}/reaction', [FeedReactionController::class, 'toggle'])->name('feed.reactions.toggle');
    Route::post('/feed/{post}/poll/vote', [FeedPollController::class, 'vote'])->name('feed.poll.vote');
    Route::post('/feed/{post}/bookmark', [FeedBookmarkController::class, 'toggle'])->name('feed.bookmarks.toggle');
    Route::post('/feed/{post}/share', [FeedController::class, 'share'])->name('feed.share');
    Route::post('/feed/{post}/translation', [FeedTranslationController::class, 'post'])->name('feed.translation.post');
    Route::post('/feed/comments/{comment}/translation', [FeedTranslationController::class, 'comment'])->name('feed.translation.comment');

    Route::view('/account', 'account.index')->name('account.index');
    Route::get('/account/settings', [NotificationSettingsController::class, 'edit'])->name('account.settings.edit');
    Route::put('/account/settings', [NotificationSettingsController::class, 'update'])->name('account.settings.update');
    Route::get('/settings/privacy', [PrivacyController::class, 'edit'])->name('settings.privacy.edit');
    Route::put('/settings/privacy', [PrivacyController::class, 'update'])->name('settings.privacy.update');
    Route::get('/settings/privacy/blocks', [PrivacyController::class, 'blocks'])->name('settings.privacy.blocks');
    Route::post('/settings/privacy/blocks', [PrivacyController::class, 'block'])->name('settings.privacy.blocks.store');
    Route::delete('/settings/privacy/blocks/{block}', [PrivacyController::class, 'unblock'])->name('settings.privacy.blocks.destroy');
    Route::get('/settings/security', [SecurityController::class, 'index'])->name('settings.security.index');
    Route::post('/settings/security/password', [SecurityController::class, 'updatePassword'])->name('settings.security.password');
    Route::post('/settings/security/2fa/setup', [SecurityController::class, 'startTwoFactorSetup'])->name('settings.security.two-factor.setup');
    Route::post('/settings/security/2fa/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('settings.security.two-factor.confirm');
    Route::post('/settings/security/2fa/disable', [SecurityController::class, 'disableTwoFactor'])->name('settings.security.two-factor.disable');
    Route::post('/settings/security/2fa/recovery-codes', [SecurityController::class, 'regenerateTwoFactorRecoveryCodes'])->name('settings.security.two-factor.recovery-codes');
    Route::get('/settings/security/export', [SecurityController::class, 'export'])->name('settings.security.export');
    Route::post('/settings/security/deletion', [SecurityController::class, 'requestDeletion'])->name('settings.security.deletion.request');
    Route::delete('/settings/security/deletion', [SecurityController::class, 'cancelDeletion'])->name('settings.security.deletion.cancel');
    Route::get('/members', [MembersController::class, 'index'])->name('members.index');
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::delete('/media/{asset}', [MediaController::class, 'destroy'])->name('media.destroy');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::get('/teams/manage', [TeamController::class, 'manage'])->name('teams.manage');
    Route::get('/teams/invitations', [TeamController::class, 'invitations'])->name('teams.invitations');
    Route::get('/teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('/teams', [TeamController::class, 'store'])->name('teams.store');
    Route::post('/teams/{team:slug}/feed', [TeamFeedController::class, 'store'])->name('teams.feed.store');
    Route::get('/teams/{team:slug}/info', [TeamController::class, 'info'])->name('teams.info');
    Route::get('/teams/{team:slug}/members', [TeamController::class, 'members'])->name('teams.members');
    Route::get('/teams/{team:slug}/team-lfg', [TeamController::class, 'teamLfg'])->name('teams.team-lfg');
    Route::get('/teams/{team:slug}', [TeamController::class, 'show'])->name('teams.show');
    Route::get('/teams/{team:slug}/edit', [TeamController::class, 'edit'])->name('teams.edit');
    Route::put('/teams/{team:slug}', [TeamController::class, 'update'])->name('teams.update');
    Route::delete('/teams/{team:slug}', [TeamController::class, 'destroy'])->name('teams.destroy');
    Route::post('/teams/{team:slug}/join', [TeamMembershipController::class, 'join'])->name('teams.join');
    Route::post('/teams/{team:slug}/leave', [TeamMembershipController::class, 'leave'])->name('teams.leave');
    Route::post('/teams/{team:slug}/requests/{member}/accept', [TeamMembershipController::class, 'accept'])->name('teams.requests.accept');
    Route::post('/teams/{team:slug}/requests/{member}/reject', [TeamMembershipController::class, 'reject'])->name('teams.requests.reject');
    Route::post('/teams/{team:slug}/members/{member}/promote', [TeamMembershipController::class, 'promote'])->name('teams.members.promote');
    Route::post('/teams/{team:slug}/members/{member}/demote', [TeamMembershipController::class, 'demote'])->name('teams.members.demote');

    Route::get('/lfg', [LfgController::class, 'index'])->name('lfg.index');
    Route::get('/lfg/create', [LfgController::class, 'create'])->name('lfg.create');
    Route::post('/lfg', [LfgController::class, 'store'])->name('lfg.store');
    Route::get('/lfg/{post}', [LfgController::class, 'show'])->name('lfg.show');
    Route::get('/lfg/{post}/edit', [LfgController::class, 'edit'])->name('lfg.edit');
    Route::put('/lfg/{post}', [LfgController::class, 'update'])->name('lfg.update');
    Route::delete('/lfg/{post}', [LfgController::class, 'destroy'])->name('lfg.destroy');
    Route::post('/lfg/{post}/applications', [LfgApplicationController::class, 'store'])->name('lfg.applications.store');
    Route::post('/lfg/{post}/applications/{application}/accept', [LfgApplicationController::class, 'accept'])->name('lfg.applications.accept');
    Route::post('/lfg/{post}/applications/{application}/reject', [LfgApplicationController::class, 'reject'])->name('lfg.applications.reject');

    Route::get('/team-lfg', [TeamLfgController::class, 'index'])->name('team-lfg.index');
    Route::get('/team-lfg/create', [TeamLfgController::class, 'create'])->name('team-lfg.create');
    Route::post('/team-lfg', [TeamLfgController::class, 'store'])->name('team-lfg.store');
    Route::get('/team-lfg/{post}', [TeamLfgController::class, 'show'])->name('team-lfg.show');
    Route::get('/team-lfg/{post}/edit', [TeamLfgController::class, 'edit'])->name('team-lfg.edit');
    Route::put('/team-lfg/{post}', [TeamLfgController::class, 'update'])->name('team-lfg.update');
    Route::delete('/team-lfg/{post}', [TeamLfgController::class, 'destroy'])->name('team-lfg.destroy');
    Route::post('/team-lfg/{post}/applications', [TeamLfgApplicationController::class, 'store'])->name('team-lfg.applications.store');
    Route::post('/team-lfg/{post}/applications/{application}/accept', [TeamLfgApplicationController::class, 'accept'])->name('team-lfg.applications.accept');
    Route::post('/team-lfg/{post}/applications/{application}/reject', [TeamLfgApplicationController::class, 'reject'])->name('team-lfg.applications.reject');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::get('/messages/with/{user}', [MessageController::class, 'withUser'])->name('messages.with-user');
    Route::post('/messages/start', [MessageController::class, 'start'])->name('messages.start');
    Route::get('/messages/{conversation}/chat-tab', [MessageController::class, 'chatTab'])->name('messages.chat-tab');
    Route::get('/messages/{conversation}/chat-tab/messages', [MessageController::class, 'chatTabMessages'])->name('messages.chat-tab.messages');
    Route::get('/messages/{conversation}', [MessageController::class, 'show'])->name('messages.show');
    Route::post('/messages/{conversation}/read', [MessageController::class, 'read'])->name('messages.read');
    Route::post('/messages/{conversation}/typing', [MessageController::class, 'typing'])->middleware('throttle:30,1')->name('messages.typing');
    Route::post('/messages/{conversation}', [MessageController::class, 'store'])->name('messages.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/moments', [MomentController::class, 'index'])->name('moments.index');
    Route::get('/moments/create', [MomentController::class, 'create'])->name('moments.create');
    Route::post('/moments', [MomentController::class, 'store'])->name('moments.store');
    Route::post('/moments/studio/features/{feature}/unlock', [MomentController::class, 'unlockStudioFeature'])->middleware('throttle:12,1')->name('moments.studio.unlock');
    Route::get('/moments/studio/{project}/processing', [MomentController::class, 'processing'])->name('moments.studio.processing');
    Route::get('/moments/studio/{project}/status', [MomentController::class, 'studioStatus'])->name('moments.studio.status');
    Route::get('/moments/r/{moment}', [MomentController::class, 'show'])->name('moments.show');
    Route::patch('/moments/r/{moment}', [MomentController::class, 'update'])->name('moments.update');
    Route::delete('/moments/r/{moment}', [MomentController::class, 'destroy'])->name('moments.destroy');
    Route::post('/moments/r/{moment}/reaction', [MomentReactionController::class, 'toggle'])->name('moments.reactions.toggle');
    Route::post('/moments/r/{moment}/bookmark', [MomentBookmarkController::class, 'toggle'])->name('moments.bookmarks.toggle');
    Route::post('/moments/r/{moment}/comments', [MomentCommentController::class, 'store'])->name('moments.comments.store');
    Route::patch('/moments/comments/{comment}', [MomentCommentController::class, 'update'])->name('moments.comments.update');
    Route::post('/moments/comments/{comment}/reaction', [MomentCommentController::class, 'toggleReaction'])->name('moments.comments.reactions.toggle');
    Route::delete('/moments/comments/{comment}', [MomentCommentController::class, 'destroy'])->name('moments.comments.destroy');
    Route::get('/contracts', [WeeklyContractController::class, 'index'])->name('contracts.index');
    Route::get('/crowns', [CrownsController::class, 'index'])->name('crowns.index');
    Route::get('/crowns/history', [CrownsController::class, 'history'])->name('crowns.history');
    Route::get('/crowns/shop', [CrownsShopController::class, 'shop'])->name('crowns.shop');
    Route::get('/crowns/inventory', [CrownsShopController::class, 'inventory'])->name('crowns.inventory');
    Route::post('/crowns/daily-login', [CrownsController::class, 'claimDailyLogin'])->middleware('throttle:6,1')->name('crowns.daily-login');
    Route::post('/crowns/collect', [CrownsController::class, 'collectPending'])->middleware('throttle:20,1')->name('crowns.collect');
    Route::post('/crowns/dismiss', [CrownsController::class, 'dismissPending'])->middleware('throttle:30,1')->name('crowns.dismiss');
    Route::post('/crowns/shop/{item}/purchase', [CrownsShopController::class, 'purchase'])->middleware('throttle:20,1')->name('crowns.shop.purchase');
    Route::post('/crowns/inventory/{inventoryItem}/equip', [CrownsShopController::class, 'equip'])->middleware('throttle:30,1')->name('crowns.inventory.equip');
    Route::post('/crowns/inventory/{slot}/unequip', [CrownsShopController::class, 'unequip'])->middleware('throttle:30,1')->where('slot', '[A-Za-z0-9_\-]+')->name('crowns.inventory.unequip');
    Route::post('/cup-ideas', [CupIdeaController::class, 'store'])->middleware('throttle:6,1')->name('cup-ideas.store');
    Route::post('/cup-ideas/{idea}/vote', [CupIdeaController::class, 'vote'])->middleware('throttle:20,1')->name('cup-ideas.vote');
    Route::get('/cup-feedback', [CupFeedbackController::class, 'create'])->name('cup-feedback.create');
    Route::post('/cup-feedback', [CupFeedbackController::class, 'store'])->middleware('throttle:6,1')->name('cup-feedback.store');
    Route::post('/loadout-challenges/{challenge:slug}/submissions', [LoadoutChallengeController::class, 'storeSubmission'])->middleware('throttle:6,1')->name('loadout-challenges.submissions.store');
    Route::get('/cups/create', [CupController::class, 'create'])->name('cups.create');
    Route::post('/cups', [CupController::class, 'store'])->name('cups.store');
    Route::get('/cups/{cup:slug}/edit', [CupController::class, 'edit'])->name('cups.edit');
    Route::put('/cups/{cup:slug}', [CupController::class, 'update'])->name('cups.update');
    Route::delete('/cups/{cup:slug}', [CupController::class, 'destroy'])->name('cups.destroy');
    Route::post('/cups/{cup:slug}/chat', [CupChatMessageController::class, 'store'])->name('cups.chat.store');
    Route::get('/cups/{cup:slug}/teams', [CupTeamController::class, 'index'])->name('cups.teams.index');
    Route::get('/cups/{cup:slug}/teams/{team}/chat', [CupTeamChatMessageController::class, 'index'])->name('cups.teams.chat.index');
    Route::post('/cups/{cup:slug}/teams/{team}/chat', [CupTeamChatMessageController::class, 'store'])->name('cups.teams.chat.store');
    Route::post('/cups/{cup:slug}/teams', [CupTeamController::class, 'store'])->name('cups.teams.store');
    Route::patch('/cups/{cup:slug}/teams/{team}', [CupTeamController::class, 'update'])->name('cups.teams.update');
    Route::patch('/cups/{cup:slug}/teams/{team}/recruiting', [CupTeamController::class, 'updateRecruiting'])->name('cups.teams.recruiting');
    Route::post('/cups/{cup:slug}/team-finder', [CupTeamController::class, 'storeFinderPost'])->name('cups.team-finder.store');
    Route::delete('/cups/{cup:slug}/team-finder', [CupTeamController::class, 'closeFinderPost'])->name('cups.team-finder.close');
    Route::get('/cups/{cup:slug}/join/{token}', [CupTeamController::class, 'join'])->name('cups.teams.join');
    Route::post('/cups/{cup:slug}/teams/{team}/leave', [CupTeamController::class, 'leave'])->name('cups.teams.leave');
    Route::post('/cups/{cup:slug}/teams/{team}/disqualify', [CupTeamController::class, 'disqualify'])->name('cups.teams.disqualify');
    Route::post('/cups/{cup:slug}/teams/{team}/reinstate', [CupTeamController::class, 'reinstate'])->name('cups.teams.reinstate');
    Route::post('/cups/{cup:slug}/teams/{team}/submissions', [CupSubmissionController::class, 'store'])->name('cups.submissions.store');
    Route::get('/cups/{cup:slug}/submissions/{submission}/screenshot', [CupSubmissionController::class, 'screenshot'])->name('cups.submissions.screenshot');
    Route::post('/cups/{cup:slug}/submissions/{submission}/approve', [CupSubmissionController::class, 'approve'])->name('cups.submissions.approve');
    Route::post('/cups/{cup:slug}/submissions/{submission}/manual-score', [CupSubmissionController::class, 'manualScore'])->name('cups.submissions.manual-score');
    Route::post('/cups/{cup:slug}/submissions/{submission}/rescore', [CupSubmissionController::class, 'rescore'])->name('cups.submissions.rescore');
    Route::post('/cups/{cup:slug}/submissions/{submission}/reject', [CupSubmissionController::class, 'reject'])->name('cups.submissions.reject');
    Route::post('/cups/{cup:slug}/randomizer/draws', [CupRandomizerDrawController::class, 'store'])->name('cups.randomizer.draws.store');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/gamification', [GamificationController::class, 'index'])->name('gamification.index');
    Route::get('/gamification/achievements/pending', function () {
        $toasts = session()->pull('hunthub_achievement_toasts', []);

        return response()->json([
            'achievements' => array_values(is_array($toasts) ? $toasts : []),
        ]);
    })->name('gamification.achievements.pending');
    Route::prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('index');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/status', [AdminUserController::class, 'updateStatus'])->name('users.status');
        Route::post('/users/{user}/admin', [AdminUserController::class, 'toggleAdmin'])->name('users.admin');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::post('/reports/{report}', [AdminReportController::class, 'update'])->name('reports.update');
        Route::get('/cup-feedback', [AdminCupFeedbackController::class, 'index'])->name('cup-feedback.index');
        Route::post('/cup-feedback/{feedback}', [AdminCupFeedbackController::class, 'update'])->name('cup-feedback.update');
        Route::get('/cup-ideas', [AdminCupIdeaController::class, 'index'])->name('cup-ideas.index');
        Route::post('/cup-ideas/{idea}', [AdminCupIdeaController::class, 'update'])->name('cup-ideas.update');
        Route::get('/moment-of-week', [AdminMomentOfWeekController::class, 'index'])->name('moment-of-week.index');
        Route::post('/moment-of-week', [AdminMomentOfWeekController::class, 'store'])->name('moment-of-week.store');
        Route::put('/moment-of-week/{spotlight}', [AdminMomentOfWeekController::class, 'update'])->name('moment-of-week.update');
        Route::delete('/moment-of-week/{spotlight}', [AdminMomentOfWeekController::class, 'destroy'])->name('moment-of-week.destroy');
        Route::get('/content', [AdminContentController::class, 'index'])->name('content.index');
        Route::get('/contracts', [AdminWeeklyContractController::class, 'index'])->name('contracts.index');
        Route::post('/contracts', [AdminWeeklyContractController::class, 'store'])->name('contracts.store');
        Route::put('/contracts/{contract}', [AdminWeeklyContractController::class, 'update'])->name('contracts.update');
        Route::delete('/contracts/{contract}', [AdminWeeklyContractController::class, 'destroy'])->name('contracts.destroy');
        Route::get('/loadout-challenges', [AdminLoadoutChallengeController::class, 'index'])->name('loadout-challenges.index');
        Route::post('/loadout-challenges', [AdminLoadoutChallengeController::class, 'store'])->name('loadout-challenges.store');
        Route::put('/loadout-challenges/{challenge:slug}', [AdminLoadoutChallengeController::class, 'update'])->name('loadout-challenges.update');
        Route::delete('/loadout-challenges/{challenge:slug}', [AdminLoadoutChallengeController::class, 'destroy'])->name('loadout-challenges.destroy');
        Route::put('/loadout-challenges/submissions/{submission}', [AdminLoadoutChallengeController::class, 'updateSubmission'])->name('loadout-challenges.submissions.update');
        Route::get('/hunt-news', [AdminHuntNewsController::class, 'index'])->name('hunt-news.index');
        Route::post('/hunt-news/sync', [AdminHuntNewsController::class, 'sync'])->name('hunt-news.sync');
        Route::post('/hunt-news/{item}/publish', [AdminHuntNewsController::class, 'publish'])->name('hunt-news.publish');
        Route::post('/hunt-news/{item}/skip', [AdminHuntNewsController::class, 'skip'])->name('hunt-news.skip');
        Route::get('/outbound-links', [AdminApprovedOutboundLinkController::class, 'index'])->name('outbound-links.index');
        Route::post('/outbound-links', [AdminApprovedOutboundLinkController::class, 'store'])->name('outbound-links.store');
        Route::put('/outbound-links/{link:slug}', [AdminApprovedOutboundLinkController::class, 'update'])->name('outbound-links.update');
        Route::delete('/outbound-links/{link:slug}', [AdminApprovedOutboundLinkController::class, 'destroy'])->name('outbound-links.destroy');
        Route::get('/campaign-links', [AdminCampaignLinkController::class, 'index'])->name('campaign-links.index');
        Route::post('/campaign-links', [AdminCampaignLinkController::class, 'store'])->name('campaign-links.store');
        Route::put('/campaign-links/{campaignLink:slug}', [AdminCampaignLinkController::class, 'update'])->name('campaign-links.update');
        Route::delete('/campaign-links/{campaignLink:slug}', [AdminCampaignLinkController::class, 'destroy'])->name('campaign-links.destroy');
        Route::get('/gamification', [AdminGamificationController::class, 'index'])->name('gamification.index');
        Route::get('/navigation', [AdminNavigationController::class, 'index'])->name('navigation.index');
        Route::post('/navigation', [AdminNavigationController::class, 'update'])->name('navigation.update');
        Route::get('/theme-preview', [AdminThemePreviewController::class, 'index'])->name('theme-preview.index');
        Route::get('/theme-preview/shell', [AdminThemePreviewController::class, 'shell'])->name('theme-preview.shell');
        Route::post('/theme-preview/start', [AdminThemePreviewController::class, 'start'])->name('theme-preview.start');
        Route::post('/theme-preview/stop', [AdminThemePreviewController::class, 'stop'])->name('theme-preview.stop');
        Route::post('/navigation/items', [AdminNavigationController::class, 'store'])->name('navigation.store');
        Route::post('/navigation/mobile', [AdminNavigationController::class, 'updateMobile'])->name('navigation.mobile.update');
        Route::post('/navigation/mobile/items', [AdminNavigationController::class, 'storeMobile'])->name('navigation.mobile.store');
        Route::post('/gamification/badges', [AdminGamificationController::class, 'storeBadge'])->name('gamification.badges.store');
        Route::put('/gamification/badges/{badge}', [AdminGamificationController::class, 'updateBadge'])->name('gamification.badges.update');
        Route::delete('/gamification/badges/{badge}', [AdminGamificationController::class, 'destroyBadge'])->name('gamification.badges.destroy');
        Route::post('/gamification/badges/{badge}/award', [AdminGamificationController::class, 'awardBadge'])->name('gamification.badges.award');
        Route::delete('/gamification/badges/{badge}/users/{user}', [AdminGamificationController::class, 'revokeBadge'])->name('gamification.badges.revoke');
        Route::post('/gamification/quests', [AdminGamificationController::class, 'storeQuest'])->name('gamification.quests.store');
        Route::put('/gamification/quests/{quest}', [AdminGamificationController::class, 'updateQuest'])->name('gamification.quests.update');
        Route::delete('/gamification/quests/{quest}', [AdminGamificationController::class, 'destroyQuest'])->name('gamification.quests.destroy');
        Route::get('/vikinger-mapping', [VikingerMappingController::class, 'index'])->name('vikinger-mapping.index');
        Route::post('/content/feed-posts/{post}/ai-label', [AdminContentController::class, 'updateFeedAiLabel'])->name('content.feed-ai-label');
        Route::post('/content/{type}/{id}/status', [AdminContentController::class, 'updateStatus'])->name('content.status');
    });
});

Route::get('/cups/{cup:slug}/{section}', [CupController::class, 'showSection'])
    ->whereIn('section', ['rules', 'prizes', 'leaderboard', 'participants', 'submit', 'submissions'])
    ->name('cups.show.section');
Route::get('/cups/{cup:slug}', [CupController::class, 'show'])->name('cups.show');

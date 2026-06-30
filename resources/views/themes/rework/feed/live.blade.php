@php
    $viewer = auth()->user();
    $socialiteFeedFilter = $socialiteFeedFilter ?? 'all';
    $reworkAsset = fn (string $path): string => \App\Support\HntTheme::asset($path, 'rework');
    $reworkStyleVersion = @filemtime(public_path('assets/themes/rework/styles.css')) ?: time();
    $reworkScriptVersion = @filemtime(public_path('assets/themes/rework/script.js')) ?: time();
    $socialiteChatTabsVersion = @filemtime(public_path('assets/socialite/js/hnt-socialite-chat-tabs.js')) ?: time();
    $formatCount = fn (int $count): string => number_format($count);
    $feedFilterUrl = function (string $filterKey): string {
        $query = request()->query();
        unset($query['page'], $query['fragment']);

        if ($filterKey === 'all') {
            unset($query['filter']);
        } else {
            $query['filter'] = $filterKey;
        }

        return route('feed.index', $query);
    };
    $memberProfileUrl = function ($member): string {
        if (! $member?->username) {
            return '#';
        }

        return (int) $member->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $member);
    };
    $postAuthorUrl = function ($author): string {
        if (! $author?->username) {
            return '#';
        }

        return (int) $author->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $author);
    };
    $crownsSummary = $socialiteCrownsSummary ?? ['balance' => 0, 'enabled' => false];
    $profileStats = $socialiteProfileStats ?? [];
    $marksBalance = (int) ($crownsSummary['balance'] ?? 0);
    $shopUrl = \Illuminate\Support\Facades\Route::has('crowns.shop') ? route('crowns.shop') : null;
    $membersUrl = \Illuminate\Support\Facades\Route::has('members.index') ? route('members.index') : null;
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $notificationsUrl = route('notifications.index');
    $messagesUrl = route('messages.index');
    $defaultAvatar = asset('assets/vikinger/img/default-avatar.svg');
    $headerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $headerName = $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter');
    $headerHandle = $viewer?->username ? '@'.$viewer->username : __('ui.members');
    $viewer?->loadMissing('profile');
    $viewerProfile = $viewer?->profile;
    $viewerAvatar = $viewer?->avatarUrl() ?: $defaultAvatar;
    $viewerCover = $viewer?->coverUrl() ?: asset('assets/vikinger/img/default-cover.svg');
    $viewerCompletion = $viewer ? \App\Support\ProfileCompletion::score($viewer) : 0;
    $profileVisibility = old('profile_visibility', $viewerProfile?->profile_visibility ?? 'public');
    $profilePlatform = old('platform', $viewerProfile?->platform);
    $profilePlaystyle = old('playstyle', $viewerProfile?->playstyle);
    $profileRegion = old('region', $viewerProfile?->region);
    $profileLanguage = old('language', $viewerProfile?->language);
    $profileIsLfgAvailable = (bool) old('is_lfg_available', $viewerProfile?->is_lfg_available);
    $reworkProfileOptions = [
        'platform' => ['PC', 'PlayStation', 'Xbox', 'Crossplay'],
        'playstyle' => [
            'Tactical' => __('ui.playstyle_tactical'),
            'Aggressive' => __('ui.playstyle_aggressive'),
            'Beginner friendly' => __('ui.playstyle_beginner'),
            'Casual' => 'Casual',
            'Competitive' => 'Competitive',
        ],
        'region' => ['EU', 'US East', 'US West', 'Asia', 'Oceania'],
        'language' => ['Deutsch', 'English', 'Francais', 'Espanol', 'Other'],
    ];
    $headerNotificationsUnread = $viewer
        ? $viewer->notificationItems()->standard()->unread()->count()
        : 0;
    $headerNotifications = $viewer
        ? $viewer->notificationItems()
            ->standard()
            ->with('actor.profile')
            ->orderByRaw('read_at is not null')
            ->latest()
            ->limit(5)
            ->get()
        : collect();
    $headerFriendRequests = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->with('requester.profile')
            ->latest()
            ->limit(5)
            ->get()
        : collect();
    $headerFriendRequestCount = $viewer
        ? \App\Models\Friendship::query()
            ->where('recipient_id', $viewer->id)
            ->where('status', \App\Models\Friendship::STATUS_PENDING)
            ->count()
        : 0;
    $headerMessageConversations = $viewer
        ? \App\Models\Conversation::query()
            ->forUser($viewer)
            ->where('type', 'private')
            ->with(['users.profile', 'users.privacySettings', 'latestMessage.user'])
            ->latest('updated_at')
            ->limit(5)
            ->get()
        : collect();
    $headerMessagesUnread = $viewer && method_exists($viewer, 'unreadMessagesCount')
        ? $viewer->unreadMessagesCount()
        : 0;
    $highlightScore = function ($post): int {
        return (int) ($post?->reactions_count ?? 0)
            + (int) ($post?->comments_count ?? 0)
            + (int) ($post?->shares_count ?? 0);
    };
    $feedFilters = [
        'all' => __('ui.feed_filter_all'),
        'friends' => __('ui.rework_profile_friends'),
        'media' => __('ui.feed_filter_media'),
        'mentions' => 'Mentions',
    ];
    $reworkNotificationSettings = $reworkNotificationSettings ?? null;
    $reworkNotificationGroups = $reworkNotificationGroups ?? [];
    $reworkPrivacySettings = $reworkPrivacySettings ?? null;
    $reworkBlockedUsers = $reworkBlockedUsers ?? collect();
    $reworkTwoFactorEnabled = (bool) ($reworkTwoFactorEnabled ?? false);
    $reworkTwoFactorRecoveryCount = (int) ($reworkTwoFactorRecoveryCount ?? 0);
    $reworkDeletionRequest = $reworkDeletionRequest ?? null;
    $reworkPrivacyToggles = [
        'allow_team_invites' => ['title' => __('ui.allow_team_invites'), 'text' => __('ui.allow_team_invites_text')],
        'allow_lfg_invites' => ['title' => __('ui.allow_lfg_invites'), 'text' => __('ui.allow_lfg_invites_text')],
        'show_online_status' => ['title' => __('ui.show_online_status'), 'text' => __('ui.show_online_status_text')],
        'show_activity_feed' => ['title' => __('ui.show_activity_feed'), 'text' => __('ui.show_activity_feed_text')],
        'show_gamification' => ['title' => __('ui.show_gamification'), 'text' => __('ui.show_gamification_text')],
        'data_usage_consent' => ['title' => __('ui.data_usage_consent'), 'text' => __('ui.data_usage_consent_text')],
    ];
    $reworkSettingsFields = array_merge(
        array_keys($reworkNotificationGroups),
        ['profile_visibility', 'allow_messages_from', 'username', 'reason', 'current_password', 'password', 'delete_confirmation', 'code'],
        array_keys($reworkPrivacyToggles)
    );
    $reworkSettingsHasErrors = collect($reworkSettingsFields)->contains(fn ($field) => $errors->has($field));
    $reworkSettingsStatusMessages = [
        __('ui.notification_settings_saved'),
        __('ui.privacy_settings_saved'),
        __('ui.user_blocked_status'),
        __('ui.user_unblocked_status'),
        __('ui.security_password_updated'),
        __('ui.two_factor_setup_started'),
        __('ui.account_deletion_cancelled_status'),
    ];
    $reworkSettingsShouldOpen = $reworkSettingsHasErrors || (session('status') && in_array(session('status'), $reworkSettingsStatusMessages, true));
    $reworkSettingsActiveTab = 'notifications';
    if (collect(['profile_visibility', 'allow_messages_from', 'allow_team_invites', 'allow_lfg_invites', 'show_online_status', 'show_activity_feed', 'show_gamification', 'data_usage_consent'])->contains(fn ($field) => $errors->has($field)) || session('status') === __('ui.privacy_settings_saved')) {
        $reworkSettingsActiveTab = 'privacy';
    } elseif (collect(['username', 'reason'])->contains(fn ($field) => $errors->has($field)) || in_array(session('status'), [__('ui.user_blocked_status'), __('ui.user_unblocked_status')], true)) {
        $reworkSettingsActiveTab = 'blocked';
    } elseif (collect(['current_password', 'password', 'code', 'delete_confirmation'])->contains(fn ($field) => $errors->has($field)) || in_array(session('status'), [__('ui.security_password_updated'), __('ui.two_factor_setup_started'), __('ui.account_deletion_cancelled_status')], true)) {
        $reworkSettingsActiveTab = 'security';
    }
@endphp
<!DOCTYPE html>

<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>HNT.rocks Rework Feed Preview</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>
<style>
/* 111: inline mobile guard, independent from cached external CSS */
@media (hover: none) and (pointer: coarse), (max-width: 1100px) {
  .right-col,
  .right-col *,
  body > .rework-profile-late-sticky-clone,
  body > .rework-profile-late-sticky-clone * {
    display: none !important;
    visibility: hidden !important;
    opacity: 0 !important;
    pointer-events: none !important;
    position: static !important;
    width: 0 !important;
    height: 0 !important;
    max-width: 0 !important;
    max-height: 0 !important;
    overflow: hidden !important;
    transform: none !important;
    animation: none !important;
    transition: none !important;
  }

  .content-grid {
    display: block !important;
    grid-template-columns: minmax(0, 1fr) !important;
  }

  .left-col {
    width: 100% !important;
    max-width: none !important;
  }
}
</style>
</head>
<body>
<div class="app">
@include('themes.rework.partials.sidebar')
<main class="main">
@include('themes.rework.partials.topbar')
<section class="content-grid">
<div class="left-col">
<section class="card hero-card">
<div class="eyebrow">Newsfeed</div>
<h1>Check What Your Friends Up To!</h1>
<p>Conveniently customize proactive web services for leveraged without continually aggregate frictionless ou well-structured HNT activity..</p>
<div aria-label="{{ __('ui.rework_post_composer_open') }}" class="composer-mini" data-post-composer-open="" role="button" tabindex="0">
<span>{{ __('ui.rework_composer_prompt', ['name' => $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter')]) }}</span>
<span class="spacer"></span>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-image ph-icon"></i></a>
<a class="square-icon" data-post-composer-open="" href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
<a class="btn" data-post-composer-open="" href="#">{{ __('ui.rework_post_create') }}</a>
</div>
</section>
<nav class="rework-feed-filters" aria-label="{{ __('ui.rework_feed_filters_aria') }}">
@foreach($feedFilters as $filterKey => $filterLabel)
<a @class(['active' => $socialiteFeedFilter === $filterKey]) href="{{ $feedFilterUrl($filterKey) }}">{{ $filterLabel }}</a>
@endforeach
</nav>
<div data-rework-post-stream>
@include('themes.rework.feed.partials.post-items', ['socialitePosts' => $socialitePosts, 'reportedFeedKeys' => $reportedFeedKeys ?? collect()])
</div>
@if(method_exists($socialitePosts, 'hasMorePages') && $socialitePosts->hasMorePages())
<div class="rework-load-more-wrap" data-rework-load-more-wrap>
<button
    class="btn rework-load-more"
    type="button"
    data-rework-load-more
    data-next-url="{{ $socialitePosts->nextPageUrl() }}"
    data-loading-label="{{ __('ui.rework_loading') }}"
    data-ready-label="{{ __('ui.feed_load_more_posts') }}"
    data-error-label="{{ __('ui.rework_try_again') }}"
>
    <span data-rework-load-more-label>{{ __('ui.feed_load_more_posts') }}</span>
</button>
</div>
@endif
</div>
@include('themes.rework.partials.right-widgets')
</section>
</main>
</div>
@include('themes.rework.feed.partials.post-modals')
<div
    aria-hidden="true"
    class="modal-backdrop profile-edit-backdrop"
    data-profile-edit-modal=""
    data-label-saved="{{ __('ui.profile_saved') }}"
    data-label-save-failed="{{ __('ui.profile_save_failed') }}"
    data-label-validation="{{ __('ui.profile_validation_error') }}"
>
<section aria-labelledby="profile-edit-modal-title" aria-modal="true" class="settings-modal profile-edit-modal settings-has-footer-submit" role="dialog">
<header class="settings-modal-header">
<div>
<span class="settings-eyebrow">{{ __('ui.my_profile') }}</span>
<h2 id="profile-edit-modal-title">{{ __('ui.edit_profile') }}</h2>
<p>{{ __('ui.profile_edit_modal_intro') }}</p>
</div>
<button aria-label="{{ __('ui.close') }}" class="settings-close" data-profile-edit-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<nav aria-label="{{ __('ui.edit_profile') }}" class="settings-tabs profile-edit-tabs">
<button class="is-active" data-profile-edit-tab="basic" type="button"><i aria-hidden="true" class="ph ph-user ph-icon"></i><span>{{ __('ui.profile_basic_info') }}</span></button>
<button data-profile-edit-tab="game" type="button"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i><span>{{ __('ui.profile_game_info') }}</span></button>
<button data-profile-edit-tab="social" type="button"><i aria-hidden="true" class="ph ph-link ph-icon"></i><span>{{ __('ui.profile_social_links') }}</span></button>
<button data-profile-edit-tab="media" type="button"><i aria-hidden="true" class="ph ph-image ph-icon"></i><span>{{ __('ui.profile_media') }}</span></button>
</nav>
<form action="{{ route('profile.update') }}" data-profile-edit-form enctype="multipart/form-data" method="post" novalidate>
@csrf
@method('PUT')
<div class="settings-modal-body profile-edit-modal-body">
<div class="profile-edit-summary">
<div class="profile-edit-cover" style="background-image: linear-gradient(180deg, rgba(17,17,15,.08), rgba(17,17,15,.76)), url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div>
<div class="profile-edit-summary-row">
<img alt="{{ $headerName }}" data-profile-edit-avatar-preview data-rework-profile-avatar src="{{ $viewerAvatar }}">
<div>
<strong data-profile-edit-name-preview data-rework-profile-name>{{ old('name', $viewer?->name) ?: $headerName }}</strong>
<span data-profile-edit-headline-preview data-rework-profile-headline>{{ old('headline', $viewerProfile?->headline) ?: __('ui.profile_no_headline') }}</span>
</div>
<em data-profile-edit-completion>{{ $viewerCompletion }}%</em>
</div>
</div>
<p class="settings-status profile-edit-status" data-profile-edit-status hidden></p>
<section class="settings-panel is-active" data-profile-edit-panel="basic">
<div class="settings-section-head">
<span>{{ __('ui.my_profile') }}</span>
<h3>{{ __('ui.profile_basic_info') }}</h3>
<p>{{ __('ui.profile_completion_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>{{ __('ui.display_name') }}</span><input autocomplete="name" maxlength="80" name="name" required type="text" value="{{ old('name', $viewer?->name) }}"/><small class="settings-field-error" data-profile-edit-error="name"></small></label>
<label><span>{{ __('ui.profile_headline') }}</span><input maxlength="120" name="headline" type="text" value="{{ old('headline', $viewerProfile?->headline) }}"/><small class="settings-field-error" data-profile-edit-error="headline"></small></label>
<label class="profile-edit-wide"><span>{{ __('ui.profile_bio') }}</span><textarea maxlength="1200" name="bio" rows="4">{{ old('bio', $viewerProfile?->bio) }}</textarea><small class="settings-field-error" data-profile-edit-error="bio"></small></label>
<label><span>{{ __('ui.profile_visibility') }}</span><select name="profile_visibility" required><option value="public" @selected($profileVisibility === 'public')>{{ __('ui.visibility_public') }}</option><option value="registered" @selected($profileVisibility === 'registered')>{{ __('ui.visibility_registered') }}</option><option value="private" @selected($profileVisibility === 'private')>{{ __('ui.visibility_private') }}</option></select><small class="settings-field-error" data-profile-edit-error="profile_visibility"></small></label>
</div>
<label class="settings-toggle-row profile-edit-lfg" for="rework-profile-lfg"><input id="rework-profile-lfg" name="is_lfg_available" value="1" @checked($profileIsLfgAvailable) type="checkbox"/><span></span><div><strong>{{ __('ui.profile_lfg_available') }}</strong><p>{{ __('ui.preview_profile_edit_lfg_available_hint') }}</p></div></label>
</section>
<section class="settings-panel" data-profile-edit-panel="game">
<div class="settings-section-head">
<span>Hunt</span>
<h3>{{ __('ui.profile_game_info') }}</h3>
<p>{{ __('ui.preview_profile_edit_hunt_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>{{ __('ui.platform') }}</span><select name="platform"><option value="">{{ __('ui.platform_open') }}</option>@foreach($reworkProfileOptions['platform'] as $option)<option value="{{ $option }}" @selected($profilePlatform === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="platform"></small></label>
<label><span>{{ __('ui.playstyle') }}</span><select name="playstyle"><option value="">{{ __('ui.playstyle_open') }}</option>@foreach($reworkProfileOptions['playstyle'] as $value => $label)<option value="{{ $value }}" @selected($profilePlaystyle === $value || $profilePlaystyle === $label)>{{ $label }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="playstyle"></small></label>
<label><span>{{ __('ui.region') }}</span><select name="region"><option value="">{{ __('ui.preview_profile_edit_region_open') }}</option>@foreach($reworkProfileOptions['region'] as $option)<option value="{{ $option }}" @selected($profileRegion === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="region"></small></label>
<label><span>{{ __('ui.language') }}</span><select name="language"><option value="">{{ __('ui.preview_profile_edit_language_open') }}</option>@foreach($reworkProfileOptions['language'] as $option)<option value="{{ $option }}" @selected($profileLanguage === $option)>{{ $option }}</option>@endforeach</select><small class="settings-field-error" data-profile-edit-error="language"></small></label>
<label><span>{{ __('ui.hunt_role') }}</span><input maxlength="60" name="hunt_role" type="text" value="{{ old('hunt_role', $viewerProfile?->hunt_role) }}"/><small class="settings-field-error" data-profile-edit-error="hunt_role"></small></label>
<label><span>Discord</span><input maxlength="80" name="discord_name" type="text" value="{{ old('discord_name', $viewerProfile?->discord_name) }}"/><small class="settings-field-error" data-profile-edit-error="discord_name"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="social">
<div class="settings-section-head">
<span>{{ __('ui.profile_social_links') }}</span>
<h3>{{ __('ui.profile_social_links') }}</h3>
<p>{{ __('ui.preview_profile_edit_social_text') }}</p>
</div>
<div class="settings-form-grid">
<label><span>Steam URL</span><input maxlength="255" name="steam_url" placeholder="https://steamcommunity.com/id/..." type="url" value="{{ old('steam_url', $viewerProfile?->steam_url) }}"/><small class="settings-field-error" data-profile-edit-error="steam_url"></small></label>
<label><span>Twitch URL</span><input maxlength="255" name="twitch_url" placeholder="https://www.twitch.tv/..." type="url" value="{{ old('twitch_url', $viewerProfile?->twitch_url) }}"/><small class="settings-field-error" data-profile-edit-error="twitch_url"></small></label>
<label><span>YouTube URL</span><input maxlength="255" name="youtube_url" placeholder="https://www.youtube.com/@..." type="url" value="{{ old('youtube_url', $viewerProfile?->youtube_url) }}"/><small class="settings-field-error" data-profile-edit-error="youtube_url"></small></label>
</div>
</section>
<section class="settings-panel" data-profile-edit-panel="media">
<div class="settings-section-head">
<span>{{ __('ui.profile_media') }}</span>
<h3>{{ __('ui.profile_media') }}</h3>
<p>{{ __('ui.profile_upload_hint') }}</p>
</div>
<div class="profile-edit-upload-grid">
<label class="profile-edit-upload" for="rework-profile-avatar"><span>{{ __('ui.profile_avatar') }}</span><img alt="{{ __('ui.profile_avatar') }}" data-profile-edit-avatar-preview src="{{ $viewerAvatar }}"><input accept="image/jpeg,image/png,image/webp" id="rework-profile-avatar" name="avatar" type="file" data-profile-edit-file="avatar"><em>{{ __('ui.avatar_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="avatar"></small></label>
<label class="profile-edit-upload" for="rework-profile-cover"><span>{{ __('ui.profile_cover') }}</span><div style="background-image: url('{{ $viewerCover }}');" data-profile-edit-cover-preview></div><input accept="image/jpeg,image/png,image/webp" id="rework-profile-cover" name="cover" type="file" data-profile-edit-file="cover"><em>{{ __('ui.cover_upload_hint') }}</em><small class="settings-field-error" data-profile-edit-error="cover"></small></label>
</div>
</section>
</div>
<footer class="settings-modal-footer">
<button class="settings-cancel" data-profile-edit-modal-close="" type="button">{{ __('ui.cancel') }}</button>
<button class="settings-save" data-profile-edit-submit="" type="submit">{{ __('ui.save_changes') }}</button>
</footer>
</form>
</section>
</div>
@include('themes.rework.feed.partials.settings-modal')
<script type="application/json" data-rework-i18n>{!! json_encode([
    'composerBodyRequired' => __('ui.rework_composer_body_required'),
    'composerBodyOrMediaRequired' => __('ui.rework_composer_body_or_media_required'),
    'postCreateFailed' => __('ui.rework_post_create_failed'),
    'postSubmit' => __('ui.rework_post_submit'),
    'postSubmitting' => __('ui.rework_post_submitting'),
    'postSave' => __('ui.preview_action_save'),
    'postSaving' => __('ui.rework_saving'),
    'postEditSaveFailed' => __('ui.rework_post_edit_save_failed'),
    'postDeleteClose' => __('ui.rework_post_delete_close_aria'),
    'cancel' => __('ui.preview_action_cancel'),
    'save' => __('ui.preview_action_save'),
    'settingsSaveFailed' => __('ui.rework_settings_save_failed'),
    'settings' => __('ui.settings'),
    'logout' => __('ui.logout'),
    'profileOpen' => __('ui.rework_profile_open'),
    'profileEdit' => __('ui.profile_edit'),
    'settingsSaved' => __('ui.rework_saved'),
    'confirm' => __('ui.rework_confirm'),
    'postUpdateUrlMissing' => __('ui.rework_post_update_url_missing'),
    'postEditPrompt' => __('ui.rework_post_edit_prompt'),
    'postBodyEmpty' => __('ui.rework_post_body_empty'),
    'postEditSaveFailed' => __('ui.rework_post_edit_save_failed'),
    'postDeleteTitle' => __('ui.rework_post_delete_title'),
    'postDeleteText' => __('ui.rework_post_delete_text'),
    'postDeleteQuestion' => __('ui.rework_post_delete_question'),
    'postDeleteContentText' => __('ui.rework_post_delete_content_text'),
    'postDeleteCancel' => __('ui.rework_post_delete_cancel'),
    'postDeleteConfirm' => __('ui.rework_post_delete_confirm'),
    'postDeleteConfirmButton' => __('ui.rework_post_delete_confirm_button'),
    'searchAllResultsFor' => __('ui.rework_search_all_results_for', ['query' => '__query__']),
    'searchNoQuickResults' => __('ui.rework_search_no_quick_results'),
    'searchFullHint' => __('ui.rework_search_full_hint'),
    'searchHit' => __('ui.rework_search_hit'),
    'searchLabel' => __('ui.search'),
    'feelsWithLabel' => __('ui.rework_feels_with_label', ['label' => '__label__']),
    'pollVotes' => __('ui.rework_poll_votes', ['count' => '__count__']),
    'profileMediaCoverSaving' => __('ui.rework_profile_cover_saving'),
    'profileMediaAvatarSaving' => __('ui.rework_profile_avatar_saving'),
    'uploadFailed' => __('ui.rework_upload_failed'),
    'saved' => __('ui.rework_saved'),
    'cupSubmission' => __('ui.rework_cup_submission'),
    'cupUploadStarts' => __('ui.rework_cup_upload_starts'),
    'cupUploadUploading' => __('ui.rework_cup_upload_uploading'),
    'cupUploadUploaded' => __('ui.rework_cup_upload_uploaded', ['percent' => '__percent__']),
    'cupUploadScreenshotUploading' => __('ui.rework_cup_screenshot_uploading'),
    'cupProcessingRunning' => __('ui.rework_cup_processing_running'),
    'cupProcessingText' => __('ui.rework_cup_processing_text'),
    'cupSubmissionProcessed' => __('ui.rework_cup_submission_processed'),
    'cupSubmissionFailed' => __('ui.rework_cup_submission_failed'),
    'cupSubmissionProcessedText' => __('ui.rework_cup_submission_processed_text'),
    'cupUploadSelectScreenshot' => __('ui.rework_cup_upload_select_screenshot'),
    'cupSubmissionChecking' => __('ui.rework_cup_submission_checking'),
    'cupSubmissionCouldNotProcess' => __('ui.rework_cup_submission_could_not_process'),
    'cupUploadNetworkError' => __('ui.rework_cup_upload_network_error'),
    'cupUploadAborted' => __('ui.rework_cup_upload_aborted'),
    'cupSubmissionSave' => __('ui.rework_cup_submission_save'),
    'cupCounted' => __('ui.rework_cup_counted'),
    'cupError' => __('ui.rework_cup_error'),
    'cupReview' => __('ui.rework_cup_review'),
    'cupStatus' => __('ui.rework_cup_status'),
    'cupScore' => __('ui.rework_cup_score'),
    'cupCheck' => __('ui.rework_cup_check'),
    'cupNotSaved' => __('ui.rework_cup_not_saved'),
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
<script defer src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion }}"></script>
<script defer src="{{ asset('assets/socialite/js/hnt-socialite-chat-tabs.js') }}?v={{ $socialiteChatTabsVersion }}"></script>
</body>
</html>

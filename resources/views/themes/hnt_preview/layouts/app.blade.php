<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('themes.hnt_preview.partials.head')
    @stack('head')
</head>
<body class="hnt-preview-body">
    <div class="app-window hnt-preview-shell @yield('app_window_class')" id="hntPreviewApp">
        @include('themes.hnt_preview.partials.sidebar-left')
        @auth
            @include('themes.hnt_preview.partials.notifications-shell')
            @include('themes.hnt_preview.partials.friend-requests-shell')
            @include('themes.hnt_preview.partials.messages-shell')
        @endauth
        @include('themes.hnt_preview.partials.mobile-chrome')

        <main class="@yield('main_class', 'feed-main')" id="hntPreviewMain">
            @yield('content')
        </main>

        @hasSection('right_sidebar')
            @yield('right_sidebar')
        @else
            @include('themes.hnt_preview.partials.sidebar-right')
        @endif
    </div>

    <div class="hnt-chat-tabs-shell" data-hnt-chat-tabs-shell aria-live="polite"></div>

    @auth
        @include('themes.hnt_preview.partials.composer-modal')
    @endauth
    @include('themes.hnt_preview.partials.post-modal')
    @include('themes.hnt_preview.partials.report-modal')
    @include('themes.hnt_preview.partials.likes-modal')
    @include('themes.hnt_preview.partials.lfg-create-modal')
    @include('themes.hnt_preview.partials.lightbox-modal')
    @include('partials.cookie-consent')

    @php
        $hntPreviewI18n = [
        'preview_locale_number' => __('ui.preview_locale_number'),
        'preview_lfg_creating' => __('ui.preview_lfg_creating'),
        'preview_composer_poll_answer_placeholder' => __('ui.preview_composer_poll_answer_placeholder'),
        'feed_video' => __('ui.feed_video'),
        'preview_feed_medium_alt' => __('ui.preview_feed_medium_alt'),
        'remove_media' => __('ui.remove_media'),
        'preview_post_modal_loading' => __('ui.preview_post_modal_loading'),
        'preview_post_modal_loading_subtitle' => __('ui.preview_post_modal_loading_subtitle'),
        'preview_share_link_copied' => __('ui.preview_share_link_copied'),
        'preview_share_post_aria' => __('ui.preview_share_post_aria'),
        'preview_share_default_text' => __('ui.preview_share_default_text'),
        'preview_share_prompt' => __('ui.preview_share_prompt'),
        'preview_share_shared_aria' => __('ui.preview_share_shared_aria'),
        'preview_share_failed' => __('ui.preview_share_failed'),
        'preview_bookmark_save_aria' => __('ui.preview_bookmark_save_aria'),
        'preview_bookmark_remove_aria' => __('ui.preview_bookmark_remove_aria'),
        'preview_bookmark_failed' => __('ui.preview_bookmark_failed'),
        'preview_likes_title' => __('ui.preview_likes_title'),
        'preview_likes_loading' => __('ui.preview_likes_loading'),
        'preview_likes_empty_title' => __('ui.preview_likes_empty_title'),
        'preview_likes_empty_text' => __('ui.preview_likes_empty_text'),
        'preview_likes_load_failed' => __('ui.preview_likes_load_failed'),
        'preview_error_title' => __('ui.preview_error_title'),
        'preview_post_modal_ready_subtitle' => __('ui.preview_post_modal_ready_subtitle'),
        'preview_post_modal_error_subtitle' => __('ui.preview_post_modal_error_subtitle'),
        'preview_comments_load_failed' => __('ui.preview_comments_load_failed'),
        'preview_post_modal_empty' => __('ui.preview_post_modal_empty'),
        'preview_report_default_label' => __('ui.preview_report_default_label'),
        'preview_report_already_reported_aria' => __('ui.preview_report_already_reported_aria'),
        'preview_comment_save_failed' => __('ui.preview_comment_save_failed'),
        'preview_comment_delete_confirm' => __('ui.preview_comment_delete_confirm'),
        'preview_comment_delete_failed' => __('ui.preview_comment_delete_failed'),
        'preview_post_save_failed' => __('ui.preview_post_save_failed'),
        'preview_post_delete_confirm' => __('ui.preview_post_delete_confirm'),
        'preview_post_delete_failed' => __('ui.preview_post_delete_failed'),
        'preview_comment_send_failed' => __('ui.preview_comment_send_failed'),
        'preview_comment_sent' => __('ui.preview_comment_sent'),
        'preview_comment_add_image' => __('ui.preview_comment_add_image'),
        'preview_comment_clear_images' => __('ui.preview_comment_clear_images'),
        'preview_comment_body_or_media_required' => __('ui.preview_comment_body_or_media_required'),
        'preview_comment_media_limit' => __('ui.preview_comment_media_limit'),
        'preview_comment_image_preview' => __('ui.preview_comment_image_preview'),
        'preview_emoji_button' => __('ui.preview_emoji_button'),
        'preview_emoji_picker_title' => __('ui.preview_emoji_picker_title'),
        'preview_emoji_recent' => __('ui.preview_emoji_recent'),
        'preview_emoji_empty_recent' => __('ui.preview_emoji_empty_recent'),
        'preview_emoji_smileys' => __('ui.preview_emoji_smileys'),
        'preview_emoji_gestures' => __('ui.preview_emoji_gestures'),
        'preview_emoji_nature' => __('ui.preview_emoji_nature'),
        'preview_emoji_food' => __('ui.preview_emoji_food'),
        'preview_emoji_activity' => __('ui.preview_emoji_activity'),
        'preview_emoji_objects' => __('ui.preview_emoji_objects'),
        'preview_emoji_symbols' => __('ui.preview_emoji_symbols'),
        'preview_feed_load_more_loading' => __('ui.preview_feed_load_more_loading'),
        'preview_likes_count_many' => __('ui.preview_likes_count_many'),
        'preview_likes_count_one' => __('ui.preview_likes_count_one'),
        'preview_share_button_title' => __('ui.preview_share_button_title'),
        'preview_saved' => __('ui.preview_saved'),
        'preview_save' => __('ui.preview_save'),
        'preview_like_failed' => __('ui.preview_like_failed'),
        'preview_cup_try_again' => __('ui.preview_cup_try_again'),
        'preview_cup_error_time' => __('ui.preview_cup_error_time'),
        'preview_cup_error_text' => __('ui.preview_cup_error_text'),
        'preview_cup_error_title' => __('ui.preview_cup_error_title'),
        'preview_cup_done_time' => __('ui.preview_cup_done_time'),
        'preview_cup_saved_text' => __('ui.preview_cup_saved_text'),
        'preview_cup_saved_title' => __('ui.preview_cup_saved_title'),
        'preview_cup_result_fallback' => __('ui.preview_cup_result_fallback'),
        'preview_cup_check_text' => __('ui.preview_cup_check_text'),
        'preview_cup_check_title' => __('ui.preview_cup_check_title'),
        'preview_cup_estimated_total' => __('ui.preview_cup_estimated_total'),
        'preview_cup_upload_text' => __('ui.preview_cup_upload_text'),
        'preview_cup_upload_title' => __('ui.preview_cup_upload_title'),
        'preview_cup_checking_longer' => __('ui.preview_cup_checking_longer'),
        'preview_cup_checking_remaining' => __('ui.preview_cup_checking_remaining'),
        'preview_cup_checking_estimated' => __('ui.preview_cup_checking_estimated'),
        'preview_cup_no_screenshot' => __('ui.preview_cup_no_screenshot'),
        'preview_loading_short' => __('ui.preview_loading_short'),
        'preview_profile_crop_cover_title' => __('ui.preview_profile_crop_cover_title'),
        'preview_profile_crop_avatar_title' => __('ui.preview_profile_crop_avatar_title'),
        'preview_profile_crop_cover_help' => __('ui.preview_profile_crop_cover_help'),
        'preview_profile_crop_avatar_help' => __('ui.preview_profile_crop_avatar_help'),
        'preview_profile_image_saving' => __('ui.preview_profile_image_saving'),
        'preview_profile_image_process_failed' => __('ui.preview_profile_image_process_failed'),
        'preview_profile_image_saved' => __('ui.preview_profile_image_saved'),
        'preview_profile_image_save_failed' => __('ui.preview_profile_image_save_failed'),
        'preview_profile_posts_load_failed' => __('ui.preview_profile_posts_load_failed'),
        'preview_profile_posts_read_failed' => __('ui.preview_profile_posts_read_failed'),
        'message_chat_open_failed' => __('ui.message_chat_open_failed'),
        'message_send_failed' => __('ui.message_send_failed'),
        'preview_read_more' => __('ui.preview_read_more'),
        'preview_read_less' => __('ui.preview_read_less'),
        'translation_action_short' => __('ui.translation_action_short'),
        'translation_hide' => __('ui.translation_hide'),
        'translation_loading' => __('ui.translation_loading'),
        'translation_error' => __('ui.translation_error'),
        ];
        $hntPreviewSharedI18n = trans('hnt_preview');
    @endphp
    <script>
        window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
        window.HNT_PREVIEW_USER_ID = @json(auth()->id());
        window.HNT_PREVIEW_I18N = Object.assign({}, @json($hntPreviewI18n), @json($hntPreviewSharedI18n));
        window.HNT_PREVIEW_LIVE_BADGES = {
            endpoint: @json(\Illuminate\Support\Facades\Route::has('socialite.header.live-badges') ? route('socialite.header.live-badges') : null),
            notificationsEndpoint: @json(\Illuminate\Support\Facades\Route::has('socialite.header.notifications') ? route('socialite.header.notifications') : null),
            messagesEndpoint: @json(\Illuminate\Support\Facades\Route::has('socialite.header.messages') ? route('socialite.header.messages') : null),
            friendRequestsEndpoint: @json(\Illuminate\Support\Facades\Route::has('socialite.header.friend-requests') ? route('socialite.header.friend-requests') : null),
            interval: 8000,
            shellInterval: 5000,
            chatTabInterval: 4500,
        };
    </script>
    @auth
        @php
            $hhRealtimeConfig = config('hnt-realtime');
            $hhRealtimeEnabled = filled($hhRealtimeConfig['app_key'] ?? null)
                && filled($hhRealtimeConfig['host'] ?? null);
            $hhRealtimePayload = [
                'enabled' => $hhRealtimeEnabled,
                'appKey' => $hhRealtimeConfig['app_key'] ?? null,
                'host' => $hhRealtimeConfig['host'] ?? null,
                'port' => (int) ($hhRealtimeConfig['port'] ?? 443),
                'scheme' => $hhRealtimeConfig['scheme'] ?? 'https',
                'userId' => auth()->id(),
                'authEndpoint' => $hhRealtimeConfig['auth_endpoint'] ?? '/broadcasting/auth',
                'csrfToken' => csrf_token(),
                'endpoints' => [
                    'badges' => \Illuminate\Support\Facades\Route::has('socialite.header.live-badges') ? route('socialite.header.live-badges') : null,
                    'notifications' => \Illuminate\Support\Facades\Route::has('socialite.header.notifications') ? route('socialite.header.notifications') : null,
                    'messages' => \Illuminate\Support\Facades\Route::has('socialite.header.messages') ? route('socialite.header.messages') : null,
                    'friendRequests' => \Illuminate\Support\Facades\Route::has('socialite.header.friend-requests') ? route('socialite.header.friend-requests') : null,
                ],
            ];
        @endphp
        <script>
            window.HH_REALTIME = {!! json_encode($hhRealtimePayload, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
        </script>
    @endauth
    <script src="{{ asset('assets/themes/hnt_preview/preview-shell.js') }}?v=765" defer></script>
    <script src="{{ asset('assets/themes/hnt_preview/comms-dock.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/comms-dock.js')) ?: time() }}" defer></script>
    @auth
        <script src="{{ asset('assets/socialite/js/hnt-socialite-message-typing.js') }}?v=172ee-typing" defer></script>
        <script src="{{ asset('assets/vikinger/js/hnt-realtime.js') }}?v=172ee-typing" defer></script>
        <script src="{{ asset('assets/socialite/js/hnt-presence-heartbeat.js') }}?v=172f-a" defer></script>
    @endauth
    @stack('scripts')
</body>
</html>
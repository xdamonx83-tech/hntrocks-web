<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="hh-socialite-html">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#070605">
    @include('partials.seo-meta')
    @include('partials.favicon')
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/hnt-local-fonts.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/styles.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/tailwind.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/css/style.css') }}?v=440">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/vikinger/css/hunthub-vikinger-start.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/socialite/hnt-socialite-bridge.css') }}">
</head>
<body class="hh-socialite-body">
<div id="wrapper" class="hh-socialite-wrapper">
    @include('partials.socialite.header')
    @include('partials.socialite.sidebar')

    <main id="site__main" class="2xl:ml-[--w-side] xl:ml-[--w-side-sm] p-2.5 h-[calc(100vh-var(--m-top))] mt-[--m-top] hh-socialite-main">
        @yield('content')
    </main>
</div>

@include('partials.report-modal')
@include('partials.app-promo-modal')
@include('partials.cookie-consent')

<script src="{{ asset('assets/socialite/js/uikit.min.js') }}" defer></script>
<script src="{{ asset('assets/socialite/js/simplebar.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/utils/app.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/utils/page-loader.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/utils/liquidify.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/vendor/xm_plugins.min.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/vendor/tiny-slider.min.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/vendor/Chart.bundle.min.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/global/global.hexagons.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/global/global.tooltips.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/global/global.charts.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/global/global.popups.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/form/form.utils.js') }}" defer></script>
<script src="{{ asset('assets/vikinger/js/utils/svg-loader.js') }}" defer></script>
@php
    $hhI18n = [
        'comment_singular' => __('ui.js_i18n_comment_singular'),
        'comment_plural' => __('ui.js_i18n_comment_plural'),
        'reply_singular' => __('ui.js_i18n_reply_singular'),
        'reply_plural' => __('ui.js_i18n_reply_plural'),
        'reply' => __('ui.js_i18n_reply'),
        'just_now' => __('ui.just_now'),
        'edit' => __('ui.js_i18n_edit'),
        'delete' => __('ui.js_i18n_delete'),
        'cancel' => __('ui.js_i18n_cancel'),
        'save' => __('ui.js_i18n_save'),
        'close' => __('ui.js_i18n_aria_close'),
        'next_media' => __('ui.js_i18n_next_media'),
        'reply_to' => __('ui.js_i18n_reply_to'),
        'comment_delete_title' => __('ui.js_i18n_comment_delete_title'),
        'comment_delete_text' => __('ui.js_i18n_comment_delete_text'),
        'no_new_entries' => __('ui.js_i18n_no_new_entries'),
        'message_empty' => __('ui.js_i18n_message_empty'),
        'no_headline' => __('ui.js_i18n_no_headline'),
        'validation_error' => __('ui.js_i18n_validation_error'),
        'profile_saved' => __('ui.js_i18n_profile_saved'),
        'profile_save_failed' => __('ui.js_i18n_profile_save_failed'),
        'add_more_media' => __('ui.js_i18n_add_more_media'),
        'media_preview_alt' => __('ui.js_i18n_media_preview_alt'),
        'remove_media' => __('ui.js_i18n_remove_media'),
        'upload_running' => __('ui.js_i18n_upload_running'),
        'upload_running_dots' => __('ui.js_i18n_upload_running_dots'),
        'upload_preparing' => __('ui.js_i18n_upload_preparing'),
        'upload_failed' => __('ui.js_i18n_upload_failed'),
        'upload_processing' => __('ui.js_i18n_upload_processing'),
        'upload_done_processing' => __('ui.js_i18n_upload_done_processing'),
        'upload_aborted' => __('ui.js_i18n_upload_aborted'),
        'done_open_post' => __('ui.js_i18n_done_open_post'),
        'post_published' => __('ui.js_i18n_post_published'),
        'feed_upload_file_too_large' => __('ui.js_i18n_feed_upload_file_too_large'),
        'feed_upload_too_many_files' => __('ui.js_i18n_feed_upload_too_many_files'),
        'report_user' => __('ui.js_i18n_report_user'),
        'report_feed_post' => __('ui.js_i18n_report_feed_post'),
        'report_feed_comment' => __('ui.js_i18n_report_feed_comment'),
        'reported' => __('ui.js_i18n_reported'),
        'post_reported' => __('ui.js_i18n_post_reported'),
        'report_open_failed' => __('ui.js_i18n_report_open_failed'),
        'sending' => __('ui.js_i18n_sending'),
        'report_success' => __('ui.js_i18n_report_success'),
        'feed_body_empty' => __('ui.js_i18n_feed_body_empty'),
        'post_updated' => __('ui.js_i18n_post_updated'),
        'post_update_failed' => __('ui.js_i18n_post_update_failed'),
        'show_less' => __('ui.js_i18n_show_less'),
        'show_more' => __('ui.js_i18n_show_more'),
        'load_replies' => __('ui.js_i18n_load_replies'),
        'hide_replies' => __('ui.js_i18n_hide_replies'),
        'load_more_comments' => __('ui.js_i18n_load_more_comments'),
        'hide_more_comments' => __('ui.js_i18n_hide_more_comments'),
        'translation_show' => __('ui.translation_show'),
        'translation_hide' => __('ui.translation_hide'),
        'translation_loading' => __('ui.translation_loading'),
        'translation_error' => __('ui.translation_error'),
        'translation_provider_ai' => __('ui.translation_provider_ai'),
    ];
@endphp
<script>
    window.HH_I18N = {!! json_encode($hhI18n, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!};
</script>
<script src="{{ asset('assets/vikinger/js/hunthub-start.js') }}" defer></script>
@auth
    <script src="{{ asset('assets/socialite/js/hnt-presence-heartbeat.js') }}?v=172f-a" defer></script>
@endauth
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>

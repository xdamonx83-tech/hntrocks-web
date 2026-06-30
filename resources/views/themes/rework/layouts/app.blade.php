@php
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $reworkStyleVersion = $reworkStyleVersion ?? (@filemtime(public_path('assets/themes/rework/styles.css')) ?: time());
    $reworkScriptVersion = $reworkScriptVersion ?? (@filemtime(public_path('assets/themes/rework/script.js')) ?: time());
    $reworkBodyClass = trim($__env->yieldContent('body_class'));
    $reworkLeftColClass = trim($__env->yieldContent('left_col_class'));
@endphp
<!DOCTYPE html>
<html lang="{{ $currentLocale }}">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1" name="viewport"/>
<meta name="csrf-token" content="{{ csrf_token() }}"/>
<title>@yield('title', 'HNT.rocks')</title>
<link href="https://fonts.googleapis.com" rel="preconnect"/>
<link crossorigin="" href="https://fonts.gstatic.com" rel="preconnect"/>
<link href="https://fonts.googleapis.com/css2?family=Bai+Jamjuree:wght@400;500;600;700&amp;family=Bakbak+One&amp;family=Montserrat:wght@300;400;500;600;700;800;900&amp;display=swap" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/regular/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/bold/style.css" rel="stylesheet"/>
<link href="https://unpkg.com/@phosphor-icons/web@2.1.2/src/fill/style.css" rel="stylesheet"/>
<link href="{{ \App\Support\HntTheme::asset('styles.css', 'rework') }}?v={{ $reworkStyleVersion }}" rel="stylesheet"/>
@stack('rework-head')
</head>
<body @if($reworkBodyClass !== '') class="{{ $reworkBodyClass }}" @endif>
<div class="app">
@include('themes.rework.partials.sidebar')
<main class="main">
@include('themes.rework.partials.topbar')
<section class="content-grid">
<div class="left-col @if($reworkLeftColClass !== '') {{ $reworkLeftColClass }} @endif">
@yield('content')
</div>
@include('themes.rework.partials.right-widgets')
</section>
</main>
</div>
@include('themes.rework.feed.partials.settings-modal')
@stack('rework-modals')
<script type="application/json" data-rework-i18n>{!! json_encode([
    'settingsSaveFailed' => __('ui.rework_settings_save_failed'),
    'settings' => __('ui.settings'),
    'logout' => __('ui.logout'),
    'profileOpen' => __('ui.rework_profile_open'),
    'profileEdit' => __('ui.profile_edit'),
    'settingsSaved' => __('ui.rework_saved'),
    'searchAllResultsFor' => __('ui.rework_search_all_results_for', ['query' => '__query__']),
    'searchNoQuickResults' => __('ui.rework_search_no_quick_results'),
    'searchFullHint' => __('ui.rework_search_full_hint'),
    'searchHit' => __('ui.rework_search_hit'),
    'searchLabel' => __('ui.search'),
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
<script src="{{ \App\Support\HntTheme::asset('script.js', 'rework') }}?v={{ $reworkScriptVersion }}" defer></script>
@stack('rework-scripts')
</body>
</html>

@extends('themes.hnt_preview.layouts.app')

@php
    $status = (string) $project->status;
    $isFailed = $status === 'failed';
    $stateLabel = match ($status) {
        'queued' => __('ui.moment_studio_processing_state_queued'),
        'rendering' => __('ui.moment_studio_processing_state_rendering'),
        'failed' => __('ui.moment_studio_processing_state_failed'),
        default => __('ui.moment_studio_processing_state_preparing'),
    };
    $stateMessage = match ($status) {
        'queued' => __('ui.moment_studio_processing_message_queued'),
        'rendering' => __('ui.moment_studio_processing_message_rendering'),
        'failed' => __('ui.moment_studio_processing_message_failed'),
        default => __('ui.moment_studio_processing_message_preparing'),
    };
    $viewer = auth()->user();
    $author = $project->user ?: $viewer;
    $authorName = $author?->name ?: $author?->username ?: config('app.name', 'HNT.rocks');
    $caption = $project->caption ?: __('ui.moment_studio_processing_heading');
@endphp

@section('title', __('ui.moment_studio_processing_title') . ' · HNT.rocks')
@section('app_window_class', 'hnt-moments-window hnt-moment-processing-window')
@section('main_class', 'hnt-moments-main')
@section('right_sidebar')
    <aside class="hnt-moments-right-placeholder" hidden></aside>
@endsection

@section('content')
<div class="hnt-moment-screen hnt-moment-processing-reel @if($isFailed) is-failed @endif" data-hnt-moment-processing data-status-url="{{ $statusEndpoint }}">
    <header class="hnt-moment-mobile-topbar" aria-label="{{ __('ui.preview_nav_moments') }}">
        <a class="hnt-moment-mobile-icon" href="{{ $feedUrl }}" aria-label="{{ __('ui.back') }}">
            <i class="ph ph-caret-left" aria-hidden="true"></i>
        </a>
        <strong>{{ __('ui.preview_nav_moments') }}</strong>
        <a class="hnt-moment-mobile-icon" href="{{ $studioUrl }}" aria-label="{{ __('ui.moment_add') }}">
            <i class="ph ph-plus" aria-hidden="true"></i>
        </a>
    </header>

    <section class="hnt-moment-stage-wrap" aria-label="{{ __('ui.moment_studio_processing_title') }}">
        <article class="hnt-moment-stage hnt-moment-processing-stage">
            <div class="hnt-moment-processing-bg" aria-hidden="true"></div>
            <div class="hnt-moment-processing-vignette" aria-hidden="true"></div>

            <div class="hnt-moment-processing-status-card">
                <span class="hnt-moment-processing-kicker">{{ __('ui.moment_studio_processing_kicker') }}</span>
                <div class="hnt-moment-processing-spinner @if($isFailed) is-failed @endif"></div>
                <h1 data-hnt-processing-label>{{ $stateLabel }}</h1>
                <p data-hnt-processing-main-message>{{ $stateMessage }}</p>
                <dl>
                    <div>
                        <dt>{{ __('ui.moment_studio_processing_length') }}</dt>
                        <dd>{{ gmdate('i:s', max(0, (int) $project->total_duration_seconds)) }}</dd>
                    </div>
                    <div>
                        <dt>{{ __('ui.moment_studio_processing_status') }}</dt>
                        <dd data-hnt-processing-meta-label>{{ $stateLabel }}</dd>
                    </div>
                </dl>
                <div class="hnt-moment-processing-note" data-hnt-processing-note>
                    @if($isFailed)
                        {{ __('ui.moment_studio_processing_failed_note') }}
                    @else
                        {{ __('ui.moment_studio_processing_auto_note') }}
                    @endif
                </div>
            </div>

            <div class="hnt-moment-copy hnt-moment-processing-copy">
                @if($author)
                    <a class="hnt-moment-author" href="{{ route('profile.public', $author) }}">
                        <img src="{{ $author->avatarUrl() }}" alt="">
                        <span>{{ $authorName }}</span>
                    </a>
                @else
                    <div class="hnt-moment-author">
                        <span>{{ $authorName }}</span>
                    </div>
                @endif
                <p class="hnt-moment-caption">{!! \App\Support\Hashtag::renderText($caption) !!}</p>
                <p class="hnt-moment-description" data-hnt-processing-message>{{ $stateMessage }}</p>
            </div>
        </article>

        <aside class="hnt-moment-action-rail hnt-moment-processing-actions" aria-label="{{ __('ui.moment_actions') }}">
            <a class="hnt-moment-action-wrap" href="{{ $feedUrl }}" aria-label="{{ __('ui.moment_studio_processing_to_feed') }}">
                <span class="hnt-moment-action"><i class="ph ph-house" aria-hidden="true"></i></span>
                <span>{{ __('ui.moment_studio_processing_to_feed') }}</span>
            </a>
            @if($isFailed)
                <a class="hnt-moment-action-wrap" href="{{ $studioUrl }}" aria-label="{{ __('ui.moment_studio_processing_retry') }}">
                    <span class="hnt-moment-action"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></span>
                    <span>{{ __('ui.moment_studio_processing_retry') }}</span>
                </a>
            @else
                <button class="hnt-moment-action-wrap" type="button" data-hnt-processing-check aria-label="{{ __('ui.moment_studio_processing_check_now') }}">
                    <span class="hnt-moment-action"><i class="ph ph-arrow-clockwise" aria-hidden="true"></i></span>
                    <span>{{ __('ui.moment_studio_processing_check_now') }}</span>
                </button>
            @endif
        </aside>
    </section>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const root = document.querySelector('[data-hnt-moment-processing]');
    if (!root) return;

    const endpoint = root.getAttribute('data-status-url');
    const label = root.querySelector('[data-hnt-processing-label]');
    const metaLabel = root.querySelector('[data-hnt-processing-meta-label]');
    const message = root.querySelector('[data-hnt-processing-message]');
    const mainMessage = root.querySelector('[data-hnt-processing-main-message]');
    const note = root.querySelector('[data-hnt-processing-note]');
    const button = root.querySelector('[data-hnt-processing-check]');
    const spinner = root.querySelector('.hnt-moment-processing-spinner');
    let timer = null;
    let checking = false;

    const applyState = (data) => {
        if (!data || typeof data !== 'object') return;

        if (data.label) {
            if (label) label.textContent = data.label;
            if (metaLabel) metaLabel.textContent = data.label;
        }

        if (data.message) {
            if (message) message.textContent = data.message;
            if (mainMessage) mainMessage.textContent = data.message;
        }

        if (data.status === 'failed') {
            root.classList.add('is-failed');
            spinner?.classList.add('is-failed');
            if (note) note.textContent = @json(__('ui.moment_studio_processing_failed_note'));
            if (timer) window.clearInterval(timer);
            timer = null;
            return;
        }

        if (data.redirect_url) {
            if (note) note.textContent = @json(__('ui.moment_studio_processing_redirecting'));
            window.setTimeout(function () {
                window.location.href = data.redirect_url;
            }, 650);
        }
    };

    const checkStatus = async () => {
        if (!endpoint || checking) return;
        checking = true;

        try {
            const response = await fetch(endpoint, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });

            if (response.ok) {
                applyState(await response.json());
            }
        } catch (error) {
            // Silent: the next poll will try again.
        } finally {
            checking = false;
        }
    };

    button?.addEventListener('click', checkStatus);
    timer = window.setInterval(checkStatus, 4000);
    window.setTimeout(checkStatus, 900);
})();
</script>
@endpush

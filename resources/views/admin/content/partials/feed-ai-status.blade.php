@php
    /** @var \App\Models\FeedPost $post */
    $aiStatus = $post->aiAdminStatusLabel();
    $hasAiInfo = $aiStatus !== null || filled($post->ai_detection_error);
@endphp

@if ($hasAiInfo)
    <div class="hh-admin-ai-status">
        @if ($aiStatus)
            <span class="hh-admin-ai-status-badge {{ $post->ai_detected_possible && ! $post->hasVisibleAiContentLabel() ? 'is-possible' : 'is-confirmed' }}">
                {{ $aiStatus }}
            </span>
        @elseif ($post->ai_detection_error)
            <span class="hh-admin-ai-status-badge is-muted">{{ __('ui.ai_content_check_skipped') }}</span>
        @endif

        @if ($post->ai_detection_reason)
            <small>{{ $post->ai_detection_reason }}</small>
        @endif

        @if ($post->ai_detected_possible || $post->admin_confirmed_ai)
            <div class="hh-admin-ai-status-actions">
                @if (! $post->admin_confirmed_ai)
                    <form method="post" action="{{ route('admin.content.feed-ai-label', $post) }}">
                        @csrf
                        <input type="hidden" name="action" value="confirm">
                        <button class="hh-secondary-button" type="submit">{{ __('ui.ai_content_admin_confirm_label') }}</button>
                    </form>
                @endif

                @if (! $post->ai_user_declared)
                    <form method="post" action="{{ route('admin.content.feed-ai-label', $post) }}">
                        @csrf
                        <input type="hidden" name="action" value="dismiss">
                        <button class="hh-secondary-button" type="submit">{{ __('ui.ai_content_admin_dismiss_label') }}</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
@endif

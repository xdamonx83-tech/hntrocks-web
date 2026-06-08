@php
    $lfgUrl = route('lfg.show', $post);
    $author = $post->user;
    $isPrivate = $post->visibility === 'private';
    $isOpen = $post->status === 'open';
    $isFull = $post->status === 'full';
    $slotsOpen = $post->slotsOpen();
    $pendingCount = (int) ($post->pending_count ?? 0);
    $tags = $post->displayTags();
    $body = filled($post->body) ? $post->body : __('ui.lfg_no_description');
    $canReportLfg = ! $post->isOwner(auth()->user());
@endphp

<div class="user-preview hh-lfg-user-preview">
    <figure class="user-preview-cover liquid">
        <img src="{{ $author->coverUrl() }}" alt="{{ __('ui.lfg_detail_cover_alt', ['user' => $author->name]) }}">
    </figure>

    <div class="user-preview-info">
        <div class="tag-sticker {{ $isOpen ? 'hh-lfg-status-open' : ($isFull ? 'hh-lfg-status-full' : 'hh-lfg-status-closed') }}" title="{{ $post->statusLabel() }}">
            <svg class="tag-sticker-icon {{ $isOpen ? 'icon-join-group' : 'icon-cross' }}">
                <use xlink:href="{{ $isOpen ? '#svg-join-group' : '#svg-cross' }}"></use>
            </svg>
        </div>

        <div class="user-short-description">
            <a class="user-short-description-avatar user-avatar medium no-stats" href="{{ route('profile.public', $author) }}">
                <div class="user-avatar-border"><div class="hexagon-120-130"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-100-110" data-src="{{ $author->avatarUrl() }}"></div></div>
            </a>

            <p class="user-short-description-title"><a href="{{ $lfgUrl }}">{{ $post->title }}</a></p>
            <p class="user-short-description-text">{{ $author->name }} · {{ $post->created_at->diffForHumans() }}</p>
        </div>

        <p class="hh-lfg-card-excerpt">{{ \Illuminate\Support\Str::limit($body, 110) }}</p>

        <div class="user-stats hh-lfg-card-stats">
            <div class="user-stat"><p class="user-stat-title">{{ $slotsOpen }}</p><p class="user-stat-text">{{ __('ui.lfg_stat_free') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $pendingCount }}</p><p class="user-stat-text">{{ __('ui.lfg_stat_requests') }}</p></div>
            <div class="user-stat"><p class="user-stat-title">{{ $post->statusLabel() }}</p><p class="user-stat-text">{{ __('ui.lfg_stat_status') }}</p></div>
        </div>

        <div class="hh-lfg-card-meta">
            @foreach ($tags as $tag)
                <span>{{ $tag }}</span>
            @endforeach
            <span>{{ $post->voiceLabel() }}</span>
            @if ($isPrivate)
                <span>{{ $post->visibilityLabel() }}</span>
            @endif
        </div>

        <div class="user-avatar-list medium reverse centered hh-lfg-slot-list" aria-label="{{ __('ui.lfg_slots_label') }}">
            <a class="user-avatar smaller no-stats" href="{{ route('profile.public', $author) }}" title="{{ $author->name }}">
                <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $author->avatarUrl() }}"></div></div>
            </a>

            @for ($slot = 1; $slot <= max(0, min($slotsOpen, 3)); $slot++)
                <div class="user-avatar smaller no-stats hh-lfg-empty-slot" title="{{ __('ui.lfg_free_slot') }}">
                    <div class="user-avatar-border"><div class="hexagon-34-36"></div></div>
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"></div></div>
                </div>
            @endfor
        </div>

        <div class="user-preview-actions hh-lfg-preview-actions hh-report-card-actions">
            <a class="button secondary full" href="{{ $lfgUrl }}">
                <i class="button-icon hh-ph-action-icon ph ph-users-three" aria-hidden="true"></i>
                {{ __('ui.lfg_view') }}
            </a>
            @if ($canReportLfg)
                <button class="button white hh-report-card-action text-tooltip-tft" type="button" title="{{ __('ui.lfg_report') }}" data-title="{{ __('ui.lfg_report') }}" aria-label="{{ __('ui.lfg_report') }}" data-hh-report-open data-hh-report-type="lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.lfg_report_label', ['title' => $post->title]) }}">
                    <i class="button-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                    <span class="hh-report-button-label">{{ __('ui.report_short') }}</span>
                </button>
            @endif
        </div>
    </div>
</div>

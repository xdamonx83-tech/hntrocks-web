@php
    $sharedViewer = $viewer ?? auth()->user();
    $sharedPost->loadMissing(['user.profile', 'team', 'media.mediaAsset']);
    $sharedCanView = $sharedPost->canBeViewedBy($sharedViewer);
    $sharedProfileRoute = $sharedCanView
        ? ($sharedPost->user_id === $sharedViewer?->id ? route('profile.show') : route('profile.public', $sharedPost->user))
        : '#';
    $sharedMediaItems = $sharedCanView ? $sharedPost->media : collect();
    $sharedFirstMedia = $sharedMediaItems->first();
    $sharedPostTeam = $sharedCanView ? $sharedPost->team : null;
@endphp

<div class="hh-shared-post-preview">
    @if ($sharedCanView)
        <div class="user-status hh-shared-post-author">
            <a class="user-status-avatar" href="{{ $sharedProfileRoute }}">
                <div class="user-avatar small no-outline">
                    <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $sharedPost->user->avatarUrl() }}"></div></div>
                    <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                    <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                    <div class="user-avatar-badge">
                        <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                        <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                        <p class="user-avatar-badge-text">{{ $sharedPost->user->level ?? 1 }}</p>
                    </div>
                </div>
            </a>

            <p class="user-status-title medium">
                <a class="bold" href="{{ $sharedProfileRoute }}">{{ $sharedPost->user->name }}</a>
                @if ($sharedPostTeam)
                    {{ __('ui.post_posted_in_team') }} <a class="bold" href="{{ route('teams.show', $sharedPostTeam) }}">{{ $sharedPostTeam->name }}</a>
                @endif
            </p>
            <p class="user-status-text small">{{ $sharedPost->created_at->diffForHumans() }}</p>
        </div>

        @if ($sharedPost->body)
            <p class="hh-shared-post-text">{!! nl2br(e($sharedPost->body)) !!}</p>
        @endif

        @if ($sharedFirstMedia)
            <div class="hh-shared-post-media">
                @if ($sharedFirstMedia->isImage())
                    <img src="{{ $sharedFirstMedia->url() }}" alt="{{ $sharedFirstMedia->original_name ?: __('ui.shared_post_alt') }}">
                @elseif ($sharedFirstMedia->isVideo())
                    <video src="{{ $sharedFirstMedia->url() }}" controls playsinline preload="metadata"></video>
                @endif
            </div>
        @endif
    @else
        <p class="hh-shared-post-unavailable">{{ __('ui.shared_post_unavailable') }}</p>
    @endif
</div>

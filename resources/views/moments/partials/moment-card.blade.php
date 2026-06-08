@php
    $caption = $moment->caption ?: __('ui.moments_default_caption');
    $username = $moment->user?->username ?: __('ui.hunthub_system');
    $media = $moment->media;
    $cover = $moment->cover;
    $previewImage = null;
    $videoPreview = null;

    if ($cover) {
        $previewImage = $cover->thumbnailUrl();
    } elseif ($media && filled($media->thumbnail_path)) {
        $previewImage = $media->thumbnailUrl();
    } elseif ($media && $media->isImage()) {
        $previewImage = $media->thumbnailUrl();
    } elseif ($media && $media->isVideo()) {
        $videoPreview = $media->url();
    }
@endphp

<article class="video-box hh-moment-video-card">
    <a class="video-box-cover hh-moment-video-cover" href="{{ route('moments.show', $moment) }}" aria-label="{{ __('ui.moments_open_label', ['title' => $caption]) }}">
        <figure class="video-box-cover-image hh-moment-video-image">
            @if ($previewImage)
                <img src="{{ $previewImage }}" alt="{{ $caption }}" loading="lazy">
            @elseif ($videoPreview)
                <video class="hh-moment-video-preview" src="{{ $videoPreview }}#t=0.15" muted playsinline preload="metadata" aria-label="{{ $caption }}"></video>
            @else
                <span class="hh-moment-video-fallback" aria-hidden="true"></span>
            @endif
        </figure>

        <span class="hh-moment-video-shade" aria-hidden="true"></span>

        <span class="play-button hh-moment-video-play" aria-hidden="true">
            <svg class="play-button-icon icon-play"><use xlink:href="#svg-play"></use></svg>
        </span>

        <span class="hh-moment-video-format">9:16</span>

        <span class="hh-moment-video-author">
            <img src="{{ $moment->user->avatarUrl() }}" alt="">
            <span>{{ '@'.$username }}</span>
        </span>
    </a>

    <div class="video-box-info hh-moment-video-info">
        <p class="video-box-title hh-moment-video-title">
            <a href="{{ route('moments.show', $moment) }}">{{ \Illuminate\Support\Str::limit($caption, 58) }}</a>
        </p>
        <p class="video-box-text hh-moment-video-time">{{ $moment->created_at->diffForHumans() }}</p>

        <div class="hh-moment-video-meta" aria-label="{{ __('ui.moments_stats_label') }}">
            <span>{{ __('ui.moments_likes_short', ['count' => $moment->likes_count]) }}</span>
            <span>{{ __('ui.moments_comments_short', ['count' => $moment->comments_count]) }}</span>
            <span>{{ __('ui.moments_saves_short', ['count' => $moment->bookmarks_count]) }}</span>
        </div>
    </div>
</article>

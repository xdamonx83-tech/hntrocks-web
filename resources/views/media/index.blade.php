@extends('layouts.app')

@section('title', __('ui.media_library'))

@section('content')
@php
    $activeType = $filters['type'] ?? '';
    $activeContext = $filters['context'] ?? '';
    $formatBytes = static function (int $bytes): string {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }

        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1).' MB';
        }

        if ($bytes >= 1024) {
            return round($bytes / 1024).' KB';
        }

        return $bytes.' B';
    };
    $typeOptions = [
        '' => __('ui.media_all'),
        'image' => __('ui.images'),
        'video' => __('ui.videos'),
        'file' => __('ui.files'),
    ];
    $contextOptions = [
        '' => __('ui.media_all_contexts'),
        'library' => __('ui.media_library'),
        'feed' => __('ui.feed'),
        'profile_avatar' => __('ui.media_profile_avatar'),
        'profile_cover' => __('ui.media_profile_cover'),
        'profile' => __('ui.media_profile'),
        'team_avatar' => __('ui.media_team_avatar'),
        'team_cover' => __('ui.media_team_cover'),
        'team' => __('ui.visibility_team'),
        'messages' => __('ui.media_messages'),
        'moments' => 'Moments',
        'cups' => 'Cups',
    ];
@endphp

<div class="section-header hh-media-library-header">
    <div class="section-header-info">
        <p class="section-pretitle">{{ __('ui.media_manage_uploads') }}</p>
        <h2 class="section-title">{{ __('ui.media_library') }}</h2>
    </div>

    <div class="section-header-actions">
        <a class="section-header-action" href="#hh-media-upload">{{ __('ui.media_upload_action') }}</a>
        <p class="section-header-action">{{ __('ui.results_count', ['count' => $assets->total()]) }}</p>
    </div>
</div>

@if (session('status'))
    <div class="hh-alert hh-alert-success hh-media-library-alert">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger hh-media-library-alert">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-3-9 medium-space hh-media-library-grid">
    <aside class="hh-media-library-sidebar">
        <div id="hh-media-upload" class="widget-box hh-media-library-upload-widget">
            <p class="widget-box-title">{{ __('ui.media_upload_title') }}</p>

            <div class="widget-box-content">
                <p class="hh-media-library-widget-text">{{ __('ui.media_upload_intro') }}</p>

                <form class="form hh-media-library-form" method="post" action="{{ route('media.store') }}" enctype="multipart/form-data">
                    @csrf

                    <label class="hh-media-library-dropzone" for="media_files">
                        <i class="hh-ph-action-icon ph ph-upload-simple" aria-hidden="true"></i>
                        <span>
                            <strong>{{ __('ui.media_select_files') }}</strong>
                            <small>{{ __('ui.media_limit_hint', ['count' => $uploadLimits['count'], 'mb' => $uploadLimits['file_mb']]) }}</small>
                        </span>
                    </label>
                    <input id="media_files" class="hh-visually-hidden" type="file" name="files[]" accept="image/*,video/mp4,video/webm,video/quicktime,.pdf,.txt" multiple required>

                    <div class="form-row">
                        <div class="form-item">
                            <div class="form-select">
                                <label for="media_context">{{ __('ui.media_context') }}</label>
                                <select id="media_context" name="context">
                                    <option value="library">{{ __('ui.media_library') }}</option>
                                    <option value="feed">Feed</option>
                                    <option value="profile">{{ __('ui.media_profile') }}</option>
                                    <option value="team">Team</option>
                                    <option value="messages">{{ __('ui.media_messages') }}</option>
                                    <option value="moments">Moments</option>
                                    <option value="cups">Cups</option>
                                </select>
                                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-item">
                            <div class="form-select">
                                <label for="media_visibility">{{ __('ui.visibility') }}</label>
                                <select id="media_visibility" name="visibility">
                                    <option value="registered">{{ __('ui.visibility_registered_users') }}</option>
                                    <option value="private">{{ __('ui.visibility_private') }}</option>
                                    <option value="public">{{ __('ui.visibility_public') }}</option>
                                </select>
                                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                            </div>
                        </div>
                    </div>

                    <button class="button medium primary hh-media-library-submit" type="submit">
                        <span>{{ __('ui.upload') }}</span>
                        <i class="hh-ph-action-icon ph ph-arrow-up-right" aria-hidden="true"></i>
                    </button>
                </form>
            </div>
        </div>

        <div class="widget-box hh-media-library-filter-widget">
            <p class="widget-box-title">{{ __('ui.filter') }}</p>

            <div class="widget-box-content">
                <form class="form hh-media-library-filter-form" method="get" action="{{ route('media.index') }}">
                    <div class="form-row">
                        <div class="form-item">
                            <div class="form-select">
                                <label for="media_type">{{ __('ui.media_type') }}</label>
                                <select id="media_type" name="type">
                                    @foreach ($typeOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($activeType === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-item">
                            <div class="form-select">
                                <label for="media_context_filter">{{ __('ui.media_context') }}</label>
                                <select id="media_context_filter" name="context">
                                    @foreach ($contextOptions as $value => $label)
                                        <option value="{{ $value }}" @selected($activeContext === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                            </div>
                        </div>
                    </div>

                    <div class="hh-media-library-filter-actions">
                        <button class="button small secondary" type="submit">{{ __('ui.filter_apply') }}</button>
                        @if ($activeType !== '' || $activeContext !== '')
                            <a class="button small white" href="{{ route('media.index') }}">{{ __('ui.reset') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <div class="widget-box hh-media-library-stats-widget">
            <p class="widget-box-title">{{ __('ui.summary') }}</p>

            <div class="widget-box-content">
                <div class="hh-media-library-stats">
                    <div>
                        <strong>{{ $stats['total'] }}</strong>
                        <span>{{ __('ui.media') }}</span>
                    </div>
                    <div>
                        <strong>{{ $stats['images'] }}</strong>
                        <span>{{ __('ui.images') }}</span>
                    </div>
                    <div>
                        <strong>{{ $stats['videos'] }}</strong>
                        <span>{{ __('ui.videos') }}</span>
                    </div>
                    <div>
                        <strong>{{ $formatBytes((int) $stats['storage_bytes']) }}</strong>
                        <span>{{ __('ui.storage') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <section class="hh-media-library-content">
        <div class="section-header hh-media-library-results-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ $typeOptions[$activeType] ?? __('ui.media_all') }} · {{ $contextOptions[$activeContext] ?? __('ui.media_all_contexts') }}</p>
                <h2 class="section-title">{{ __('ui.my_media') }}</h2>
            </div>
        </div>

        @if ($assets->count())
            <div class="grid grid-3-3-3 centered-on-mobile hh-media-library-items">
                @foreach ($assets as $asset)
                    @php
                        $isLinked = $asset->isLinkedToContent();
                        $isVideo = $asset->isVideo();
                        $isImage = $asset->isImage();
                        $title = $asset->original_name ?: 'Medium';
                    @endphp

                    <article class="hh-media-library-item {{ $isImage ? 'is-image' : ($isVideo ? 'is-video' : 'is-file') }}">
                        <a class="hh-media-library-preview" href="{{ $asset->url() }}" target="_blank" rel="noopener" title="{{ $title }}">
                            @if ($isImage)
                                <img src="{{ $asset->thumbnailUrl() }}" alt="{{ $asset->alt_text ?: $title }}">
                            @elseif ($isVideo)
                                <video src="{{ $asset->url() }}" preload="metadata" muted playsinline></video>
                                <span class="hh-media-library-play" aria-hidden="true">
                                    <i class="hh-ph-action-icon ph ph-play-fill"></i>
                                </span>
                            @else
                                <span class="hh-media-library-file-icon">
                                    <i class="hh-ph-action-icon ph ph-file"></i>
                                    <small>{{ strtoupper($asset->extension ?: 'FILE') }}</small>
                                </span>
                            @endif
                        </a>

                        <div class="hh-media-library-info">
                            <p class="hh-media-library-title" title="{{ $title }}">{{ \Illuminate\Support\Str::limit($title, 42) }}</p>
                            <p class="hh-media-library-meta">{{ $asset->contextLabel() }} · {{ $asset->readableSize() }}</p>
                            <p class="hh-media-library-meta">
                                @if ($asset->width && $asset->height)
                                    {{ $asset->width }} × {{ $asset->height }} px
                                @elseif ($asset->duration_seconds)
                                    {{ gmdate($asset->duration_seconds >= 3600 ? 'H:i:s' : 'i:s', $asset->duration_seconds) }} Min.
                                @else
                                    {{ $asset->created_at?->diffForHumans() }}
                                @endif
                            </p>
                        </div>

                        <div class="hh-media-library-actions">
                            <a class="hh-media-library-action text-tooltip-tft-medium" data-title="{{ __('ui.open') }}" href="{{ $asset->url() }}" target="_blank" rel="noopener" aria-label="{{ __('ui.open') }}">
                                <i class="hh-ph-action-icon ph ph-arrow-square-out"></i>
                                <span>{{ __('ui.open') }}</span>
                            </a>

                            <form method="post" action="{{ route('media.destroy', $asset) }}" onsubmit="return confirm('{{ __('ui.media_delete_confirm') }}')">
                                @csrf
                                @method('DELETE')
                                <button class="hh-media-library-action is-danger text-tooltip-tft-medium" data-title="{{ $isLinked ? __('ui.linked_to_content') : __('ui.delete') }}" type="submit" @disabled($isLinked) aria-label="{{ __('ui.delete') }}">
                                    <i class="hh-ph-action-icon ph ph-trash"></i>
                                    <span>{{ __('ui.delete') }}</span>
                                </button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @else
            <div class="widget-box hh-media-library-empty">
                <p class="widget-box-title">{{ __('ui.no_media_found') }}</p>
                <p class="widget-box-text">{{ __('ui.no_media_text') }}</p>
                @if ($activeType !== '' || $activeContext !== '')
                    <a class="button small white" href="{{ route('media.index') }}">{{ __('ui.reset_filters') }}</a>
                @endif
            </div>
        @endif

        @if ($assets->hasPages())
            <nav class="hh-pagination hh-media-library-pagination" aria-label="{{ __('ui.media_navigation') }}">
                @if ($assets->onFirstPage())
                    <span class="is-disabled">{{ __('ui.pagination_previous') }}</span>
                @else
                    <a href="{{ $assets->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
                @endif

                <span>{{ __('ui.page_x_of_y', ['current' => $assets->currentPage(), 'last' => $assets->lastPage()]) }}</span>

                @if ($assets->hasMorePages())
                    <a href="{{ $assets->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
                @else
                    <span class="is-disabled">{{ __('ui.pagination_next') }}</span>
                @endif
            </nav>
        @endif
    </section>
</div>
@endsection

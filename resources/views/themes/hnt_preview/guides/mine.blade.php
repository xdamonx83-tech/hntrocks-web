@extends('themes.hnt_preview.guides.layout')

@section('title', __('guides_mine.title').' · HNT.ROCKS')
@section('body_class', 'my-guides-page')
@section('skip_guides_base_styles', '1')

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-index-demo.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-index-demo.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-my-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-my-live.css')) ?: time() }}" rel="stylesheet">
@endpush

@push('scripts')
<script src="{{ asset('assets/themes/hnt_preview/guides/guides-my-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-my-live.js')) ?: time() }}" defer></script>
@endpush

@php
    $statusOrder = ['all', 'draft', 'pending_review', 'changes_requested', 'published', 'rejected', 'archived'];
    $statusClasses = [
        'draft' => 'draft',
        'pending_review' => 'review',
        'changes_requested' => 'changes',
        'published' => 'published',
        'rejected' => 'rejected',
        'archived' => 'archived',
    ];
    $reputationTarget = 500;
    $reputationProgress = min(100, max(0, (int) round(($guideReputation / $reputationTarget) * 100)));
    $reputationRemaining = max(0, $reputationTarget - $guideReputation);
@endphp

@section('content')
<section class="my-guides-hero">
    <div>
        <span>{{ __('guides_mine.kicker') }}</span>
        <h1>{{ __('guides_mine.title') }}</h1>
        <p>{{ __('guides_mine.intro') }}</p>
    </div>

    <form action="{{ route('guides.store') }}" method="post">
        @csrf
        <button class="my-guides-create" type="submit"><i class="ph ph-plus" aria-hidden="true"></i> {{ __('guides_mine.create') }}</button>
    </form>

    <div class="my-guides-stats" aria-label="{{ __('guides_mine.title') }}">
        <article><strong>{{ number_format($totalGuides, 0, ',', '.') }}</strong><span>{{ __('guides_mine.stats.total') }}</span></article>
        <article><strong>{{ number_format((int) ($statusCounts['pending_review'] ?? 0), 0, ',', '.') }}</strong><span>{{ __('guides_mine.stats.review') }}</span></article>
        <article><strong>{{ number_format($helpfulTotal, 0, ',', '.') }}</strong><span>{{ __('guides_mine.stats.helpful') }}</span></article>
        <article><strong>{{ number_format($guideReputation, 0, ',', '.') }}</strong><span>{{ __('guides_mine.stats.reputation') }}</span></article>
    </div>
</section>

<section class="my-guides-layout">
    <aside class="my-guides-side">
        <section>
            <header>
                <div><span>{{ __('guides_mine.sidebar.kicker') }}</span><h2>{{ __('guides_mine.sidebar.title') }}</h2></div>
                <b>{{ number_format($totalGuides, 0, ',', '.') }}</b>
            </header>
            <nav aria-label="{{ __('guides_mine.sidebar.title') }}">
                @foreach($statusOrder as $statusKey)
                    @php
                        $statusParams = request()->except('page', 'status');
                        if ($statusKey !== 'all') {
                            $statusParams['status'] = $statusKey;
                        }
                    @endphp
                    <a @class(['active' => $activeStatus === $statusKey]) href="{{ route('guides.mine', $statusParams) }}">
                        <span>{{ __('guides_mine.status.'.$statusKey) }}</span>
                        <b>{{ number_format((int) ($statusCounts[$statusKey] ?? 0), 0, ',', '.') }}</b>
                    </a>
                @endforeach
            </nav>
        </section>

        <section class="my-reputation-card">
            <span>{{ __('guides_mine.reputation.kicker') }}</span>
            <div class="guide-reputation-ring">
                <strong>{{ number_format($guideReputation, 0, ',', '.') }}</strong>
                <span>{{ __('guides_mine.reputation.points') }}</span>
            </div>
            <h2>{{ $guideReputation >= $reputationTarget ? __('guides_mine.reputation.expert') : __('guides_mine.reputation.author') }}</h2>
            <p>{{ $guideReputation >= $reputationTarget ? __('guides_mine.badges.unlocked') : __('guides_mine.reputation.remaining', ['points' => number_format($reputationRemaining, 0, ',', '.')]) }}</p>
            <div class="my-reputation-progress"><i style="width:{{ $reputationProgress }}%"></i></div>
        </section>
    </aside>

    <main class="my-guides-main">
        <header class="my-guides-toolbar">
            <div>
                <span>{{ __('guides_mine.toolbar.kicker') }}</span>
                <h2>{!! trans_choice('guides_mine.toolbar.count', $guides->total(), ['count' => '<b>'.number_format($guides->total(), 0, ',', '.').'</b>']) !!}</h2>
            </div>

            <form class="my-guides-search-form" action="{{ route('guides.mine') }}" method="get" data-my-guides-filter>
                @if($activeStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $activeStatus }}">
                @endif
                <div class="guide-search">
                    <i class="ph ph-magnifying-glass" aria-hidden="true"></i>
                    <input name="q" value="{{ $search }}" placeholder="{{ __('guides_mine.toolbar.search') }}" autocomplete="off">
                </div>
                <select name="sort" aria-label="{{ __('guides_mine.toolbar.updated') }}" data-my-guides-sort>
                    <option value="updated" @selected($activeSort === 'updated')>{{ __('guides_mine.toolbar.updated') }}</option>
                    <option value="status" @selected($activeSort === 'status')>{{ __('guides_mine.toolbar.status') }}</option>
                    <option value="helpful" @selected($activeSort === 'helpful')>{{ __('guides_mine.toolbar.helpful') }}</option>
                </select>
            </form>
        </header>

        <section class="my-guide-list" id="myGuideList">
            @forelse($guides as $guide)
                @php
                    $workingRevision = $guide->workingRevision;
                    $publishedRevision = $guide->publishedRevision;
                    $revision = $workingRevision ?: $publishedRevision;
                    $coverUrl = $revision?->cover_media_id ? route('guides.media.show', $revision->cover_media_id) : null;
                    $hasPublicVersion = $publishedRevision !== null;
                    $isPublishedWithDraft = $hasPublicVersion && $guide->status === 'draft' && $workingRevision !== null;
                    $displayStatusKey = $isPublishedWithDraft ? 'published_with_draft' : $guide->status;
                    $statusClass = $isPublishedWithDraft ? 'published' : ($statusClasses[$guide->status] ?? 'draft');
                    $blocks = collect((array) ($workingRevision?->content_blocks ?? $revision?->content_blocks ?? []));
                    $completeBlocks = $blocks->filter(function ($block): bool {
                        $block = is_array($block) ? $block : [];
                        $type = (string) ($block['type'] ?? '');
                        return match ($type) {
                            'heading', 'paragraph' => trim((string) ($block['text'] ?? '')) !== '',
                            'steps', 'list' => collect((array) ($block['items'] ?? []))->filter(fn ($item) => trim((string) $item) !== '')->isNotEmpty(),
                            'image' => ! empty($block['media_id']),
                            'notice', 'warning' => trim((string) ($block['title'] ?? '')) !== '' && trim((string) ($block['text'] ?? '')) !== '',
                            default => false,
                        };
                    })->count();
                    $completionTotal = 4 + max(1, $blocks->count());
                    $completionDone = ($revision?->title ? 1 : 0)
                        + ($revision?->summary ? 1 : 0)
                        + ($revision?->category_id ? 1 : 0)
                        + ($revision?->cover_media_id ? 1 : 0)
                        + $completeBlocks;
                    $completion = min(100, (int) round(($completionDone / $completionTotal) * 100));
                    $moderationReason = $workingRevision?->moderation_reason ?: $revision?->moderation_reason;
                @endphp

                <article class="my-guide-row {{ $statusClass }}" data-my-guide data-status="{{ $guide->status }}">
                    @if($coverUrl)
                        <img class="my-guide-cover-image" src="{{ $coverUrl }}" alt="">
                    @else
                        <div class="my-guide-cover-placeholder"><i class="ph ph-book-open-text" aria-hidden="true"></i></div>
                    @endif

                    <div class="my-guide-main">
                        <div>
                            <span class="guide-status {{ $statusClass }}">{{ __('guides_mine.status_single.'.$displayStatusKey) }}</span>
                            <small>
                                @if($guide->status === 'pending_review' && $workingRevision?->submitted_at)
                                    {{ __('guides_mine.row.submitted', ['time' => $workingRevision->submitted_at->diffForHumans()]) }}
                                @else
                                    {{ __('guides_mine.row.last_edited', ['time' => $guide->updated_at?->diffForHumans()]) }}
                                @endif
                            </small>
                        </div>

                        <h3>{{ $revision?->title ?: __('guides.editor.title_placeholder') }}</h3>

                        @if($guide->status === 'changes_requested' && $moderationReason)
                            <div class="my-guide-reason">
                                <strong>{{ __('guides_mine.row.moderation_reason') }}</strong>
                                <p>{{ $moderationReason }}</p>
                            </div>
                        @elseif($guide->status === 'pending_review')
                            <p>{{ __('guides_mine.row.review_text') }}</p>
                            <div class="my-guide-timeline">
                                <span class="done">{{ __('guides_mine.row.decision_draft') }}</span>
                                <span class="active">{{ __('guides_mine.row.decision_review') }}</span>
                                <span>{{ __('guides_mine.row.decision_result') }}</span>
                            </div>
                        @elseif($hasPublicVersion)
                            <p>{{ __('guides_mine.row.published_text') }}</p>
                            <div class="my-guide-version-pair">
                                <span><b>{{ number_format((float) $publishedRevision->version, 1, ',', '.') }}</b> {{ __('guides_mine.row.public') }}</span>
                                @if($workingRevision)
                                    <span><b>{{ number_format((float) $workingRevision->version, 1, ',', '.') }}</b> {{ __('guides_mine.row.working_draft') }}</span>
                                @endif
                            </div>
                        @else
                            <p>{{ $revision?->summary ?: __('guides.status_text.'.$guide->status) }}</p>
                            @if($guide->status === 'draft')
                                <div class="my-guide-progress"><i style="width:{{ $completion }}%"></i></div>
                            @elseif($guide->status === 'archived')
                                <p class="my-guide-secondary-copy">{{ __('guides_mine.row.archived_text') }}</p>
                            @endif
                        @endif
                    </div>

                    <div class="my-guide-stats">
                        @if($guide->status === 'draft' && ! $hasPublicVersion)
                            <strong>{{ $completion }}%</strong><span>{{ __('guides_mine.row.complete') }}</span>
                        @elseif($guide->status === 'pending_review')
                            <strong>{{ number_format((float) ($revision?->version ?? 1), 1, ',', '.') }}</strong><span>{{ __('guides_mine.row.version', ['version' => '']) }}</span>
                        @elseif($guide->status === 'changes_requested')
                            <strong>{{ $moderationReason ? 1 : 0 }}</strong><span>{{ __('guides_mine.row.points_open') }}</span>
                        @elseif($hasPublicVersion)
                            <strong>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</strong><span>{{ __('guides_mine.stats.helpful') }}</span>
                        @elseif($guide->status === 'archived')
                            <strong>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</strong><span>{{ __('guides_mine.row.previous_helpful') }}</span>
                        @else
                            <strong>0</strong><span>{{ __('guides_mine.row.public_count') }}</span>
                        @endif
                    </div>

                    <div class="my-guide-actions">
                        @can('update', $guide)
                            <a href="{{ route('guides.edit', $guide) }}">{{ $guide->status === 'changes_requested' ? __('guides_mine.actions.changes') : ($hasPublicVersion ? __('guides_mine.actions.new_version') : __('guides_mine.actions.edit')) }}</a>
                        @endcan

                        @if($revision)
                            <a class="secondary" href="{{ route('guides.preview', $guide) }}">{{ __('guides_mine.actions.preview') }}</a>
                        @endif

                        @if($guide->isPublished())
                            <a class="secondary" href="{{ route('guides.show', $guide) }}">{{ __('guides_mine.actions.open') }}</a>
                        @endif

                        @if($guide->status === 'pending_review')
                            <form action="{{ route('guides.withdraw', $guide) }}" method="post">
                                @csrf
                                <button class="secondary" type="submit">{{ __('guides_mine.actions.withdraw') }}</button>
                            </form>
                        @endif

                        @can('delete', $guide)
                            <button
                                class="icon danger"
                                type="button"
                                title="{{ __('guides_mine.actions.delete') }}"
                                aria-label="{{ __('guides_mine.actions.delete') }}"
                                data-guide-delete
                                data-guide-delete-url="{{ route('guides.destroy', $guide) }}"
                                data-guide-delete-message="{{ __('guides_mine.delete_dialog.text', ['title' => $revision?->title ?: __('guides.editor.title_placeholder')]) }}"
                            ><i class="ph ph-trash" aria-hidden="true"></i></button>
                        @endcan
                    </div>
                </article>
            @empty
                <div class="guide-empty guide-empty-visible">
                    <i class="ph ph-books" aria-hidden="true"></i>
                    <h3>{{ __('guides_mine.empty.title') }}</h3>
                    <p>{{ __('guides_mine.empty.text') }}</p>
                </div>
            @endforelse
        </section>

        @if($guides->hasPages())
            <div class="my-guides-pagination">{{ $guides->links() }}</div>
        @endif
    </main>

    <aside class="my-guides-right">
        <section class="my-guide-notification">
            <span>{{ __('guides_mine.latest.kicker') }}</span>
            @if($latestDecision)
                @php
                    $latestRevision = $latestDecision->workingRevision ?: $latestDecision->publishedRevision;
                    $latestCanEdit = in_array($latestDecision->status, ['changes_requested', 'rejected'], true);
                    $latestUrl = $latestCanEdit
                        ? route('guides.edit', $latestDecision)
                        : ($latestDecision->isPublished() ? route('guides.show', $latestDecision) : route('guides.preview', $latestDecision));
                @endphp
                <h2>{{ __('guides_mine.status_single.'.$latestDecision->status) }}</h2>
                <p>„{{ $latestRevision?->title ?: __('guides.editor.title_placeholder') }}“</p>
                <a href="{{ $latestUrl }}">{{ $latestCanEdit ? __('guides_mine.latest.edit') : __('guides_mine.latest.open') }} <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
            @else
                <h2>{{ __('guides_mine.latest.empty_title') }}</h2>
                <p>{{ __('guides_mine.latest.empty_text') }}</p>
            @endif
        </section>

        <section class="my-version-widget">
            <span>{{ __('guides_mine.versions.kicker') }}</span>
            <h2>{{ __('guides_mine.versions.title') }}</h2>
            @if($versionGuide?->publishedRevision)
                <div><strong>{{ number_format((float) $versionGuide->publishedRevision->version, 1, ',', '.') }}</strong><span>{{ __('guides_mine.versions.public') }}</span></div>
                <i></i>
                <div><strong>{{ $versionGuide->workingRevision ? number_format((float) $versionGuide->workingRevision->version, 1, ',', '.') : '—' }}</strong><span>{{ $versionGuide->workingRevision ? __('guides_mine.versions.draft') : __('guides_mine.versions.none') }}</span></div>
            @else
                <div><strong>—</strong><span>{{ __('guides_mine.versions.public') }}</span></div>
                <i></i>
                <div><strong>—</strong><span>{{ __('guides_mine.versions.none') }}</span></div>
            @endif
            <p>{{ __('guides_mine.versions.text') }}</p>
        </section>

        <section class="my-badges-widget">
            <span>{{ __('guides_mine.badges.kicker') }}</span>
            <h2>{{ __('guides_mine.badges.title') }}</h2>
            <div>
                <article @class(['locked' => $publishedCount < 1])><b>GA</b><span><strong>{{ __('guides_mine.badges.author') }}</strong><small>{{ $publishedCount >= 1 ? __('guides_mine.badges.unlocked') : $publishedCount.' / 1' }}</small></span></article>
                <article @class(['locked' => $helpfulGuideCount < 10])><b>10</b><span><strong>{{ __('guides_mine.badges.helpful_guides') }}</strong><small>{{ min(10, $helpfulGuideCount) }} / 10</small></span></article>
                <article @class(['locked' => $guideReputation < $reputationTarget])><b>CE</b><span><strong>{{ __('guides_mine.badges.expert') }}</strong><small>{{ number_format(min($reputationTarget, $guideReputation), 0, ',', '.') }} / {{ number_format($reputationTarget, 0, ',', '.') }}</small></span></article>
            </div>
        </section>
    </aside>
</section>

<dialog class="my-guide-delete-dialog" data-guide-delete-dialog aria-labelledby="guideDeleteTitle">
    <div class="my-guide-delete-dialog-card">
        <header>
            <div><span>{{ __('guides_mine.delete_dialog.kicker') }}</span><h2 id="guideDeleteTitle">{{ __('guides_mine.delete_dialog.title') }}</h2></div>
            <button type="button" data-guide-delete-close aria-label="{{ __('guides_mine.delete_dialog.cancel') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
        </header>
        <p data-guide-delete-copy></p>
        <small><i class="ph ph-lock" aria-hidden="true"></i>{{ __('guides_mine.delete_dialog.note') }}</small>
        <footer>
            <button class="secondary" type="button" data-guide-delete-close>{{ __('guides_mine.delete_dialog.cancel') }}</button>
            <form method="post" data-guide-delete-form>
                @csrf
                @method('DELETE')
                <button class="danger" type="submit"><i class="ph ph-trash" aria-hidden="true"></i>{{ __('guides_mine.delete_dialog.confirm') }}</button>
            </form>
        </footer>
    </div>
</dialog>
@endsection

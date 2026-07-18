@extends('themes.hnt_preview.guides.layout')

@section('title', $revision->title.' · HNT.ROCKS')
@section('meta_description', $revision->summary)
@section('robots', $isPreview ? 'noindex,nofollow' : 'index,follow')
@section('canonical', $isPreview ? route('guides.preview', $guide) : route('guides.show', $guide))
@section('body_class', 'guides-detail-live')
@section('skip_guides_base_styles', '1')

@if($revision->cover_media_id)
    @section('og_image', route('guides.media.show', $revision->cover_media_id))
@endif

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-detail-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-detail-live.css')) ?: time() }}" rel="stylesheet">
@endpush

@php
    $difficultyLabels = [
        'beginner' => 'Anfänger',
        'advanced' => 'Fortgeschritten',
        'expert' => 'Experte',
    ];
    $platformLabels = [
        'all' => 'Alle Plattformen',
        'pc' => 'PC',
        'playstation' => 'PlayStation',
        'xbox' => 'Xbox',
    ];
    $languageLabels = [
        'de' => 'Deutsch',
        'en' => 'Englisch',
    ];
    $author = $guide->author;
    $authorName = $author?->name ?: ($author?->username ?: 'HNT Hunter');
    $authorPublishedGuideCount = $authorPublishedGuideCount ?? 0;
    $authorBadgeCount = $authorBadgeCount ?? null;
    $relatedGuides = $relatedGuides ?? collect();
@endphp

@section('content')
@if($isPreview)
    <div class="guide-preview-banner"><i class="ph ph-eye" aria-hidden="true"></i> {{ __('guides.detail.preview_notice') }}</div>
@endif

<article class="guide-detail">
    <section class="guide-detail-hero">
        <div class="guide-detail-cover">
            @if($revision->cover_media_id)
                <img src="{{ route('guides.media.show', $revision->cover_media_id) }}" alt="{{ $revision->title }}">
            @else
                <div class="guide-detail-cover-placeholder" aria-hidden="true">
                    <i class="ph ph-book-open-text"></i>
                </div>
            @endif

            @if($revision->category)
                <span>{{ mb_strtoupper($revision->category->label()) }}</span>
            @endif
        </div>

        <div class="guide-detail-copy">
            <div class="guide-detail-status">
                <span class="guide-chip success">{{ $isPreview ? 'Vorschau' : 'Veröffentlicht' }}</span>
                <span class="guide-chip">{{ $languageLabels[$revision->language] ?? strtoupper((string) $revision->language) }}</span>
                <span class="guide-chip">{{ $platformLabels[$revision->platform] ?? ucfirst((string) $revision->platform) }}</span>
                <span class="guide-chip">{{ $difficultyLabels[$revision->difficulty] ?? ucfirst((string) $revision->difficulty) }}</span>
            </div>

            <h1>{{ $revision->title }}</h1>
            <p>{{ $revision->summary }}</p>

            <div class="guide-detail-author">
                <img src="{{ $author?->avatarUrl() }}" alt="" loading="lazy">
                <div>
                    <strong>{{ $authorName }}</strong>
                    <span>Guide-Autor · aktualisiert {{ ($revision->reviewed_at ?: $guide->published_at ?: $revision->updated_at)?->diffForHumans() }}</span>
                </div>
                @if($author)
                    <a href="{{ route('profile.public', $author) }}">Profil <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
                @endif
            </div>
        </div>

        <aside aria-label="Guide-Statistik">
            <article>
                <strong data-guide-helpful-total>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</strong>
                <span>hilfreich</span>
            </article>
            <article class="guide-action-placeholder">
                <strong>—</strong>
                <span>Aufrufe · geplant</span>
            </article>
            <article>
                <strong>{{ max(1, (int) $revision->reading_time_minutes) }} Min.</strong>
                <span>Lesezeit</span>
            </article>
        </aside>
    </section>

    @unless($isPreview)
        <section class="guide-detail-actions">
            @auth
                @if(! $guide->isOwnedBy(auth()->user()))
                    <button
                        class="{{ $viewerHelpful ? 'active' : '' }}"
                        data-guide-toggle
                        data-guide-toggle-group="helpful"
                        data-url="{{ route('guides.helpful.toggle', $guide) }}"
                        data-active-label="{{ __('guides.helpful.marked') }}"
                        data-inactive-label="{{ __('guides.helpful.label') }}"
                        type="button"
                    >
                        <i class="ph ph-heart" aria-hidden="true"></i>
                        <span>{{ $viewerHelpful ? __('guides.helpful.marked') : __('guides.helpful.label') }}</span>
                        <b data-count>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</b>
                    </button>
                @else
                    <button type="button" disabled title="{{ __('guides.helpful.own_forbidden') }}">
                        <i class="ph ph-heart" aria-hidden="true"></i>
                        <span>Eigener Guide</span>
                        <b>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</b>
                    </button>
                @endif

                <button
                    class="{{ $viewerBookmarked ? 'active' : '' }}"
                    data-guide-toggle
                    data-guide-toggle-group="bookmark"
                    data-url="{{ route('guides.bookmark.toggle', $guide) }}"
                    data-active-label="{{ __('guides.bookmark.saved_label') }}"
                    data-inactive-label="{{ __('guides.bookmark.save') }}"
                    type="button"
                >
                    <i class="ph ph-bookmark-simple" aria-hidden="true"></i>
                    <span>{{ $viewerBookmarked ? __('guides.bookmark.saved_label') : __('guides.bookmark.save') }}</span>
                </button>
            @else
                <a class="guide-action-login" href="{{ route('login') }}"><i class="ph ph-heart" aria-hidden="true"></i> Hilfreich</a>
                <a class="guide-action-login" href="{{ route('login') }}"><i class="ph ph-bookmark-simple" aria-hidden="true"></i> Speichern</a>
            @endauth

            <button type="button" data-guide-comments-open>
                <i class="ph ph-chat-circle" aria-hidden="true"></i>
                Kommentare
                <b data-guide-comment-count>{{ number_format((int) $guide->comments_count, 0, ',', '.') }}</b>
            </button>

            <button
                type="button"
                data-guide-share
                data-url="{{ route('guides.show', $guide) }}"
                data-title="{{ $revision->title }}"
            >
                <i class="ph ph-share-network" aria-hidden="true"></i>
                Teilen
            </button>

            <span class="guide-detail-action-spacer" aria-hidden="true"></span>

            @auth
                @if(! $guide->isOwnedBy(auth()->user()))
                    <details class="guide-detail-report-menu">
                        <summary><i class="ph ph-flag" aria-hidden="true"></i> Melden</summary>
                        <form action="{{ route('reports.store') }}" method="post">
                            @csrf
                            <input type="hidden" name="type" value="guide">
                            <input type="hidden" name="id" value="{{ $guide->id }}">
                            <label>
                                Grund
                                <select name="reason" required>
                                    <option value="spam">Spam</option>
                                    <option value="abuse">Belästigung</option>
                                    <option value="hate">Hassrede</option>
                                    <option value="fraud">Täuschung</option>
                                    <option value="privacy">Privatsphäre</option>
                                    <option value="other">Sonstiges</option>
                                </select>
                            </label>
                            <label>
                                Details
                                <textarea name="body" maxlength="2000"></textarea>
                            </label>
                            <button type="submit">Meldung senden</button>
                        </form>
                    </details>
                @endif

                @if($guide->isOwnedBy(auth()->user()))
                    <a class="guide-detail-edit-link" href="{{ route('guides.edit', $guide) }}">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        Neue Version bearbeiten
                    </a>
                @endif
            @endauth
        </section>
    @endunless

    <section class="guide-detail-layout">
        <aside class="guide-detail-toc">
            <section>
                <span>INHALT</span>
                <h2>In diesem Guide</h2>
                <nav>
                    @php $headingCount = 0; @endphp
                    @foreach((array) $revision->content_blocks as $block)
                        @if(($block['type'] ?? null) === 'heading' && filled($block['text'] ?? null))
                            @php
                                $headingCount++;
                                $headingId = 'guide-block-'.preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($block['id'] ?? $loop->index));
                            @endphp
                            <a class="{{ $headingCount === 1 ? 'active' : '' }}" href="#{{ $headingId }}">{{ $block['text'] }}</a>
                        @endif
                    @endforeach

                    @if($headingCount === 0)
                        <p>{{ __('guides.detail.no_toc') }}</p>
                    @endif
                </nav>
            </section>

            <section class="guide-version-card">
                <span>VERSION</span>
                <strong>v{{ (int) $revision->version }}</strong>
                <p>
                    @if($guide->published_at)
                        Freigegeben am {{ $guide->published_at->format('d.m.Y') }}
                    @else
                        Noch nicht veröffentlicht
                    @endif
                </p>
                <span class="guide-version-placeholder">Versionsverlauf · geplant</span>
            </section>
        </aside>

        <article class="guide-article">
            @include('themes.hnt_preview.guides.partials.blocks', ['revision' => $revision])

            @unless($isPreview)
                <footer class="guide-article-end">
                    <div>
                        <span>War dieser Guide hilfreich?</span>
                        <strong>Deine Bewertung stärkt gute Community-Autoren.</strong>
                    </div>

                    @auth
                        @if(! $guide->isOwnedBy(auth()->user()))
                            <button
                                class="{{ $viewerHelpful ? 'active' : '' }}"
                                data-guide-toggle
                                data-guide-toggle-group="helpful"
                                data-url="{{ route('guides.helpful.toggle', $guide) }}"
                                data-active-label="{{ __('guides.helpful.marked') }}"
                                data-inactive-label="Als hilfreich markieren"
                                type="button"
                            >
                                <i class="ph ph-heart" aria-hidden="true"></i>
                                <span>{{ $viewerHelpful ? __('guides.helpful.marked') : 'Als hilfreich markieren' }}</span>
                            </button>
                        @endif
                    @else
                        <a href="{{ route('login') }}">Anmelden und als hilfreich markieren</a>
                    @endauth
                </footer>
            @endunless
        </article>

        <aside class="guide-detail-sidebar">
            <section>
                <span>GUIDE-INFOS</span>
                <h2>Auf einen Blick</h2>
                <div class="guide-info-list">
                    <div><span>Kategorie</span><strong>{{ $revision->category?->label() ?: '—' }}</strong></div>
                    <div><span>Schwierigkeit</span><strong>{{ $difficultyLabels[$revision->difficulty] ?? ucfirst((string) $revision->difficulty) }}</strong></div>
                    <div><span>Plattform</span><strong>{{ $platformLabels[$revision->platform] ?? ucfirst((string) $revision->platform) }}</strong></div>
                    <div><span>Sprache</span><strong>{{ $languageLabels[$revision->language] ?? strtoupper((string) $revision->language) }}</strong></div>
                    <div><span>Moderation</span><strong class="green">{{ $isPreview ? 'Vorschau' : 'Geprüft' }}</strong></div>
                </div>
            </section>

            <section class="guide-author-reputation">
                <span>AUTOR-REPUTATION</span>
                <div>
                    <strong>{{ $authorReputation === null ? '—' : number_format($authorReputation, 0, ',', '.') }}</strong>
                    <small>Reputation</small>
                </div>
                <p>
                    {{ number_format((int) $authorPublishedGuideCount, 0, ',', '.') }} veröffentlichte Guides
                    ·
                    {{ $authorBadgeCount === null ? 'Badges verborgen' : number_format((int) $authorBadgeCount, 0, ',', '.').' Badges' }}
                </p>
                @if($author)
                    <a href="{{ route('profile.public', $author) }}">Autor ansehen <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
                @endif
            </section>

            <section class="guide-related">
                <header>
                    <div>
                        <span>PASSEND DAZU</span>
                        <h2>Weitere Guides</h2>
                    </div>
                </header>

                @forelse($relatedGuides as $relatedGuide)
                    @php
                        $relatedRevision = $relatedGuide->publishedRevision;
                        $relatedCover = $relatedRevision?->cover_media_id
                            ? route('guides.media.show', $relatedRevision->cover_media_id)
                            : null;
                    @endphp
                    <a href="{{ route('guides.show', $relatedGuide) }}">
                        @if($relatedCover)
                            <img src="{{ $relatedCover }}" alt="" loading="lazy">
                        @else
                            <span class="guide-related-cover-placeholder"><i class="ph ph-book-open-text" aria-hidden="true"></i></span>
                        @endif
                        <div>
                            <strong>{{ $relatedRevision?->title }}</strong>
                            <span>{{ number_format((int) $relatedGuide->helpful_count, 0, ',', '.') }} hilfreich</span>
                        </div>
                    </a>
                @empty
                    <p>Noch keine weiteren veröffentlichten Guides in dieser Kategorie.</p>
                @endforelse
            </section>
        </aside>
    </section>
</article>

@unless($isPreview)
    <dialog class="guide-comments-dialog" data-guide-comments-dialog>
        <div class="guide-comments-shell">
            <header>
                <div>
                    <span>GUIDE-DISKUSSION</span>
                    <h2>Kommentare <b data-guide-comment-count>{{ number_format((int) $guide->comments_count, 0, ',', '.') }}</b></h2>
                </div>
                <button type="button" data-guide-comments-close aria-label="Kommentare schließen">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="guide-comments-body" data-guide-comment-list>
                @forelse($comments as $comment)
                    @include('themes.hnt_preview.guides.partials.comment', [
                        'comment' => $comment,
                        'guide' => $guide,
                        'isReply' => false,
                    ])
                @empty
                    <div class="guide-comments-empty" data-guide-comments-empty>{{ __('guides.comments.empty') }}</div>
                @endforelse

                @if(method_exists($comments, 'hasMorePages') && $comments->hasMorePages())
                    <div class="guides-pagination">
                        <button type="button" data-guide-comments-more data-next-url="{{ $comments->nextPageUrl() }}">
                            {{ __('guides.comments.load_more') }}
                        </button>
                    </div>
                @endif
            </div>

            @auth
                <form
                    class="guide-comment-composer"
                    action="{{ route('guides.comments.store', $guide) }}"
                    method="post"
                    data-guide-comment-form
                    data-label-author="{{ __('guides.comments.author_badge') }}"
                    data-label-reply="{{ __('guides.comments.reply') }}"
                    data-label-edit="{{ __('guides.comments.edit') }}"
                    data-label-delete="{{ __('guides.comments.delete') }}"
                    data-label-report="{{ __('guides.detail.report') }}"
                    data-label-report-details="{{ __('guides.detail.report_details') }}"
                    data-label-report-send="{{ __('guides.detail.report_send') }}"
                >
                    @csrf
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="">
                    <div>
                        <textarea name="body" maxlength="2000" required placeholder="{{ __('guides.comments.placeholder') }}" data-guide-comment-input></textarea>
                        <span>
                            <b><i data-guide-comment-length>0</i>/2000</b>
                            <button type="submit" aria-label="{{ __('guides.comments.send') }}">
                                <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                            </button>
                        </span>
                    </div>
                </form>
            @else
                <footer class="guide-comments-login">
                    <a href="{{ route('login') }}">{{ __('guides.comments.login') }}</a>
                </footer>
            @endauth
        </div>
    </dialog>
@endunless
@endsection

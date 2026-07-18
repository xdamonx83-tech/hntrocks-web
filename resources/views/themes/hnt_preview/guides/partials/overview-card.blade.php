@php
    $revision = $guide->publishedRevision;
    $category = $revision?->category;
    $author = $guide->author;
    $authorName = $author?->name ?: ($author?->username ?: 'HNT Hunter');
    $coverUrl = $revision?->cover_media_id ? route('guides.media.show', $revision->cover_media_id) : null;
    $isSaved = in_array((int) $guide->id, $bookmarkedGuideIds ?? [], true);
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
@endphp

<article class="guide-card">
    <a class="guide-card-cover" href="{{ route('guides.show', $guide) }}">
        @if($coverUrl)
            <img src="{{ $coverUrl }}" alt="{{ $revision?->title }}" loading="lazy">
        @else
            <div class="guide-real-cover-placeholder" aria-hidden="true">
                <i class="ph ph-book-open-text"></i>
            </div>
        @endif
        @if($category)
            <span>{{ $category->label() }}</span>
        @endif
    </a>

    <div class="guide-card-body">
        <div class="guide-card-author">
            <img src="{{ $author?->avatarUrl() }}" alt="" loading="lazy">
            <div>
                @if($author)
                    <a href="{{ route('profile.public', $author) }}"><strong>{{ $authorName }}</strong></a>
                @else
                    <strong>{{ $authorName }}</strong>
                @endif
                <small>{{ $guide->published_at?->diffForHumans() ?: 'Geprüft' }} · geprüft</small>
            </div>

            @auth
                <button
                    class="{{ $isSaved ? 'saved' : '' }}"
                    data-guide-card-bookmark
                    data-url="{{ route('guides.bookmark.toggle', $guide) }}"
                    aria-label="{{ $isSaved ? 'Guide aus gespeicherten Guides entfernen' : 'Guide speichern' }}"
                    aria-pressed="{{ $isSaved ? 'true' : 'false' }}"
                    type="button"
                >
                    <i class="ph {{ $isSaved ? 'ph-bookmark-simple-fill' : 'ph-bookmark-simple' }}" aria-hidden="true"></i>
                </button>
            @else
                <a class="guide-card-login-save" href="{{ route('login') }}" aria-label="Anmelden, um den Guide zu speichern">
                    <i class="ph ph-bookmark-simple" aria-hidden="true"></i>
                </a>
            @endauth
        </div>

        <a href="{{ route('guides.show', $guide) }}"><h3>{{ $revision?->title }}</h3></a>
        <p>{{ $revision?->summary }}</p>

        <div class="guide-card-tags">
            <span class="guide-chip">{{ $difficultyLabels[$revision?->difficulty] ?? ucfirst((string) $revision?->difficulty) }}</span>
            <span class="guide-chip">{{ $platformLabels[$revision?->platform] ?? ucfirst((string) $revision?->platform) }}</span>
            <span class="guide-chip">{{ $languageLabels[$revision?->language] ?? strtoupper((string) $revision?->language) }}</span>
        </div>

        <footer>
            <span><i class="ph ph-heart" aria-hidden="true"></i> <b>{{ number_format((int) $guide->helpful_count, 0, ',', '.') }}</b> hilfreich</span>
            <span><i class="ph ph-chat-circle" aria-hidden="true"></i> {{ number_format((int) $guide->comments_count, 0, ',', '.') }} Kommentare</span>
            <a href="{{ route('guides.show', $guide) }}">Lesen <i class="ph ph-arrow-up-right" aria-hidden="true"></i></a>
        </footer>
    </div>
</article>

@forelse($socialitePosts as $post)
    @include('themes.rework.feed.partials.post-card', ['post' => $post, 'reportedFeedKeys' => $reportedFeedKeys ?? collect()])
@empty
    <article class="card post-card rework-empty-state">
        <div class="eyebrow">Feed Preview</div>
        <h2>Keine Posts gefunden</h2>
        <p>Dieser Rework-Feed zeigt echte Beiträge, sobald der aktuelle Filter passende Feed-Posts liefert.</p>
    </article>
@endforelse

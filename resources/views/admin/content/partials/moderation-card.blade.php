<article class="hh-card hh-card-compact hh-admin-moderation-card">
    <h2>{{ $title }}</h2>
    <div class="hh-admin-list">
        @forelse($items as $item)
            @php
                $targetUrl = null;
                if (isset($url) && is_callable($url)) {
                    try {
                        $targetUrl = $url($item);
                    } catch (\Throwable) {
                        $targetUrl = null;
                    }
                }
            @endphp
            <div class="hh-admin-moderation-row">
                <div class="hh-admin-moderation-main">
                    <strong>{{ $label($item) }}</strong>
                    <span>{{ $sub($item) }}</span>
                    <small class="hh-muted">ID #{{ $item->id }} · Status: {{ $item->status ?? '—' }}</small>
                    @if (isset($extra) && is_callable($extra))
                        {!! $extra($item) !!}
                    @endif
                </div>
                <div class="hh-admin-moderation-side">
                    @if ($targetUrl)
                        <a class="hh-link-button hh-admin-open-content" href="{{ $targetUrl }}" target="_blank" rel="noopener">
                            {{ $urlLabel ?? 'Inhalt öffnen' }}
                        </a>
                    @endif
                    <form method="post" action="{{ route('admin.content.status', [$type, $item->id]) }}">
                        @csrf
                        <select name="status">
                            @foreach($statuses as $value => $text)
                                <option value="{{ $value }}" @selected(($item->status ?? '') === $value)>{{ $text }}</option>
                            @endforeach
                        </select>
                        <button class="hh-secondary-button" type="submit">Setzen</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="hh-muted">Keine Einträge vorhanden.</p>
        @endforelse
    </div>
</article>

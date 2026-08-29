@extends('admin.layouts.app')

@section('title', ($revision->title ?: 'Guide') . ' · Guides · Admin')
@section('admin_heading', 'Guide prüfen')

@section('content')
    @php
        $authorName = $guide->author?->name ?: ($guide->author?->username ?: 'Unbekannt');
        $category = $revision->category?->label(app()->getLocale() === 'en' ? 'en' : 'de');
        $coverMedia = $revision->media->firstWhere('id', $revision->cover_media_id);
    @endphp

    <section class="hh-page-header">
        <div>
            <p class="hh-kicker">{{ $statuses[$guide->status] ?? $guide->status }} · Revision {{ $revision->version }}</p>
            <h1>{{ $revision->title ?: 'Guide ohne Titel' }}</h1>
            <p>{{ $revision->summary ?: 'Noch keine Zusammenfassung vorhanden.' }}</p>
        </div>
        <div class="hh-admin-actions">
            <a class="hh-secondary-button" href="{{ route('admin.guides.index', ['status' => $guide->status]) }}">Zur Übersicht</a>
            @if($guide->current_published_revision_id && $guide->status !== 'archived')
                <a class="hh-primary-button" href="{{ url('/guides/'.$guide->slug) }}" target="_blank" rel="noopener">Öffentliche Version</a>
            @endif
        </div>
    </section>

    @if(session('status'))
        <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="hh-alert hh-alert-danger">
            <strong>Aktion konnte nicht ausgeführt werden.</strong>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="hh-card hh-card-compact">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Metadaten</p>
                <h2>Guide-Informationen</h2>
            </div>
            @if($guide->is_featured)
                <span class="hh-kicker">Featured</span>
            @endif
        </div>
        <div class="hh-admin-report-context">
            <span>Autor: {{ $authorName }}</span>
            @if($guide->author?->username)
                <span>@{{ $guide->author->username }}</span>
            @endif
            <span>Status: {{ $statuses[$guide->status] ?? $guide->status }}</span>
            @if($category)<span>Kategorie: {{ $category }}</span>@endif
            <span>Sprache: {{ strtoupper($revision->language ?: '-') }}</span>
            <span>Schwierigkeit: {{ ucfirst($revision->difficulty ?: '-') }}</span>
            <span>Plattform: {{ strtoupper($revision->platform ?: '-') }}</span>
            <span>Lesezeit: {{ max(1, (int) $revision->reading_time_minutes) }} Min.</span>
            @if($revision->submitted_at)<span>Eingereicht: {{ $revision->submitted_at->format('d.m.Y H:i') }}</span>@endif
            @if($revision->reviewed_at)<span>Geprüft: {{ $revision->reviewed_at->format('d.m.Y H:i') }}</span>@endif
        </div>

        @if(!empty($revision->tags))
            <div class="hh-admin-report-context hh-section-space">
                @foreach($revision->tags as $tag)
                    <span>#{{ $tag }}</span>
                @endforeach
            </div>
        @endif

        @if($revision->moderation_reason)
            <div class="hh-alert hh-alert-danger hh-section-space">
                <strong>Letzte Moderationsbegründung:</strong> {{ $revision->moderation_reason }}
            </div>
        @endif
    </section>

    @if($coverMedia)
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <p class="hh-kicker">Cover</p>
                    <h2>Titelbild</h2>
                </div>
                <a class="hh-secondary-button" href="{{ route('admin.guides.media', $coverMedia) }}" target="_blank" rel="noopener">Original öffnen</a>
            </div>
            <img src="{{ route('admin.guides.media', $coverMedia) }}" alt="Guide-Cover" style="display:block;width:100%;max-height:520px;object-fit:contain;border-radius:12px;">
        </section>
    @endif

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Inhalt</p>
                <h2>Eingereichte Revision</h2>
            </div>
            <span class="hh-muted">{{ count((array) $revision->content_blocks) }} Blöcke</span>
        </div>

        @forelse((array) $revision->content_blocks as $block)
            @php
                $type = (string) ($block['type'] ?? '');
                $blockMedia = $type === 'image'
                    ? $revision->media->firstWhere('id', (int) ($block['media_id'] ?? 0))
                    : null;
            @endphp

            <article class="hh-card hh-card-compact hh-section-space">
                <p class="hh-kicker">{{ strtoupper($type ?: 'BLOCK') }}</p>

                @if($type === 'heading')
                    <h2>{{ $block['text'] ?? '' }}</h2>
                @elseif($type === 'paragraph')
                    <p>{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p>
                @elseif($type === 'steps')
                    <ol>
                        @foreach((array) ($block['items'] ?? []) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ol>
                @elseif($type === 'list')
                    <ul>
                        @foreach((array) ($block['items'] ?? []) as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @elseif($type === 'image')
                    @if($blockMedia)
                        <a href="{{ route('admin.guides.media', $blockMedia) }}" target="_blank" rel="noopener">
                            <img src="{{ route('admin.guides.media', $blockMedia) }}" alt="{{ $block['caption'] ?? 'Guide-Bild' }}" style="display:block;width:100%;max-height:620px;object-fit:contain;border-radius:12px;">
                        </a>
                    @else
                        <div class="hh-alert hh-alert-danger">Bilddatei #{{ (int) ($block['media_id'] ?? 0) }} wurde nicht gefunden.</div>
                    @endif
                    @if(!empty($block['caption']))
                        <p class="hh-muted">{{ $block['caption'] }}</p>
                    @endif
                @elseif($type === 'notice' || $type === 'warning')
                    <div class="hh-alert {{ $type === 'warning' ? 'hh-alert-danger' : 'hh-alert-success' }}">
                        @if(!empty($block['title']))<strong>{{ $block['title'] }}</strong>@endif
                        <p>{!! nl2br(e((string) ($block['text'] ?? ''))) !!}</p>
                    </div>
                @else
                    <pre>{{ json_encode($block, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
            </article>
        @empty
            <p class="hh-muted">Diese Revision enthält noch keine Inhaltsblöcke.</p>
        @endforelse
    </section>

    @if($guide->status === 'pending_review')
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <p class="hh-kicker">Moderation</p>
                    <h2>Entscheidung treffen</h2>
                    <p class="hh-muted">Änderungswunsch und Ablehnung benötigen eine Begründung mit mindestens 10 Zeichen.</p>
                </div>
            </div>

            <div class="hh-admin-actions">
                <form method="post" action="{{ route('admin.guides.moderate', $guide) }}">
                    @csrf
                    <input type="hidden" name="action" value="approve">
                    <button class="hh-primary-button" type="submit">Guide freigeben</button>
                </form>
            </div>

            <form method="post" action="{{ route('admin.guides.moderate', $guide) }}" class="hh-section-space">
                @csrf
                <input type="hidden" name="action" value="changes">
                <label for="changes-reason">Änderungen anfordern</label>
                <textarea id="changes-reason" name="reason" rows="4" minlength="10" maxlength="2000" required placeholder="Was soll der Autor ändern?">{{ old('action') === 'changes' ? old('reason') : '' }}</textarea>
                <div class="hh-admin-actions hh-section-space">
                    <button class="hh-secondary-button" type="submit">Änderungen anfordern</button>
                </div>
            </form>

            <form method="post" action="{{ route('admin.guides.moderate', $guide) }}" class="hh-section-space">
                @csrf
                <input type="hidden" name="action" value="reject">
                <label for="reject-reason">Guide ablehnen</label>
                <textarea id="reject-reason" name="reason" rows="4" minlength="10" maxlength="2000" required placeholder="Warum wird der Guide abgelehnt?">{{ old('action') === 'reject' ? old('reason') : '' }}</textarea>
                <div class="hh-admin-actions hh-section-space">
                    <button class="hh-secondary-button" type="submit">Guide ablehnen</button>
                </div>
            </form>
        </section>
    @endif

    @if($guide->current_published_revision_id && $guide->status !== 'archived')
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <p class="hh-kicker">Sichtbarkeit</p>
                    <h2>Veröffentlichten Guide verwalten</h2>
                </div>
            </div>
            <div class="hh-admin-actions">
                <form method="post" action="{{ route('admin.guides.featured', $guide) }}">
                    @csrf
                    <input type="hidden" name="featured" value="{{ $guide->is_featured ? 0 : 1 }}">
                    <button class="hh-secondary-button" type="submit">
                        {{ $guide->is_featured ? 'Featured entfernen' : 'Als Featured markieren' }}
                    </button>
                </form>
            </div>

            <form method="post" action="{{ route('admin.guides.archive', $guide) }}" class="hh-section-space">
                @csrf
                <label for="archive-reason">Guide archivieren</label>
                <textarea id="archive-reason" name="reason" rows="3" minlength="10" maxlength="2000" required placeholder="Grund für die Archivierung"></textarea>
                <div class="hh-admin-actions hh-section-space">
                    <button class="hh-secondary-button" type="submit">Guide archivieren</button>
                </div>
            </form>
        </section>
    @elseif($guide->status === 'archived')
        <section class="hh-card hh-section-space">
            <div class="hh-card-title-row">
                <div>
                    <p class="hh-kicker">Archiv</p>
                    <h2>Guide wiederherstellen</h2>
                </div>
                <form method="post" action="{{ route('admin.guides.restore', $guide) }}">
                    @csrf
                    <button class="hh-primary-button" type="submit">Wiederherstellen</button>
                </form>
            </div>
        </section>
    @endif

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Historie</p>
                <h2>Moderationsverlauf</h2>
            </div>
        </div>

        @forelse($guide->moderationEvents as $event)
            <article class="hh-card hh-card-compact hh-section-space">
                <div class="hh-admin-report-head">
                    <div>
                        <strong>{{ $event->action }}</strong>
                        <div class="hh-admin-report-context">
                            @if($event->from_status || $event->to_status)
                                <span>{{ $event->from_status ?: '—' }} → {{ $event->to_status ?: '—' }}</span>
                            @endif
                            @if($event->revision)<span>Revision {{ $event->revision->version }}</span>@endif
                            <span>{{ $event->actor?->name ?: ($event->actor?->username ?: 'System') }}</span>
                            <span>{{ $event->created_at?->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($event->reason)
                            <p>{{ $event->reason }}</p>
                        @endif
                    </div>
                </div>
            </article>
        @empty
            <p class="hh-muted">Noch keine Moderationsereignisse vorhanden.</p>
        @endforelse
    </section>
@endsection

@extends('themes.hnt_preview.guides.layout')

@section('title', __('guides.editor.title').' · HNT.ROCKS')
@section('body_class', 'guide-editor-live')
@section('skip_guides_base_styles', '1')

@push('head')
<link href="{{ asset('assets/themes/hnt_preview/guides/guides-editor-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/guides/guides-editor-live.css')) ?: time() }}" rel="stylesheet">
@endpush

@php
    $blocks = (array) $revision->content_blocks;
    $tags = array_values(array_filter((array) $revision->tags));
    $hasBasics = filled($revision->title) && filled($revision->summary);
    $hasClassification = filled($revision->category_id) && filled($revision->platform);
    $hasCover = filled($revision->cover_media_id);
    $hasBlocks = count($blocks) >= 3;
    $completion = (int) round((collect([$hasBasics, $hasClassification, $hasCover, $hasBlocks])->filter()->count() / 4) * 100);
    $author = auth()->user();
    $authorName = $author?->name ?: ($author?->username ?: 'HNT Hunter');
    $publicVersion = $guide->publishedRevision?->version;
@endphp

@section('content')
<form
    class="guide-editor-form"
    action="{{ route('guides.update', $guide) }}"
    method="post"
    data-guide-editor
    data-submit-url="{{ route('guides.submit', $guide) }}"
    data-media-url="{{ route('guides.media.store', $guide) }}"
    data-preview-url="{{ route('guides.preview', $guide) }}"
>
    @csrf
    @method('put')

    <section class="guide-editor-top">
        <div>
            <span>GUIDE-EDITOR</span>
            <h1>{{ __('guides.editor.title') }}</h1>
            <p>Automatisch gespeicherter Block-Editor. Kein ungeprüftes HTML und keine Veröffentlichung ohne Moderation.</p>
        </div>

        <div class="guide-editor-save-state">
            <span>
                <i aria-hidden="true"></i>
                <b data-autosave-state>{{ __('guides.editor.saved') }}</b>
                <small data-autosave-time>{{ $revision->updated_at?->diffForHumans() }}</small>
            </span>
            <button type="submit" data-save-guide>
                <i class="ph ph-floppy-disk" aria-hidden="true"></i>
                Entwurf speichern
            </button>
            <button class="primary" type="button" data-submit-guide>
                Zur Prüfung einreichen
                <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
            </button>
        </div>
    </section>

    @if($revision->moderation_reason)
        <aside class="guide-moderation-note">
            <i class="ph ph-warning" aria-hidden="true"></i>
            <div>
                <strong>{{ __('guides.editor.changes_title') }}</strong>
                <p>{{ $revision->moderation_reason }}</p>
            </div>
        </aside>
    @endif

    <section class="guide-editor-layout">
        <aside class="guide-editor-left">
            <section>
                <header>
                    <div>
                        <span>FORTSCHRITT</span>
                        <h2>Guide-Inhalt</h2>
                    </div>
                    <strong data-editor-completion>{{ $completion }}%</strong>
                </header>

                <div class="guide-editor-progress">
                    <i data-editor-progress style="width: {{ $completion }}%"></i>
                </div>

                <nav data-editor-nav>
                    <a class="active" href="#editorBasics">
                        <i class="ph ph-pencil-simple" aria-hidden="true"></i>
                        Grundlagen
                        <b data-nav-basics>{{ $hasBasics ? '✓' : '–' }}</b>
                    </a>
                    <a href="#editorCover">
                        <i class="ph ph-image" aria-hidden="true"></i>
                        Titelbild
                        <b data-nav-cover>{{ $hasCover ? '✓' : '–' }}</b>
                    </a>
                    <a href="#editorBlocks">
                        <i class="ph ph-list-bullets" aria-hidden="true"></i>
                        Inhalt
                        <b data-block-count>{{ count($blocks) }}</b>
                    </a>
                    <a href="#editorSettings">
                        <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
                        Einstellungen
                        <b>✓</b>
                    </a>
                </nav>
            </section>

            <section class="guide-editor-status">
                <span>STATUS</span>
                <h2>{{ __('guides.status.'.$guide->status) }}</h2>
                <p>
                    @if($guide->status === 'changes_requested')
                        Überarbeite die angeforderten Punkte und reiche den Guide anschließend erneut ein.
                    @else
                        Nur du kannst diesen Entwurf sehen. Eine öffentliche Version bleibt bis zur Freigabe unverändert.
                    @endif
                </p>
                <div><span>Entwurf</span><strong>Version {{ $revision->version }}</strong></div>
                <div><span>Öffentlich</span><strong>{{ $publicVersion ? 'Version '.$publicVersion : 'Noch keine Version' }}</strong></div>
            </section>
        </aside>

        <main class="guide-editor-center">
            <section class="guide-editor-panel" id="editorBasics">
                <header>
                    <div>
                        <span>GRUNDLAGEN</span>
                        <h2>Titel und Einordnung</h2>
                    </div>
                </header>

                <div class="guide-editor-fields">
                    <label class="full">
                        <span>Titel</span>
                        <input
                            type="text"
                            name="title"
                            maxlength="160"
                            value="{{ old('title', $revision->title) }}"
                            placeholder="{{ __('guides.editor.title_placeholder') }}"
                            data-editor-title
                        >
                        <small><b data-title-count>{{ mb_strlen((string) old('title', $revision->title)) }}</b>/160</small>
                    </label>

                    <label class="full">
                        <span>Kurzbeschreibung</span>
                        <textarea
                            name="summary"
                            maxlength="420"
                            placeholder="{{ __('guides.editor.summary_placeholder') }}"
                            data-editor-summary
                        >{{ old('summary', $revision->summary) }}</textarea>
                        <small><b data-summary-count>{{ mb_strlen((string) old('summary', $revision->summary)) }}</b>/420</small>
                    </label>

                    <label>
                        <span>Kategorie</span>
                        <select name="category_id" data-editor-category>
                            <option value="">{{ __('guides.all_categories') }}</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) old('category_id', $revision->category_id) === $category->id)>
                                    {{ $category->label() }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Sprache</span>
                        <select name="language">
                            @foreach(['de', 'en'] as $value)
                                <option value="{{ $value }}" @selected(old('language', $revision->language) === $value)>
                                    {{ __('guides.language.'.$value) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Schwierigkeit</span>
                        <select name="difficulty">
                            @foreach(['beginner', 'advanced', 'expert'] as $value)
                                <option value="{{ $value }}" @selected(old('difficulty', $revision->difficulty) === $value)>
                                    {{ __('guides.difficulty.'.$value) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label>
                        <span>Plattform</span>
                        <select name="platform">
                            @foreach(['all', 'pc', 'playstation', 'xbox'] as $value)
                                <option value="{{ $value }}" @selected(old('platform', $revision->platform) === $value)>
                                    {{ __('guides.platform.'.$value) }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="full">
                        <span>Tags</span>
                        <div class="guide-tag-input" data-tag-input>
                            <div data-tag-list>
                                @foreach($tags as $tag)
                                    <span data-tag="{{ $tag }}">
                                        {{ $tag }}
                                        <button type="button" data-remove-tag aria-label="{{ $tag }} entfernen">×</button>
                                    </span>
                                @endforeach
                            </div>
                            <input type="text" maxlength="30" placeholder="Tag hinzufügen" data-tag-entry>
                        </div>
                        <input type="hidden" name="tags_text" value="{{ implode(', ', $tags) }}">
                        <small>Maximal 8 Tags. Mit Enter oder Komma bestätigen.</small>
                    </label>
                </div>
            </section>

            <section class="guide-editor-panel" id="editorCover">
                <header>
                    <div>
                        <span>TITELBILD</span>
                        <h2>Guide visuell einordnen</h2>
                    </div>
                    <button type="button" data-cover-upload>
                        <i class="ph ph-upload-simple" aria-hidden="true"></i>
                        {{ $hasCover ? 'Bild ersetzen' : 'Bild hochladen' }}
                    </button>
                </header>

                <div class="guide-cover-editor">
                    <div data-cover-preview>
                        @if($revision->cover_media_id)
                            <img src="{{ route('guides.media.show', $revision->cover_media_id) }}" alt="">
                        @else
                            <span class="guide-cover-placeholder">
                                <i class="ph ph-image" aria-hidden="true"></i>
                                Noch kein Titelbild
                            </span>
                        @endif
                    </div>
                    <div>
                        <strong>Empfohlen: 1600 × 900 Pixel</strong>
                        <p>JPG, PNG oder WebP. Bilder werden geprüft, verkleinert und ohne EXIF-Daten gespeichert.</p>
                        <span><i class="ph ph-shield-check" aria-hidden="true"></i> Community-sicher</span>
                    </div>
                </div>

                <input type="file" accept="image/jpeg,image/png,image/webp" data-cover-input hidden>
                <input type="hidden" name="cover_media_id" value="{{ $revision->cover_media_id }}">
            </section>

            <section class="guide-editor-panel" id="editorBlocks">
                <header>
                    <div>
                        <span>INHALT</span>
                        <h2>Block-Editor</h2>
                    </div>
                    <div>
                        <button type="button" data-editor-undo title="Letzte Blockänderung rückgängig" disabled>
                            <i class="ph ph-arrow-counter-clockwise" aria-hidden="true"></i>
                        </button>
                        <button type="button" data-editor-redo title="Blockänderung wiederholen" disabled>
                            <i class="ph ph-arrow-clockwise" aria-hidden="true"></i>
                        </button>
                    </div>
                </header>

                <div class="guide-editor-blocks" data-editor-blocks>
                    <div class="guide-editor-empty" data-editor-empty>
                        <i class="ph ph-file-plus" aria-hidden="true"></i>
                        <strong>Beginne mit deinem ersten Inhaltsblock.</strong>
                        <span>Überschrift, Absatz oder Schritt auswählen.</span>
                    </div>
                </div>

                <div class="guide-add-blocks">
                    <span>BLOCK HINZUFÜGEN</span>
                    <div>
                        @foreach([
                            'paragraph' => ['ph-text-align-left', 'Absatz'],
                            'heading' => ['ph-text-h-two', 'Überschrift'],
                            'steps' => ['ph-list-numbers', 'Schritte'],
                            'image' => ['ph-image', 'Bild'],
                            'notice' => ['ph-info', 'Hinweis'],
                            'warning' => ['ph-warning', 'Warnung'],
                            'list' => ['ph-list-bullets', 'Liste'],
                        ] as $type => [$icon, $label])
                            <button type="button" data-add-block="{{ $type }}">
                                <i class="ph {{ $icon }}" aria-hidden="true"></i>
                                {{ $label }}
                            </button>
                        @endforeach
                        <button class="planned" type="button" disabled title="Moment-Blöcke sind in Vorbereitung">
                            <i class="ph ph-play-circle" aria-hidden="true"></i>
                            Moment
                            <em>Geplant</em>
                        </button>
                    </div>
                </div>
            </section>

            <section class="guide-editor-panel" id="editorSettings">
                <header>
                    <div>
                        <span>VERÖFFENTLICHUNG</span>
                        <h2>Kommentare und Sichtbarkeit</h2>
                    </div>
                </header>

                <div class="guide-editor-options">
                    <label>
                        <input type="checkbox" checked disabled>
                        <span>
                            <strong>Kommentare nach Freigabe aktiv</strong>
                            <small>Veröffentlichte Guides verwenden das echte Guide-Kommentarsystem.</small>
                        </span>
                    </label>
                    <label>
                        <input type="checkbox" checked disabled>
                        <span>
                            <strong>Guide-Benachrichtigungen aktiv</strong>
                            <small>Kommentare, Antworten und Moderationsentscheidungen folgen deinen Benachrichtigungseinstellungen.</small>
                        </span>
                    </label>
                    <label class="planned">
                        <input type="checkbox" disabled>
                        <span>
                            <strong>Guide im Profil anzeigen <em>Geplant</em></strong>
                            <small>Wird aktiviert, sobald der öffentliche Guide-Tab im Profil verfügbar ist.</small>
                        </span>
                    </label>
                </div>
            </section>
        </main>

        <aside class="guide-editor-right">
            <section class="guide-live-preview">
                <header>
                    <div>
                        <span>LIVE-VORSCHAU</span>
                        <h2>Guide-Karte</h2>
                    </div>
                    <a href="{{ route('guides.preview', $guide) }}" target="_blank" rel="noopener" title="Vollständige Vorschau öffnen">
                        <i class="ph ph-arrow-up-right" aria-hidden="true"></i>
                    </a>
                </header>

                <div class="guide-mini-cover" data-mini-cover>
                    @if($revision->cover_media_id)
                        <img src="{{ route('guides.media.show', $revision->cover_media_id) }}" alt="">
                    @else
                        <i class="ph ph-image" aria-hidden="true"></i>
                    @endif
                </div>

                <div>
                    <span data-mini-category>{{ $revision->category?->label() ?: 'Keine Kategorie' }}</span>
                    <h3 data-mini-title>{{ $revision->title ?: 'Dein Guide-Titel' }}</h3>
                    <p data-mini-summary>{{ $revision->summary ?: 'Deine Kurzbeschreibung erscheint hier.' }}</p>
                    <footer>
                        <img src="{{ $author?->avatarUrl() }}" alt="">
                        <span>
                            <strong>{{ $authorName }}</strong>
                            <small>Entwurf · Version {{ $revision->version }}</small>
                        </span>
                    </footer>
                </div>
            </section>

            <section class="guide-editor-checklist">
                <span>PRÜFLISTE</span>
                <h2>Bereit zur Moderation?</h2>
                <div>
                    <p class="{{ $hasBasics ? 'done' : '' }}" data-check-basics><i></i><span>Titel und Kurzbeschreibung</span></p>
                    <p class="{{ $hasClassification ? 'done' : '' }}" data-check-classification><i></i><span>Kategorie und Plattform</span></p>
                    <p class="{{ $hasCover ? 'done' : '' }}" data-check-cover><i></i><span>Titelbild vorhanden</span></p>
                    <p class="{{ $hasBlocks ? 'done' : '' }}" data-check-blocks><i></i><span>Mindestens drei Inhaltsblöcke</span></p>
                    <p class="planned"><i></i><span>Rechtschreibprüfung <em>Geplant</em></span></p>
                </div>
            </section>

            <section class="guide-version-info">
                <span>VERSIONIERUNG</span>
                <h2>{{ $publicVersion ? 'Öffentliche Version bleibt live' : 'Erste Veröffentlichung vorbereiten' }}</h2>
                <p>
                    @if($publicVersion)
                        Version {{ $publicVersion }} bleibt sichtbar, bis dieser Entwurf geprüft und freigegeben wurde.
                    @else
                        Dieser Guide wird erst nach erfolgreicher Moderation öffentlich sichtbar.
                    @endif
                </p>
                <div><strong>{{ $publicVersion ?: '–' }}</strong><span>aktuell öffentlich</span></div>
                <div><strong>{{ $revision->version }}</strong><span>dieser Entwurf</span></div>
            </section>
        </aside>
    </section>

    <input type="hidden" name="content_blocks" data-content-blocks-input>
</form>

<dialog class="guide-action-dialog guide-submit-dialog" data-guide-submit-dialog>
    <form method="dialog">
        <button type="button" data-guide-dialog-close aria-label="Dialog schließen">
            <i class="ph ph-x" aria-hidden="true"></i>
        </button>
        <span>ZUR PRÜFUNG EINREICHEN</span>
        <h2>Guide an die Moderation senden?</h2>
        <p>Der Guide erscheint erst öffentlich, wenn ein Admin ihn geprüft und freigegeben hat. Über die Entscheidung wirst du benachrichtigt.</p>
        <div class="guide-submit-checks">
            <div><i class="ph ph-check" aria-hidden="true"></i><span>Titel, Beschreibung und Kategorie vollständig</span></div>
            <div><i class="ph ph-check" aria-hidden="true"></i><span>Mindestens drei Inhaltsblöcke vorhanden</span></div>
            <div><i class="ph ph-shield-check" aria-hidden="true"></i><span>Bilder werden sicher verarbeitet</span></div>
        </div>
        <footer>
            <button value="cancel">Noch bearbeiten</button>
            <button type="button" data-guide-submit-confirm>Jetzt einreichen</button>
        </footer>
    </form>
</dialog>

<script type="application/json" id="guideEditorData">{!! json_encode([
    'blocks' => $blocks,
    'labels' => [
        'heading' => __('guides.editor.block_heading'),
        'paragraph' => __('guides.editor.block_paragraph'),
        'steps' => __('guides.editor.block_steps'),
        'list' => __('guides.editor.block_list'),
        'image' => __('guides.editor.block_image'),
        'notice' => __('guides.editor.block_notice'),
        'warning' => __('guides.editor.block_warning'),
        'heading_level' => __('guides.editor.heading_level'),
        'text' => __('guides.editor.text'),
        'items' => __('guides.editor.items'),
        'caption' => __('guides.editor.caption'),
        'box_title' => __('guides.editor.box_title'),
        'image_upload' => __('guides.editor.image_upload'),
        'move_up' => __('guides.editor.move_up'),
        'move_down' => __('guides.editor.move_down'),
        'remove' => __('guides.editor.remove'),
        'saving' => __('guides.editor.saving'),
        'saved' => __('guides.editor.saved'),
        'save_failed' => __('guides.editor.save_failed'),
        'submit_confirm' => __('guides.editor.submit_confirm'),
        'unsaved_warning' => __('guides.editor.unsaved_warning'),
    ],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
@endsection

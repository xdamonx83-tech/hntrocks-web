@extends('admin.layouts.app')

@section('title', 'Inhaltsmoderation · Admin · hnt.rocks')

@section('admin_heading', 'Inhaltsmoderation')

@section('content')
@php
    $contentSections = [
        'feed-posts' => [
            'title' => 'Feed-Beiträge',
            'items' => $feedPosts,
            'statuses' => ['published' => 'Veröffentlicht', 'hidden' => 'Versteckt', 'removed' => 'Entfernt'],
            'label' => fn($item) => $item->excerpt(80),
            'sub' => fn($item) => '@'.$item->user?->username.' · '.$item->created_at->diffForHumans(),
            'url' => fn($item) => route('feed.show', $item),
            'url_label' => 'Beitrag öffnen',
            'extra' => fn($item) => view('admin.content.partials.feed-ai-status', ['post' => $item])->render(),
        ],
        'moments' => [
            'title' => 'Moments',
            'items' => $moments,
            'statuses' => ['published' => 'Veröffentlicht', 'hidden' => 'Versteckt', 'removed' => 'Entfernt'],
            'label' => fn($item) => $item->caption ?: 'Moment #'.$item->id,
            'sub' => fn($item) => '@'.$item->user?->username.' · '.$item->created_at->diffForHumans(),
            'url' => fn($item) => route('moments.show', $item),
            'url_label' => 'Moment öffnen',
        ],
        'media' => [
            'title' => 'Medien',
            'items' => $mediaAssets,
            'statuses' => ['ready' => 'Bereit', 'hidden' => 'Versteckt', 'quarantined' => 'Quarantäne', 'removed' => 'Entfernt'],
            'label' => fn($item) => $item->original_name ?: 'Medium #'.$item->id,
            'sub' => fn($item) => $item->contextLabel().' · '.($item->user?->username ?? 'System'),
            'extra' => fn($item) => view('admin.content.partials.media-preview', [
                'asset' => $item,
                'meta' => $mediaPreviewMeta[$item->id] ?? [],
            ])->render(),
        ],
        'teams' => [
            'title' => 'Teams',
            'items' => $teams,
            'statuses' => ['active' => 'Aktiv', 'archived' => 'Archiviert', 'suspended' => 'Gesperrt'],
            'label' => fn($item) => $item->name,
            'sub' => fn($item) => 'Owner: @'.$item->owner?->username.' · '.$item->created_at->diffForHumans(),
            'url' => fn($item) => route('teams.show', $item),
            'url_label' => 'Team öffnen',
        ],
        'lfg' => [
            'title' => 'LFG',
            'items' => $lfgPosts,
            'statuses' => ['open' => 'Offen', 'full' => 'Voll', 'closed' => 'Geschlossen', 'archived' => 'Archiviert'],
            'label' => fn($item) => $item->title,
            'sub' => fn($item) => '@'.$item->user?->username.' · '.$item->statusLabel(),
            'url' => fn($item) => route('lfg.show', $item),
            'url_label' => 'LFG öffnen',
        ],
        'team-lfg' => [
            'title' => 'Team-LFG',
            'items' => $teamLfgPosts,
            'statuses' => ['open' => 'Offen', 'filled' => 'Besetzt', 'closed' => 'Geschlossen', 'archived' => 'Archiviert'],
            'label' => fn($item) => $item->title,
            'sub' => fn($item) => $item->typeLabel().' · @'.$item->user?->username,
            'url' => fn($item) => route('team-lfg.show', $item),
            'url_label' => 'Team-LFG öffnen',
        ],
        'cups' => [
            'title' => 'Cups',
            'items' => $cups,
            'statuses' => ['planned' => 'Geplant', 'active' => 'Aktiv', 'finished' => 'Beendet', 'archived' => 'Archiviert'],
            'label' => fn($item) => $item->title,
            'sub' => fn($item) => '@'.$item->owner?->username.' · '.$item->statusLabel(),
            'url' => fn($item) => route('cups.show', $item),
            'url_label' => 'Cup öffnen',
        ],
        'cup-submissions' => [
            'title' => 'Cup-Einreichungen',
            'items' => $cupSubmissions,
            'statuses' => ['pending' => 'Wartet', 'approved' => 'Angenommen', 'rejected' => 'Abgelehnt'],
            'label' => fn($item) => ($item->cup?->title ?? 'Cup').' · '.$item->points.' Punkte',
            'sub' => fn($item) => '@'.$item->submitter?->username.' · '.$item->statusLabel(),
            'url' => fn($item) => $item->cup ? route('cups.show.section', [$item->cup, 'submissions']) : null,
            'url_label' => 'Einreichung öffnen',
        ],
    ];
@endphp

<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>Inhaltsmoderation</h1>
        <p>Wähle oben einen Bereich aus. So bleibt die Moderation übersichtlich und du musst nicht durch alle Inhaltstypen scrollen.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

<nav class="hh-admin-content-tabs" aria-label="Inhaltsbereiche">
    <a href="{{ route('admin.content.index', ['section' => 'all']) }}" @class(['is-active' => $selectedSection === 'all'])>
        <span>Alle</span>
        <strong>{{ array_sum($sectionCounts) }}</strong>
    </a>
    @foreach($contentSections as $key => $section)
        <a href="{{ route('admin.content.index', ['section' => $key]) }}" @class(['is-active' => $selectedSection === $key])>
            <span>{{ $section['title'] }}</span>
            <strong>{{ $sectionCounts[$key] ?? 0 }}</strong>
        </a>
    @endforeach
</nav>

<section class="hh-admin-content-grid">
    @foreach($contentSections as $key => $section)
        @continue($selectedSection !== 'all' && $selectedSection !== $key)

        @include('admin.content.partials.moderation-card', [
            'title' => $section['title'],
            'type' => $key,
            'items' => $section['items'],
            'statuses' => $section['statuses'],
            'label' => $section['label'],
            'sub' => $section['sub'],
            'extra' => $section['extra'] ?? null,
            'url' => $section['url'] ?? null,
            'urlLabel' => $section['url_label'] ?? 'Inhalt öffnen',
        ])
    @endforeach
</section>
@endsection

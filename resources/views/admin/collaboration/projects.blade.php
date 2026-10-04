@extends('admin.layouts.app')

@section('title', 'Admin-Projekte & Aufgaben · HNT-ACP')
@section('admin_heading', 'Aufgaben & To-do')

@section('content')
@php
    // Design fixture only: no persisted projects or fake production activity.
    $previewProjects = [
        ['title' => 'Community & Moderation', 'subtitle' => 'Musterprojekt 01', 'progress' => 68, 'tasks' => '7 / 10', 'due' => 'Beispielfrist', 'assignee' => 'Demo-Admin A'],
        ['title' => 'News & Guides', 'subtitle' => 'Musterprojekt 02', 'progress' => 38, 'tasks' => '3 / 8', 'due' => 'Beispielfrist', 'assignee' => 'Demo-Admin B'],
        ['title' => 'Maps & Arsenal', 'subtitle' => 'Musterprojekt 03', 'progress' => 75, 'tasks' => '6 / 8', 'due' => 'Beispielfrist', 'assignee' => 'Demo-Admin A'],
        ['title' => 'App & Infrastruktur', 'subtitle' => 'Musterprojekt 04', 'progress' => 15, 'tasks' => '2 / 12', 'due' => 'Beispielfrist', 'assignee' => 'Demo-Admin C'],
    ];
@endphp
<div class="acp-collaboration">
    <header class="acp-collab-head">
        <div>
            <span class="acp-collab-kicker">Intern / Zusammenarbeit</span>
            <h1>Aufgaben & Projekte</h1>
            <p>Projektübersicht nach dem Semi-Boxed-Template · <code>apps-projects-list.html</code></p>
        </div>
        <span class="acp-collab-tag">Entwicklungsvorschau · nicht gespeichert</span>
    </header>
    <div class="acp-collab-content">
        <section class="acp-collab-panel">
            <div class="acp-collab-filters">
                <span><strong>Projektkarten</strong> · Ansichtsvorschau</span>
                <small>Vier Beispieldatensätze, keine Datenbankverbindung</small>
            </div>
            <div class="acp-collab-grid">
                @foreach($previewProjects as $project)
                    <article class="acp-project-card is-example" aria-label="Beispielprojekt {{ $project['title'] }}">
                        <div class="acp-project-heading">
                            <div>
                                <strong>{{ $project['title'] }}</strong>
                                <small>{{ $project['subtitle'] }}</small>
                            </div>
                            <em>DEMO</em>
                        </div>
                        <div class="acp-project-meta">
                            <span>Aufgaben: {{ $project['tasks'] }}</span>
                            <span>{{ $project['due'] }}</span>
                            <span>Zuständig: {{ $project['assignee'] }}</span>
                        </div>
                        <div class="acp-project-progress">
                            <div><span>Fortschritt (Beispiel)</span><strong>{{ $project['progress'] }} %</strong></div>
                            <div class="acp-project-bar"><span style="--progress:{{ $project['progress'] }}%"></span></div>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
        <aside class="acp-collab-side">
            <section class="acp-collab-panel">
                <h2>Geplante Projektfunktionen</h2>
                <p>Die Karten zeigen das spätere Layout. Änderungen können hier noch nicht gespeichert werden.</p>
                <ul>
                    <li>Projekt erstellen und bearbeiten<small>Mit Beschreibung und Frist</small></li>
                    <li>Admins zuweisen<small>Mehrere Zuständigkeiten pro Projekt</small></li>
                    <li>Aufgaben und Checklisten<small>Fortschritt pro Projekt berechnen</small></li>
                    <li>Fälligkeiten und Status<small>Überblick über offene To-dos</small></li>
                </ul>
            </section>
            <section class="acp-collab-panel">
                <h2>Interner Chat</h2>
                <p>Auch der Admin-Chat erhält eine eigene Oberfläche. Aktuell werden dort keine Nachrichten gespeichert oder versendet.</p>
                <p style="margin-top:16px"><a href="{{ route('admin.collaboration.chat') }}" style="color:var(--acp-blue);text-decoration:underline">Chat-Vorschau öffnen</a></p>
            </section>
        </aside>
    </div>
</div>
@endsection

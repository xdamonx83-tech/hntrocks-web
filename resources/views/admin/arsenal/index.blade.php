@extends('admin.layouts.app')

@section('title', 'Arsenal QA · HNT-ACP')
@section('admin_heading', 'Arsenal QA')

@push('head')
<style>
.hnt-arsenal-grid{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:12px;margin:18px 0}
.hnt-arsenal-stat{padding:16px;border:1px solid #252b31;border-radius:14px;background:#11161a}
.hnt-arsenal-stat strong{display:block;font-size:26px;color:#f4f7f7}
.hnt-arsenal-stat span{font-size:11px;color:#8f9ba0;text-transform:uppercase;letter-spacing:.08em}
.hnt-arsenal-toolbar{display:grid;grid-template-columns:2fr repeat(4,1fr) auto;gap:10px;align-items:end;margin:18px 0}
.hnt-arsenal-toolbar label{display:flex;flex-direction:column;gap:6px;font-size:11px;color:#8f9ba0}
.hnt-arsenal-toolbar input,.hnt-arsenal-toolbar select{min-height:40px;padding:0 11px;border:1px solid #29333a;border-radius:8px;background:#0d1215;color:#e8eded}
.hnt-arsenal-table{width:100%;border-collapse:collapse}
.hnt-arsenal-table th,.hnt-arsenal-table td{padding:11px 10px;border-bottom:1px solid #222a2f;text-align:left;vertical-align:top}
.hnt-arsenal-item-cell{display:flex;align-items:center;gap:10px}.hnt-arsenal-thumb{width:64px;height:42px;object-fit:contain;border:1px solid #283239;border-radius:7px;background:#0b1012;padding:4px}
.hnt-arsenal-table th{font-size:10px;color:#7f8c91;text-transform:uppercase;letter-spacing:.08em}
.hnt-arsenal-table td{font-size:12px;color:#c7d0d2}
.hnt-arsenal-name{color:#fff;font-weight:700;text-decoration:none}
.hnt-arsenal-meta{display:block;margin-top:3px;color:#77858a;font-size:10px}
.hnt-arsenal-badge{display:inline-flex;padding:3px 7px;border:1px solid #344047;border-radius:999px;font-size:9px;text-transform:uppercase;letter-spacing:.06em}
.hnt-arsenal-split{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(300px,.7fr);gap:16px;align-items:start}
.hnt-arsenal-side{display:flex;flex-direction:column;gap:12px}
.hnt-arsenal-mini{padding:14px;border:1px solid #252b31;border-radius:12px;background:#101518}
.hnt-arsenal-mini h3{margin:0 0 10px;font-size:12px;color:#eef2f2}
.hnt-arsenal-mini p{margin:5px 0;font-size:11px;color:#96a2a6}
@media(max-width:1100px){.hnt-arsenal-grid{grid-template-columns:repeat(3,1fr)}.hnt-arsenal-toolbar{grid-template-columns:1fr 1fr}.hnt-arsenal-split{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Equipment Database</p>
        <h1>Arsenal QA</h1>
        <p>Read-only Prüfung der importierten Waffen, Tools, Consumables und Sync-Daten.</p>
    </div>
</section>

<div class="hnt-arsenal-grid">
    @foreach([
        'Gesamt' => $stats['total'],
        'Waffen' => $stats['weapons'],
        'Tools' => $stats['tools'],
        'Consumables' => $stats['consumables'],
        'Missing' => $stats['missing'],
        'Ohne lokales Bild' => $stats['without_local_asset'],
    ] as $label => $value)
        <div class="hnt-arsenal-stat"><strong>{{ $value }}</strong><span>{{ $label }}</span></div>
    @endforeach
</div>

<form method="get" class="hnt-arsenal-toolbar">
    <label>Suche
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, Slug oder Source-ID">
    </label>
    <label>Typ
        <select name="type">
            <option value="">Alle</option>
            @foreach(['weapon' => 'Waffen', 'tool' => 'Tools', 'consumable' => 'Consumables'] as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label>Status
        <select name="status">
            <option value="">Alle</option>
            <option value="active" @selected(($filters['status'] ?? '') === 'active')>Aktiv</option>
            <option value="missing" @selected(($filters['status'] ?? '') === 'missing')>Missing</option>
        </select>
    </label>
    <label>Klasse
        <select name="class">
            <option value="">Alle</option>
            @foreach($classes as $class)
                <option value="{{ $class }}" @selected(($filters['class'] ?? '') === $class)>{{ $class }}</option>
            @endforeach
        </select>
    </label>
    <label>Vergleichsgruppe
        <select name="group">
            <option value="">Alle</option>
            @foreach($groups as $group)
                <option value="{{ $group }}" @selected(($filters['group'] ?? '') === $group)>{{ $group }}</option>
            @endforeach
        </select>
    </label>
    <button class="hh-primary-button" type="submit">Filtern</button>
</form>

<div class="hnt-arsenal-split">
    <section class="hh-card hh-card-compact">
        <table class="hnt-arsenal-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Typ / Klasse</th>
                    <th>Gruppe</th>
                    <th>Ammo</th>
                    <th>Daten</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                @php($de = $item->translations->firstWhere('locale', 'de'))
                <tr>
                    <td>
                        <div class="hnt-arsenal-item-cell">
                            @if($item->imageUrl())<img class="hnt-arsenal-thumb" src="{{ $item->imageUrl() }}" alt="" loading="lazy">@endif
                            <span>
                                <a class="hnt-arsenal-name" href="{{ route('admin.arsenal.show', $item->slug) }}">{{ $de?->name ?: $item->name }}</a>
                                <span class="hnt-arsenal-meta">{{ $item->slug }}</span>
                            </span>
                        </div>
                    </td>
                    <td>{{ $item->item_type }}<span class="hnt-arsenal-meta">{{ $item->equipment_class ?: '—' }}</span></td>
                    <td><span class="hnt-arsenal-badge">{{ $item->comparison_group }}</span></td>
                    <td>{{ $item->ammo_type ?: '—' }}<span class="hnt-arsenal-meta">{{ $item->slot_size ? $item->slot_size.' Slots' : '' }}</span></td>
                    <td>{{ $item->stats_count }} Stats · {{ $item->ammo_count }} Ammo<span class="hnt-arsenal-meta">{{ $item->traits_count }} Traits · {{ $item->skins_count }} Skins</span></td>
                    <td>{{ $item->source_status }}<span class="hnt-arsenal-meta">{{ $item->last_synced_at?->format('d.m.Y H:i') }}</span></td>
                </tr>
            @empty
                <tr><td colspan="6">Keine Items für diesen Filter.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="hh-pagination">{{ $items->links() }}</div>
    </section>

    <aside class="hnt-arsenal-side">
        <section class="hnt-arsenal-mini">
            <h3>Letzte Syncs</h3>
            @forelse($syncRuns as $run)
                <p><strong>#{{ $run->id }} · {{ $run->status }}</strong> · {{ $run->source_name ?: 'Quelle' }}</p>
                <p>Neu {{ $run->counts['new'] ?? 0 }} · Geändert {{ $run->counts['changed'] ?? 0 }} · Unverändert {{ $run->counts['unchanged'] ?? 0 }} · Fehler {{ $run->counts['errors'] ?? 0 }}</p>
                <p>{{ $run->started_at }} @if($run->dry_run) · Dry Run @endif</p>
            @empty
                <p>Noch keine persistierten Sync-Runs.</p>
            @endforelse
        </section>

        <section class="hnt-arsenal-mini">
            <h3>Letzte Änderungen</h3>
            @forelse($recentChanges as $change)
                <p>
                    @if($change->slug)
                        <a class="hnt-arsenal-name" href="{{ route('admin.arsenal.show', $change->slug) }}">{{ $change->name ?: $change->external_id }}</a>
                    @else
                        <strong>{{ $change->external_id }}</strong>
                    @endif
                    <span class="hnt-arsenal-meta">{{ $change->change_type }}{{ $change->field ? ' · '.$change->field : '' }} · {{ $change->created_at }}</span>
                </p>
            @empty
                <p>Keine protokollierten Änderungen.</p>
            @endforelse
        </section>
    </aside>
</div>
@endsection

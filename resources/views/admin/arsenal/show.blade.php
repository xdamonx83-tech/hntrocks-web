@extends('admin.layouts.app')

@section('title', $item->name.' · Arsenal QA')
@section('admin_heading', 'Arsenal QA')

@push('head')
<style>
.hnt-arsenal-detail-head{display:flex;align-items:flex-start;justify-content:space-between;gap:18px;margin-bottom:16px}
.hnt-arsenal-detail-head h1{margin:0}.hnt-arsenal-detail-head p{margin:6px 0;color:#8d9a9e}
.hnt-arsenal-detail-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin:16px 0}
.hnt-arsenal-box{padding:14px;border:1px solid #263037;border-radius:12px;background:#101518}
.hnt-arsenal-box span{display:block;font-size:9px;color:#7f8c91;text-transform:uppercase;letter-spacing:.08em}
.hnt-arsenal-box strong{display:block;margin-top:4px;color:#edf1f1;font-size:13px}
.hnt-arsenal-columns{display:grid;grid-template-columns:minmax(0,1.2fr) minmax(320px,.8fr);gap:16px;align-items:start}
.hnt-arsenal-stack{display:flex;flex-direction:column;gap:14px}
.hnt-arsenal-table{width:100%;border-collapse:collapse}
.hnt-arsenal-table th,.hnt-arsenal-table td{padding:9px;border-bottom:1px solid #232b30;text-align:left;font-size:11px;vertical-align:top}
.hnt-arsenal-table th{color:#7f8c91;text-transform:uppercase;font-size:9px;letter-spacing:.08em}
.hnt-arsenal-copy{white-space:pre-line;color:#b4bec0;line-height:1.55}
.hnt-arsenal-pill{display:inline-flex;margin:2px;padding:3px 7px;border:1px solid #354147;border-radius:999px;font-size:9px}
.hnt-arsenal-json{max-height:360px;overflow:auto;padding:12px;background:#0a0e10;border-radius:8px;font-size:10px;color:#aeb9bc}
@media(max-width:1050px){.hnt-arsenal-detail-grid{grid-template-columns:1fr 1fr}.hnt-arsenal-columns{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
@php($de = $item->translations->firstWhere('locale', 'de'))
<div class="hnt-arsenal-detail-head">
    <div>
        <p class="hh-kicker">Arsenal Item #{{ $item->id }}</p>
        <h1>{{ $de?->name ?: $item->name }}</h1>
        <p>{{ $item->slug }} · {{ $item->external_id }}</p>
    </div>
    <a class="hh-primary-button" href="{{ route('admin.arsenal.index') }}">Zur Übersicht</a>
</div>

<div class="hnt-arsenal-detail-grid">
    <div class="hnt-arsenal-box"><span>Typ</span><strong>{{ $item->item_type }}</strong></div>
    <div class="hnt-arsenal-box"><span>Klasse</span><strong>{{ $item->equipment_class ?: '—' }}</strong></div>
    <div class="hnt-arsenal-box"><span>Vergleichsgruppe</span><strong>{{ $item->comparison_group }}</strong></div>
    <div class="hnt-arsenal-box"><span>Status</span><strong>{{ $item->source_status }}</strong></div>
    <div class="hnt-arsenal-box"><span>Munition</span><strong>{{ $item->ammo_type ?: '—' }}</strong></div>
    <div class="hnt-arsenal-box"><span>Slots</span><strong>{{ $item->slot_size ?? '—' }}</strong></div>
    <div class="hnt-arsenal-box"><span>Preis</span><strong>{{ $item->price ?? '—' }}</strong></div>
    <div class="hnt-arsenal-box"><span>Familie</span><strong>{{ $item->family?->name ?: '—' }}</strong></div>
</div>

<div class="hnt-arsenal-columns">
    <div class="hnt-arsenal-stack">
        <section class="hh-card hh-card-compact">
            <h2>Beschreibungen</h2>
            @foreach(['de','en','es','ru'] as $locale)
                @php($translation = $item->translations->firstWhere('locale', $locale))
                <div style="margin-top:12px">
                    <strong>{{ strtoupper($locale) }} @if($translation?->description_is_manual) · manuell @endif</strong>
                    <p class="hnt-arsenal-copy">{{ $translation?->description ?: 'Keine eigene Beschreibung – API nutzt ggf. Fallback.' }}</p>
                </div>
            @endforeach
        </section>

        <section class="hh-card hh-card-compact">
            <h2>Stats</h2>
            <table class="hnt-arsenal-table">
                <thead><tr><th>Gruppe</th><th>Wert</th><th>Richtung</th></tr></thead>
                <tbody>
                @forelse($item->stats->sortBy(fn($stat) => $stat->definition?->sort_order ?? 9999) as $stat)
                    <tr>
                        <td>{{ $stat->definition?->label ?: '—' }}</td>
                        <td>{{ $stat->value }} {{ $stat->definition?->unit }}</td>
                        <td>{{ $stat->definition?->comparison_direction ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Keine Stats.</td></tr>
                @endforelse
                </tbody>
            </table>
        </section>

        <section class="hh-card hh-card-compact">
            <h2>Ammo & Falloff</h2>
            @forelse($item->ammo as $ammo)
                <div style="margin-top:14px">
                    <strong>{{ $ammo->name }} · {{ $ammo->ammo_type ?: '—' }}</strong>
                    <p class="hnt-arsenal-copy">Damage {{ $ammo->damage ?? '—' }} · Velocity {{ $ammo->velocity ?? '—' }} · Loaded {{ $ammo->loaded ?? '—' }} · Reserve {{ $ammo->reserve ?? '—' }}</p>
                    @if($ammo->falloffPoints->isNotEmpty())
                        <table class="hnt-arsenal-table">
                            <thead><tr><th>Distanz</th><th>Damage</th></tr></thead>
                            <tbody>@foreach($ammo->falloffPoints as $point)<tr><td>{{ $point->distance }} m</td><td>{{ $point->damage }}</td></tr>@endforeach</tbody>
                        </table>
                    @endif
                </div>
            @empty
                <p class="hnt-arsenal-copy">Keine Ammo-Daten.</p>
            @endforelse
        </section>
    </div>

    <aside class="hnt-arsenal-stack">
        <section class="hh-card hh-card-compact">
            <h2>Relations</h2>
            <p><strong>Traits:</strong><br>@forelse($item->traits as $trait)<span class="hnt-arsenal-pill">{{ $trait->name }}</span>@empty — @endforelse</p>
            <p><strong>Varianten:</strong><br>@forelse($variants as $variant)<a class="hnt-arsenal-pill" href="{{ route('admin.arsenal.show', $variant->slug) }}">{{ $variant->name }}</a>@empty — @endforelse</p>
            <p><strong>Skins:</strong> {{ $item->skins->count() }}</p>
            @foreach($item->skins->take(20) as $skin)<span class="hnt-arsenal-pill">{{ $skin->name ?: $skin->external_id }}{{ $skin->rarity ? ' · '.$skin->rarity : '' }}</span>@endforeach
            @if($item->skins->count() > 20)<p class="hnt-arsenal-copy">+ {{ $item->skins->count() - 20 }} weitere</p>@endif
        </section>

        <section class="hh-card hh-card-compact">
            <h2>Asset-Status</h2>
            <p class="hnt-arsenal-copy">Original: {{ $item->original_asset_url ?: '—' }}</p>
            <p class="hnt-arsenal-copy">Lokal: {{ $item->local_asset_path ?: '—' }}</p>
            <p class="hnt-arsenal-copy">Lizenz: {{ $item->license_note ?: '—' }}</p>
        </section>

        <section class="hh-card hh-card-compact">
            <h2>Quelle</h2>
            <p class="hnt-arsenal-copy">{{ $item->source?->name ?: '—' }}</p>
            <p class="hnt-arsenal-copy">{{ $item->source_url ?: '—' }}</p>
            <p class="hnt-arsenal-copy">Letzter Sync: {{ $item->last_synced_at?->format('d.m.Y H:i:s') ?: '—' }}</p>
        </section>

        <section class="hh-card hh-card-compact">
            <h2>Sync-Historie</h2>
            @forelse($changes as $change)
                <p class="hnt-arsenal-copy"><strong>{{ $change->change_type }}</strong>{{ $change->field ? ' · '.$change->field : '' }}<br>{{ $change->created_at }}</p>
            @empty
                <p class="hnt-arsenal-copy">Keine Änderungen protokolliert.</p>
            @endforelse
        </section>

        <details class="hh-card hh-card-compact">
            <summary>Importierte Fakten anzeigen</summary>
            <pre class="hnt-arsenal-json">{{ json_encode($item->facts, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) }}</pre>
        </details>
    </aside>
</div>
@endsection

@extends('admin.layouts.app')

@section('title', 'Cup-Einreichungen · Admin · HNT.rocks')
@section('admin_heading', 'Cup-Einreichungen')

@section('content')
<style>
    .cupmod-stats {
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:14px;
        margin:20px 0;
    }
    .cupmod-stat {
        background:#11110f;
        border:1px solid rgba(255,255,255,.08);
        border-radius:16px;
        padding:18px;
    }
    .cupmod-stat span { display:block;color:#999;font-size:13px;margin-bottom:5px; }
    .cupmod-stat strong { font-size:27px; }

    .cupmod-filter {
        display:grid;
        grid-template-columns:1.2fr 1fr 1fr auto;
        gap:12px;
        align-items:end;
        background:#11110f;
        border:1px solid rgba(255,255,255,.08);
        border-radius:16px;
        padding:18px;
        margin-bottom:22px;
    }
    .cupmod-filter label,
    .cupmod-field label {
        display:block;
        font-size:12px;
        color:#aaa;
        margin-bottom:6px;
    }
    .cupmod-filter input,
    .cupmod-filter select,
    .cupmod-field input,
    .cupmod-field select,
    .cupmod-field textarea {
        width:100%;
        box-sizing:border-box;
        background:#090a08;
        color:#eee;
        border:1px solid rgba(255,255,255,.12);
        border-radius:10px;
        padding:10px 12px;
    }
    .cupmod-list { display:grid;gap:20px; }
    .cupmod-card {
        background:#11110f;
        border:1px solid rgba(255,255,255,.08);
        border-radius:18px;
        overflow:hidden;
    }
    .cupmod-head {
        display:flex;
        justify-content:space-between;
        gap:18px;
        padding:18px 20px;
        border-bottom:1px solid rgba(255,255,255,.08);
    }
    .cupmod-head h2 { margin:0 0 4px;font-size:18px; }
    .cupmod-head p { margin:0;color:#999;font-size:13px; }

    .cupmod-status {
        align-self:flex-start;
        border-radius:999px;
        padding:7px 10px;
        font-size:12px;
        font-weight:700;
        background:#26241d;
    }
    .cupmod-status.open { color:#e3b55f; }
    .cupmod-status.approved { color:#a7c86f; }
    .cupmod-status.rejected { color:#db7a70; }

    .cupmod-body {
        display:grid;
        grid-template-columns:minmax(300px,1.05fr) minmax(340px,1fr);
        gap:22px;
        padding:20px;
    }
    .cupmod-image {
        display:block;
        width:100%;
        max-height:430px;
        object-fit:contain;
        background:#050505;
        border-radius:12px;
        border:1px solid rgba(255,255,255,.08);
    }
    .cupmod-open-image {
        display:inline-block;
        margin-top:9px;
        color:#b6ca78;
        text-decoration:none;
        font-size:13px;
    }
    .cupmod-reported {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:8px;
        margin-top:14px;
    }
    .cupmod-reported div {
        background:#0b0c0a;
        border-radius:10px;
        padding:11px;
    }
    .cupmod-reported span { display:block;color:#888;font-size:11px; }
    .cupmod-reported strong { display:block;margin-top:3px;font-size:16px; }

    .cupmod-fields {
        display:grid;
        grid-template-columns:repeat(3,1fr);
        gap:12px;
    }
    .cupmod-field.full { grid-column:1/-1; }

    .cupmod-extract {
        display:flex;
        align-items:center;
        gap:8px;
        margin:15px 0;
        color:#ddd;
    }
    .cupmod-extract input { width:auto; }

    .cupmod-score {
        background:#0a0b09;
        border:1px solid rgba(255,255,255,.07);
        border-radius:12px;
        padding:13px;
        margin:15px 0;
        color:#aaa;
        font-size:13px;
    }
    .cupmod-score strong { color:#eee;font-size:18px; }

    .cupmod-actions {
        display:flex;
        flex-wrap:wrap;
        gap:10px;
        margin-top:14px;
    }
    .cupmod-btn {
        border:0;
        border-radius:10px;
        padding:10px 16px;
        font-weight:700;
        cursor:pointer;
    }
    .cupmod-btn.accept { background:#a8c875;color:#10120c; }
    .cupmod-btn.reject { background:#542824;color:#f2b1aa; }
    .cupmod-btn.filter { background:#a8c875;color:#10120c; }

    .cupmod-note {
        margin-top:12px;
        padding:12px;
        border-left:3px solid #555;
        background:#0a0b09;
        color:#aaa;
        font-size:13px;
    }

    .cupmod-empty {
        padding:36px;
        text-align:center;
        color:#888;
        background:#11110f;
        border-radius:16px;
    }

    @media (max-width: 1000px) {
        .cupmod-stats { grid-template-columns:repeat(2,1fr); }
        .cupmod-filter { grid-template-columns:1fr 1fr; }
        .cupmod-body { grid-template-columns:1fr; }
    }
    @media (max-width: 650px) {
        .cupmod-stats,
        .cupmod-filter,
        .cupmod-fields,
        .cupmod-reported { grid-template-columns:1fr; }
    }
</style>

<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin</p>
        <h1>Cup-Einreichungen</h1>
        <p>Screenshots prüfen, Werte bestätigen und Runs freigeben oder ablehnen.</p>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="hh-alert hh-alert-danger">
        {{ $errors->first() }}
    </div>
@endif

<div class="cupmod-stats">
    <div class="cupmod-stat"><span>Alle</span><strong>{{ $counts['all'] }}</strong></div>
    <div class="cupmod-stat"><span>Prüfung offen</span><strong>{{ $counts['open'] }}</strong></div>
    <div class="cupmod-stat"><span>Angenommen</span><strong>{{ $counts['approved'] }}</strong></div>
    <div class="cupmod-stat"><span>Abgelehnt</span><strong>{{ $counts['rejected'] }}</strong></div>
</div>

<form method="GET" action="{{ route('admin.cup-submissions.index') }}" class="cupmod-filter">
    <div>
        <label for="cup">Cup</label>
        <select id="cup" name="cup">
            <option value="">Alle Cups</option>
            @foreach($cups as $cup)
                <option value="{{ $cup->id }}" @selected($selectedCup === $cup->id)>
                    {{ $cup->title }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="all" @selected($selectedStatus === 'all')>Alle</option>
            <option value="open" @selected($selectedStatus === 'open')>Prüfung offen</option>
            <option value="approved" @selected($selectedStatus === 'approved')>Angenommen</option>
            <option value="rejected" @selected($selectedStatus === 'rejected')>Abgelehnt</option>
        </select>
    </div>

    <div>
        <label for="q">Spieler suchen</label>
        <input id="q" name="q" value="{{ $search }}" placeholder="Username">
    </div>

    <button class="cupmod-btn filter" type="submit">Filtern</button>
</form>

<div class="cupmod-list">
@forelse($submissions as $submission)
    @php
        $reportedKills = $submission->reported_kills;
        $reportedBounty = $submission->reported_bounty_tokens;
        $reportedExtracted = $submission->reported_extracted;

        $defaultKills = $reportedKills !== null ? $reportedKills : $submission->kills;
        $defaultBounty = $reportedBounty !== null ? $reportedBounty : $submission->bounty_tokens;
        $defaultExtracted = $reportedExtracted !== null ? $reportedExtracted : $submission->extracted;

        $statusClass = in_array($submission->status, ['approved_manual', 'approved', 'processed'], true)
            ? 'approved'
            : (in_array($submission->status, ['rejected_manual', 'rejected', 'invalid'], true) ? 'rejected' : 'open');
    @endphp

    <article class="cupmod-card" data-score-card>
        <div class="cupmod-head">
            <div>
                <h2>
                    {{ $submission->cup?->title ?? 'Unbekannter Cup' }}
                    · Einreichung #{{ $submission->id }}
                </h2>
                <p>
                    @{{ $submission->submitter?->username ?? 'unbekannt' }}
                    · {{ optional($submission->submitted_at ?? $submission->created_at)->format('d.m.Y H:i') }}
                    · {{ $submission->statusLabel() }}
                </p>
            </div>
            <span class="cupmod-status {{ $statusClass }}">
                {{ $submission->statusLabel() }}
            </span>
        </div>

        <div class="cupmod-body">
            <div>
                @if($submission->cup)
                    <a
                        href="{{ route('cups.submissions.screenshot', [$submission->cup, $submission]) }}"
                        target="_blank"
                        rel="noopener"
                    >
                        <img
                            class="cupmod-image"
                            src="{{ route('cups.submissions.screenshot', [$submission->cup, $submission]) }}"
                            alt="Screenshot Einreichung #{{ $submission->id }}"
                            loading="lazy"
                        >
                    </a>

                    <a
                        class="cupmod-open-image"
                        href="{{ route('cups.submissions.screenshot', [$submission->cup, $submission]) }}"
                        target="_blank"
                        rel="noopener"
                    >
                        Screenshot in voller Größe öffnen
                    </a>
                @endif

                <div class="cupmod-reported">
                    <div>
                        <span>Vom Spieler: Kills</span>
                        <strong>{{ $reportedKills ?? '–' }}</strong>
                    </div>
                    <div>
                        <span>Vom Spieler: Bounty</span>
                        <strong>{{ $reportedBounty ?? '–' }}</strong>
                    </div>
                    <div>
                        <span>Vom Spieler: Extraktion</span>
                        <strong>
                            @if($reportedExtracted === null)
                                –
                            @else
                                {{ $reportedExtracted ? 'Ja' : 'Nein' }}
                            @endif
                        </strong>
                    </div>
                </div>

                @if($submission->note)
                    <div class="cupmod-note">
                        <strong>Spieler-Notiz:</strong><br>
                        {{ $submission->note }}
                    </div>
                @endif
            </div>

            <div>
                @if($submission->cup)
                <form
                    method="POST"
                    action="{{ route('cups.submissions.manual-score', [$submission->cup, $submission]) }}"
                    data-score-form
                >
                    @csrf
                    <input type="hidden" name="return_to" value="admin">

                    <div class="cupmod-fields">
                        <div class="cupmod-field">
                            <label>Hunter-Kills</label>
                            <input
                                type="number"
                                name="kills"
                                min="0"
                                max="99"
                                value="{{ $defaultKills }}"
                                required
                                data-kills
                            >
                        </div>

                        <div class="cupmod-field">
                            <label>Bounty-Tokens</label>
                            <input
                                type="number"
                                name="bounty_tokens"
                                min="0"
                                max="4"
                                value="{{ $defaultBounty }}"
                                required
                                data-bounty
                            >
                        </div>

                        <div class="cupmod-field">
                            <label>Bestätigte Banishes</label>
                            <select name="banishes" data-banishes>
                                <option value="0" @selected((int) $submission->banishes === 0)>0</option>
                                <option value="1" @selected((int) $submission->banishes === 1)>1</option>
                                <option value="2" @selected((int) $submission->banishes === 2)>2</option>
                            </select>
                        </div>

                        <div class="cupmod-field full">
                            <input type="hidden" name="extracted" value="0">
                            <label class="cupmod-extract">
                                <input
                                    type="checkbox"
                                    name="extracted"
                                    value="1"
                                    @checked($defaultExtracted)
                                    data-extracted
                                >
                                Erfolgreiche Extraktion bestätigt
                            </label>
                        </div>

                        <div class="cupmod-field full">
                            <label>Admin-Notiz</label>
                            <textarea name="review_note" rows="3">{{ $submission->review_note }}</textarea>
                        </div>
                    </div>

                    @if($submission->cup->usesManualReviewScoring())
                        <div class="cupmod-score">
                            <div>
                                Kills × 1 + Bounty × 2 + Banish × 2
                            </div>
                            <div style="margin-top:6px">
                                Berechnete Punkte:
                                <strong data-score>0</strong>
                            </div>
                            <small>Ohne erfolgreiche Extraktion oder ohne Bounty-Token: 0 Punkte.</small>
                        </div>
                    @else
                        <div class="cupmod-score">
                            Dieser Cup verwendet eine andere Wertungsregel.
                            Die Serverlogik berechnet die Punkte beim Speichern.
                        </div>
                    @endif

                    <div class="cupmod-actions">
                        <button class="cupmod-btn accept" type="submit">
                            ✓ Werte bestätigen & annehmen
                        </button>

                        <button
                            class="cupmod-btn reject"
                            type="submit"
                            formaction="{{ route('cups.submissions.reject', [$submission->cup, $submission]) }}"
                            onclick="return confirm('Diese Einreichung wirklich ablehnen?')"
                        >
                            ✕ Ablehnen
                        </button>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </article>
@empty
    <div class="cupmod-empty">
        Keine Einreichungen für diesen Filter gefunden.
    </div>
@endforelse
</div>

<div style="margin-top:24px">
    {{ $submissions->links() }}
</div>

<script>
document.querySelectorAll('[data-score-card]').forEach((card) => {
    const kills = card.querySelector('[data-kills]');
    const bounty = card.querySelector('[data-bounty]');
    const banishes = card.querySelector('[data-banishes]');
    const extracted = card.querySelector('[data-extracted]');
    const score = card.querySelector('[data-score]');

    if (!kills || !bounty || !banishes || !extracted || !score) return;

    const update = () => {
        const k = Math.max(0, Number(kills.value) || 0);
        const b = Math.max(0, Math.min(4, Number(bounty.value) || 0));
        const bn = Math.max(0, Math.min(2, Number(banishes.value) || 0));

        score.textContent = extracted.checked && b > 0
            ? String(k + (b * 2) + (bn * 2))
            : '0';
    };

    [kills, bounty, banishes, extracted].forEach((element) => {
        element.addEventListener('input', update);
        element.addEventListener('change', update);
    });

    update();
});
</script>
@endsection

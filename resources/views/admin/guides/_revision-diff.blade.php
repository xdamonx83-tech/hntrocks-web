@inject('guideDiffService', 'App\Services\Guides\GuideRevisionDiffService')

@php
    $revisionDiff = $guideDiffService->compare($guide->publishedRevision, $revision);
@endphp

@if($revisionDiff['applicable'])
    <style>
        .hnt-guide-diff-grid{display:grid;gap:14px}.hnt-guide-diff-summary{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.hnt-guide-diff-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border:1px solid var(--acp-border-2);border-radius:999px;background:var(--acp-active);font-size:12px}.hnt-guide-diff-pill.is-add{color:var(--acp-green)}.hnt-guide-diff-pill.is-remove{color:var(--acp-red)}.hnt-guide-diff-pill.is-change{color:var(--acp-gold)}.hnt-guide-diff-item{padding:16px;border:1px solid var(--acp-border);border-radius:14px;background:var(--acp-panel-2)}.hnt-guide-diff-item.is-add{border-color:color-mix(in srgb,var(--acp-green) 34%,var(--acp-border))}.hnt-guide-diff-item.is-remove{border-color:color-mix(in srgb,var(--acp-red) 34%,var(--acp-border))}.hnt-guide-diff-item.is-change{border-color:color-mix(in srgb,var(--acp-gold) 30%,var(--acp-border))}.hnt-guide-diff-head{display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:10px}.hnt-guide-diff-head strong{color:#fff}.hnt-guide-diff-change{display:grid;gap:8px;padding-top:10px;border-top:1px solid var(--acp-border)}.hnt-guide-diff-change:first-of-type{padding-top:0;border-top:0}.hnt-guide-diff-label{color:var(--acp-muted);font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em}.hnt-guide-diff-inline{white-space:pre-wrap;overflow-wrap:anywhere;line-height:1.7;color:#fff}.hnt-guide-diff-inline ins{text-decoration:none;color:var(--acp-green);background:color-mix(in srgb,var(--acp-green) 12%,transparent);border-radius:4px;padding:1px 2px}.hnt-guide-diff-inline del{color:var(--acp-red);background:color-mix(in srgb,var(--acp-red) 12%,transparent);border-radius:4px;padding:1px 2px}.hnt-guide-diff-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.hnt-guide-diff-value{padding:10px 12px;border-radius:10px;background:var(--acp-active);white-space:pre-wrap;overflow-wrap:anywhere}.hnt-guide-diff-value small{display:block;margin-bottom:5px;color:var(--acp-muted)}.hnt-guide-diff-value.is-old{border-left:3px solid var(--acp-red)}.hnt-guide-diff-value.is-new{border-left:3px solid var(--acp-green)}@media(max-width:760px){.hnt-guide-diff-columns{grid-template-columns:1fr}}
    </style>

    <section class="hh-card hh-section-space">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Versionsvergleich</p>
                <h2>Änderungen gegenüber Version {{ $revisionDiff['baseline_version'] }}</h2>
                <p class="hh-muted">Eingereicht als Version {{ $revisionDiff['candidate_version'] }}. Grün = hinzugefügt, Rot = entfernt.</p>
            </div>
            <div class="hnt-guide-diff-summary">
                @if($revisionDiff['counts']['added'])<span class="hnt-guide-diff-pill is-add">+{{ $revisionDiff['counts']['added'] }} Blöcke</span>@endif
                @if($revisionDiff['counts']['removed'])<span class="hnt-guide-diff-pill is-remove">−{{ $revisionDiff['counts']['removed'] }} Blöcke</span>@endif
                @if($revisionDiff['counts']['changed'])<span class="hnt-guide-diff-pill is-change">{{ $revisionDiff['counts']['changed'] }} geändert</span>@endif
                @if($revisionDiff['counts']['moved'])<span class="hnt-guide-diff-pill">{{ $revisionDiff['counts']['moved'] }} verschoben</span>@endif
            </div>
        </div>

        @if(!$revisionDiff['has_changes'])
            <div class="hh-alert hh-alert-success hh-section-space">Zwischen den beiden Versionen wurden keine inhaltlichen Änderungen erkannt.</div>
        @else
            @if(!empty($revisionDiff['metadata']))
                <div class="hnt-guide-diff-grid hh-section-space">
                    @foreach($revisionDiff['metadata'] as $change)
                        <article class="hnt-guide-diff-item is-change">
                            <div class="hnt-guide-diff-head">
                                <strong>{{ $change['label'] }}</strong>
                                <span class="hnt-guide-diff-pill is-change">Geändert</span>
                            </div>

                            @if(($change['kind'] ?? '') === 'text')
                                <div class="hnt-guide-diff-inline">
                                    @foreach($change['segments'] as $segment)
                                        @if($segment['type'] === 'add')<ins>{{ $segment['text'] }}</ins>@elseif($segment['type'] === 'remove')<del>{{ $segment['text'] }}</del>@else<span>{{ $segment['text'] }}</span>@endif
                                    @endforeach
                                </div>
                            @else
                                <div class="hnt-guide-diff-columns">
                                    <div class="hnt-guide-diff-value is-old"><small>Vorher</small>{{ $change['old'] }}</div>
                                    <div class="hnt-guide-diff-value is-new"><small>Neu</small>{{ $change['new'] }}</div>
                                </div>
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif

            @if(!empty($revisionDiff['blocks']))
                <div class="hnt-guide-diff-grid hh-section-space">
                    @foreach($revisionDiff['blocks'] as $blockChange)
                        <article class="hnt-guide-diff-item {{ ($blockChange['status'] ?? '') === 'added' ? 'is-add' : (($blockChange['status'] ?? '') === 'removed' ? 'is-remove' : 'is-change') }}">
                            <div class="hnt-guide-diff-head">
                                <strong>{{ $blockChange['label'] }}</strong>
                                <div class="hnt-guide-diff-summary">
                                    @if(($blockChange['status'] ?? '') === 'added')
                                        <span class="hnt-guide-diff-pill is-add">Hinzugefügt · Position {{ $blockChange['new_position'] }}</span>
                                    @elseif(($blockChange['status'] ?? '') === 'removed')
                                        <span class="hnt-guide-diff-pill is-remove">Entfernt · Position {{ $blockChange['old_position'] }}</span>
                                    @else
                                        <span class="hnt-guide-diff-pill is-change">Geändert</span>
                                        @if(!empty($blockChange['moved']))<span class="hnt-guide-diff-pill">Position {{ $blockChange['old_position'] }} → {{ $blockChange['new_position'] }}</span>@endif
                                    @endif
                                </div>
                            </div>

                            @if(($blockChange['status'] ?? '') === 'added')
                                <div class="hnt-guide-diff-value is-new">{{ $blockChange['new_text'] ?: 'Leerer Block' }}</div>
                            @elseif(($blockChange['status'] ?? '') === 'removed')
                                <div class="hnt-guide-diff-value is-old">{{ $blockChange['old_text'] ?: 'Leerer Block' }}</div>
                            @else
                                @foreach(($blockChange['changes'] ?? []) as $change)
                                    <div class="hnt-guide-diff-change">
                                        <div class="hnt-guide-diff-label">{{ $change['label'] }}</div>
                                        @if(($change['kind'] ?? '') === 'text')
                                            <div class="hnt-guide-diff-inline">
                                                @foreach($change['segments'] as $segment)
                                                    @if($segment['type'] === 'add')<ins>{{ $segment['text'] }}</ins>@elseif($segment['type'] === 'remove')<del>{{ $segment['text'] }}</del>@else<span>{{ $segment['text'] }}</span>@endif
                                                @endforeach
                                            </div>
                                        @else
                                            <div class="hnt-guide-diff-columns">
                                                <div class="hnt-guide-diff-value is-old"><small>Vorher</small>{{ $change['old'] }}</div>
                                                <div class="hnt-guide-diff-value is-new"><small>Neu</small>{{ $change['new'] }}</div>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            @endif
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </section>
@endif

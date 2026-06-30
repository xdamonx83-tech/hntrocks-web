@extends('themes.rework.layouts.app')

@section('title', 'HNT.rocks Members')
@section('body_class', 'members-page')
@section('left_col_class', 'members-left-col')

@section('content')
<section class="members-head">
    <div>
        <span class="members-eyebrow">HNT Community</span>
        <h1>Members</h1>
    </div>
    <a class="members-filter-btn" data-members-filter-open href="#">
        <i aria-hidden="true" class="ph ph-funnel-simple ph-icon"></i>
        Filters
    </a>
</section>

<section aria-label="Members Filter" class="members-controls">
    <div aria-label="Memberansicht" class="members-tabs" role="tablist">
        <a class="active" href="#">Alle</a>
        <a href="#">Freunde</a>
    </div>
    <label aria-label="Members durchsuchen" class="members-search">
        <i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>
        <input placeholder="Search" type="text"/>
    </label>
</section>

<section aria-label="Members" class="members-grid">
    <article class="member-card member-card-featured">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/post-cover.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="Edward Evans" src="{{ \App\Support\HntTheme::asset('images/profile-avatar-krispie.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>Edward Evans</strong><small>Level 2</small></div>
            </div>
            <p>Noch keine Kurzbeschreibung.</p>
            <div class="member-mini-stats">
                <span><b>12</b><small>Posts</small></span>
                <span><b>7</b><small>Freunde</small></span>
                <span><b>4</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>Plattform offen</span>
                <span>Spielstil offen</span>
                <span>Region offen</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>

    <article class="member-card">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/high-2.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="Ted Stinson" src="{{ \App\Support\HntTheme::asset('images/sug-1.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>Ted Stinson</strong><small>Level 6</small></div>
            </div>
            <p>Spielt gern taktisch, ruhig und mit klarer Ansage.</p>
            <div class="member-mini-stats">
                <span><b>31</b><small>Posts</small></span>
                <span><b>18</b><small>Freunde</small></span>
                <span><b>9</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>Xbox</span>
                <span>Locker</span>
                <span>EU</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>

    <article class="member-card">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/high-3.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="Lily Aldrian" src="{{ \App\Support\HntTheme::asset('images/sug-2.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>Lily Aldrian</strong><small>Level 4</small></div>
            </div>
            <p>Community Runs, Loadout-Ideen und schnelle Duo-Runden.</p>
            <div class="member-mini-stats">
                <span><b>18</b><small>Posts</small></span>
                <span><b>11</b><small>Freunde</small></span>
                <span><b>6</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>PC</span>
                <span>Fokus</span>
                <span>EU</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>

    <article class="member-card">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/post-cover.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="MKBHD" src="{{ \App\Support\HntTheme::asset('images/sug-3.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>MKBHD</strong><small>Level 9</small></div>
            </div>
            <p>Testet Loadouts und sucht meist entspannte Abendrunden.</p>
            <div class="member-mini-stats">
                <span><b>44</b><small>Posts</small></span>
                <span><b>22</b><small>Freunde</small></span>
                <span><b>14</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>PS5</span>
                <span>Push</span>
                <span>EU</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>

    <article class="member-card">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/high-1.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="BigDaddy" src="{{ \App\Support\HntTheme::asset('images/high-1.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>BigDaddy</strong><small>Level 12</small></div>
            </div>
            <p>Schießt lieber kontrolliert und spielt meistens Trios.</p>
            <div class="member-mini-stats">
                <span><b>27</b><small>Posts</small></span>
                <span><b>15</b><small>Freunde</small></span>
                <span><b>5</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>Xbox</span>
                <span>Taktisch</span>
                <span>EU</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>

    <article class="member-card">
        <div class="member-cover"><img alt="" src="{{ \App\Support\HntTheme::asset('images/high-2.png', 'rework') }}"/></div>
        <div class="member-card-body">
            <div class="member-card-topline">
                <div class="member-avatar-wrap">
                    <img alt="NoobPlayer69" src="{{ \App\Support\HntTheme::asset('images/high-2.png', 'rework') }}"/>
                    <span><img alt="" src="{{ \App\Support\HntTheme::asset('images/bounty-mark.png', 'rework') }}"/></span>
                </div>
                <div><strong>NoobPlayer69</strong><small>Level 3</small></div>
            </div>
            <p>Neu dabei, aber motiviert für entspannte Community-Runs.</p>
            <div class="member-mini-stats">
                <span><b>8</b><small>Posts</small></span>
                <span><b>5</b><small>Freunde</small></span>
                <span><b>2</b><small>Moments</small></span>
            </div>
            <div class="member-meta-grid">
                <span>Offen</span>
                <span>Locker</span>
                <span>EU</span>
                <span>LFG offen</span>
            </div>
            <div class="member-actions">
                <a class="btn" href="#">Freund hinzufügen</a>
                <a class="btn light" href="#">Nachricht</a>
            </div>
        </div>
    </article>
</section>
@endsection

@push('rework-modals')
<div aria-hidden="true" class="modal-backdrop members-filter-backdrop" data-members-filter-modal>
    <section aria-labelledby="members-filter-title" aria-modal="true" class="members-filter-modal" role="dialog">
        <header class="members-filter-head">
            <div>
                <span>Members</span>
                <h2 id="members-filter-title">Filter</h2>
            </div>
            <button aria-label="Filter schließen" data-members-filter-close type="button">
                <i aria-hidden="true" class="ph ph-x ph-icon"></i>
            </button>
        </header>
        <div class="members-filter-grid">
            <label class="members-field">
                <span>Plattform</span>
                <select>
                    <option>Alle Plattformen</option>
                    <option>PC</option>
                    <option>Xbox</option>
                    <option>PlayStation</option>
                </select>
            </label>
            <label class="members-field">
                <span>Spielstil</span>
                <select>
                    <option>Alle Spielstile</option>
                    <option>Locker</option>
                    <option>Taktisch</option>
                    <option>Push</option>
                </select>
            </label>
            <label class="members-field">
                <span>Region</span>
                <select>
                    <option>Alle Regionen</option>
                    <option>EU</option>
                    <option>US</option>
                </select>
            </label>
            <label class="members-field">
                <span>Sprache</span>
                <select>
                    <option>Alle Sprachen</option>
                    <option>Deutsch</option>
                    <option>Englisch</option>
                </select>
            </label>
            <label class="members-check">
                <input type="checkbox"/>
                <span></span>
                <strong>Nur LFG</strong>
            </label>
        </div>
        <footer class="members-filter-footer">
            <a class="btn" href="#">Suchen</a>
            <a class="members-reset" href="#">Zurücksetzen</a>
        </footer>
    </section>
</div>
@endpush

@push('rework-scripts')
<script>
(() => {
    const modal = document.querySelector('[data-members-filter-modal]');
    const closeButtons = document.querySelectorAll('[data-members-filter-close]');

    window.closeMembersFilterModal = () => {
        if (!modal) return;
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('is-modal-open');
    };

    const openMembersFilterModal = () => {
        if (!modal) return;
        if (typeof closeAllDropdowns === 'function') closeAllDropdowns();
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('is-modal-open');
    };

    document.querySelectorAll('[data-members-filter-open]').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            openMembersFilterModal();
        });
    });

    closeButtons.forEach((button) => {
        button.addEventListener('click', (event) => {
            event.preventDefault();
            window.closeMembersFilterModal();
        });
    });

    modal?.addEventListener('click', (event) => {
        if (event.target === modal) window.closeMembersFilterModal();
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') window.closeMembersFilterModal();
    });
})();
</script>
@endpush

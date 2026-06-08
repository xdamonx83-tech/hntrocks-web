@extends('admin.layouts.app')

@section('title', 'Badges & Quests · Admin · hnt.rocks')

@section('admin_heading', 'Badges & Quests')

@section('content')
<section class="hh-page-header">
    <div>
        <p class="hh-kicker">Admin · Gamification</p>
        <h1>Badges &amp; Quests</h1>
        <p>Badges mit eigenen Icons verwalten, manuell vergeben und Quests für echte Fortschritte anlegen.</p>
    </div>
    <div class="hh-page-header-meta">
        <strong>{{ $badges->count() }}</strong>
        <span>Badges</span>
    </div>
</section>

@if(session('status'))
    <div class="hh-alert hh-alert-success">{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>Bitte prüfen:</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<section class="hh-admin-stat-grid">
    <article class="hh-card hh-card-compact">
        <strong>{{ $badges->where('is_active', true)->count() }}</strong>
        <span>aktive Badges</span>
    </article>
    <article class="hh-card hh-card-compact">
        <strong>{{ $badges->where('is_manual_only', true)->count() }}</strong>
        <span>manuell</span>
    </article>
    <article class="hh-card hh-card-compact">
        <strong>{{ $badges->sum('users_count') }}</strong>
        <span>Vergaben</span>
    </article>
    <article class="hh-card hh-card-compact">
        <strong>{{ $quests->where('is_active', true)->count() }}</strong>
        <span>aktive Quests</span>
    </article>
</section>

<section class="hh-admin-grid hh-admin-gamification-grid">
    <article class="hh-card hh-card-compact">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Neues Badge</p>
                <h2>Badge anlegen</h2>
            </div>
        </div>

        <form class="hh-admin-badge-form" method="post" action="{{ route('admin.gamification.badges.store') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="badge-name">Name</label>
                <input id="badge-name" type="text" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="z. B. Bayou Veteran">
            </div>
            <div>
                <label for="badge-slug">Slug optional</label>
                <input id="badge-slug" type="text" name="slug" value="{{ old('slug') }}" maxlength="80" placeholder="bayou-veteran">
            </div>
            <div>
                <label for="badge-category">Kategorie</label>
                <input id="badge-category" type="text" name="category" value="{{ old('category', 'community') }}" required maxlength="80">
            </div>
            <div>
                <label for="badge-rarity">Seltenheit</label>
                <select id="badge-rarity" name="rarity" required>
                    @foreach(['common' => 'Normal', 'uncommon' => 'Ungewöhnlich', 'rare' => 'Selten', 'epic' => 'Episch', 'legendary' => 'Legendär'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('rarity', 'common') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="badge-icon">Fallback-Icon</label>
                <input id="badge-icon" type="text" name="icon" value="{{ old('icon') }}" maxlength="40" placeholder="★">
            </div>
            <div>
                <label for="badge-icon-file">Icon hochladen</label>
                <input id="badge-icon-file" type="file" name="icon_file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/*">
            </div>
            <div>
                <label for="badge-xp">XP-Belohnung</label>
                <input id="badge-xp" type="number" name="xp_reward" value="{{ old('xp_reward', 0) }}" min="0" max="100000">
            </div>
            <div>
                <label for="badge-sort">Sortierung</label>
                <input id="badge-sort" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="100000">
            </div>
            <div class="hh-admin-form-wide">
                <label for="badge-description">Beschreibung</label>
                <textarea id="badge-description" name="description" rows="3" maxlength="2000" placeholder="Wofür bekommt man dieses Badge?">{{ old('description') }}</textarea>
            </div>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktiv
            </label>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="is_manual_only" value="1" @checked(old('is_manual_only'))>
                Nur manuell vergeben
            </label>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="notify_on_award" value="1" checked>
                Notification bei Vergabe
            </label>
            <button class="hh-primary-button hh-admin-form-submit" type="submit">Badge anlegen</button>
        </form>
    </article>

    <article class="hh-card hh-card-compact">
        <div class="hh-card-title-row">
            <div>
                <p class="hh-kicker">Neue Quest</p>
                <h2>Quest anlegen</h2>
            </div>
        </div>

        <form class="hh-admin-quest-form" method="post" action="{{ route('admin.gamification.quests.store') }}" enctype="multipart/form-data">
            @csrf
            <div>
                <label for="quest-name">Name</label>
                <input id="quest-name" type="text" name="name" value="{{ old('name') }}" required maxlength="140" placeholder="z. B. Erster Moment">
            </div>
            <div>
                <label for="quest-slug">Slug optional</label>
                <input id="quest-slug" type="text" name="slug" value="{{ old('slug') }}" maxlength="100" placeholder="erster-moment">
            </div>
            <div>
                <label for="quest-category">Kategorie</label>
                <input id="quest-category" type="text" name="category" value="{{ old('category', 'daily') }}" required maxlength="80">
            </div>
            <div>
                <label for="quest-action">Aktion</label>
                <select id="quest-action" name="action" required>
                    @foreach($questActions as $action)
                        <option value="{{ $action }}" @selected(old('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quest-target">Zielwert</label>
                <input id="quest-target" type="number" name="target_count" value="{{ old('target_count', 1) }}" min="1" max="100000" required>
            </div>
            <div>
                <label for="quest-xp">XP-Belohnung</label>
                <input id="quest-xp" type="number" name="xp_reward" value="{{ old('xp_reward', 0) }}" min="0" max="100000">
            </div>
            <div>
                <label for="quest-badge">Badge-Belohnung</label>
                <select id="quest-badge" name="badge_slug">
                    <option value="">Kein Badge</option>
                    @foreach($badges as $badge)
                        <option value="{{ $badge->slug }}" @selected(old('badge_slug') === $badge->slug)>{{ $badge->name }} · {{ $badge->slug }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quest-period">Zeitraum</label>
                <select id="quest-period" name="period">
                    @foreach($questPeriods as $value => $label)
                        <option value="{{ $value }}" @selected(old('period', '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="quest-icon">Fallback-Icon</label>
                <input id="quest-icon" type="text" name="icon" value="{{ old('icon') }}" maxlength="40" placeholder="!">
            </div>
            <div>
                <label for="quest-icon-file">Quest-Icon hochladen</label>
                <input id="quest-icon-file" type="file" name="icon_file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/*">
            </div>
            <div>
                <label for="quest-sort">Sortierung</label>
                <input id="quest-sort" type="number" name="sort_order" value="{{ old('sort_order', 0) }}" min="0" max="100000">
            </div>
            <div class="hh-admin-form-wide">
                <label for="quest-description">Beschreibung</label>
                <textarea id="quest-description" name="description" rows="3" maxlength="2000" placeholder="Was muss der Spieler tun?">{{ old('description') }}</textarea>
            </div>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="is_active" value="1" checked>
                Aktiv
            </label>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="is_repeatable" value="1" @checked(old('is_repeatable'))>
                Wiederholbar
            </label>
            <label class="hh-checkline hh-checkline-card">
                <input type="checkbox" name="notify_on_completion" value="1" checked>
                Notification bei Abschluss
            </label>
            <button class="hh-primary-button hh-admin-form-submit" type="submit">Quest anlegen</button>
        </form>
    </article>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <p class="hh-kicker">Manuelle Vergabe</p>
            <h2>Badge vergeben</h2>
        </div>
    </div>

    <form class="hh-admin-filter hh-admin-user-search" method="get" action="{{ route('admin.gamification.index') }}">
        <div>
            <label for="q">Nutzer suchen</label>
            <input id="q" type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, Username oder E-Mail">
        </div>
        <button class="hh-secondary-button" type="submit">Suchen</button>
    </form>

    <div class="hh-admin-list hh-admin-gamification-users">
        @forelse($users as $user)
            <div>
                <strong>{{ $user->name }} <span class="hh-muted">({{ '@'.$user->username }})</span></strong>
                <span>{{ $user->email }} · Level {{ $user->level ?? 1 }}</span>
                <form class="hh-admin-award-form" method="post" action="{{ $badges->first() ? route('admin.gamification.badges.award', $badges->first()) : '#' }}" data-award-form>
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $user->id }}">
                    <select name="badge_route" data-award-select @disabled($badges->isEmpty())>
                        @foreach($badges as $badge)
                            <option value="{{ route('admin.gamification.badges.award', $badge) }}">{{ $badge->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" name="award_reason" maxlength="500" placeholder="Grund optional">
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="notify_user" value="1" checked>
                        Benachrichtigen
                    </label>
                    <button class="hh-primary-button" type="submit" @disabled($badges->isEmpty())>Vergeben</button>
                </form>
            </div>
        @empty
            <p class="hh-muted">Keine Nutzer gefunden.</p>
        @endforelse
    </div>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <p class="hh-kicker">Badges</p>
            <h2>Vorhandene Badges</h2>
        </div>
        <span>{{ $badges->count() }} Einträge</span>
    </div>

    <div class="hh-admin-badge-list">
        @forelse($badges as $badge)
            <article class="hh-admin-badge-row">
                <div class="hh-admin-badge-preview hh-rarity-{{ $badge->rarity ?: 'common' }}">
                    @if($badge->iconUrl())
                        <img src="{{ $badge->iconUrl() }}" alt="{{ $badge->name }}">
                    @else
                        <span>{{ $badge->icon ?: '◆' }}</span>
                    @endif
                </div>

                <form class="hh-admin-badge-edit" method="post" action="{{ route('admin.gamification.badges.update', $badge) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div>
                        <label>Name</label>
                        <input type="text" name="name" value="{{ $badge->name }}" required maxlength="120">
                    </div>
                    <div>
                        <label>Slug</label>
                        <input type="text" name="slug" value="{{ $badge->slug }}" maxlength="80">
                    </div>
                    <div>
                        <label>Kategorie</label>
                        <input type="text" name="category" value="{{ $badge->category }}" required maxlength="80">
                    </div>
                    <div>
                        <label>Seltenheit</label>
                        <select name="rarity" required>
                            @foreach(['common' => 'Normal', 'uncommon' => 'Ungewöhnlich', 'rare' => 'Selten', 'epic' => 'Episch', 'legendary' => 'Legendär'] as $value => $label)
                                <option value="{{ $value }}" @selected(($badge->rarity ?: 'common') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Fallback-Icon</label>
                        <input type="text" name="icon" value="{{ $badge->icon }}" maxlength="40">
                    </div>
                    <div>
                        <label>Icon ersetzen</label>
                        <input type="file" name="icon_file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/*">
                    </div>
                    <div>
                        <label>XP</label>
                        <input type="number" name="xp_reward" value="{{ $badge->xp_reward ?? 0 }}" min="0" max="100000">
                    </div>
                    <div>
                        <label>Sortierung</label>
                        <input type="number" name="sort_order" value="{{ $badge->sort_order ?? 0 }}" min="0" max="100000">
                    </div>
                    <div class="hh-admin-form-wide">
                        <label>Beschreibung</label>
                        <textarea name="description" rows="2" maxlength="2000">{{ $badge->description }}</textarea>
                    </div>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="is_active" value="1" @checked($badge->is_active)>
                        Aktiv
                    </label>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="is_manual_only" value="1" @checked($badge->is_manual_only)>
                        Nur manuell
                    </label>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="notify_on_award" value="1" @checked($badge->notify_on_award)>
                        Notification
                    </label>
                    <div class="hh-admin-badge-meta">
                        <strong>{{ $badge->rarityLabel() }}</strong>
                        <span>{{ $badge->users_count }}× vergeben</span>
                    </div>
                    <button class="hh-secondary-button" type="submit">Speichern</button>
                </form>

                <div class="hh-admin-badge-awarded">
                    <strong>Vergeben an</strong>
                    @forelse($badge->users()->latest('badge_user.awarded_at')->limit(5)->get() as $awardedUser)
                        <div>
                            <span>{{ $awardedUser->name }}</span>
                            <form method="post" action="{{ route('admin.gamification.badges.revoke', [$badge, $awardedUser]) }}">
                                @csrf
                                @method('DELETE')
                                <button class="hh-link-button" type="submit">Entfernen</button>
                            </form>
                        </div>
                    @empty
                        <p class="hh-muted">Noch nicht vergeben.</p>
                    @endforelse

                    @unless($badge->users_count)
                        <form method="post" action="{{ route('admin.gamification.badges.destroy', $badge) }}">
                            @csrf
                            @method('DELETE')
                            <button class="hh-secondary-button" type="submit">Badge löschen</button>
                        </form>
                    @endunless
                </div>
            </article>
        @empty
            <p>Noch keine Badges vorhanden.</p>
        @endforelse
    </div>
</section>

<section class="hh-card hh-card-compact hh-section-space">
    <div class="hh-card-title-row">
        <div>
            <p class="hh-kicker">Quests</p>
            <h2>Vorhandene Quests</h2>
        </div>
        <span>{{ $quests->count() }} Einträge</span>
    </div>

    <div class="hh-admin-quest-list">
        @forelse($quests as $quest)
            <article class="hh-admin-quest-row">
                <div class="hh-admin-quest-preview">
                    @if($quest->iconUrl())
                        <img src="{{ $quest->iconUrl() }}" alt="{{ $quest->name }}">
                    @else
                        <span>{{ $quest->icon ?: '!' }}</span>
                    @endif
                </div>

                <form class="hh-admin-quest-edit" method="post" action="{{ route('admin.gamification.quests.update', $quest) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div>
                        <label>Name</label>
                        <input type="text" name="name" value="{{ $quest->name }}" required maxlength="140">
                    </div>
                    <div>
                        <label>Slug</label>
                        <input type="text" name="slug" value="{{ $quest->slug }}" maxlength="100">
                    </div>
                    <div>
                        <label>Kategorie</label>
                        <input type="text" name="category" value="{{ $quest->category }}" required maxlength="80">
                    </div>
                    <div>
                        <label>Aktion</label>
                        <select name="action" required>
                            @foreach($questActions as $action)
                                <option value="{{ $action }}" @selected($quest->action === $action)>{{ $action }}</option>
                            @endforeach
                            @unless(in_array($quest->action, $questActions, true))
                                <option value="{{ $quest->action }}" selected>{{ $quest->action }}</option>
                            @endunless
                        </select>
                    </div>
                    <div>
                        <label>Zielwert</label>
                        <input type="number" name="target_count" value="{{ $quest->target_count }}" min="1" max="100000" required>
                    </div>
                    <div>
                        <label>XP</label>
                        <input type="number" name="xp_reward" value="{{ $quest->xp_reward ?? 0 }}" min="0" max="100000">
                    </div>
                    <div>
                        <label>Badge-Belohnung</label>
                        <select name="badge_slug">
                            <option value="">Kein Badge</option>
                            @foreach($badges as $badge)
                                <option value="{{ $badge->slug }}" @selected($quest->badge_slug === $badge->slug)>{{ $badge->name }} · {{ $badge->slug }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Zeitraum</label>
                        <select name="period">
                            @foreach($questPeriods as $value => $label)
                                <option value="{{ $value }}" @selected(($quest->period ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label>Fallback-Icon</label>
                        <input type="text" name="icon" value="{{ $quest->icon }}" maxlength="40">
                    </div>
                    <div>
                        <label>Icon ersetzen</label>
                        <input type="file" name="icon_file" accept=".png,.jpg,.jpeg,.webp,.gif,.svg,image/*">
                    </div>
                    <div>
                        <label>Sortierung</label>
                        <input type="number" name="sort_order" value="{{ $quest->sort_order ?? 0 }}" min="0" max="100000">
                    </div>
                    <div class="hh-admin-form-wide">
                        <label>Beschreibung</label>
                        <textarea name="description" rows="2" maxlength="2000">{{ $quest->description }}</textarea>
                    </div>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="is_active" value="1" @checked($quest->is_active)>
                        Aktiv
                    </label>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="is_repeatable" value="1" @checked($quest->is_repeatable)>
                        Wiederholbar
                    </label>
                    <label class="hh-checkline hh-checkline-card">
                        <input type="checkbox" name="notify_on_completion" value="1" @checked($quest->notify_on_completion)>
                        Notification
                    </label>
                    <div class="hh-admin-badge-meta">
                        <strong>{{ $quest->periodLabel() }}</strong>
                        <span>{{ $quest->progress_count ?? 0 }} Fortschritte</span>
                    </div>
                    <button class="hh-secondary-button" type="submit">Speichern</button>
                </form>

                <div class="hh-admin-badge-awarded">
                    <strong>Quest-Info</strong>
                    <p class="hh-muted">{{ $quest->action }}</p>
                    <p class="hh-muted">Ziel: {{ $quest->target_count }} · Belohnung: {{ $quest->xp_reward }} XP{{ $quest->badge_slug ? ' · '.$quest->badge_slug : '' }}</p>
                    <p class="hh-muted">Status: {{ $quest->is_active ? 'Aktiv' : 'Inaktiv' }}</p>

                    @unless($quest->progress_count)
                        <form method="post" action="{{ route('admin.gamification.quests.destroy', $quest) }}">
                            @csrf
                            @method('DELETE')
                            <button class="hh-secondary-button" type="submit">Quest löschen</button>
                        </form>
                    @endunless
                </div>
            </article>
        @empty
            <p>Noch keine Quests vorhanden.</p>
        @endforelse
    </div>
</section>

<script>
document.querySelectorAll('[data-award-form]').forEach((form) => {
    const select = form.querySelector('[data-award-select]');
    if (!select) {
        return;
    }

    const updateAction = () => {
        if (select.value) {
            form.action = select.value;
        }
    };

    select.addEventListener('change', updateAction);
    updateAction();
});
</script>
@endsection

@extends('themes.rework.layouts.app')

@section('title', __('ui.lfg_index_title').' - HNT.rocks')
@section('meta_description', __('ui.lfg_index_banner_text'))
@section('body_class', 'lfg-page rework-lfg-page')
@section('left_col_class', 'lfg-overview-left')

@php
    $filters = $filters ?? [];
    $managedLfgPosts = $managedLfgPosts ?? collect();
    $memberSuggestions = $memberSuggestions ?? collect();
    $featuredCups = $featuredCups ?? collect();
    $postsCollection = $posts instanceof \Illuminate\Contracts\Pagination\Paginator ? $posts->getCollection() : collect($posts ?? []);
    $activeSort = (string) ($filters['sort'] ?? 'newest');
    $activeStatus = (string) ($filters['status'] ?? '');
    $currentLocale = app()->getLocale() === 'en' ? 'en' : 'de';
    $lfgUi = [
        'de' => [
            'eyebrow' => 'Hunter Board',
            'subtitle' => 'Finde Hunter für deine nächste Runde.',
            'visible_posts' => 'sichtbare LFGs',
            'own_active' => 'eigene aktive',
            'voice_wanted' => 'Voice gesucht',
            'open_slots' => 'freie Slots',
            'filters' => 'Filter',
            'status' => 'Status',
            'details' => 'Details',
            'hunt_info' => 'Hunt-Info',
            'applications' => 'Anfragen',
            'tips_title' => 'LFG Hinweise',
            'tip_clear' => 'Sag kurz, wann du spielst und was dir wichtig ist.',
            'tip_slots' => 'Halte Slots und Status aktuell, damit Bewerbungen passen.',
            'tip_voice' => 'Markiere Voice nur, wenn es wirklich wichtig ist.',
            'featured_cups' => 'Aktive Cups',
            'active_hunters' => 'Aktive Hunter',
            'no_body' => 'Keine Beschreibung hinterlegt.',
            'create_modal_title' => 'LFG erstellen',
            'create_modal_text' => 'Beschreibe kurz deine Runde, die gesuchten Slots und wie andere Hunter zu dir passen.',
            'cancel' => 'Abbrechen',
            'publish' => 'Veröffentlichen',
            'basic_data' => 'Basis',
            'hunt_data' => 'Hunt-Details',
            'settings' => 'Einstellungen',
            'optional' => 'optional',
        ],
        'en' => [
            'eyebrow' => 'Hunter Board',
            'subtitle' => 'Find hunters for your next round.',
            'visible_posts' => 'visible LFGs',
            'own_active' => 'your active',
            'voice_wanted' => 'voice wanted',
            'open_slots' => 'open slots',
            'filters' => 'Filters',
            'status' => 'Status',
            'details' => 'Details',
            'hunt_info' => 'Hunt info',
            'applications' => 'Requests',
            'tips_title' => 'LFG notes',
            'tip_clear' => 'Share when you play and what matters for the round.',
            'tip_slots' => 'Keep slots and status current so applications fit.',
            'tip_voice' => 'Use the voice marker only when it really matters.',
            'featured_cups' => 'Active cups',
            'active_hunters' => 'Active hunters',
            'no_body' => 'No description added.',
            'create_modal_title' => 'Create LFG',
            'create_modal_text' => 'Briefly describe your round, the open slots and which hunters fit.',
            'cancel' => 'Cancel',
            'publish' => 'Publish',
            'basic_data' => 'Basics',
            'hunt_data' => 'Hunt details',
            'settings' => 'Settings',
            'optional' => 'optional',
        ],
    ][$currentLocale];

    $baseUrl = route('lfg.index');
    $filterUrl = function (array $merge = [], array $remove = []) use ($baseUrl): string {
        $query = request()->query();

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        foreach ($merge as $key => $value) {
            if ($value === null || $value === '' || $value === false) {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }

        unset($query['page']);

        return $baseUrl.($query !== [] ? '?'.http_build_query($query) : '');
    };
    $statusUrl = fn (?string $status): string => $filterUrl(['status' => $status], $status ? [] : ['status']);
    $postTotal = method_exists($posts, 'total') ? (int) $posts->total() : $postsCollection->count();
    $voiceCount = $postsCollection->where('voice_required', true)->count();
    $freeSlots = $postsCollection->sum(fn ($post): int => method_exists($post, 'slotsOpen') ? $post->slotsOpen() : max(0, (int) $post->slots_total - (int) $post->slots_filled));
    $openCount = $postsCollection->where('status', 'open')->count();
    $progressPercent = $postsCollection->count() > 0 ? min(100, max(12, (int) round(($openCount / max(1, $postsCollection->count())) * 100))) : 12;
    $platformOptions = ['PC', 'PlayStation', 'Xbox', 'Crossplay'];
    $statusTabs = [
        '' => __('ui.lfg_status_all'),
        'open' => __('ui.lfg_status_open'),
        'full' => __('ui.lfg_status_full'),
        'closed' => __('ui.lfg_status_closed'),
    ];
    $socialiteMembers = $memberSuggestions;
    $socialiteHighlightLfg = $postsCollection->first();
    $socialiteHighlightCup = $featuredCups->first();
    $hhLfgOptionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
    $createModalOpen = old('hh_lfg_modal') === 'create';
    $titleValue = old('title', '');
    $bodyValue = old('body', '');
    $platformValue = old('platform', '');
    $playstyleValue = old('playstyle', '');
    $regionValue = old('region', '');
    $languageValue = old('language', '');
    $preferredTimeValue = old('preferred_time', '');
    $experienceValue = old('experience_level', '');
    $slotsTotalValue = (int) old('slots_total', 2);
    $visibilityValue = old('visibility', 'public');
    $expiresAtValue = old('expires_at', '');
    $voiceRequiredValue = (bool) old('voice_required', false);
@endphp

@section('content')
<section class="lfg-hero card">
    <div class="lfg-hero-main">
        <span class="members-eyebrow">{{ $lfgUi['eyebrow'] }}</span>
        <h1>{{ __('ui.lfg_index_title') }} / Looking for Group</h1>
        <p>{{ $lfgUi['subtitle'] }}</p>
        <div class="lfg-hero-progress" aria-hidden="true"><span style="width: {{ $progressPercent }}%"></span></div>
    </div>
    <div class="lfg-hero-actions">
        <button class="btn" type="button" data-rework-lfg-create-open><i aria-hidden="true" class="ph ph-plus ph-icon"></i>{{ __('ui.lfg_create') }}</button>
    </div>
    <div class="lfg-hero-stats" aria-label="{{ __('ui.lfg_overview') }}">
        <span><strong>{{ number_format($postTotal) }}</strong><em>{{ $lfgUi['visible_posts'] }}</em></span>
        <span><strong>{{ number_format($managedLfgPosts->count()) }}</strong><em>{{ $lfgUi['own_active'] }}</em></span>
        <span><strong>{{ number_format($voiceCount) }}</strong><em>{{ $lfgUi['voice_wanted'] }}</em></span>
        <span><strong>{{ number_format($freeSlots) }}</strong><em>{{ $lfgUi['open_slots'] }}</em></span>
    </div>
</section>

@if (session('status'))
    <div class="lfg-alert lfg-alert-success" role="status"><i aria-hidden="true" class="ph ph-check-circle ph-icon"></i>{{ session('status') }}</div>
@endif

@if ($errors->any())
    <div class="lfg-alert lfg-alert-danger" role="alert">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@if($managedLfgPosts->isNotEmpty())
    <section class="lfg-managed card" aria-label="{{ __('ui.lfg_my_posts') }}">
        <div class="lfg-section-head">
            <span>{{ __('ui.lfg_my_posts') }}</span>
            <a href="{{ $filterUrl(['mine' => 1]) }}">{{ __('ui.see_all') }}</a>
        </div>
        <div class="lfg-managed-list">
            @foreach($managedLfgPosts->take(4) as $managedPost)
                @php
                    $managedOpen = $managedPost->slotsOpen();
                    $managedAuthor = $managedPost->user;
                @endphp
                <a class="lfg-managed-item" href="{{ route('lfg.show', $managedPost) }}">
                    <img alt="{{ $managedAuthor?->name ?: 'HNT Hunter' }}" src="{{ $managedAuthor?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
                    <span><strong>{{ $managedPost->title }}</strong><em>{{ $managedOpen }} {{ __('ui.lfg_stat_free') }} - {{ $managedPost->statusLabel() }}</em></span>
                </a>
            @endforeach
        </div>
    </section>
@endif

<section class="lfg-filter-card card" aria-label="{{ $lfgUi['filters'] }}">
    <form class="lfg-filter-form" method="GET" action="{{ route('lfg.index') }}">
        <label class="lfg-search-field" for="lfg-search">
            <span>{{ __('ui.lfg_search_label') }}</span>
            <input id="lfg-search" name="q" type="search" value="{{ $filters['q'] ?? '' }}">
        </label>
        <label>
            <span>{{ __('ui.lfg_platform_filter') }}</span>
            <select name="platform" onchange="this.form.submit()">
                <option value="">{{ __('ui.lfg_filter_all') }}</option>
                @foreach($platformOptions as $option)
                    <option value="{{ $option }}" @selected(($filters['platform'] ?? '') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </label>
        <label>
            <span>{{ __('ui.lfg_sort_label') }}</span>
            <select name="sort" onchange="this.form.submit()">
                <option value="newest" @selected($activeSort === 'newest')>{{ __('ui.lfg_sort_newest') }}</option>
                <option value="open_slots" @selected($activeSort === 'open_slots')>{{ __('ui.lfg_sort_open_slots') }}</option>
                <option value="applications" @selected($activeSort === 'applications')>{{ __('ui.lfg_sort_applications') }}</option>
                <option value="expiring" @selected($activeSort === 'expiring')>{{ __('ui.lfg_sort_expiring') }}</option>
            </select>
        </label>
        @if($activeStatus !== '')
            <input type="hidden" name="status" value="{{ $activeStatus }}">
        @endif
        <label class="lfg-check">
            <input type="checkbox" name="voice_required" value="1" @checked((bool) ($filters['voice_required'] ?? false)) onchange="this.form.submit()">
            <span>{{ __('ui.lfg_voice_only') }}</span>
        </label>
        <label class="lfg-check">
            <input type="checkbox" name="mine" value="1" @checked((bool) ($filters['mine'] ?? false)) onchange="this.form.submit()">
            <span>{{ __('ui.lfg_only_mine') }}</span>
        </label>
        <button class="btn light" type="submit"><i aria-hidden="true" class="ph ph-magnifying-glass ph-icon"></i>{{ __('ui.search') }}</button>
    </form>

    <nav class="members-tabs lfg-status-tabs" aria-label="{{ $lfgUi['status'] }}">
        @foreach($statusTabs as $status => $label)
            <a @class(['active' => $activeStatus === $status]) href="{{ $statusUrl($status !== '' ? $status : null) }}">{{ $label }}</a>
        @endforeach
    </nav>
</section>

@if($postsCollection->isEmpty())
    <section class="lfg-empty-state card">
        <i aria-hidden="true" class="ph ph-binoculars ph-icon"></i>
        <strong>{{ __('ui.lfg_empty_title') }}</strong>
        <p>{{ __('ui.lfg_empty_text') }}</p>
        <button class="btn" type="button" data-rework-lfg-create-open>{{ __('ui.lfg_create') }}</button>
    </section>
@else
    <div class="lfg-grid">
        @foreach($postsCollection as $post)
            @php
                $author = $post->user;
                $slotsOpen = $post->slotsOpen();
                $tags = $post->displayTags();
                $coverUrl = $author?->coverUrl() ?: $post->coverUrl();
                $body = trim((string) $post->body);
            @endphp
            <article class="lfg-card card">
                <a class="lfg-card-media" href="{{ route('lfg.show', $post) }}">
                    @if($coverUrl)
                        <img alt="{{ $post->title }}" src="{{ $coverUrl }}">
                    @else
                        <span class="lfg-card-fallback"><i aria-hidden="true" class="ph ph-crosshair ph-icon"></i></span>
                    @endif
                    <span class="lfg-status-badge is-{{ $post->status }}">{{ $post->statusLabel() }}</span>
                </a>
                <div class="lfg-card-body">
                    <div class="lfg-card-title">
                        <h2><a href="{{ route('lfg.show', $post) }}">{{ $post->title }}</a></h2>
                        <span>{{ $slotsOpen }} {{ __('ui.lfg_stat_free') }} / {{ (int) $post->slots_total }}</span>
                    </div>
                    <div class="lfg-card-author">
                        <img alt="{{ $author?->name ?: 'HNT Hunter' }}" src="{{ $author?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
                        <span>{{ $author?->name ?: ($author?->username ?: 'HNT Hunter') }}</span>
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit($body !== '' ? $body : $lfgUi['no_body'], 96) }}</p>
                    <div class="lfg-card-badges">
                        @foreach(array_slice($tags, 0, 4) as $tag)
                            <span>{{ $tag }}</span>
                        @endforeach
                        @if($post->voice_required)
                            <strong><i aria-hidden="true" class="ph ph-microphone ph-icon"></i>{{ __('ui.lfg_voice_wanted') }}</strong>
                        @endif
                    </div>
                    <div class="lfg-card-footer">
                        <span>{{ number_format((int) ($post->pending_count ?? 0)) }} {{ __('ui.lfg_stat_requests') }}</span>
                        <a class="btn light" href="{{ route('lfg.show', $post) }}">{{ __('ui.lfg_view') }}</a>
                    </div>
                </div>
            </article>
        @endforeach
    </div>
@endif

@if($posts instanceof \Illuminate\Contracts\Pagination\Paginator && $posts->hasPages())
    <nav class="lfg-pagination" aria-label="{{ __('ui.lfg_overview') }}">
        @if($posts->onFirstPage())
            <span>{{ __('ui.pagination_previous') }}</span>
        @else
            <a href="{{ $posts->previousPageUrl() }}">{{ __('ui.pagination_previous') }}</a>
        @endif
        <strong>{{ $posts->currentPage() }}</strong>
        @if($posts->hasMorePages())
            <a href="{{ $posts->nextPageUrl() }}">{{ __('ui.pagination_next') }}</a>
        @else
            <span>{{ __('ui.pagination_next') }}</span>
        @endif
    </nav>
@endif

<section class="lfg-side-widgets" aria-label="{{ $lfgUi['details'] }}">
    <article class="lfg-side-widget card">
        <div class="lfg-section-head"><span>{{ $lfgUi['active_hunters'] }}</span><a href="{{ route('members.index') }}">{{ __('ui.see_all') }}</a></div>
        @forelse($memberSuggestions->take(3) as $member)
            <a class="lfg-widget-row" href="{{ route('profile.public', $member) }}">
                <img alt="{{ $member->name }}" src="{{ $member->avatarUrl() }}">
                <span><strong>{{ $member->name }}</strong><em>{{ $member->username ? '@'.$member->username : 'HNT Hunter' }}</em></span>
            </a>
        @empty
            <p class="lfg-widget-empty">{{ __('ui.teams_widget_empty') }}</p>
        @endforelse
    </article>
    <article class="lfg-side-widget card">
        <div class="lfg-section-head"><span>{{ $lfgUi['featured_cups'] }}</span><a href="{{ route('cups.index') }}">{{ __('ui.see_all') }}</a></div>
        @forelse($featuredCups->take(3) as $cup)
            <a class="lfg-widget-row" href="{{ route('cups.show', $cup) }}">
                <img alt="{{ $cup->title }}" src="{{ $cup->coverUrl() }}">
                <span><strong>{{ $cup->title }}</strong><em>{{ $cup->statusLabel() }}</em></span>
            </a>
        @empty
            <p class="lfg-widget-empty">{{ __('ui.teams_widget_empty') }}</p>
        @endforelse
    </article>
    <article class="lfg-side-widget card">
        <div class="lfg-section-head"><span>{{ $lfgUi['tips_title'] }}</span></div>
        <ul class="lfg-tips">
            <li>{{ $lfgUi['tip_clear'] }}</li>
            <li>{{ $lfgUi['tip_slots'] }}</li>
            <li>{{ $lfgUi['tip_voice'] }}</li>
        </ul>
    </article>
</section>

<div
    class="rework-lfg-create-modal-backdrop @if($createModalOpen) is-open @endif"
    data-rework-lfg-create-modal
    @if(! $createModalOpen) hidden @endif
>
    <section class="rework-lfg-create-modal" role="dialog" aria-modal="true" aria-labelledby="rework-lfg-create-title">
        <header class="rework-lfg-create-header">
            <div>
                <span>{{ __('ui.lfg_index_title') }}</span>
                <h2 id="rework-lfg-create-title">{{ $lfgUi['create_modal_title'] }}</h2>
                <p>{{ $lfgUi['create_modal_text'] }}</p>
            </div>
            <button type="button" class="rework-lfg-create-close" data-rework-lfg-create-close aria-label="{{ __('ui.close') }}"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
        </header>

        <form class="rework-lfg-create-form" method="POST" action="{{ route('lfg.store') }}">
            @csrf
            <input type="hidden" name="hh_lfg_modal" value="create">

            <div class="rework-lfg-create-body">
                <fieldset class="rework-lfg-fieldset">
                    <legend>{{ $lfgUi['basic_data'] }}</legend>
                    <label class="rework-lfg-field rework-lfg-field-wide" for="rework-lfg-title">
                        <span>{{ __('ui.lfg_form_title') }}</span>
                        <input id="rework-lfg-title" type="text" name="title" value="{{ $titleValue }}" maxlength="120" required placeholder="{{ __('ui.lfg_form_title_placeholder') }}">
                        @error('title') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field rework-lfg-field-wide" for="rework-lfg-body">
                        <span>{{ __('ui.lfg_form_body') }} <small>{{ $lfgUi['optional'] }}</small></span>
                        <textarea id="rework-lfg-body" name="body" rows="4" maxlength="2800" placeholder="{{ __('ui.lfg_form_body_placeholder') }}">{{ $bodyValue }}</textarea>
                        @error('body') <em>{{ $message }}</em> @enderror
                    </label>
                </fieldset>

                <fieldset class="rework-lfg-fieldset">
                    <legend>{{ $lfgUi['hunt_data'] }}</legend>
                    <label class="rework-lfg-field" for="rework-lfg-platform">
                        <span>{{ __('ui.lfg_detail_platform') }}</span>
                        <select id="rework-lfg-platform" name="platform">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach($platformOptions as $option)
                                <option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('platform') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-playstyle">
                        <span>{{ __('ui.lfg_detail_playstyle') }}</span>
                        <select id="rework-lfg-playstyle" name="playstyle">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach(['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $hhLfgOptionLabel('playstyle', $option) }}</option>
                            @endforeach
                        </select>
                        @error('playstyle') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-region">
                        <span>{{ __('ui.lfg_detail_region') }}</span>
                        <select id="rework-lfg-region" name="region">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach(['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                <option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
                            @endforeach
                        </select>
                        @error('region') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-language">
                        <span>{{ __('ui.language') }}</span>
                        <select id="rework-lfg-language" name="language">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach(['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                <option value="{{ $option }}" @selected($languageValue === $option)>{{ $hhLfgOptionLabel('language', $option) }}</option>
                            @endforeach
                        </select>
                        @error('language') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-preferred-time">
                        <span>{{ __('ui.lfg_form_preferred_time') }}</span>
                        <select id="rework-lfg-preferred-time" name="preferred_time">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach(['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                                <option value="{{ $option }}" @selected($preferredTimeValue === $option)>{{ $hhLfgOptionLabel('preferred_time', $option) }}</option>
                            @endforeach
                        </select>
                        @error('preferred_time') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-experience">
                        <span>{{ __('ui.lfg_form_experience_level') }}</span>
                        <select id="rework-lfg-experience" name="experience_level">
                            <option value="">{{ __('ui.select_option') }}</option>
                            @foreach(['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                                <option value="{{ $option }}" @selected($experienceValue === $option)>{{ $hhLfgOptionLabel('experience_level', $option) }}</option>
                            @endforeach
                        </select>
                        @error('experience_level') <em>{{ $message }}</em> @enderror
                    </label>
                </fieldset>

                <fieldset class="rework-lfg-fieldset">
                    <legend>{{ $lfgUi['settings'] }}</legend>
                    <label class="rework-lfg-field" for="rework-lfg-slots-total">
                        <span>{{ __('ui.lfg_form_slots_total') }}</span>
                        <select id="rework-lfg-slots-total" name="slots_total" required>
                            @foreach([2, 3] as $option)
                                <option value="{{ $option }}" @selected($slotsTotalValue === $option)>{{ __('ui.lfg_form_players_count', ['count' => $option]) }}</option>
                            @endforeach
                        </select>
                        @error('slots_total') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-visibility">
                        <span>{{ __('ui.lfg_visibility') }}</span>
                        <select id="rework-lfg-visibility" name="visibility" required>
                            <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.lfg_visibility_public') }}</option>
                            <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.lfg_visibility_private') }}</option>
                        </select>
                        @error('visibility') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-field" for="rework-lfg-expires-at">
                        <span>{{ __('ui.lfg_form_expires_at') }} <small>{{ $lfgUi['optional'] }}</small></span>
                        <input id="rework-lfg-expires-at" type="datetime-local" name="expires_at" value="{{ $expiresAtValue }}">
                        @error('expires_at') <em>{{ $message }}</em> @enderror
                    </label>

                    <label class="rework-lfg-modal-check" for="rework-lfg-voice-required">
                        <input id="rework-lfg-voice-required" type="checkbox" name="voice_required" value="1" @checked($voiceRequiredValue)>
                        <span><strong>{{ __('ui.lfg_form_voice_required') }}</strong><small>{{ __('ui.lfg_form_voice_required_text') }}</small></span>
                    </label>
                </fieldset>
            </div>

            <footer class="rework-lfg-create-footer">
                <button type="button" class="rework-lfg-create-secondary" data-rework-lfg-create-close>{{ $lfgUi['cancel'] }}</button>
                <button type="submit" class="rework-lfg-create-submit">{{ $lfgUi['publish'] }}</button>
            </footer>
        </form>
    </section>
</div>
@endsection

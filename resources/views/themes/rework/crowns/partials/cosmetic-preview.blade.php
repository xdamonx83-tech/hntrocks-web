@php
    $hunter = $viewer ?? auth()->user();
    $kind = (string) ($item->type ?? '');
    $variant = \Illuminate\Support\Str::slug((string) ($item->preview_class ?: $item->key));
    $name = (string) ($hunter?->name ?: $hunter?->username ?: 'Hunter');
    $avatar = $hunter?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg');
    $copy = match(app()->getLocale()) {
        'de' => ['frame' => 'Animierter Avatarrahmen', 'card' => 'Hunter-Karte', 'title' => 'Ausgerüsteter Titel', 'prestige' => 'Prestige'],
        'es' => ['frame' => 'Marco de avatar animado', 'card' => 'Tarjeta de cazador', 'title' => 'Título equipado', 'prestige' => 'Prestigio'],
        'ru' => ['frame' => 'Анимированная рамка', 'card' => 'Карточка охотника', 'title' => 'Выбранный титул', 'prestige' => 'Престиж'],
        default => ['frame' => 'Animated avatar frame', 'card' => 'Hunter card', 'title' => 'Equipped title', 'prestige' => 'Prestige'],
    };
@endphp
@if($kind === 'avatar_frame')
<div class="hnt-cosmetic-preview hnt-cosmetic-preview--frame" aria-label="{{ $copy['frame'] }}: {{ $item->displayName() }}">
    <div class="hnt-cosmetic-ring hnt-cosmetic-ring--{{ $variant }}">
        <img src="{{ $avatar }}" alt=""/>
    </div>
    <div class="hnt-cosmetic-frame-meta"><strong>{{ $name }}</strong><span>{{ $copy['frame'] }}</span></div>
</div>
@elseif($kind === 'profile_card')
<div class="hnt-cosmetic-preview hnt-cosmetic-preview--card">
    <div class="hnt-hunter-card hnt-hunter-card--{{ $variant }}" aria-label="{{ $copy['card'] }}: {{ $item->displayName() }}">
        <div class="hnt-hunter-card__mast"><span>HNT.ROCKS</span><span>HUNTER ID</span></div>
        <div class="hnt-hunter-card__body">
            <img src="{{ $avatar }}" alt=""/>
            <div><strong>{{ $name }}</strong><span>LVL {{ (int) ($hunter?->level ?? 1) }} <b>·</b> {{ $copy['prestige'] }} {{ (int) ($hunter?->prestige ?? 0) }}</span></div>
        </div>
        <div class="hnt-hunter-card__foot"><span>{{ $item->displayName() }}</span><span>✦</span></div>
    </div>
</div>
@elseif($kind === 'title')
<div class="hnt-cosmetic-preview hnt-cosmetic-preview--title">
    <span class="hnt-title-preview hnt-title-preview--{{ $variant }}">{{ preg_replace('/^(Titel|Title)\s*:\s*/iu', '', $item->displayName()) }}</span>
    <span class="hnt-title-preview-caption">{{ $copy['title'] }}</span>
</div>
@endif

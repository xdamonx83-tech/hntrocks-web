@php
    $isEnglish = app()->getLocale() === 'en';
    $locale = $isEnglish ? 'en' : 'de';
    $t = static fn (string $de, string $en): string => $isEnglish ? $en : $de;
    $number = static fn (int|float $value): string => number_format((float) $value, 0, $isEnglish ? '.' : ',', $isEnglish ? ',' : '.');

    $balance = (int) ($summary['balance'] ?? 0);
    $earned = (int) ($summary['lifetime_earned'] ?? 0);
    $spent = (int) ($summary['lifetime_spent'] ?? 0);
    $pendingTotal = (int) data_get($pendingCollection, 'total', 0);
    $pendingCount = (int) data_get($pendingCollection, 'count', 0);
    $pendingTransactions = collect(data_get($pendingCollection, 'transactions', collect()));
    $inventoryCount = (int) $inventoryItems->count();
    $streakCurrent = max(0, (int) data_get($dailyStreak, 'current_streak', 0));
    $streakMax = max(1, (int) data_get($dailyStreak, 'max_streak_days', 7));
    $streakSchedule = collect(data_get($dailyStreak, 'reward_schedule', [5, 7, 10, 12, 15, 20, 30]))->values();
    $nextStreakDay = max(1, (int) data_get($dailyStreak, 'next_streak_day', 1));
    $todayStreakAmount = max(0, (int) data_get($dailyStreak, 'today_amount', 0));
    $claimedToday = (bool) data_get($dailyStreak, 'claimed_today', false);
    $weeklyMax = max(1, (int) $weeklyRocksDays->max('amount'));
    $dailyProgressPercent = $dailyPossible > 0 ? max(0, min(100, (int) round(($dailyEarned / $dailyPossible) * 100))) : 0;
    $initialTab = in_array(request()->query('tab'), ['overview', 'earn', 'history', 'collection'], true)
        ? (string) request()->query('tab')
        : 'overview';
    $dayLabels = $isEnglish ? ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] : ['Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa', 'So'];

    $rewardLabels = [
        'account_created' => $t('Account erstellt', 'Account created'),
        'account.created' => $t('Account erstellt', 'Account created'),
        'profile_completed' => $t('Profil vervollständigt', 'Profile completed'),
        'feed_post_created' => $t('Feed-Beitrag erstellt', 'Feed post created'),
        'feed_comment_created' => $t('Kommentar geschrieben', 'Comment written'),
        'moment_created' => $t('Moment veröffentlicht', 'Moment published'),
        'moment_comment_created' => $t('Moment kommentiert', 'Moment commented'),
        'cup_submission_created' => $t('Cup-Ergebnis eingereicht', 'Cup result submitted'),
        'cup_submission_approved' => $t('Cup-Einreichung bestätigt', 'Cup submission approved'),
        'cup_idea_submitted' => $t('Cup-Idee eingereicht', 'Cup idea submitted'),
        'cup_idea_voted' => $t('Cup-Idee bewertet', 'Cup idea voted'),
        'loadout_challenge_submission_created' => $t('Loadout-Challenge eingereicht', 'Loadout challenge submitted'),
        'loadout_challenge_submission_accepted' => $t('Loadout-Challenge bestätigt', 'Loadout challenge approved'),
        'moment_of_week_selected' => $t('Moment der Woche', 'Moment of the week'),
        'quest_completed' => $t('HNT-Auftrag abgeschlossen', 'HNT contract completed'),
    ];
    $rewardLabel = static fn (array $reward): string => $rewardLabels[$reward['action']] ?? (string) $reward['description'];
    $transactionLabel = static function ($transaction) use ($rewardLabels): string {
        $description = trim((string) ($transaction->description ?? ''));
        return $description !== '' ? $description : ($rewardLabels[(string) $transaction->action] ?? ucfirst(str_replace('_', ' ', (string) $transaction->action)));
    };
    $rarityLabel = static function (?string $rarity) use ($t): string {
        return match ($rarity) {
            'legendary' => $t('LEGENDÄR', 'LEGENDARY'),
            'epic' => $t('EPISCH', 'EPIC'),
            'rare' => $t('SELTEN', 'RARE'),
            'uncommon' => $t('UNGEWÖHNLICH', 'UNCOMMON'),
            default => $t('STANDARD', 'STANDARD'),
        };
    };
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex,nofollow,noarchive">
<title>Rocks · HNT.ROCKS</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/common.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/common.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-feed/feed.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/feed.css')) ?: time() }}" rel="stylesheet">
<link href="{{ asset('assets/themes/hnt_preview/dashboard-rocks/rocks-live.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-rocks/rocks-live.css')) ?: time() }}" rel="stylesheet">
</head>
<body data-page="rocks">
<div aria-hidden="true" class="feed-shell" hidden style="display:none!important"></div>
@include('themes.hnt_preview.partials.icons')
<main class="app-shell rocks-page-shell" data-rocks-dashboard data-initial-tab="{{ $initialTab }}">
@include('themes.hnt_preview.partials.header')
<section class="rocks-stage">
<div aria-label="{{ $t('Rocks Übersicht', 'Rocks overview') }}" class="rocks-scroll" id="rocksScroll" tabindex="0">
@if(session('status'))
<div class="rocks-flash success" role="status">{{ session('status') }}</div>
@endif
@if(session('error'))
<div class="rocks-flash danger" role="alert">{{ session('error') }}</div>
@endif
<section class="rocks-overview">
<div class="rocks-heading">
<span>HNT.ROCKS ECONOMY</span>
<h1>Rocks</h1>
<div class="rocks-meta">
<span class="live"><i></i>{{ $t('Aktiv', 'Active') }}</span>
<span>{{ $t('Virtuelles Guthaben', 'Virtual balance') }}</span>
<span>{{ $t('Nicht handelbar', 'Not tradable') }}</span>
<span>{{ $t('Kein Echtgeldwert', 'No cash value') }}</span>
</div>
<p>{{ $t('Sammle Rocks durch Beiträge, Kommentare, Moments, Cups und Aufträge. Löse sie im Shop ein und verwalte freigeschaltete Items in deinem Inventar.', 'Earn Rocks through posts, comments, Moments, Cups and contracts. Spend them in the shop and manage unlocked items in your inventory.') }}</p>
<div class="rocks-bars">
<div class="rocks-summary-bar wide"><span>{{ $t('Aktuelles Guthaben', 'Current balance') }}</span><div class="dark"><b>{{ $number($balance) }}</b></div></div>
<div class="rocks-summary-bar"><span>{{ $t('Lebenszeit verdient', 'Lifetime earned') }}</span><div class="yellow"><b>{{ $number($earned) }}</b></div></div>
<div class="rocks-summary-bar"><span>{{ $t('Ausgegeben', 'Spent') }}</span><div class="striped"><b>{{ $number($spent) }}</b></div></div>
<div class="rocks-summary-bar compact"><span>{{ $t('Bereit zum Abholen', 'Ready to collect') }}</span><div class="outline"><b>+{{ $number($pendingTotal) }}</b></div></div>
</div>
</div>
<div class="rocks-overview-stats">
<article><strong>{{ $streakCurrent }}</strong><span>{{ $t('Tage Serie', 'Day streak') }}</span></article>
<article><strong>{{ $creditTransactionsCount }}</strong><span>{{ $t('Belohnungen', 'Rewards') }}</span></article>
<article><strong>{{ $inventoryCount }}</strong><span>Items</span></article>
</div>
</section>

<section class="rocks-center-flow" id="rocksCenterFlow">
<article class="rocks-center-card">
<header class="rocks-center-head">
<div class="rocks-center-title"><span>{{ $t('DEIN ROCKS-BEREICH', 'YOUR ROCKS AREA') }}</span><h2 id="rocksPanelTitle">{{ $t('Übersicht', 'Overview') }}</h2></div>
<nav aria-label="{{ $t('Rocks Bereiche', 'Rocks sections') }}" class="rocks-tabs" role="tablist">
<button class="active" data-rocks-tab="overview" data-title="{{ $t('Übersicht', 'Overview') }}" type="button">{{ $t('Übersicht', 'Overview') }}</button>
<button data-rocks-tab="earn" data-title="{{ $t('Rocks verdienen', 'Earn Rocks') }}" type="button">{{ $t('Verdienen', 'Earn') }}</button>
<button data-rocks-tab="history" data-title="{{ $t('Verlauf', 'History') }}" type="button">{{ $t('Verlauf', 'History') }}</button>
<button data-rocks-tab="collection" data-title="{{ $t('Sammlung', 'Collection') }}" type="button">{{ $t('Sammlung', 'Collection') }}</button>
</nav>
</header>
<div class="rocks-panels">
<section class="rocks-panel active" data-rocks-panel="overview">
@if($pendingTotal > 0)
<article class="rocks-claim-card">
<div class="rocks-claim-main">
<span>{{ $t('BEREIT ZUM ABHOLEN', 'READY TO COLLECT') }}</span>
<h3>{{ $number($pendingTotal) }} {{ $t('Rocks warten auf dich', 'Rocks are waiting for you') }}</h3>
<p>{{ $pendingCount }} {{ $pendingCount === 1 ? $t('neue Belohnung wurde vorgemerkt.', 'new reward is pending.') : $t('neue Belohnungen wurden vorgemerkt.', 'new rewards are pending.') }}</p>
<form method="POST" action="{{ route('rocks.collect') }}">@csrf<button type="submit">{{ $t('Alle Rocks abholen', 'Collect all Rocks') }}</button></form>
</div>
<div class="rocks-pending-list">
@foreach($pendingTransactions->take(3) as $transaction)
<article><i>+{{ $number((int) $transaction->amount) }}</i><div><strong>{{ $transactionLabel($transaction) }}</strong><small>{{ $transaction->created_at?->diffForHumans() }}</small></div></article>
@endforeach
</div>
</article>
@else
<article class="rocks-claim-card is-empty">
<div class="rocks-claim-main"><span>{{ $t('ALLES EINGESAMMELT', 'ALL COLLECTED') }}</span><h3>{{ $t('Keine offenen Rocks', 'No pending Rocks') }}</h3><p>{{ $t('Neue Belohnungen erscheinen hier automatisch.', 'New rewards appear here automatically.') }}</p></div>
</article>
@endif

<div class="rocks-overview-grid">
<article class="rocks-progress-card">
<header><div><span>{{ $t('HEUTE', 'TODAY') }}</span><h3>{{ $t('Tagesfortschritt', 'Daily progress') }}</h3></div><strong>{{ $number($dailyEarned) }} / {{ $number($dailyPossible) }}</strong></header>
<div class="rocks-daily-progress">
@forelse($dailyRewardRows->take(3) as $reward)
@php $percent = (int) min(100, round(((int) $reward['claimed_today'] / max(1, (int) $reward['daily_limit'])) * 100)); @endphp
<article><div><strong>{{ $rewardLabel($reward) }}</strong><small>{{ $reward['claimed_today'] }} / {{ $reward['daily_limit'] }}</small></div><b>+{{ $number((int) $reward['earned_today']) }}</b><i><span style="width:{{ $percent }}%"></span></i></article>
@empty
<div class="rocks-empty">{{ $t('Keine täglichen Belohnungen eingerichtet.', 'No daily rewards configured.') }}</div>
@endforelse
</div>
<footer><span>{{ $t('Tagesziel', 'Daily target') }}</span><strong>{{ $dailyProgressPercent }}%</strong><i><b style="width:{{ $dailyProgressPercent }}%"></b></i></footer>
</article>
<article class="rocks-next-card">
<header><div><span>{{ $t('ALS NÄCHSTES', 'UP NEXT') }}</span><h3>{{ $t('Schnelle Rocks', 'Quick Rocks') }}</h3></div><small>{{ $t('Heute', 'Today') }}</small></header>
@forelse($quickRewards as $index => $reward)
<article><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span><div><strong>{{ $rewardLabel($reward) }}</strong><small>{{ $reward['remaining_today'] }} {{ $t('noch möglich', 'remaining') }}</small></div><b>+{{ $number((int) $reward['amount']) }}</b></article>
@empty
<div class="rocks-empty">{{ $t('Alle täglichen Limits erreicht.', 'All daily limits reached.') }}</div>
@endforelse
</article>
</div>

<section class="rocks-section-card">
<header><div><span>{{ $t('ROCKS VERDIENEN', 'EARN ROCKS') }}</span><h3>{{ $t('Beliebte Aktivitäten', 'Popular activities') }}</h3></div><button data-rocks-tab-shortcut="earn" type="button">{{ $t('Alle anzeigen', 'Show all') }}</button></header>
<div class="rocks-featured-rewards">
@foreach($rewardRows->take(4) as $index => $reward)
<article class="{{ $index === 0 ? 'yellow' : ($index === 2 ? 'dark' : '') }}"><span>{{ \Illuminate\Support\Str::upper(str_replace('_', ' ', (string) $reward['action'])) }}</span><strong>{{ $rewardLabel($reward) }}</strong><p>{{ $reward['daily_limit'] === null ? $t('Einmalig', 'One-time') : $t('Bis zu', 'Up to').' '.$reward['daily_limit'].'× '.$t('täglich', 'daily') }}</p><b>+{{ $number((int) $reward['amount']) }}</b></article>
@endforeach
</div>
</section>

<section class="rocks-section-card">
<header><div><span>{{ $t('LETZTE AKTIONEN', 'LATEST ACTIVITY') }}</span><h3>{{ $t('Rocks-Verlauf', 'Rocks history') }}</h3></div><button data-rocks-tab-shortcut="history" type="button">{{ $t('Vollständiger Verlauf', 'Full history') }}</button></header>
<div class="rocks-history-preview">
@forelse($recentTransactions->take(4) as $transaction)
<article><span class="{{ (int) $transaction->amount >= 0 ? 'credit' : 'debit' }}">{{ (int) $transaction->amount >= 0 ? '+' : '−' }}{{ $number(abs((int) $transaction->amount)) }}</span><div><strong>{{ $transactionLabel($transaction) }}</strong><small>{{ $transaction->created_at?->diffForHumans() }}</small></div><b>{{ $number((int) $transaction->balance_after) }}</b></article>
@empty
<div class="rocks-empty">{{ $t('Noch keine Transaktionen vorhanden.', 'No transactions yet.') }}</div>
@endforelse
</div>
</section>
</section>

<section class="rocks-panel" data-rocks-panel="earn" hidden>
<div class="rocks-section-intro"><div><span>{{ $rewardRows->count() }} {{ $t('MÖGLICHKEITEN', 'WAYS') }}</span><h3>{{ $t('So verdienst du Rocks', 'How to earn Rocks') }}</h3><p>{{ $t('Tägliche Limits gelten pro Aktivität. Einmalige Belohnungen werden nur einmal gutgeschrieben.', 'Daily limits apply per activity. One-time rewards are credited only once.') }}</p></div><span class="rocks-info-pill"><i></i>{{ $t('Aktiv', 'Active') }}</span></div>
<div class="rocks-rewards-grid">
@forelse($rewardRows as $index => $reward)
<article class="rocks-reward-card"><div class="rocks-reward-icon"><span>{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span></div><div><strong>{{ $rewardLabel($reward) }}</strong><small>{{ $reward['daily_limit'] === null ? $t('Einmalig', 'One-time') : $t('Bis zu', 'Up to').' '.$reward['daily_limit'].'× '.$t('täglich', 'daily') }}</small></div><b>+{{ $number((int) $reward['amount']) }}</b></article>
@empty
<div class="rocks-empty">{{ $t('Keine Belohnungen eingerichtet.', 'No rewards configured.') }}</div>
@endforelse
</div>
<article class="rocks-app-streak-note"><div><span>{{ $t('APP-EXKLUSIV', 'APP EXCLUSIVE') }}</span><h3>{{ $t('Tägliche Login-Serie', 'Daily login streak') }}</h3><p>{{ $t('Die Login-Serie wird ausschließlich in der HNT.ROCKS-App abgeholt.', 'The login streak is collected exclusively in the HNT.ROCKS app.') }}</p></div><strong>{{ $streakSchedule->implode(' · ') }}</strong></article>
</section>

<section class="rocks-panel" data-rocks-panel="history" hidden>
<div class="rocks-section-intro history"><div><span>{{ $t('TRANSAKTIONEN', 'TRANSACTIONS') }}</span><h3>{{ $t('Dein Rocks-Verlauf', 'Your Rocks history') }}</h3><p>{{ $t('Gutschriften, Käufe und der jeweilige Kontostand nach jeder Aktion.', 'Credits, purchases and the balance after every action.') }}</p></div><div class="rocks-filter-buttons"><button class="active" data-history-filter="all" type="button">{{ $t('Alle', 'All') }}</button><button data-history-filter="credit" type="button">{{ $t('Verdient', 'Earned') }}</button><button data-history-filter="debit" type="button">{{ $t('Ausgegeben', 'Spent') }}</button></div></div>
<div class="rocks-history-table">
<header><span>{{ $t('Aktion', 'Action') }}</span><span>{{ $t('Typ', 'Type') }}</span><span>{{ $t('Betrag', 'Amount') }}</span><span>{{ $t('Stand', 'Balance') }}</span><span>{{ $t('Zeit', 'Time') }}</span></header>
@forelse($recentTransactions as $transaction)
@php $historyType = (int) $transaction->amount >= 0 ? 'credit' : 'debit'; @endphp
<article data-history-type="{{ $historyType }}"><div><i class="{{ $historyType === 'credit' ? 'green' : 'yellow' }}"></i><strong>{{ $transactionLabel($transaction) }}</strong></div><span>{{ $historyType === 'credit' ? $t('Belohnung', 'Reward') : $t('Shop', 'Shop') }}</span><b class="{{ $historyType }}">{{ $historyType === 'credit' ? '+' : '−' }}{{ $number(abs((int) $transaction->amount)) }}</b><strong>{{ $number((int) $transaction->balance_after) }}</strong><time>{{ $transaction->created_at?->diffForHumans() }}</time></article>
@empty
<div class="rocks-empty">{{ $t('Noch keine Transaktionen vorhanden.', 'No transactions yet.') }}</div>
@endforelse
</div>
</section>

<section class="rocks-panel" data-rocks-panel="collection" hidden>
<div class="rocks-section-intro"><div><span>{{ $t('INVENTAR & SHOP', 'INVENTORY & SHOP') }}</span><h3>{{ $t('Deine Sammlung', 'Your collection') }}</h3><p>{{ $t('Gekaufte Items können je nach Slot aktiviert oder wieder abgelegt werden.', 'Purchased items can be equipped or unequipped depending on their slot.') }}</p></div><span class="rocks-info-pill yellow"><i></i>{{ $inventoryCount }} Items</span></div>
<section class="rocks-equipped-card">
<header><div><span>{{ $t('AKTIV AUSGERÜSTET', 'CURRENTLY EQUIPPED') }}</span><h3>{{ $t('Dein aktueller Look', 'Your current look') }}</h3></div><a href="{{ route('crowns.inventory') }}">{{ $t('Inventar verwalten', 'Manage inventory') }}</a></header>
<div class="rocks-equipped-grid">
@forelse($equippedBySlot->take(3) as $slot => $equipped)
@php $item = $equipped->inventoryItem?->shopItem; @endphp
@if($item)
<article><div class="rocks-item-visual">{{ trim((string) $item->icon) !== '' ? $item->icon : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->displayName(), 0, 2)) }}</div><strong>{{ $item->displayName() }}</strong><small>{{ $item->slot }} · {{ $rarityLabel($item->rarity) }}</small><form method="POST" action="{{ route('crowns.inventory.unequip', $slot) }}">@csrf<button type="submit">{{ $t('Ablegen', 'Unequip') }}</button></form></article>
@endif
@empty
<div class="rocks-empty">{{ $t('Zurzeit ist kein Item ausgerüstet.', 'No item is currently equipped.') }}</div>
@endforelse
</div>
</section>

@if($inventoryItems->isNotEmpty())
<section class="rocks-shop-section"><header><div><span>{{ $t('DEIN INVENTAR', 'YOUR INVENTORY') }}</span><h3>{{ $t('Freigeschaltete Items', 'Unlocked items') }}</h3></div></header><div class="rocks-shop-grid inventory">
@foreach($inventoryItems as $inventoryItem)
@php $item = $inventoryItem->shopItem; @endphp
@if($item)
<article><span>{{ $rarityLabel($item->rarity) }}</span><div class="shop-visual">{{ trim((string) $item->icon) !== '' ? $item->icon : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->displayName(), 0, 2)) }}</div><strong>{{ $item->displayName() }}</strong><small>{{ $item->displayDescription() }}</small>
@if($inventoryItem->isEquipped())
<b>{{ $t('Ausgerüstet', 'Equipped') }}</b>
@elseif($item->isActivatable())
<form method="POST" action="{{ route('crowns.inventory.equip', $inventoryItem) }}">@csrf<button type="submit">{{ $t('Ausrüsten', 'Equip') }}</button></form>
@else
<b>{{ $t('Im Inventar', 'In inventory') }}</b>
@endif
</article>
@endif
@endforeach
</div></section>
@endif

<section class="rocks-shop-section"><header><div><span>{{ $t('SHOP-AUSWAHL', 'SHOP PICKS') }}</span><h3>{{ $t('Empfohlen für dich', 'Recommended for you') }}</h3></div><a href="{{ route('crowns.shop') }}">{{ $t('Zum Shop', 'Open shop') }}</a></header><div class="rocks-shop-grid">
@forelse($recommendedShopItems as $item)
<article><span>{{ $rarityLabel($item->rarity) }}</span><div class="shop-visual">{{ trim((string) $item->icon) !== '' ? $item->icon : \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->displayName(), 0, 2)) }}</div><strong>{{ $item->displayName() }}</strong><small>{{ $item->displayDescription() }}</small><b>{{ $number($item->effectivePrice()) }} Rocks</b><form method="POST" action="{{ route('crowns.shop.purchase', $item) }}">@csrf<button type="submit" {{ $balance < $item->effectivePrice() ? 'disabled' : '' }}>{{ $balance < $item->effectivePrice() ? $t('Zu wenig Rocks', 'Not enough Rocks') : $t('Kaufen', 'Purchase') }}</button></form></article>
@empty
<div class="rocks-empty">{{ $t('Zurzeit sind keine Shop-Items verfügbar.', 'No shop items are currently available.') }}</div>
@endforelse
</div></section>
</section>
</div>
</article>
</section>
</div>

<aside aria-label="Rocks Wallet" class="rocks-fixed-column rocks-fixed-left" id="rocksFixedLeft">
<article class="rocks-wallet-card">
<header><div><span>{{ $t('DEINE WALLET', 'YOUR WALLET') }}</span><h2>Rocks</h2></div><button aria-label="{{ $t('Verlauf öffnen', 'Open history') }}" data-rocks-tab-shortcut="history" type="button"><svg><use href="#i-arrow"></use></svg></button></header>
<div class="rocks-balance-orbit"><div><span>{{ $t('Aktuell', 'Current') }}</span><strong>{{ $number($balance) }}</strong><small>Rocks</small></div></div>
<div class="rocks-wallet-summary"><span><strong>{{ $number($earned) }}</strong><small>{{ $t('verdient', 'earned') }}</small></span><span><strong>{{ $number($spent) }}</strong><small>{{ $t('ausgegeben', 'spent') }}</small></span></div>
<div class="rocks-wallet-pending {{ $pendingTotal <= 0 ? 'is-collected' : '' }}"><div><span>{{ $t('Bereit zum Abholen', 'Ready to collect') }}</span><strong>+{{ $number($pendingTotal) }}</strong></div>@if($pendingTotal > 0)<form method="POST" action="{{ route('rocks.collect') }}">@csrf<button type="submit">{{ $t('Abholen', 'Collect') }}</button></form>@else<small>{{ $t('Alles eingesammelt', 'All collected') }}</small>@endif</div>
<div class="rocks-week-chart"><header><span>{{ $t('Diese Woche', 'This week') }}</span><strong>+{{ $number($weeklyRocksTotal) }}</strong></header><div>@foreach($weeklyRocksDays as $day)<i class="{{ $day['is_today'] ? 'today' : '' }} {{ $day['is_future'] ? 'future' : '' }}" style="height:{{ $day['amount'] > 0 ? max(8, (int) round(($day['amount'] / $weeklyMax) * 100)) : 5 }}%"></i>@endforeach</div><footer>@foreach($dayLabels as $label)<span>{{ $label }}</span>@endforeach</footer></div>
</article>
<article class="rocks-quick-card"><header><span>{{ $t('SCHNELLZUGRIFF', 'QUICK ACCESS') }}</span><h3>{{ $t('Rocks-Bereiche', 'Rocks sections') }}</h3></header><button data-rocks-tab-shortcut="collection" type="button"><svg><use href="#i-folder"></use></svg><span><strong>{{ $t('Shop & Inventar', 'Shop & inventory') }}</strong><small>{{ $t('Items kaufen und ausrüsten', 'Purchase and equip items') }}</small></span><i>→</i></button><button data-rocks-tab-shortcut="earn" type="button"><svg><use href="#i-plus"></use></svg><span><strong>{{ $t('Rocks verdienen', 'Earn Rocks') }}</strong><small>{{ $t('Alle Belohnungen ansehen', 'View all rewards') }}</small></span><i>→</i></button><button data-rocks-tab-shortcut="history" type="button"><svg><use href="#i-arrow"></use></svg><span><strong>{{ $t('Verlauf', 'History') }}</strong><small>{{ $t('Gutschriften und Käufe', 'Credits and purchases') }}</small></span><i>→</i></button></article>
</aside>

<aside aria-label="{{ $t('Login-Serie und Hinweise', 'Login streak and notices') }}" class="rocks-fixed-column rocks-fixed-right" id="rocksFixedRight">
<article class="rocks-streak-card"><header><div><span>{{ $t('APP-EXKLUSIV', 'APP EXCLUSIVE') }}</span><h2>{{ $t('Login-Serie', 'Login streak') }}</h2></div><strong>{{ $streakCurrent }}/{{ $streakMax }}</strong></header><p>{{ $t('Öffne die App täglich und sichere dir steigende Rocks-Belohnungen.', 'Open the app daily to earn increasing Rocks rewards.') }}</p><div class="rocks-streak-days">@foreach($streakSchedule as $index => $amount)@php $day = $index + 1; @endphp<span class="{{ $day <= $streakCurrent ? 'done' : ($day === $nextStreakDay && !$claimedToday ? 'today' : '') }}"><i>{{ $day }}</i><b>{{ $amount }}</b></span>@endforeach</div><div class="rocks-streak-total"><span>{{ $t('Aktuelle Serie', 'Current streak') }}</span><strong>{{ $streakCurrent }} {{ $streakCurrent === 1 ? $t('Tag', 'day') : $t('Tage', 'days') }}</strong><small>{{ $claimedToday ? $t('Heute bereits eingesammelt', 'Already collected today') : $t('Nächste Belohnung:', 'Next reward:').' '.$todayStreakAmount.' Rocks' }}</small></div><button data-rocks-toast="{{ $t('Die tägliche Login-Serie ist nur in der App verfügbar.', 'The daily login streak is only available in the app.') }}" type="button">{{ $t('Nur in der App verfügbar', 'Available in the app only') }}</button></article>
<article class="rocks-notice-card"><span>{{ $t('WICHTIGER HINWEIS', 'IMPORTANT NOTICE') }}</span><h3>{{ $t('Rein virtuelles Guthaben', 'Virtual balance only') }}</h3><p>{{ $nonCashNotice }}</p><div><i></i><small>{{ $t('Nur innerhalb von HNT.ROCKS nutzbar', 'Usable only within HNT.ROCKS') }}</small></div></article>
</aside>
</section>
<div class="toast" id="toast"></div>
</main>
<script>
window.HNT_DASHBOARD_HEADER_ENDPOINT = @json(route('feed.index'));
window.HNT_PREVIEW_LOCALE = @json(str_replace('_', '-', app()->getLocale()));
window.HNT_PREVIEW_USER_ID = @json(auth()->id());
window.HNT_PREVIEW_LIVE_BADGES = {
  endpoint: @json(route('socialite.header.live-badges')),
  notificationsEndpoint: @json(route('socialite.header.notifications')),
  messagesEndpoint: @json(route('socialite.header.messages')),
  friendRequestsEndpoint: @json(route('socialite.header.friend-requests')),
  interval: 8000,
  shellInterval: 5000,
  chatTabInterval: 4500
};
</script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-feed/app.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-feed/app.js')) ?: time() }}"></script>
<script src="{{ asset('assets/themes/hnt_preview/dashboard-rocks/rocks-live.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-rocks/rocks-live.js')) ?: time() }}" defer></script>
</body>
</html>

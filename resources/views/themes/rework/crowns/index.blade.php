@extends('themes.rework.layouts.app')

@section('title', 'HNT.rocks Wallet')
@section('body_class', 'wallet-page')
@section('left_col_class', 'wallet-left')

@section('content')
@php
    $walletBalance = (int) ($summary['balance'] ?? 0);
    $walletLifetimeEarned = (int) ($summary['lifetime_earned'] ?? 0);
    $walletLifetimeSpent = (int) ($summary['lifetime_spent'] ?? 0);
    $walletPendingTotal = (int) ($summary['pending_total'] ?? data_get($pendingCollection ?? [], 'total', 0));
    $walletPendingCount = (int) ($summary['pending_count'] ?? data_get($pendingCollection ?? [], 'count', 0));
    $walletDailyAmount = (int) ($summary['daily_login_amount'] ?? 0);
    $walletDailyClaimed = (bool) ($summary['daily_login_claimed'] ?? true);
    $walletDailyProgress = $walletDailyClaimed ? 100 : 0;
    $walletNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
    $walletRewardIconFor = static function (string $key): string {
        return match ($key) {
            'daily_login' => 'ph-calendar-check',
            'account_created', 'account.created' => 'ph-user-plus',
            'profile_completed' => 'ph-user-circle-check',
            'feed_post_created' => 'ph-note-pencil',
            'feed_comment_created', 'moment_comment_created' => 'ph-chat-circle-text',
            'moment_created' => 'ph-video-camera',
            'cup_submission_created', 'cup_submission_approved' => 'ph-trophy',
            'cup_idea_submitted', 'cup_idea_voted' => 'ph-lightbulb',
            'loadout_challenge_submission_created', 'loadout_challenge_submission_accepted' => 'ph-target',
            'moment_of_week_selected' => 'ph-medal',
            'quest_completed' => 'ph-seal-check',
            default => 'ph-sparkle',
        };
    };

    $walletRewardLimitLabel = static function (array $definition): string {
        $dailyLimit = $definition['daily_limit'] ?? null;

        if ($dailyLimit === null) {
            return 'ohne Tageslimit';
        }

        return ((int) $dailyLimit) === 1 ? '1× täglich' : 'bis ' . (int) $dailyLimit . '× täglich';
    };

    $walletTransactionIconFor = static function (?string $action, ?string $type): string {
        $action = strtolower((string) $action);
        $type = strtolower((string) $type);

        if ($type === 'debit' || str_contains($action, 'purchase') || str_contains($action, 'shop')) {
            return 'ph-storefront';
        }

        return match ($action) {
            'daily_login' => 'ph-calendar-check',
            'account_created', 'account.created' => 'ph-user-plus',
            'profile_completed' => 'ph-user-circle-check',
            'feed_post_created' => 'ph-note-pencil',
            'feed_comment_created', 'moment_comment_created' => 'ph-chat-circle-text',
            'moment_created' => 'ph-video-camera',
            'cup_submission_created', 'cup_submission_approved' => 'ph-trophy',
            'cup_idea_submitted', 'cup_idea_voted' => 'ph-lightbulb',
            'loadout_challenge_submission_created', 'loadout_challenge_submission_accepted' => 'ph-target',
            'moment_of_week_selected' => 'ph-medal',
            'quest_completed' => 'ph-seal-check',
            default => 'ph-sparkle',
        };
    };

@endphp

<section class="members-head wallet-head">
    <div>
        <span class="members-eyebrow">Bounty Marks</span>
        <h1>Wallet</h1>
        <p>Sammle, verwalte und hole deine Bounty Marks direkt ab.</p>
    </div>
    <div class="wallet-head-actions">
        <a class="members-filter-btn" href="{{ route('crowns.shop') }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i>Shop</a>
        <a class="members-filter-btn" href="{{ route('crowns.history') }}"><i aria-hidden="true" class="ph ph-clock-counter-clockwise ph-icon"></i>Verlauf</a>
    </div>
</section>

<section class="wallet-hero card">
    <div class="wallet-hero-top">
        <div class="wallet-coin-wrap">
            <img alt="Bounty Marks" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>
        </div>
        <div class="wallet-hero-copy">
            <span class="wallet-kicker">Dein Guthaben</span>
            <h2>{{ $walletNumber($walletBalance) }} Bounty Marks</h2>
            <p>Bounty Marks sind dein virtuelles HNT.rocks-Guthaben für Shop-Items, Profil-Upgrades und Sammlerbelohnungen.</p>
        </div>
        <div class="wallet-claim-box">
            <span>Bereit zum Abholen</span>
            <strong>+{{ $walletNumber($walletPendingTotal) }}</strong>
            @if($walletPendingTotal > 0)
                <form method="post" action="{{ route('crowns.collect') }}">
                    @csrf
                    <button class="btn" type="submit">Marks abholen</button>
                </form>
            @else
                <button class="btn" type="button" disabled>Nichts offen</button>
            @endif
        </div>
    </div>
    <div class="wallet-progress-block">
        <div class="wallet-progress-head">
            <span>Täglicher Login-Bonus</span>
            <strong>{{ $walletDailyClaimed ? 'abgeholt' : '+' . $walletNumber($walletDailyAmount) . ' offen' }}</strong>
        </div>
        <div class="wallet-progress"><span style="width:{{ $walletDailyProgress }}%"></span></div>
    </div>
    <div class="wallet-stats-grid">
        <article><span>Aktuell</span><strong>{{ $walletNumber($walletBalance) }}</strong><em>verfügbar</em></article>
        <article><span>Gesammelt</span><strong>{{ $walletNumber($walletLifetimeEarned) }}</strong><em>lifetime</em></article>
        <article><span>Ausgegeben</span><strong>{{ $walletNumber($walletLifetimeSpent) }}</strong><em>Shop &amp; Inventar</em></article>
    </div>
</section>

<section class="wallet-action-grid">
    <article class="wallet-action-card card wallet-action-primary">
        <div class="wallet-action-icon"><i aria-hidden="true" class="ph ph-calendar-check ph-icon"></i></div>
        <div>
            <h2>Täglicher Login-Bonus</h2>
            <p>{{ $walletDailyClaimed ? 'Heute bereits abgeholt.' : 'Hole deinen täglichen Bonus ab und halte deine Serie am Laufen.' }}</p>
        </div>
        <strong>+{{ $walletNumber($walletDailyAmount) }}</strong>
        @if(! $walletDailyClaimed && $walletDailyAmount > 0)
            <form method="post" action="{{ route('crowns.daily-login') }}">
                @csrf
                <button class="btn" type="submit">Abholen</button>
            </form>
        @else
            <button class="btn" type="button" disabled>Abgeholt</button>
        @endif
    </article>

    <article class="wallet-action-card card">
        <div class="wallet-action-icon"><i aria-hidden="true" class="ph ph-backpack ph-icon"></i></div>
        <div>
            <h2>Inventar</h2>
            <p>Gekaufte Rahmen, Titel und Profil-Upgrades verwalten.</p>
        </div>
        <a class="btn secondary" href="{{ route('crowns.inventory') }}">Öffnen</a>
    </article>

    <article class="wallet-action-card card">
        <div class="wallet-action-icon"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i></div>
        <div>
            <h2>Shop</h2>
            <p>Neue Cosmetics und Upgrades mit Bounty Marks kaufen.</p>
        </div>
        <a class="btn secondary" href="{{ route('crowns.shop') }}">Zum Shop</a>
    </article>

    <article class="wallet-action-card card">
        <div class="wallet-action-icon"><i aria-hidden="true" class="ph ph-info ph-icon"></i></div>
        <div>
            <h2>Hinweis</h2>
            <p>{{ $nonCashNotice }}</p>
        </div>
    </article>
</section>

<section class="wallet-section card" id="wallet-rewards">
    <div class="wallet-section-head">
        <div><span class="members-eyebrow">Belohnungen</span><h2>So sammelst du Marks</h2></div>
    </div>
    <div class="wallet-reward-grid">
        @foreach($rewardDefinitions as $rewardKey => $rewardDefinition)
            @php
                $rewardAmount = (int) ($rewardDefinition['amount'] ?? 0);
                $rewardDescription = (string) ($rewardDefinition['description'] ?? $rewardKey);
            @endphp
            <article>
                <i class="ph {{ $walletRewardIconFor((string) $rewardKey) }} ph-icon"></i>
                <div><strong>{{ $rewardDescription }}</strong><span>{{ $walletRewardLimitLabel((array) $rewardDefinition) }}</span></div>
                <em>+{{ $walletNumber($rewardAmount) }}</em>
            </article>
        @endforeach
    </div>
</section>

<section class="wallet-section card" id="wallet-history">
    <div class="wallet-section-head">
        <div><span class="members-eyebrow">Verlauf</span><h2>Letzte Bewegungen</h2></div>
        <a class="wallet-small-link" href="{{ route('crowns.history') }}">Alle anzeigen</a>
    </div>

    <div class="wallet-history-list">
        @forelse(($recentTransactions ?? collect()) as $transaction)
            @php
                $amount = (int) $transaction->amount;
                $isSpent = $amount < 0;
                $amountLabel = ($amount > 0 ? '+' : '') . $walletNumber($amount);
                $transactionDescription = filled($transaction->description)
                    ? (string) $transaction->description
                    : \Illuminate\Support\Str::headline(str_replace(['_', '.'], ' ', (string) $transaction->action));
                $transactionTime = $transaction->collected_at ?: $transaction->created_at;
            @endphp

            <div>
                <i class="ph {{ $walletTransactionIconFor($transaction->action, $transaction->type) }} ph-icon"></i>
                <span>
                    <strong>{{ $transactionDescription }}</strong>
                    <small>{{ optional($transactionTime)->diffForHumans() }} · {{ $transaction->action }}</small>
                </span>
                <em class="{{ $isSpent ? 'spent' : '' }}">{{ $amountLabel }}</em>
            </div>
        @empty
            <div class="wallet-history-empty">
                <i class="ph ph-clock-counter-clockwise ph-icon"></i>
                <span>
                    <strong>Noch keine Bewegungen</strong>
                    <small>Sobald du Marks sammelst, kaufst oder ausrüstest, erscheint es hier.</small>
                </span>
            </div>
        @endforelse
    </div>
</section>

@endsection

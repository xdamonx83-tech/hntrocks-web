@extends('themes.rework.layouts.app')

@section('title', 'Bounty Marks Verlauf')
@section('body_class', 'wallet-page')
@section('left_col_class', 'wallet-left')

@section('content')
@php
    $walletBalance = (int) ($summary['balance'] ?? 0);
    $walletNumber = static fn (int $value): string => number_format($value, 0, ',', '.');
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
            'arcade_ranked_win', 'arcade_ranked_draw', 'arcade_ranked_loss' => 'ph-game-controller',
            default => 'ph-sparkle',
        };
    };
@endphp

<section class="members-head wallet-head">
    <div>
        <span class="members-eyebrow">Bounty Marks</span>
        <h1>Verlauf</h1>
        <p>Alle Bewegungen deines HNT.rocks-Guthabens auf einen Blick.</p>
    </div>
    <div class="wallet-head-actions">
        <a class="members-filter-btn" href="{{ route('crowns.index') }}"><i aria-hidden="true" class="ph ph-wallet ph-icon"></i>Wallet</a>
        <a class="members-filter-btn" href="{{ route('crowns.shop') }}"><i aria-hidden="true" class="ph ph-storefront ph-icon"></i>Shop</a>
    </div>
</section>

<section class="wallet-hero card">
    <div class="wallet-hero-top">
        <div class="wallet-coin-wrap">
            <img alt="Bounty Marks" src="{{ \App\Support\HntTheme::asset('images/shop-coin.webp', 'rework') }}"/>
        </div>
        <div class="wallet-hero-copy">
            <span class="wallet-kicker">Aktuelles Guthaben</span>
            <h2>{{ $walletNumber($walletBalance) }} Bounty Marks</h2>
            <p>Gewinne, Belohnungen und Ausgaben werden hier chronologisch aufgeführt.</p>
        </div>
    </div>
</section>

<section class="wallet-section card" id="wallet-history">
    <div class="wallet-section-head">
        <div><span class="members-eyebrow">Aktivität</span><h2>Alle Bewegungen</h2></div>
    </div>

    <div class="wallet-history-list">
        @forelse(($transactions ?? collect()) as $transaction)
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
                    <small>{{ optional($transactionTime)->format('d.m.Y H:i') }} · {{ $transaction->action }}</small>
                </span>
                <em class="{{ $isSpent ? 'spent' : '' }}">{{ $amountLabel }}</em>
            </div>
        @empty
            <div class="wallet-history-empty">
                <i class="ph ph-clock-counter-clockwise ph-icon"></i>
                <span>
                    <strong>Noch keine Bewegungen</strong>
                    <small>Sobald du Bounty Marks sammelst oder ausgibst, erscheint es hier.</small>
                </span>
            </div>
        @endforelse
    </div>

    @if($transactions && $transactions->hasPages())
        <div class="mt-6">
            {{ $transactions->links() }}
        </div>
    @endif
</section>

@endsection

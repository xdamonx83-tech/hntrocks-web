@extends('themes.socialite.layouts.app')

@section('title', __('ui.crowns_history_meta_title'))
@section('meta_description', __('ui.crowns_history_meta_description'))

@section('content')
@php
    $balance = (int) ($summary['balance'] ?? 0);
@endphp

<div class="2xl:max-w-[1220px] max-w-[1065px] mx-auto" id="js-crowns-history-page">
    <div class="page-heading">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="page-title">{{ __('ui.crowns_history_title') }}</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-white/60">{{ __('ui.crowns_history_subtitle') }}</p>
            </div>
            <a href="{{ route('crowns.index') }}" class="button bg-secondery text-black dark:text-white">
                <i class="ph ph-arrow-left" aria-hidden="true"></i>
                {{ __('ui.crowns_back_to_wallet') }}
            </a>
        </div>
    </div>

    <section class="box p-5 sm:p-6 mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-yellow-600 uppercase tracking-wide">{{ __('ui.crowns_history_kicker') }}</p>
                <h2 class="mt-1 text-xl font-bold text-black dark:text-white">{{ number_format($balance, 0, ',', '.') }} Bounty Marks</h2>
            </div>
        </div>
    </section>

    <section class="box p-5 sm:p-6">
        <div class="space-y-3">
            @forelse($transactions ?? [] as $transaction)
                <article class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="min-w-0">
                        <h3 class="truncate font-bold text-black dark:text-white">{{ $transaction->description ?: $transaction->action }}</h3>
                        <p class="mt-1 text-xs text-gray-500 dark:text-white/60">
                            {{ $transaction->created_at?->format('d.m.Y H:i') }} · {{ __('ui.crowns_balance_after') }} {{ number_format((int) $transaction->balance_after, 0, ',', '.') }}
                        </p>
                    </div>
                    <strong class="shrink-0 {{ (int) $transaction->amount >= 0 ? 'text-emerald-600 dark:text-emerald-300' : 'text-red-600 dark:text-red-300' }}">
                        {{ (int) $transaction->amount >= 0 ? '+' : '' }}{{ number_format((int) $transaction->amount, 0, ',', '.') }}
                    </strong>
                </article>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-gray-500 dark:border-white/10 dark:text-white/60">
                    {{ __('ui.crowns_history_empty') }}
                </div>
            @endforelse
        </div>

        @if($transactions && $transactions->hasPages())
            <div class="mt-6">
                {{ $transactions->links() }}
            </div>
        @endif
    </section>
</div>
@endsection

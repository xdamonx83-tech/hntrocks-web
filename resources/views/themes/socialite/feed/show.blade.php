@extends('themes.socialite.layouts.app')

@php
    $authorName = $post->user?->name ?: 'HNT Hunter';
    $backUrl = $socialiteBackUrl ?? url('/feed');
    $backLabel = $socialiteBackLabel ?? 'Back to feed';
    $modeLabel = $socialiteModeLabel ?? 'Single post';
@endphp

@section('title', $authorName . ' · Feed · HNT.rocks')
@section('meta_description', 'HNT.rocks Feed-Beitrag von ' . $authorName . '.')

@section('content')
    <div class="max-w-[680px] mx-auto py-4 md:py-6">
        <div class="mb-4 flex items-center justify-between gap-3">
            <a href="{{ $backUrl }}" class="inline-flex items-center gap-2 rounded-full bg-white px-4 py-2 text-sm font-bold text-black shadow-sm border border-slate-200 hover:bg-slate-50 dark:bg-dark2 dark:text-white dark:border-slate-700 dark:hover:bg-dark3">
                <ion-icon name="chevron-back-outline" class="text-lg"></ion-icon>
                <span>{{ $backLabel }}</span>
            </a>

            <div class="text-xs font-semibold text-slate-500 dark:text-white/60">
                {{ $modeLabel }}
            </div>
        </div>

        @include('themes.socialite.feed.partials.post-card', ['post' => $post])
    </div>
@endsection

@extends('themes.socialite.layouts.app')

@section('title', $post->title . ' · LFG')
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($post->body ?: __('ui.lfg_no_description')), 150))

@php
    $author = $post->user;
    $viewer = auth()->user();
    $acceptedApplications = $post->applications->where('status', 'accepted');
    $pendingApplications = $post->applications->where('status', 'pending');
    $slotUsers = collect([$author])->merge($acceptedApplications->pluck('user'))->filter()->take((int) $post->slots_total);
    $emptySlots = max(0, (int) $post->slots_total - $slotUsers->count());
    $slotsOpen = $post->slotsOpen();
    $isOpen = $post->status === 'open';
    $isFull = $post->status === 'full';
    $tags = collect($post->displayTags())->filter()->values();
    $description = filled($post->body) ? $post->body : __('ui.lfg_no_description');
    $canReportLfg = ! $canManage;
    $profileUrl = function ($user) {
        if (! $user?->username) {
            return route('members.index');
        }

        return (int) $user->id === (int) auth()->id()
            ? route('profile.show')
            : route('profile.public', $user);
    };
@endphp

@section('content')
<div class="max-w-[1065px] mx-auto max-lg:-m-2.5 pb-10">
    @if (session('status'))
        <div class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="mb-4 rounded-xl bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-700 dark:bg-rose-500/10 dark:text-rose-300">{{ $errors->first() }}</div>
    @endif

    <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 overflow-hidden mb-6">
        <div class="relative h-56 bg-slate-100 dark:bg-dark3 overflow-hidden">
            <img src="{{ $author->coverUrl() }}" alt="{{ __('ui.lfg_detail_cover_alt', ['user' => $author->name]) }}" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/65 via-black/20 to-transparent"></div>
            <div class="absolute top-4 right-4 flex items-center gap-2">
                <a href="{{ route('lfg.index') }}" class="inline-flex items-center gap-2 rounded-lg bg-white/90 px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-white dark:bg-dark2/90 dark:text-white">
                    <ion-icon name="arrow-back-outline" class="text-lg"></ion-icon>
                    {{ __('ui.lfg_detail_back_to_overview') }}
                </a>
                <a href="{{ route('lfg.show', ['post' => $post, 'classic_lfg' => 1]) }}" class="inline-flex items-center gap-2 rounded-lg bg-black/45 px-3 py-2 text-sm font-semibold text-white hover:bg-black/60">
                    <ion-icon name="open-outline" class="text-lg"></ion-icon>
                    {{ __('ui.classic') }}
                </a>
            </div>
            <div class="absolute bottom-5 left-5 right-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <div class="flex flex-wrap gap-2 mb-3">
                            <span class="rounded-full bg-white/95 px-3 py-1 text-xs font-bold text-blue-600 dark:bg-dark2 dark:text-blue-400">{{ $post->statusLabel() }}</span>
                            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">{{ $post->visibilityLabel() }}</span>
                            <span class="rounded-full bg-white/20 px-3 py-1 text-xs font-bold text-white">{{ $post->created_at->diffForHumans() }}</span>
                        </div>
                        <h1 class="text-3xl md:text-4xl font-bold text-white leading-tight">{{ $post->title }}</h1>
                    </div>
                    <div class="flex gap-2">
                        @if ($canManage)
                            <a href="{{ route('lfg.edit', $post) }}" class="button bg-blue-600 text-white hover:bg-blue-700 px-4 py-2 rounded-lg font-semibold">{{ __('ui.lfg_detail_edit') }}</a>
                        @elseif ($canReportLfg)
                            <button class="button bg-white/95 text-gray-700 hover:bg-white px-4 py-2 rounded-lg font-semibold text-tooltip-tft" type="button" title="{{ __('ui.lfg_report') }}" data-title="{{ __('ui.lfg_report') }}" aria-label="{{ __('ui.lfg_report') }}" data-hh-report-open data-hh-report-type="lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.lfg_report_label', ['title' => $post->title]) }}">
                                {{ __('ui.report_short') }}
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="p-5 flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 dark:border-slate-700/50">
            <a href="{{ $profileUrl($author) }}" class="flex items-center gap-3 min-w-0">
                <img src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" class="w-12 h-12 rounded-full object-cover bg-slate-100">
                <span class="min-w-0">
                    <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $author->name }}</span>
                    <span class="block text-sm text-gray-500 truncate">{{ '@'.$author->username }} · {{ __('ui.lfg_detail_owner') }}</span>
                </span>
            </a>

            <div class="flex flex-wrap gap-2 text-sm">
                <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.lfg_stat_free') }}: {{ $slotsOpen }}</span>
                <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.lfg_stat_requests') }}: {{ (int) $post->pending_count }}</span>
                <span class="rounded-lg bg-slate-100 px-3 py-2 font-semibold text-gray-700 dark:bg-dark3 dark:text-white">{{ __('ui.lfg_detail_voice') }}: {{ $post->voiceLabel() }}</span>
            </div>
        </div>
    </article>

    <div class="lg:flex lg:items-start gap-6">
        <main class="min-w-0 flex-1 space-y-6">
            <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                <h2 class="text-xl font-bold mb-3">{{ __('ui.lfg_detail_information_title') }}</h2>
                <div class="prose prose-slate max-w-none dark:prose-invert text-gray-600 dark:text-white/80 leading-7">
                    <p>{!! \App\Support\MentionRenderer::render($description) !!}</p>
                </div>
                <div class="flex flex-wrap gap-2 mt-5">
                    @forelse ($tags as $tag)
                        <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-gray-600 dark:bg-dark3 dark:text-white/80">{{ $tag }}</span>
                    @empty
                        <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-semibold text-gray-600 dark:bg-dark3 dark:text-white/80">{{ __('ui.lfg_detail_no_tags') }}</span>
                    @endforelse
                </div>
            </article>

            <article class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-4">
                <div class="flex items-center justify-between gap-3 mb-3">
                    <h2 class="text-lg font-bold">{{ __('ui.lfg_detail_slots_title') }}</h2>
                    <span class="rounded-lg bg-slate-100 px-3 py-1.5 text-sm font-bold text-gray-600 dark:bg-dark3 dark:text-white/80">
                        {{ __('ui.lfg_stat_free') }}: {{ $slotsOpen }}
                    </span>
                </div>
                <div class="space-y-2">
                    @foreach ($slotUsers as $slotUser)
                        <a href="{{ $profileUrl($slotUser) }}" class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 hover:bg-slate-100 dark:bg-dark3 dark:hover:bg-slate-800">
                            <img src="{{ $slotUser->avatarUrl() }}" alt="{{ $slotUser->name }}" class="rounded-full object-cover shrink-0" style="width:44px;height:44px;">
                            <span class="min-w-0">
                                <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $slotUser->name }}</span>
                                <span class="block text-sm text-gray-500 truncate">{{ '@'.$slotUser->username }}</span>
                            </span>
                        </a>
                    @endforeach
                    @for ($i = 0; $i < $emptySlots; $i++)
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 dark:bg-dark3">
                            <span class="rounded-full bg-slate-200 dark:bg-slate-700 flex items-center justify-center text-gray-500 shrink-0" style="width:44px;height:44px;"><ion-icon name="person-add-outline"></ion-icon></span>
                            <span class="font-bold text-gray-500">{{ __('ui.lfg_free_slot') }}</span>
                        </div>
                    @endfor
                </div>
            </article>

            @if ($canManage)
                <article id="lfg-applications" class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                    <h2 class="text-xl font-bold mb-4">{{ __('ui.lfg_detail_applications_title') }} <span class="text-blue-600">{{ (int) $post->pending_count }}</span></h2>
                    <div class="space-y-3">
                        @forelse ($post->applications as $application)
                            <div class="rounded-xl bg-slate-50 p-3 dark:bg-dark3">
                                <div class="flex items-start justify-between gap-3">
                                    <a href="{{ $profileUrl($application->user) }}" class="flex items-center gap-3 min-w-0">
                                        <img src="{{ $application->user->avatarUrl() }}" alt="{{ $application->user->name }}" class="w-11 h-11 rounded-full object-cover">
                                        <span class="min-w-0">
                                            <span class="block font-bold text-gray-900 dark:text-white truncate">{{ $application->user->name }}</span>
                                            <span class="block text-sm text-gray-500">{{ $application->statusLabel() }} · {{ $application->created_at->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                    @if ($application->status === 'pending')
                                        <div class="flex gap-2 shrink-0">
                                            <form method="post" action="{{ route('lfg.applications.accept', [$post, $application]) }}">@csrf<button class="rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white" type="submit">{{ __('ui.lfg_detail_accept_application') }}</button></form>
                                            <form method="post" action="{{ route('lfg.applications.reject', [$post, $application]) }}">@csrf<button class="rounded-lg bg-slate-200 px-3 py-2 text-sm font-bold text-gray-700 dark:bg-slate-700 dark:text-white" type="submit">{{ __('ui.lfg_detail_reject_application') }}</button></form>
                                        </div>
                                    @endif
                                </div>
                                <p class="mt-3 text-sm text-gray-600 dark:text-white/75">{{ $application->message ?: __('ui.lfg_detail_application_no_message') }}</p>
                            </div>
                        @empty
                            <p class="text-gray-500">{{ __('ui.lfg_detail_no_applications') }}</p>
                        @endforelse
                    </div>
                </article>
            @endif
        </main>

        <aside class="lg:w-80 shrink-0 space-y-6 max-lg:mt-6">
            <section class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                <h2 class="text-lg font-bold mb-4">{{ __('ui.lfg_detail_action_title') }}</h2>
                @if ($canManage)
                    <div class="space-y-2">
                        <a class="block w-full text-center rounded-lg bg-blue-600 px-4 py-2 font-bold text-white" href="{{ route('lfg.edit', $post) }}">{{ __('ui.lfg_detail_edit') }}</a>
                        <a class="block w-full text-center rounded-lg bg-slate-100 px-4 py-2 font-bold text-gray-700 dark:bg-dark3 dark:text-white" href="{{ route('lfg.index') }}">{{ __('ui.lfg_detail_back_to_overview') }}</a>
                    </div>
                @elseif ($post->canApply($viewer))
                    <form method="post" action="{{ route('lfg.applications.store', $post) }}" class="space-y-3">
                        @csrf
                        <label for="message" class="block text-sm font-bold text-gray-600 dark:text-white/80">{{ __('ui.lfg_detail_application_message_label') }}</label>
                        <textarea id="message" name="message" rows="4" maxlength="800" class="w-full rounded-xl bg-slate-100 px-3 py-3 text-sm outline-none dark:bg-dark3 dark:text-white" placeholder="{{ __('ui.lfg_detail_application_placeholder') }}"></textarea>
                        <button class="w-full rounded-lg bg-blue-600 px-4 py-2 font-bold text-white" type="submit">{{ __('ui.lfg_detail_apply_button') }}</button>
                    </form>
                @elseif ($viewerApplication)
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-dark3">
                        <p class="font-bold">{{ __('ui.lfg_detail_your_application') }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ $viewerApplication->statusLabel() }}</p>
                        @if ($viewerApplication->message)
                            <p class="text-sm text-gray-600 dark:text-white/75 mt-3">{{ $viewerApplication->message }}</p>
                        @endif
                    </div>
                @else
                    <div class="rounded-xl bg-slate-50 p-4 dark:bg-dark3">
                        <p class="font-bold">{{ __('ui.lfg_detail_application_closed') }}</p>
                        <p class="text-sm text-gray-500 mt-1">{{ __('ui.lfg_detail_application_closed_text') }}</p>
                    </div>
                @endif
            </section>

            <section class="bg-white rounded-xl shadow-sm border1 dark:bg-dark2 p-5">
                <h2 class="text-lg font-bold mb-4">{{ __('ui.lfg_detail_information_title') }}</h2>
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_status') }}</dt><dd class="font-bold text-right">{{ $post->statusLabel() }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_platform') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('platform', $post->platform) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_region') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('region', $post->region) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_language') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('language', $post->language) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_playstyle') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('playstyle', $post->playstyle) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_preferred_time') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('preferred_time', $post->preferred_time) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-gray-500">{{ __('ui.lfg_detail_experience') }}</dt><dd class="font-bold text-right">{{ $post->localizedOptionLabel('experience_level', $post->experience_level) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                </dl>
            </section>
        </aside>
    </div>
</div>
@endsection

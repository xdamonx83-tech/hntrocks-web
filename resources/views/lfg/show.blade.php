@extends('layouts.app')

@section('title', $post->title.' · LFG')

@section('content')
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
    $statusClass = $isOpen ? 'hh-lfg-status-open' : ($isFull ? 'hh-lfg-status-full' : 'hh-lfg-status-closed');
    $tags = $post->displayTags();
    $description = filled($post->body) ? $post->body : __('ui.lfg_no_description');
    $canReportLfg = ! $canManage;
@endphp

@if (session('status'))
    <div class="hh-toast-stack" aria-live="polite" aria-atomic="true">
        <div class="hh-toast hh-toast-success">
            <i class="hh-toast-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
            <span>{{ session('status') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="hh-alert hh-alert-danger">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="section-banner hh-lfg-detail-section-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/groups-icon.png') }}" alt="{{ __('ui.lfg_detail_banner_alt') }}">
    <p class="section-banner-title">{{ __('ui.lfg_detail_banner_title') }}</p>
    <p class="section-banner-text">{{ __('ui.lfg_detail_banner_text') }}</p>
</div>

<div class="grid grid-8-4 stretched mobile-prefer-content hh-lfg-product-layout">
    <div class="grid-column">
        <article class="widget-box no-padding hh-lfg-product-main">
            <figure class="hh-lfg-product-cover liquid">
                <img src="{{ $author->coverUrl() }}" alt="{{ __('ui.lfg_detail_cover_alt', ['user' => $author->name]) }}">
            </figure>

            <div class="hh-lfg-product-body">
                <div class="hh-lfg-product-title-row">
                    <div>
                        <p class="hh-lfg-product-kicker">{{ $post->statusLabel() }} · {{ $post->visibilityLabel() }} · {{ $post->created_at->diffForHumans() }}</p>
                        <h1 class="hh-lfg-product-title">{{ $post->title }}</h1>
                    </div>

                    <div class="tag-sticker {{ $statusClass }}" title="{{ $post->statusLabel() }}">
                        <svg class="tag-sticker-icon {{ $isOpen ? 'icon-join-group' : 'icon-cross' }}">
                            <use xlink:href="{{ $isOpen ? '#svg-join-group' : '#svg-cross' }}"></use>
                        </svg>
                    </div>
                </div>

                <div class="hh-lfg-product-author user-status">
                    <a class="user-status-avatar" href="{{ route('profile.public', $author) }}">
                        <div class="user-avatar small no-outline">
                            <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $author->avatarUrl() }}"></div></div>
                            <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                            <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                            <div class="user-avatar-badge">
                                <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                <p class="user-avatar-badge-text">{{ $author->level ?? 1 }}</p>
                            </div>
                        </div>
                    </a>
                    <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $author) }}">{{ $author->name }}</a></p>
                    <p class="user-status-text small">{{ '@'.$author->username }} · {{ __('ui.lfg_detail_owner') }}</p>
                </div>

                <div class="hh-lfg-product-description">
                    <p>{!! \App\Support\MentionRenderer::render($description) !!}</p>
                </div>

                <div class="hh-lfg-product-tags">
                    @forelse ($tags as $tag)
                        <span>{{ $tag }}</span>
                    @empty
                        <span>{{ __('ui.lfg_detail_no_tags') }}</span>
                    @endforelse
                    <span>{{ $post->voiceLabel() }}</span>
                </div>
            </div>
        </article>

        <div class="grid grid-6-6 hh-lfg-detail-info-grid">
            <div class="widget-box hh-lfg-detail-widget">
                <p class="widget-box-title">{{ __('ui.lfg_detail_information_title') }}</p>
                <div class="widget-box-content">
                    <div class="information-line-list hh-lfg-information-lines">
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_status') }}</p><p class="information-line-text">{{ $post->statusLabel() }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_platform') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('platform', $post->platform) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_playstyle') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('playstyle', $post->playstyle) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_region') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('region', $post->region) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_language') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('language', $post->language) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_preferred_time') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('preferred_time', $post->preferred_time) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_experience') }}</p><p class="information-line-text">{{ $post->localizedOptionLabel('experience_level', $post->experience_level) ?: __('ui.lfg_detail_not_set') }}</p></div>
                        <div class="information-line"><p class="information-line-title">{{ __('ui.lfg_detail_voice') }}</p><p class="information-line-text">{{ $post->voiceLabel() }}</p></div>
                    </div>
                </div>
            </div>

            <div class="widget-box hh-lfg-detail-widget">
                <p class="widget-box-title">{{ __('ui.lfg_detail_slots_title') }}</p>
                <div class="widget-box-content">
                    <div class="user-avatar-list reverse medium hh-lfg-detail-slot-avatars">
                        @foreach ($slotUsers as $slotUser)
                            <a class="user-avatar small no-stats" href="{{ route('profile.public', $slotUser) }}" title="{{ $slotUser->name }}">
                                <div class="user-avatar-border"><div class="hexagon-40-44"></div></div>
                                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $slotUser->avatarUrl() }}"></div></div>
                            </a>
                        @endforeach

                        @for ($i = 0; $i < $emptySlots; $i++)
                            <div class="user-avatar small no-stats hh-lfg-empty-slot" title="{{ __('ui.lfg_free_slot') }}">
                                <div class="user-avatar-border"><div class="hexagon-40-44"></div></div>
                                <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ asset('assets/vikinger/img/avatar/01.jpg') }}"></div></div>
                            </div>
                        @endfor
                    </div>

                    <div class="user-stats hh-lfg-detail-stats">
                        <div class="user-stat"><p class="user-stat-title">{{ $slotsOpen }}</p><p class="user-stat-text">{{ __('ui.lfg_stat_free') }}</p></div>
                        <div class="user-stat"><p class="user-stat-title">{{ (int) $post->pending_count }}</p><p class="user-stat-text">{{ __('ui.lfg_stat_requests') }}</p></div>
                        <div class="user-stat"><p class="user-stat-title">{{ (int) $post->accepted_count }}</p><p class="user-stat-text">{{ __('ui.lfg_detail_accepted') }}</p></div>
                    </div>
                </div>
            </div>
        </div>

        @if ($canManage)
            <article id="lfg-applications" class="widget-box hh-lfg-detail-widget hh-lfg-applications-widget">
                <p class="widget-box-title">{{ __('ui.lfg_detail_applications_title') }} <span class="highlighted">{{ (int) $post->pending_count }}</span></p>
                <div class="widget-box-content">
                    <div class="user-status-list hh-lfg-application-list">
                        @forelse ($post->applications as $application)
                            <div class="user-status request-small hh-lfg-application-status {{ $application->status === 'pending' ? 'is-pending' : '' }}">
                                <a class="user-status-avatar" href="{{ route('profile.public', $application->user) }}">
                                    <div class="user-avatar small no-outline">
                                        <div class="user-avatar-content"><div class="hexagon-image-30-32" data-src="{{ $application->user->avatarUrl() }}"></div></div>
                                        <div class="user-avatar-progress"><div class="hexagon-progress-40-44"></div></div>
                                        <div class="user-avatar-progress-border"><div class="hexagon-border-40-44"></div></div>
                                        <div class="user-avatar-badge">
                                            <div class="user-avatar-badge-border"><div class="hexagon-22-24"></div></div>
                                            <div class="user-avatar-badge-content"><div class="hexagon-dark-16-18"></div></div>
                                            <p class="user-avatar-badge-text">{{ $application->user->level ?? 1 }}</p>
                                        </div>
                                    </div>
                                </a>

                                <p class="user-status-title"><a class="bold" href="{{ route('profile.public', $application->user) }}">{{ $application->user->name }}</a></p>
                                <p class="user-status-text small">{{ $application->statusLabel() }} · {{ $application->created_at->diffForHumans() }}</p>
                                <p class="hh-lfg-application-message">{{ $application->message ?: __('ui.lfg_detail_application_no_message') }}</p>

                                @if ($application->status === 'pending')
                                    <div class="action-request-list hh-lfg-application-actions">
                                        <form method="post" action="{{ route('lfg.applications.accept', [$post, $application]) }}">
                                            @csrf
                                            <button class="action-request accept text-tooltip-tft" type="submit" title="{{ __('ui.lfg_detail_accept_application') }}" data-title="{{ __('ui.lfg_detail_accept_application') }}" aria-label="{{ __('ui.lfg_detail_accept_application') }}">
                                                <i class="action-request-icon hh-ph-action-icon ph ph-check" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                        <form method="post" action="{{ route('lfg.applications.reject', [$post, $application]) }}">
                                            @csrf
                                            <button class="action-request decline text-tooltip-tft" type="submit" title="{{ __('ui.lfg_detail_reject_application') }}" data-title="{{ __('ui.lfg_detail_reject_application') }}" aria-label="{{ __('ui.lfg_detail_reject_application') }}">
                                                <i class="action-request-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @empty
                            <p class="widget-box-text light">{{ __('ui.lfg_detail_no_applications') }}</p>
                        @endforelse
                    </div>
                </div>
            </article>
        @endif
    </div>

    <div class="grid-column">
        <aside class="product-preview hh-lfg-action-preview">
            <figure class="product-preview-image liquid">
                <img src="{{ $author->coverUrl() }}" alt="{{ __('ui.lfg_detail_cover_alt', ['user' => $author->name]) }}">
            </figure>

            <div class="product-preview-info">
                <p class="product-preview-title">{{ __('ui.lfg_detail_action_title') }}</p>
                <p class="product-preview-category physical">{{ $post->statusLabel() }}</p>
                <p class="product-preview-text">{{ __('ui.lfg_detail_action_text', ['slots' => $slotsOpen]) }}</p>

                @if ($canManage)
                    <div class="hh-lfg-owner-actions">
                        <a class="button secondary" href="{{ route('lfg.edit', $post) }}">{{ __('ui.lfg_detail_edit') }}</a>
                        <a class="button tertiary" href="{{ route('lfg.index') }}">{{ __('ui.lfg_detail_back_to_overview') }}</a>
                    </div>
                @elseif ($post->canApply($viewer))
                    <form method="post" action="{{ route('lfg.applications.store', $post) }}" class="form hh-lfg-application-form">
                        @csrf
                        <div class="form-input small active mid-textarea">
                            <label for="message">{{ __('ui.lfg_detail_application_message_label') }}</label>
                            <textarea id="message" name="message" rows="4" maxlength="800" placeholder="{{ __('ui.lfg_detail_application_placeholder') }}"></textarea>
                        </div>
                        <button class="button primary" type="submit">{{ __('ui.lfg_detail_apply_button') }}</button>
                    </form>
                @elseif ($viewerApplication)
                    <div class="hh-lfg-viewer-application-state">
                        <p class="hh-lfg-viewer-application-label">{{ __('ui.lfg_detail_your_application') }}</p>
                        <p class="hh-lfg-viewer-application-status">{{ $viewerApplication->statusLabel() }}</p>
                        @if ($viewerApplication->message)
                            <p class="hh-lfg-viewer-application-message">{{ $viewerApplication->message }}</p>
                        @endif
                    </div>
                @else
                    <div class="hh-lfg-viewer-application-state">
                        <p class="hh-lfg-viewer-application-label">{{ __('ui.lfg_detail_application_closed') }}</p>
                        <p class="hh-lfg-viewer-application-message">{{ __('ui.lfg_detail_application_closed_text') }}</p>
                    </div>
                @endif

                @if ($canReportLfg)
                    <button class="button white full hh-report-wide-action text-tooltip-tft" type="button" title="{{ __('ui.lfg_report') }}" data-title="{{ __('ui.lfg_report') }}" aria-label="{{ __('ui.lfg_report') }}" data-hh-report-open data-hh-report-type="lfg" data-hh-report-id="{{ $post->id }}" data-hh-report-label="{{ __('ui.lfg_report_label', ['title' => $post->title]) }}">
                        <i class="button-icon hh-ph-action-icon ph ph-warning-octagon" aria-hidden="true"></i>
                        <span>{{ __('ui.report_short') }}</span>
                    </button>
                @endif
            </div>

            <div class="product-preview-meta">
                <div class="product-preview-author">
                    <img class="product-preview-author-image" src="{{ $author->avatarUrl() }}" alt="{{ $author->name }}" width="18" height="18">
                    <p class="product-preview-author-title">{{ __('ui.lfg_detail_owner') }}</p>
                    <p class="product-preview-author-text"><a href="{{ route('profile.public', $author) }}">{{ $author->name }}</a></p>
                </div>
                <p class="hh-lfg-preview-status">{{ $post->voiceLabel() }}</p>
            </div>
        </aside>
    </div>
</div>
@endsection

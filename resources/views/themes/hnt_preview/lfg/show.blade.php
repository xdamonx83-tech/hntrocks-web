@extends('themes.hnt_preview.layouts.app')

@section('title', __('ui.preview_lfg_detail_page_title', ['title' => $post->title]))
@section('main_class', 'lfg-detail-main')

@php
    use Illuminate\Support\Str;

    $viewer = auth()->user();
    $author = $post->user;
    $acceptedApplications = $post->applications->where('status', 'accepted');
    $pendingApplications = $post->applications->where('status', 'pending');
    $slotUsers = collect([$author])->merge($acceptedApplications->pluck('user'))->filter()->take((int) $post->slots_total);
    $emptySlots = max(0, (int) $post->slots_total - $slotUsers->count());
    $slotsOpen = $post->slotsOpen();
    $tags = collect($post->displayTags())->filter()->values();
    $description = trim((string) $post->body) !== '' ? $post->body : __('ui.lfg_no_description');
    $coverUrl = $post->coverUrl();
    $avatarUrl = $author?->avatarUrl();
    $statusClass = match ($post->status) {
        'open' => 'open',
        'full' => 'full',
        'closed' => 'closed',
        default => 'neutral',
    };
    $canDelete = (bool) ($canDelete ?? ($viewer ? $post->canDelete($viewer) : false));
    $canApply = $viewer && ! $canDelete && $post->canApply($viewer);
    $detailDateFormat = app()->getLocale() === 'de' ? 'd. M Y' : 'M j, Y';
@endphp

@section('content')
    <div class="lfg-detail-shell">
        @if (session('status'))
            <div class="lfg-alert success">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="lfg-alert warning">{{ $errors->first() }}</div>
        @endif

        <article class="lfg-detail-hero">
            <section class="lfg-detail-cover" @if($coverUrl) style="--lfg-cover: url('{{ $coverUrl }}')" @endif>
                <div class="lfg-cover-art-noise" aria-hidden="true"></div>

                <a class="lfg-back-btn" href="{{ route('lfg.index') }}">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                    {{ __('ui.preview_lfg_back_overview') }}
                </a>

                @if (! $canManage && ! $canDelete)
                    <button class="lfg-report-btn" type="button" data-hnt-report-open data-report-type="lfg" data-report-id="{{ $post->id }}" data-report-label="{{ __('ui.preview_lfg_report_label', ['title' => $post->title]) }}">
                        {{ __('ui.preview_lfg_report') }}
                    </button>
                @endif

                <div class="lfg-title-center">
                    <div class="lfg-status-pills">
                        <span class="hot">{{ __('ui.preview_lfg_slots_open_short', ['count' => $slotsOpen]) }}</span>
                        <span class="{{ $statusClass }}">{{ $post->statusLabel() }}</span>
                        <span>{{ $post->voiceLabel() }}</span>
                    </div>
                    <h1>{{ $post->title }}</h1>
                    <p>{{ Str::limit(strip_tags($description), 130) }}</p>
                </div>
            </section>

            <section class="lfg-detail-owner-row">
                <a class="lfg-owner" href="{{ route('profile.public', $author) }}">
                    <img class="avatar lfg-owner-avatar" src="{{ $avatarUrl }}" alt="{{ $author?->name }}">
                    <span>
                        <strong>{{ $author?->name }}</strong>
                        <span>{{ '@'.$author?->username }} · {{ __('ui.lfg_detail_owner') }} · {{ $post->created_at?->diffForHumans() }}</span>
                    </span>
                </a>
                <div class="lfg-hero-stats">
                    <span>{{ __('ui.preview_lfg_slots_filled', ['filled' => (int) $post->slots_filled, 'total' => (int) $post->slots_total]) }}</span>
                    <span>{{ __('ui.preview_lfg_requests_count', ['count' => (int) $post->pending_count]) }}</span>
                    @if($post->expires_at)
                        <span>{{ __('ui.preview_lfg_until_short', ['date' => $post->expires_at->translatedFormat($detailDateFormat)]) }}</span>
                    @endif
                </div>
            </section>
        </article>

        <div class="lfg-detail-grid">
            <div class="lfg-detail-left">
                <section class="lfg-detail-panel">
                    <div class="lfg-section-title-row">
                        <h2>{{ __('ui.preview_lfg_description') }}</h2>
                        <span>{{ $post->visibilityLabel() }}</span>
                    </div>
                    <p>{!! \App\Support\Hashtag::renderText($description) !!}</p>

                    @if($tags->isNotEmpty())
                        <div class="lfg-detail-tags" aria-label="{{ __('ui.preview_lfg_tags_aria') }}">
                            @foreach($tags as $tag)
                                <span>{{ $tag }}</span>
                            @endforeach
                        </div>
                    @else
                        <div class="lfg-detail-tags"><span>{{ __('ui.lfg_detail_no_tags') }}</span></div>
                    @endif
                </section>

                <section class="lfg-detail-panel">
                    <div class="lfg-section-title-row">
                        <h2>{{ __('ui.preview_lfg_team_slots') }}</h2>
                        <span>{{ __('ui.preview_lfg_slots_open_short', ['count' => $slotsOpen]) }}</span>
                    </div>

                    <div class="lfg-slot-list">
                        @foreach($slotUsers as $slotUser)
                            <a class="lfg-slot" href="{{ route('profile.public', $slotUser) }}">
                                <img class="avatar lfg-slot-avatar" src="{{ $slotUser->avatarUrl() }}" alt="{{ $slotUser->name }}">
                                <span>
                                    <strong>{{ $slotUser->name }}</strong>
                                    <span>{{ '@'.$slotUser->username }} · {{ $loop->first ? __('ui.lfg_detail_owner') : __('ui.lfg_application_accepted') }}</span>
                                </span>
                            </a>
                        @endforeach

                        @for($i = 0; $i < $emptySlots; $i++)
                            <div class="lfg-slot empty">
                                <span class="lfg-slot-icon" aria-hidden="true">
                                    <i class="ph ph-plus" aria-hidden="true"></i>
                                </span>
                                <span>
                                    <strong>{{ __('ui.preview_lfg_free_slot') }}</strong>
                                    <span>{{ __('ui.preview_lfg_ready_for_request') }}</span>
                                </span>
                            </div>
                        @endfor
                    </div>
                </section>

                @if($canManage)
                    <section class="lfg-detail-panel" id="lfg-applications">
                        <div class="lfg-section-title-row">
                            <h2>{{ __('ui.lfg_detail_applications_title') }}</h2>
                            <span>{{ __('ui.preview_lfg_applications_open', ['count' => (int) $post->pending_count]) }}</span>
                        </div>

                        <div class="lfg-application-list">
                            @forelse($post->applications as $application)
                                <article class="lfg-application-card {{ $application->status }}">
                                    <a class="lfg-application-user" href="{{ route('profile.public', $application->user) }}">
                                        <img class="avatar" src="{{ $application->user->avatarUrl() }}" alt="{{ $application->user->name }}">
                                        <span>
                                            <strong>{{ $application->user->name }}</strong>
                                            <span>{{ $application->statusLabel() }} · {{ $application->created_at?->diffForHumans() }}</span>
                                        </span>
                                    </a>
                                    <p>{{ $application->message ?: __('ui.lfg_detail_application_no_message') }}</p>
                                    @if($application->status === 'pending')
                                        <div class="lfg-application-actions">
                                            <form method="post" action="{{ route('lfg.applications.accept', [$post, $application]) }}">
                                                @csrf
                                                <button class="lfg-icon-action accept" type="submit" title="{{ __('ui.lfg_detail_accept_application') }}" aria-label="{{ __('ui.lfg_detail_accept_application') }}">
                                                    <i class="ph ph-check" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                            <form method="post" action="{{ route('lfg.applications.reject', [$post, $application]) }}">
                                                @csrf
                                                <button class="lfg-icon-action reject" type="submit" title="{{ __('ui.lfg_detail_reject_application') }}" aria-label="{{ __('ui.lfg_detail_reject_application') }}">
                                                    <i class="ph ph-x" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </article>
                            @empty
                                <p>{{ __('ui.lfg_detail_no_applications') }}</p>
                            @endforelse
                        </div>
                    </section>
                @endif
            </div>

            <aside class="lfg-detail-side">
                <section class="lfg-detail-panel lfg-info-panel">
                    <h2>{{ __('ui.lfg_detail_information_title') }}</h2>
                    <dl class="lfg-info-list">
                        <div><dt>{{ __('ui.lfg_detail_status') }}</dt><dd>{{ $post->statusLabel() }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_platform') }}</dt><dd>{{ $post->localizedOptionLabel('platform', $post->platform) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_playstyle') }}</dt><dd>{{ $post->localizedOptionLabel('playstyle', $post->playstyle) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_region') }}</dt><dd>{{ $post->localizedOptionLabel('region', $post->region) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_language') }}</dt><dd>{{ $post->localizedOptionLabel('language', $post->language) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_preferred_time') }}</dt><dd>{{ $post->localizedOptionLabel('preferred_time', $post->preferred_time) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_experience') }}</dt><dd>{{ $post->localizedOptionLabel('experience_level', $post->experience_level) ?: __('ui.lfg_detail_not_set') }}</dd></div>
                        <div><dt>{{ __('ui.lfg_detail_voice') }}</dt><dd>{{ $post->voiceLabel() }}</dd></div>
                    </dl>
                </section>
            </aside>
        </div>

        <section class="lfg-detail-panel lfg-apply-panel lfg-detail-action-wide">
            <h2>{{ ($canManage || $canDelete) ? __('ui.preview_lfg_manage_title') : __('ui.lfg_detail_action_title') }}</h2>

            @if($canManage || $canDelete)
                <p>{{ $canManage ? __('ui.preview_lfg_manage_text') : __('ui.preview_lfg_admin_delete_text') }}</p>
                <div class="lfg-owner-action-row">
                    @if($canManage)
                        <button class="btn-create" type="button" data-hnt-lfg-edit-open>{{ __('ui.lfg_detail_edit') }}</button>
                    @endif

                    @if($canDelete)
                        <form class="lfg-delete-form" method="post" action="{{ route('lfg.destroy', $post) }}" onsubmit="return confirm('{{ __('ui.lfg_delete_confirm') }}')">
                            @csrf
                            @method('DELETE')
                            <button class="btn-following lfg-delete-btn" type="submit">{{ __('ui.lfg_delete') }}</button>
                        </form>
                    @endif

                    <a class="btn-following" href="{{ route('lfg.index') }}">{{ __('ui.lfg_detail_back_to_overview') }}</a>
                </div>
            @elseif($canApply)
                <p>{{ __('ui.lfg_detail_action_text', ['slots' => $slotsOpen]) }}</p>
                <form method="post" action="{{ route('lfg.applications.store', $post) }}">
                    @csrf
                    <label for="lfg-application-message">{{ __('ui.lfg_detail_application_message_label') }}</label>
                    <textarea id="lfg-application-message" name="message" maxlength="800" placeholder="{{ __('ui.lfg_detail_application_placeholder') }}"></textarea>
                    <button class="lfg-apply-btn" type="submit">{{ __('ui.lfg_detail_apply_button') }}</button>
                </form>
            @elseif($viewerApplication)
                <p class="lfg-state-label">{{ __('ui.lfg_detail_your_application') }}</p>
                <div class="lfg-state-box">
                    <strong>{{ $viewerApplication->statusLabel() }}</strong>
                    @if($viewerApplication->message)
                        <span>{{ $viewerApplication->message }}</span>
                    @endif
                </div>
            @else
                <p class="lfg-state-label">{{ __('ui.lfg_detail_application_closed') }}</p>
                <div class="lfg-state-box"><span>{{ __('ui.lfg_detail_application_closed_text') }}</span></div>
            @endif
        </section>
    </div>

    @if($canManage)
        @include('themes.hnt_preview.partials.lfg-edit-modal', ['post' => $post])
    @endif
@endsection

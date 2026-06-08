@extends('layouts.app')

@section('title', __('ui.team_create_title') . ' · hnt.rocks')

@section('content')
@php
    $nameValue = old('name', '');
    $taglineValue = old('tagline', '');
    $descriptionValue = old('description', '');
    $platformValue = old('platform', '');
    $playstyleValue = old('playstyle', '');
    $regionValue = old('region', '');
    $languageValue = old('language', '');
    $visibilityValue = old('visibility', 'public');
    $recruitmentStatusValue = old('recruitment_status', 'open');
@endphp

<div class="section-banner hh-account-hub-banner hh-team-create-hub-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.team_create_banner_text') }}</p>
</div>

@if ($errors->any())
    <div class="hh-alert hh-alert-danger hh-team-create-page-alert">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-teams-hub-grid hh-team-create-hub-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'teams',
        'activeLink' => 'create',
        'teamFormId' => 'team-create-form',
        'teamPrimaryLabel' => __('ui.create_team'),
        'teamSecondaryHref' => route('teams.manage'),
        'teamSecondaryLabel' => __('ui.discard_all'),
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.teams') }}</p>
                <h2 class="section-title">{{ __('ui.team_create_title') }}</h2>
            </div>
        </div>

        <form id="team-create-form" class="form hh-team-hub-form" method="POST" action="{{ route('teams.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="grid-column">
                <div class="widget-box hh-team-hub-widget">
                    <p class="widget-box-title">{{ __('ui.team_info') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small {{ $nameValue ? 'active' : '' }} @error('name') active @enderror">
                                    <label for="team-create-name">{{ __('ui.team_name') }}</label>
                                    <input id="team-create-name" type="text" name="name" value="{{ $nameValue }}" maxlength="80" required>
                                </div>
                                @error('name') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small {{ $taglineValue ? 'active' : '' }} @error('tagline') active @enderror">
                                    <label for="team-create-tagline">{{ __('ui.team_tagline') }}</label>
                                    <input id="team-create-tagline" type="text" name="tagline" value="{{ $taglineValue }}" maxlength="140">
                                </div>
                                @error('tagline') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input textarea {{ $descriptionValue ? 'active' : '' }} @error('description') active @enderror">
                                    <label for="team-create-description">{{ __('ui.team_description') }}</label>
                                    <textarea id="team-create-description" name="description" rows="6" maxlength="2500" placeholder="{{ __('ui.team_description_placeholder') }}">{{ $descriptionValue }}</textarea>
                                </div>
                                @error('description') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-team-hub-widget">
                    <p class="widget-box-title">{{ __('ui.avatar_and_cover') }}</p>

                    <div class="widget-box-content">
                        <div class="grid grid-6-6 centered-on-mobile hh-team-modal-upload-grid">
                            <label class="upload-box hh-team-upload-box" for="team-create-avatar">
                                <i class="upload-box-icon hh-ph-action-icon ph ph-users" aria-hidden="true"></i>
                                <p class="upload-box-title">{{ __('ui.team_avatar') }}</p>
                                <p class="upload-box-text">{{ __('ui.team_avatar_hint') }}</p>
                                <input class="hh-team-file-input" type="file" id="team-create-avatar" name="avatar" accept="image/jpeg,image/png,image/webp">
                                @error('avatar') <span class="hh-form-error">{{ $message }}</span> @enderror
                            </label>

                            <label class="upload-box hh-team-upload-box" for="team-create-cover">
                                <i class="upload-box-icon hh-ph-action-icon ph ph-images" aria-hidden="true"></i>
                                <p class="upload-box-title">{{ __('ui.team_cover') }}</p>
                                <p class="upload-box-text">{{ __('ui.team_cover_hint') }}</p>
                                <input class="hh-team-file-input" type="file" id="team-create-cover" name="cover" accept="image/jpeg,image/png,image/webp">
                                @error('cover') <span class="hh-form-error">{{ $message }}</span> @enderror
                            </label>
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-team-hub-widget">
                    <p class="widget-box-title">{{ __('ui.team_setup') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('visibility') active @enderror">
                                    <label for="team-create-visibility">{{ __('ui.team_visibility') }}</label>
                                    <select id="team-create-visibility" name="visibility" required>
                                        <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.visibility_public') }}</option>
                                        <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.visibility_private') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('visibility') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-select @error('recruitment_status') active @enderror">
                                    <label for="team-create-recruitment-status">{{ __('ui.team_recruiting') }}</label>
                                    <select id="team-create-recruitment-status" name="recruitment_status" required>
                                        <option value="open" @selected($recruitmentStatusValue === 'open')>{{ __('ui.recruiting_open') }}</option>
                                        <option value="closed" @selected($recruitmentStatusValue === 'closed')>{{ __('ui.recruiting_closed') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('recruitment_status') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('platform') active @enderror">
                                    <label for="team-create-platform">{{ __('ui.platform') }}</label>
                                    <select id="team-create-platform" name="platform">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                                            <option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('platform') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-select @error('playstyle') active @enderror">
                                    <label for="team-create-playstyle">{{ __('ui.playstyle') }}</label>
                                    <select id="team-create-playstyle" name="playstyle">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                            <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('playstyle') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('region') active @enderror">
                                    <label for="team-create-region">{{ __('ui.region') }}</label>
                                    <select id="team-create-region" name="region">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                            <option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('region') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-select @error('language') active @enderror">
                                    <label for="team-create-language">{{ __('ui.language') }}</label>
                                    <select id="team-create-language" name="language">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                            <option value="{{ $option }}" @selected($languageValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('language') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

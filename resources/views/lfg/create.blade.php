@extends('layouts.app')

@section('title', __('ui.lfg_create_title') . ' · hnt.rocks')

@section('content')
@php
    $titleValue = old('title', '');
    $bodyValue = old('body', '');
    $platformValue = old('platform', '');
    $playstyleValue = old('playstyle', '');
    $regionValue = old('region', '');
    $languageValue = old('language', '');
    $preferredTimeValue = old('preferred_time', '');
    $experienceValue = old('experience_level', '');
    $slotsTotalValue = (int) old('slots_total', 2);
    $visibilityValue = old('visibility', 'public');
    $expiresAtValue = old('expires_at', '');
    $voiceRequiredValue = (bool) old('voice_required', false);
    $hhLfgOptionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
@endphp

<div class="section-banner hh-account-hub-banner hh-lfg-create-hub-banner">
    <img class="section-banner-icon" src="{{ asset('assets/vikinger/img/banner/accounthub-icon.png') }}" alt="{{ __('ui.account_hub') }}">
    <p class="section-banner-title">{{ __('ui.account_hub') }}</p>
    <p class="section-banner-text">{{ __('ui.lfg_create_banner_text') }}</p>
</div>

@if ($errors->any())
    <div class="hh-alert hh-alert-danger hh-lfg-create-page-alert">
        <strong>{{ __('ui.please_check') }}</strong>
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="grid grid-3-9 medium-space hh-account-hub-grid hh-lfg-create-hub-grid">
    @include('teams.partials.account-sidebar', [
        'activeSection' => 'lfg',
        'activeLink' => 'create',
        'teamFormId' => 'lfg-create-form',
        'teamPrimaryLabel' => __('ui.lfg_publish'),
        'teamSecondaryHref' => route('lfg.index'),
        'teamSecondaryLabel' => __('ui.discard_all'),
    ])

    <div class="account-hub-content">
        <div class="section-header">
            <div class="section-header-info">
                <p class="section-pretitle">{{ __('ui.lfg_index_title') }}</p>
                <h2 class="section-title">{{ __('ui.lfg_create_title') }}</h2>
            </div>
        </div>

        <form id="lfg-create-form" class="form hh-lfg-create-page-form" method="POST" action="{{ route('lfg.store') }}">
            @csrf

            <div class="grid-column">
                <div class="widget-box hh-lfg-create-page-widget">
                    <p class="widget-box-title">{{ __('ui.lfg_create_tab_info') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small {{ $titleValue ? 'active' : '' }} @error('title') active @enderror">
                                    <label for="lfg-create-title">{{ __('ui.lfg_form_title') }}</label>
                                    <input id="lfg-create-title" type="text" name="title" value="{{ $titleValue }}" maxlength="120" required placeholder="{{ __('ui.lfg_form_title_placeholder') }}">
                                </div>
                                @error('title') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input textarea {{ $bodyValue ? 'active' : '' }} @error('body') active @enderror">
                                    <label for="lfg-create-body">{{ __('ui.lfg_form_body') }}</label>
                                    <textarea id="lfg-create-body" name="body" rows="6" maxlength="2800" data-hh-mention-context="lfg" placeholder="{{ __('ui.lfg_form_body_placeholder') }}">{{ $bodyValue }}</textarea>
                                </div>
                                @error('body') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-lfg-create-page-widget">
                    <p class="widget-box-title">{{ __('ui.lfg_create_tab_details') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('platform') active @enderror">
                                    <label for="lfg-create-platform">{{ __('ui.lfg_detail_platform') }}</label>
                                    <select id="lfg-create-platform" name="platform">
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
                                    <label for="lfg-create-playstyle">{{ __('ui.lfg_detail_playstyle') }}</label>
                                    <select id="lfg-create-playstyle" name="playstyle">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                            <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $hhLfgOptionLabel('playstyle', $option) }}</option>
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
                                    <label for="lfg-create-region">{{ __('ui.lfg_detail_region') }}</label>
                                    <select id="lfg-create-region" name="region">
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
                                    <label for="lfg-create-language">{{ __('ui.language') }}</label>
                                    <select id="lfg-create-language" name="language">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                            <option value="{{ $option }}" @selected($languageValue === $option)>{{ $hhLfgOptionLabel('language', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('language') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('preferred_time') active @enderror">
                                    <label for="lfg-create-preferred-time">{{ __('ui.lfg_form_preferred_time') }}</label>
                                    <select id="lfg-create-preferred-time" name="preferred_time">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                                            <option value="{{ $option }}" @selected($preferredTimeValue === $option)>{{ $hhLfgOptionLabel('preferred_time', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('preferred_time') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-select @error('experience_level') active @enderror">
                                    <label for="lfg-create-experience">{{ __('ui.lfg_form_experience_level') }}</label>
                                    <select id="lfg-create-experience" name="experience_level">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                                            <option value="{{ $option }}" @selected($experienceValue === $option)>{{ $hhLfgOptionLabel('experience_level', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('experience_level') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="widget-box hh-lfg-create-page-widget">
                    <p class="widget-box-title">{{ __('ui.lfg_create_tab_settings') }}</p>

                    <div class="widget-box-content">
                        <div class="form-row split">
                            <div class="form-item">
                                <div class="form-select @error('slots_total') active @enderror">
                                    <label for="lfg-create-slots-total">{{ __('ui.lfg_form_slots_total') }}</label>
                                    <select id="lfg-create-slots-total" name="slots_total" required>
                                        @foreach ([2, 3, 4] as $option)
                                            <option value="{{ $option }}" @selected($slotsTotalValue === $option)>{{ __('ui.lfg_form_players_count', ['count' => $option]) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('slots_total') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="form-item">
                                <div class="form-select @error('visibility') active @enderror">
                                    <label for="lfg-create-visibility">{{ __('ui.lfg_visibility') }}</label>
                                    <select id="lfg-create-visibility" name="visibility" required>
                                        <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.lfg_visibility_public') }}</option>
                                        <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.lfg_visibility_private') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                @error('visibility') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <div class="form-input small {{ $expiresAtValue ? 'active' : '' }} @error('expires_at') active @enderror">
                                    <label for="lfg-create-expires-at">{{ __('ui.lfg_form_expires_at') }}</label>
                                    <input id="lfg-create-expires-at" type="datetime-local" name="expires_at" value="{{ $expiresAtValue }}">
                                </div>
                                @error('expires_at') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-item">
                                <label class="switch-option hh-lfg-create-page-switch" for="lfg-create-voice-required">
                                    <span class="hh-notification-switch-copy">
                                        <span class="switch-option-title">{{ __('ui.lfg_form_voice_required') }}</span>
                                        <span class="switch-option-text">{{ __('ui.lfg_form_voice_required_text') }}</span>
                                    </span>

                                    <input
                                        class="hh-notification-switch-input"
                                        id="lfg-create-voice-required"
                                        type="checkbox"
                                        name="voice_required"
                                        value="1"
                                        @checked($voiceRequiredValue)
                                    >
                                    <span class="form-switch @if($voiceRequiredValue) active @endif" aria-hidden="true">
                                        <span class="form-switch-button"></span>
                                    </span>
                                </label>
                                @error('voice_required') <p class="hh-form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

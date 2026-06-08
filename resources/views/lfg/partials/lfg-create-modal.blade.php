@php
    $modalId = $modalId ?? 'hh-lfg-create-modal';
    $formId = $formId ?? $modalId.'-form';
    $formAction = $formAction ?? route('lfg.store');
    $closeHref = $closeHref ?? route('lfg.index');
    $isEmbedded = $isEmbedded ?? false;
    $isModalOpen = $open ?? true;
    $useOldInput = $useOldInput ?? true;
    $fieldValue = fn ($field, $default = null) => $useOldInput ? old($field, $default) : $default;
    $titleValue = $fieldValue('title', '');
    $bodyValue = $fieldValue('body', '');
    $platformValue = $fieldValue('platform', '');
    $playstyleValue = $fieldValue('playstyle', '');
    $regionValue = $fieldValue('region', '');
    $languageValue = $fieldValue('language', '');
    $preferredTimeValue = $fieldValue('preferred_time', '');
    $experienceValue = $fieldValue('experience_level', '');
    $slotsTotalValue = (int) $fieldValue('slots_total', 2);
    $visibilityValue = $fieldValue('visibility', 'public');
    $expiresAtValue = $fieldValue('expires_at', '');
    $voiceRequiredValue = (bool) $fieldValue('voice_required', false);
    $hhLfgOptionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
    $previewTitle = trim((string) $titleValue) !== '' ? $titleValue : __('ui.lfg_create_preview_title');
    $previewText = trim((string) $bodyValue) !== '' ? \Illuminate\Support\Str::limit($bodyValue, 90) : __('ui.lfg_create_preview_text');
    $currentUser = auth()->user();
    $coverUrl = $currentUser?->coverUrl() ?? asset('assets/vikinger/img/cover/01.jpg');
    $mainTabFields = ['title', 'body'];
    $detailsTabFields = ['platform', 'playstyle', 'region', 'language', 'preferred_time', 'experience_level'];
    $settingsTabFields = ['slots_total', 'visibility', 'expires_at', 'voice_required'];
    $hasErrorsFor = function (array $fields) use ($errors, $useOldInput) {
        if (!$useOldInput || !$errors->any()) {
            return false;
        }

        foreach ($fields as $field) {
            if ($errors->has($field)) {
                return true;
            }
        }

        return false;
    };
    $activeLfgCreateTab = 'main';

    if (!$hasErrorsFor($mainTabFields) && $hasErrorsFor($detailsTabFields)) {
        $activeLfgCreateTab = 'details';
    } elseif (!$hasErrorsFor($mainTabFields) && !$hasErrorsFor($detailsTabFields) && $hasErrorsFor($settingsTabFields)) {
        $activeLfgCreateTab = 'settings';
    }
@endphp

<div class="hh-lfg-modal-backdrop {{ $isModalOpen ? 'is-open' : '' }}" aria-hidden="true" data-hh-lfg-create-modal-backdrop data-hh-lfg-create-modal-close @unless($isModalOpen) hidden @endunless></div>

<!-- POPUP BOX -->
<div id="{{ $modalId }}" class="popup-box mid popup-manage-item hh-lfg-create-modal hh-lfg-create-modal-vikinger {{ $isModalOpen ? 'is-open' : '' }}" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title" data-hh-lfg-create-modal data-hh-lfg-create-modal-embedded="{{ $isEmbedded ? 'true' : 'false' }}" data-hh-lfg-create-modal-close-url="{{ $closeHref }}" @unless($isModalOpen) hidden @endunless>
    <!-- POPUP CLOSE BUTTON -->
    <div class="popup-close-button popup-manage-item-trigger" role="button" tabindex="0" aria-label="{{ __('ui.discard_all') }}" data-hh-lfg-create-modal-close>
        <!-- POPUP CLOSE BUTTON ICON -->
        <i class="popup-close-button-icon hh-ph-action-icon ph ph-x" aria-hidden="true"></i>
        <!-- /POPUP CLOSE BUTTON ICON -->
    </div>
    <!-- /POPUP CLOSE BUTTON -->

    <!-- POPUP BOX BODY -->
    <div class="popup-box-body">
        <!-- POPUP BOX SIDEBAR -->
        <div class="popup-box-sidebar">
            <!-- PRODUCT PREVIEW -->
            <div class="product-preview">
                <!-- PRODUCT PREVIEW IMAGE -->
                <figure class="product-preview-image liquid" style="background: url('{{ $coverUrl }}') center center / cover no-repeat;">
                    <img src="{{ $coverUrl }}" alt="{{ $currentUser?->name ?? 'hnt.rocks' }}" style="display: none;">
                </figure>
                <!-- /PRODUCT PREVIEW IMAGE -->

                <!-- PRODUCT PREVIEW INFO -->
                <div class="product-preview-info">
                    <!-- TEXT STICKER -->
                    <p class="text-sticker"><span class="highlighted">+</span> LFG</p>
                    <!-- /TEXT STICKER -->

                    <!-- PRODUCT PREVIEW TITLE -->
                    <p class="product-preview-title"><span data-hh-team-preview-name data-empty-text="{{ __('ui.lfg_create_preview_title') }}">{{ $previewTitle }}</span></p>
                    <!-- /PRODUCT PREVIEW TITLE -->

                    <!-- PRODUCT PREVIEW CATEGORY -->
                    <p class="product-preview-category digital"><span data-hh-team-preview-tagline data-empty-text="{{ __('ui.lfg_create_preview_text') }}">{{ $previewText }}</span></p>
                    <!-- /PRODUCT PREVIEW CATEGORY -->
                </div>
                <!-- /PRODUCT PREVIEW INFO -->
            </div>
            <!-- /PRODUCT PREVIEW -->

            <!-- SIDEBAR MENU ITEM -->
            <div class="sidebar-menu-item">
                <!-- SIDEBAR MENU BODY -->
                <div class="sidebar-menu-body" role="tablist" aria-label="{{ __('ui.lfg_create_title') }}">
                    <!-- SIDEBAR MENU LINK -->
                    <p class="sidebar-menu-link {{ $activeLfgCreateTab === 'main' ? 'active' : '' }}" role="tab" tabindex="{{ $activeLfgCreateTab === 'main' ? '0' : '-1' }}" aria-selected="{{ $activeLfgCreateTab === 'main' ? 'true' : 'false' }}" aria-controls="{{ $modalId }}-panel-main" data-hh-lfg-create-tab="main" data-hh-lfg-create-tab-title="{{ __('ui.lfg_create_tab_info') }}">{{ __('ui.lfg_create_tab_info') }}</p>
                    <!-- /SIDEBAR MENU LINK -->

                    <!-- SIDEBAR MENU LINK -->
                    <p class="sidebar-menu-link {{ $activeLfgCreateTab === 'details' ? 'active' : '' }}" role="tab" tabindex="{{ $activeLfgCreateTab === 'details' ? '0' : '-1' }}" aria-selected="{{ $activeLfgCreateTab === 'details' ? 'true' : 'false' }}" aria-controls="{{ $modalId }}-panel-details" data-hh-lfg-create-tab="details" data-hh-lfg-create-tab-title="{{ __('ui.lfg_create_tab_details') }}">{{ __('ui.lfg_create_tab_details') }}</p>
                    <!-- /SIDEBAR MENU LINK -->

                    <!-- SIDEBAR MENU LINK -->
                    <p class="sidebar-menu-link {{ $activeLfgCreateTab === 'settings' ? 'active' : '' }}" role="tab" tabindex="{{ $activeLfgCreateTab === 'settings' ? '0' : '-1' }}" aria-selected="{{ $activeLfgCreateTab === 'settings' ? 'true' : 'false' }}" aria-controls="{{ $modalId }}-panel-settings" data-hh-lfg-create-tab="settings" data-hh-lfg-create-tab-title="{{ __('ui.lfg_create_tab_settings') }}">{{ __('ui.lfg_create_tab_settings') }}</p>
                    <!-- /SIDEBAR MENU LINK -->
                </div>
                <!-- /SIDEBAR MENU BODY -->
            </div>
            <!-- /SIDEBAR MENU ITEM -->

            <!-- POPUP BOX SIDEBAR FOOTER -->
            <div class="popup-box-sidebar-footer">
                <!-- BUTTON -->
                <button class="button primary full" type="submit" form="{{ $formId }}">{{ __('ui.lfg_publish') }}</button>
                <!-- /BUTTON -->

                <!-- BUTTON -->
                <button class="button white full popup-manage-item-trigger" type="button" data-hh-lfg-create-modal-close>{{ __('ui.discard_all') }}</button>
                <!-- /BUTTON -->
            </div>
            <!-- /POPUP BOX SIDEBAR FOOTER -->
        </div>
        <!-- /POPUP BOX SIDEBAR -->

        <!-- POPUP BOX CONTENT -->
        <div class="popup-box-content limited" data-simplebar>
            <!-- WIDGET BOX -->
            <div class="widget-box">
                <!-- WIDGET BOX TITLE -->
                <p class="widget-box-title" id="{{ $modalId }}-title" data-hh-lfg-create-panel-title>{{ __('ui.lfg_create_tab_'.($activeLfgCreateTab === 'main' ? 'info' : $activeLfgCreateTab)) }}</p>
                <!-- /WIDGET BOX TITLE -->

                <!-- WIDGET BOX CONTENT -->
                <div class="widget-box-content">
                    @if ($useOldInput && $errors->any())
                        <div class="hh-alert hh-alert-danger hh-lfg-create-errors">
                            <strong>{{ __('ui.please_check') }}</strong>
                            <ul class="hh-lfg-create-error-list">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <!-- FORM -->
                    <form id="{{ $formId }}" class="form hh-lfg-create-form" method="post" action="{{ $formAction }}">
                        @csrf
                        <input type="hidden" name="hh_lfg_modal" value="create">

                        <div id="{{ $modalId }}-panel-main" class="hh-lfg-create-panel {{ $activeLfgCreateTab === 'main' ? 'is-active' : '' }}" role="tabpanel" data-hh-lfg-create-panel="main" @unless($activeLfgCreateTab === 'main') hidden @endunless>
                            <!-- FORM ROW -->
                        <div class="form-row">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM INPUT -->
                                <div class="form-input small {{ $titleValue ? 'active' : '' }}">
                                    <label for="{{ $modalId }}-title-input">{{ __('ui.lfg_form_title') }}</label>
                                    <input type="text" id="{{ $modalId }}-title-input" name="title" value="{{ $titleValue }}" maxlength="120" required placeholder="{{ __('ui.lfg_form_title_placeholder') }}" data-hh-team-live="name">
                                </div>
                                <!-- /FORM INPUT -->
                                @if($useOldInput) @error('title')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->

                        <!-- FORM ROW -->
                        <div class="form-row">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM INPUT -->
                                <div class="form-input small mid-textarea {{ $bodyValue ? 'active' : '' }}">
                                    <textarea id="{{ $modalId }}-body" name="body" maxlength="2800" placeholder="{{ __('ui.lfg_form_body_placeholder') }}" data-hh-team-live="tagline" data-hh-mention-context="lfg">{{ $bodyValue }}</textarea>
                                </div>
                                <!-- /FORM INPUT -->
                                @if($useOldInput) @error('body')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->
                        </div>

                        <div id="{{ $modalId }}-panel-details" class="hh-lfg-create-panel {{ $activeLfgCreateTab === 'details' ? 'is-active' : '' }}" role="tabpanel" data-hh-lfg-create-panel="details" @unless($activeLfgCreateTab === 'details') hidden @endunless>
                            <!-- FORM ROW -->
                        <div class="form-row split">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-platform">{{ __('ui.platform') }}</label>
                                    <select id="{{ $modalId }}-platform" name="platform">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                                            <option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('platform')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->

                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-playstyle">{{ __('ui.playstyle') }}</label>
                                    <select id="{{ $modalId }}-playstyle" name="playstyle">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                            <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $hhLfgOptionLabel('playstyle', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('playstyle')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->

                        <!-- FORM ROW -->
                        <div class="form-row split">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-region">{{ __('ui.region') }}</label>
                                    <select id="{{ $modalId }}-region" name="region">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                            <option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('region')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->

                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-language">{{ __('ui.language') }}</label>
                                    <select id="{{ $modalId }}-language" name="language">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                            <option value="{{ $option }}" @selected($languageValue === $option)>{{ $hhLfgOptionLabel('language', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('language')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->

                        <!-- FORM ROW -->
                        <div class="form-row split">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-preferred-time">{{ __('ui.lfg_form_preferred_time') }}</label>
                                    <select id="{{ $modalId }}-preferred-time" name="preferred_time">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                                            <option value="{{ $option }}" @selected($preferredTimeValue === $option)>{{ $hhLfgOptionLabel('preferred_time', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('preferred_time')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->

                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-experience">{{ __('ui.lfg_form_experience_level') }}</label>
                                    <select id="{{ $modalId }}-experience" name="experience_level">
                                        <option value="">{{ __('ui.select_option') }}</option>
                                        @foreach (['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                                            <option value="{{ $option }}" @selected($experienceValue === $option)>{{ $hhLfgOptionLabel('experience_level', $option) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('experience_level')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->
                        </div>

                        <div id="{{ $modalId }}-panel-settings" class="hh-lfg-create-panel {{ $activeLfgCreateTab === 'settings' ? 'is-active' : '' }}" role="tabpanel" data-hh-lfg-create-panel="settings" @unless($activeLfgCreateTab === 'settings') hidden @endunless>
                            <!-- FORM ROW -->
                        <div class="form-row split">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-slots-total">{{ __('ui.lfg_form_slots_total') }}</label>
                                    <select id="{{ $modalId }}-slots-total" name="slots_total" required>
                                        @foreach ([2, 3, 4] as $option)
                                            <option value="{{ $option }}" @selected($slotsTotalValue === $option)>{{ __('ui.lfg_form_players_count', ['count' => $option]) }}</option>
                                        @endforeach
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('slots_total')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->

                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM SELECT -->
                                <div class="form-select">
                                    <label for="{{ $modalId }}-visibility">{{ __('ui.lfg_visibility') }}</label>
                                    <select id="{{ $modalId }}-visibility" name="visibility" required>
                                        <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.lfg_visibility_public') }}</option>
                                        <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.lfg_visibility_private') }}</option>
                                    </select>
                                    <svg class="form-select-icon icon-small-arrow"><use xlink:href="#svg-small-arrow"></use></svg>
                                </div>
                                <!-- /FORM SELECT -->
                                @if($useOldInput) @error('visibility')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->

                        <!-- FORM ROW -->
                        <div class="form-row">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <!-- FORM INPUT -->
                                <div class="form-input small {{ $expiresAtValue ? 'active' : '' }}">
                                    <label for="{{ $modalId }}-expires-at">{{ __('ui.lfg_form_expires_at') }}</label>
                                    <input type="datetime-local" id="{{ $modalId }}-expires-at" name="expires_at" value="{{ $expiresAtValue }}">
                                </div>
                                <!-- /FORM INPUT -->
                                @if($useOldInput) @error('expires_at')<span class="hh-form-error">{{ $message }}</span>@enderror @endif
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->

                        <!-- FORM ROW -->
                        <div class="form-row">
                            <!-- FORM ITEM -->
                            <div class="form-item">
                                <label class="hh-lfg-modal-checkline">
                                    <input type="checkbox" name="voice_required" value="1" @checked($voiceRequiredValue)>
                                    <span>
                                        <strong>{{ __('ui.lfg_form_voice_required') }}</strong>
                                        <em>{{ __('ui.lfg_form_voice_required_text') }}</em>
                                    </span>
                                </label>
                            </div>
                            <!-- /FORM ITEM -->
                        </div>
                        <!-- /FORM ROW -->
                        </div>
                    </form>
                    <!-- /FORM -->
                </div>
                <!-- WIDGET BOX CONTENT -->
            </div>
            <!-- /WIDGET BOX -->
        </div>
        <!-- /POPUP BOX CONTENT -->
    </div>
    <!-- /POPUP BOX BODY -->
</div>
<!-- /POPUP BOX -->

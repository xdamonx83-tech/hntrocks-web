@php
    $viewer = auth()->user();
    $optionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
    $useOldInput = old('_hnt_lfg_edit_modal') === '1';
    $titleValue = $useOldInput ? old('title', $post->title) : $post->title;
    $bodyValue = $useOldInput ? old('body', $post->body) : $post->body;
    $platformValue = $useOldInput ? old('platform', $post->platform) : $post->platform;
    $playstyleValue = $useOldInput ? old('playstyle', $post->playstyle) : $post->playstyle;
    $regionValue = $useOldInput ? old('region', $post->region) : $post->region;
    $languageValue = $useOldInput ? old('language', $post->language) : $post->language;
    $preferredTimeValue = $useOldInput ? old('preferred_time', $post->preferred_time) : $post->preferred_time;
    $experienceValue = $useOldInput ? old('experience_level', $post->experience_level) : $post->experience_level;
    $slotsTotalValue = (int) ($useOldInput ? old('slots_total', $post->slots_total ?? 2) : ($post->slots_total ?? 2));
    $slotsFilledValue = (int) ($useOldInput ? old('slots_filled', $post->slots_filled ?? 1) : ($post->slots_filled ?? 1));
    $statusValue = $useOldInput ? old('status', $post->status ?? 'open') : ($post->status ?? 'open');
    $visibilityValue = $useOldInput ? old('visibility', $post->visibility ?? 'public') : ($post->visibility ?? 'public');
    $expiresAtValue = $useOldInput ? old('expires_at', $post->expires_at ? $post->expires_at->format('Y-m-d\TH:i') : '') : ($post->expires_at ? $post->expires_at->format('Y-m-d\TH:i') : '');
    $voiceRequiredValue = $useOldInput ? old('voice_required') === '1' : (bool) $post->voice_required;
    $shouldOpen = $useOldInput && $errors->any();
    $existingCoverUrl = $post->cover_path ? $post->coverUrl() : null;
    $showExistingCover = $existingCoverUrl && ! ($useOldInput && old('remove_cover') === '1');
@endphp

@if($viewer && $post->canManage($viewer))
<div class="hnt-lfg-create-backdrop {{ $shouldOpen ? 'open' : '' }}" id="hntLfgEditModal" aria-hidden="{{ $shouldOpen ? 'false' : 'true' }}" data-hnt-lfg-wizard-modal data-hnt-lfg-open-selector="[data-hnt-lfg-edit-open]">
    <section class="hnt-lfg-create-modal" role="dialog" aria-modal="true" aria-labelledby="hntLfgEditTitle">
        <form method="post" action="{{ route('lfg.update', $post) }}" enctype="multipart/form-data" data-hnt-lfg-create-form data-hnt-lfg-submitting-label="{{ __('ui.saving') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="_hnt_lfg_edit_modal" value="1">
            <input type="hidden" name="remove_cover" value="0" data-hnt-lfg-cover-remove-input>
            <div class="hnt-lfg-create-handle" aria-hidden="true"></div>

            <header class="hnt-lfg-create-header">
                <button class="hnt-lfg-create-back" type="button" data-hnt-lfg-create-prev aria-label="{{ __('ui.preview_lfg_create_back_aria') }}">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                </button>
                <div>
                    <span class="hnt-lfg-create-kicker">HNT LFG</span>
                    <h2 id="hntLfgEditTitle">{{ __('ui.lfg_edit_title') }}</h2>
                </div>
                <button class="hnt-lfg-create-next-link" type="button" data-hnt-lfg-create-next>{{ __('ui.preview_lfg_create_next') }}</button>
                <button class="icon-btn modal-close hnt-lfg-create-close" type="button" data-hnt-lfg-create-close aria-label="{{ __('ui.preview_lfg_create_close_aria') }}">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="hnt-lfg-create-progress" aria-label="{{ __('ui.preview_lfg_create_steps_aria') }}">
                <span class="is-active" data-hnt-lfg-create-dot="0">{{ __('ui.preview_lfg_create_step_cover') }}</span>
                <span data-hnt-lfg-create-dot="1">{{ __('ui.preview_lfg_create_step_text') }}</span>
                <span data-hnt-lfg-create-dot="2">{{ __('ui.preview_lfg_create_step_details') }}</span>
            </div>

            <div class="hnt-lfg-create-body">
                <section class="hnt-lfg-create-step is-active" data-hnt-lfg-create-step="0">
                    <div class="hnt-lfg-upload-stage {{ $showExistingCover ? 'has-cover' : '' }}" data-hnt-lfg-cover-stage>
                        <input id="hntLfgEditCoverInput" class="hnt-preview-file-input" type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-hnt-lfg-cover-input>
                        <label class="hnt-lfg-cover-picker" for="hntLfgEditCoverInput" @if($showExistingCover) hidden aria-hidden="true" @endif>
                            <span class="hnt-lfg-cover-icon" aria-hidden="true">
                                <i class="ph ph-image-square" aria-hidden="true"></i>
                            </span>
                            <strong>{{ __('ui.preview_lfg_cover_choose') }}</strong>
                            <small>{{ __('ui.preview_lfg_cover_hint') }}</small>
                        </label>
                        <figure class="hnt-lfg-cover-preview" data-hnt-lfg-cover-preview @unless($showExistingCover) hidden @endunless>
                            <img src="{{ $showExistingCover ? $existingCoverUrl : '' }}" alt="{{ __('ui.preview_lfg_cover_preview_alt') }}">
                            <button type="button" data-hnt-lfg-cover-remove aria-label="{{ __('ui.preview_lfg_cover_remove_aria') }}">
                                <i class="ph ph-x" aria-hidden="true"></i>
                            </button>
                        </figure>
                    </div>
                </section>

                <section class="hnt-lfg-create-step" data-hnt-lfg-create-step="1" hidden>
                    <div class="hnt-lfg-create-grid one">
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_title') }}</span>
                            <input type="text" name="title" value="{{ $titleValue }}" maxlength="120" required placeholder="{{ __('ui.preview_lfg_title_placeholder') }}" data-hnt-hashtag-input>
                            <div class="hnt-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_body') }}</span>
                            <textarea name="body" maxlength="2800" rows="8" placeholder="{{ __('ui.preview_lfg_body_placeholder') }}" data-hnt-hashtag-input>{{ $bodyValue }}</textarea>
                            <div class="hnt-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                        </label>
                    </div>
                </section>

                <section class="hnt-lfg-create-step" data-hnt-lfg-create-step="2" hidden>
                    <div class="hnt-lfg-create-grid">
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.platform') }}</span>
                            <select name="platform">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['PC', 'PlayStation', 'Xbox', 'Crossplay'] as $option)
                                    <option value="{{ $option }}" @selected($platformValue === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.playstyle') }}</span>
                            <select name="playstyle">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                    <option value="{{ $option }}" @selected($playstyleValue === $option)>{{ $optionLabel('playstyle', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.region') }}</span>
                            <select name="region">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                    <option value="{{ $option }}" @selected($regionValue === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.language') }}</span>
                            <select name="language">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                    <option value="{{ $option }}" @selected($languageValue === $option)>{{ $optionLabel('language', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.preview_lfg_field_time') }}</span>
                            <select name="preferred_time">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                                    <option value="{{ $option }}" @selected($preferredTimeValue === $option)>{{ $optionLabel('preferred_time', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_experience_level') }}</span>
                            <select name="experience_level">
                                <option value="">{{ __('ui.preview_lfg_option_any') }}</option>
                                @foreach(['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                                    <option value="{{ $option }}" @selected($experienceValue === $option)>{{ $optionLabel('experience_level', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_slots_total') }}</span>
                            <select name="slots_total" required>
                                @foreach([2, 3] as $option)
                                    <option value="{{ $option }}" @selected($slotsTotalValue === $option)>{{ __('ui.lfg_form_players_count', ['count' => $option]) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_slots_filled') }}</span>
                            <select name="slots_filled" required>
                                @foreach([1, 2, 3] as $option)
                                    <option value="{{ $option }}" @selected($slotsFilledValue === $option)>{{ __('ui.lfg_form_players_count', ['count' => $option]) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_detail_status') }}</span>
                            <select name="status" required>
                                <option value="open" @selected($statusValue === 'open')>{{ __('ui.lfg_status_open') }}</option>
                                <option value="full" @selected($statusValue === 'full')>{{ __('ui.lfg_status_full') }}</option>
                                <option value="closed" @selected($statusValue === 'closed')>{{ __('ui.lfg_status_closed') }}</option>
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_visibility') }}</span>
                            <select name="visibility" required>
                                <option value="public" @selected($visibilityValue === 'public')>{{ __('ui.lfg_visibility_public') }}</option>
                                <option value="private" @selected($visibilityValue === 'private')>{{ __('ui.lfg_visibility_private') }}</option>
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.preview_lfg_expires_at') }}</span>
                            <input type="datetime-local" name="expires_at" value="{{ $expiresAtValue }}">
                        </label>
                        <label class="hnt-lfg-check">
                            <input type="checkbox" name="voice_required" value="1" @checked($voiceRequiredValue)>
                            <span>{{ __('ui.lfg_form_voice_required') }}</span>
                        </label>
                    </div>
                </section>
            </div>

            <footer class="hnt-lfg-create-footer">
                <button class="btn-following" type="button" data-hnt-lfg-create-close>{{ __('ui.preview_lfg_cancel') }}</button>
                <button class="btn-following" type="button" data-hnt-lfg-create-prev>{{ __('ui.preview_lfg_back') }}</button>
                <button class="btn-create" type="button" data-hnt-lfg-create-next>{{ __('ui.preview_lfg_create_next') }}</button>
                <button class="btn-create hnt-lfg-create-submit" type="submit" data-hnt-lfg-create-submit hidden aria-hidden="true">{{ __('ui.lfg_save') }}</button>
            </footer>
        </form>
    </section>
</div>
@endif

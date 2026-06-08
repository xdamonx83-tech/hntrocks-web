@php
    $viewer = auth()->user();
    $optionLabel = fn (string $field, string $value): string => \App\Models\LfgPost::localizedOptionLabelFor($field, $value) ?? $value;
@endphp

@if($viewer)
<div class="hnt-lfg-create-backdrop" id="hntLfgCreateModal" aria-hidden="true" data-hnt-lfg-wizard-modal data-hnt-lfg-open-selector="[data-hnt-lfg-create-open]">
    <section class="hnt-lfg-create-modal" role="dialog" aria-modal="true" aria-labelledby="hntLfgCreateTitle">
        <form method="post" action="{{ route('lfg.store') }}" enctype="multipart/form-data" data-hnt-lfg-create-form data-hnt-lfg-submitting-label="{{ __('ui.preview_lfg_creating') }}">
            @csrf
            <div class="hnt-lfg-create-handle" aria-hidden="true"></div>

            <header class="hnt-lfg-create-header">
                <button class="hnt-lfg-create-back" type="button" data-hnt-lfg-create-prev aria-label="{{ __('ui.preview_lfg_create_back_aria') }}">
                    <i class="ph ph-caret-left" aria-hidden="true"></i>
                </button>
                <div>
                    <span class="hnt-lfg-create-kicker">HNT LFG</span>
                    <h2 id="hntLfgCreateTitle">{{ __('ui.lfg_create') }}</h2>
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
                    <div class="hnt-lfg-upload-stage" data-hnt-lfg-cover-stage>
                        <input id="hntLfgCoverInput" class="hnt-preview-file-input" type="file" name="cover" accept="image/jpeg,image/png,image/webp" data-hnt-lfg-cover-input>
                        <label class="hnt-lfg-cover-picker" for="hntLfgCoverInput">
                            <span class="hnt-lfg-cover-icon" aria-hidden="true">
                                <i class="ph ph-image-square" aria-hidden="true"></i>
                            </span>
                            <strong>{{ __('ui.preview_lfg_cover_choose') }}</strong>
                            <small>{{ __('ui.preview_lfg_cover_hint') }}</small>
                        </label>
                        <figure class="hnt-lfg-cover-preview" data-hnt-lfg-cover-preview hidden>
                            <img src="" alt="{{ __('ui.preview_lfg_cover_preview_alt') }}">
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
                            <input type="text" name="title" maxlength="120" required placeholder="{{ __('ui.preview_lfg_title_placeholder') }}" data-hnt-hashtag-input>
                            <div class="hnt-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_body') }}</span>
                            <textarea name="body" maxlength="2800" rows="8" placeholder="{{ __('ui.preview_lfg_body_placeholder') }}" data-hnt-hashtag-input></textarea>
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
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.playstyle') }}</span>
                            <select name="playstyle">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Entspannt', 'Taktisch', 'Aggressiv', 'Competitive', 'Einsteigerfreundlich'] as $option)
                                    <option value="{{ $option }}">{{ $optionLabel('playstyle', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.region') }}</span>
                            <select name="region">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['EU', 'US East', 'US West', 'Asia', 'Oceania'] as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.language') }}</span>
                            <select name="language">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Deutsch', 'Englisch', 'Deutsch / Englisch', 'Mehrsprachig'] as $option)
                                    <option value="{{ $option }}">{{ $optionLabel('language', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.preview_lfg_field_time') }}</span>
                            <select name="preferred_time">
                                <option value="">{{ __('ui.preview_lfg_option_open') }}</option>
                                @foreach(['Morgens', 'Mittags', 'Abends', 'Nachts', 'Wochenende', 'Flexibel'] as $option)
                                    <option value="{{ $option }}">{{ $optionLabel('preferred_time', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_experience_level') }}</span>
                            <select name="experience_level">
                                <option value="">{{ __('ui.preview_lfg_option_any') }}</option>
                                @foreach(['Einsteiger', 'Fortgeschritten', 'Erfahren', 'Competitive', 'Egal'] as $option)
                                    <option value="{{ $option }}">{{ $optionLabel('experience_level', $option) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_form_slots_total') }}</span>
                            <select name="slots_total" required>
                                <option value="2">{{ __('ui.preview_lfg_team_size_duo') }}</option>
                                <option value="3">{{ __('ui.preview_lfg_team_size_trio') }}</option>
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.lfg_visibility') }}</span>
                            <select name="visibility" required>
                                <option value="public">{{ __('ui.lfg_visibility_public') }}</option>
                                <option value="private">{{ __('ui.lfg_visibility_private') }}</option>
                            </select>
                        </label>
                        <label class="hnt-lfg-field">
                            <span>{{ __('ui.preview_lfg_expires_at') }}</span>
                            <input type="datetime-local" name="expires_at">
                        </label>
                        <label class="hnt-lfg-check">
                            <input type="checkbox" name="voice_required" value="1">
                            <span>{{ __('ui.lfg_form_voice_required') }}</span>
                        </label>
                    </div>
                </section>
            </div>

            <footer class="hnt-lfg-create-footer">
                <button class="btn-following" type="button" data-hnt-lfg-create-close>{{ __('ui.preview_lfg_cancel') }}</button>
                <button class="btn-following" type="button" data-hnt-lfg-create-prev>{{ __('ui.preview_lfg_back') }}</button>
                <button class="btn-create" type="button" data-hnt-lfg-create-next>{{ __('ui.preview_lfg_create_next') }}</button>
                <button class="btn-create hnt-lfg-create-submit" type="submit" data-hnt-lfg-create-submit hidden aria-hidden="true">{{ __('ui.preview_lfg_publish') }}</button>
            </footer>
        </form>
    </section>
</div>
@endif

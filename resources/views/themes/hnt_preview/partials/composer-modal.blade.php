@php
    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?: ($currentUser?->username ?: 'Admin');
    $avatarUrl = $currentUser?->avatarUrl();
@endphp

<div class="modal-backdrop" id="composerModal" aria-hidden="true">
    <section class="composer-modal" role="dialog" aria-modal="true" aria-labelledby="composerModalTitle">
        <form method="post" action="{{ route('feed.store') }}" enctype="multipart/form-data" data-hnt-composer-form>
            @csrf
            <input type="hidden" name="visibility" value="public" data-hnt-composer-visibility-input>
            <input type="hidden" name="background_style" value="none">
            <input type="hidden" name="feeling_key" value="none" data-hnt-composer-feeling-input>

            <span class="composer-orb composer-orb-one" aria-hidden="true"></span>
            <span class="composer-orb composer-orb-two" aria-hidden="true"></span>
            <div class="composer-handle" aria-hidden="true"></div>

            <header class="composer-header">
                <div>
                    <span class="composer-kicker">HNT Feed</span>
                    <h2 id="composerModalTitle">{{ __('ui.preview_composer_title') }}</h2>
                    <p>{{ __('ui.preview_composer_intro') }}</p>
                </div>
                <button class="icon-btn modal-close" type="button" aria-label="{{ __('ui.preview_composer_close_aria') }}">
                    <i class="ph ph-x" aria-hidden="true"></i>
                </button>
            </header>

            <div class="composer-body">
                <div class="composer-author-row">
                    <div class="post-author composer-author">
                        <span class="avatar avatar-sm hnt-avatar-shell">
                            @if($avatarUrl)
                                <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
                            @endif
                        </span>
                        <div class="author-info">
                            <strong>{{ $displayName }}</strong>
                            <span data-hnt-composer-visibility-copy>{{ __('ui.preview_composer_visibility_copy_public') }}</span>
                        </div>
                    </div>
                    <div class="composer-privacy-dropdown nav-dropdown" data-hnt-composer-privacy>
                        <button class="composer-audience nav-dropdown-toggle" type="button" aria-expanded="false">
                            <span data-hnt-composer-visibility-label>{{ __('ui.preview_composer_visibility_label_public') }}</span>
                            <i class="ph ph-caret-down nav-dropdown-chev" aria-hidden="true"></i>
                        </button>
                        <div class="nav-submenu composer-privacy-menu" aria-hidden="true">
                            <button class="nav-submenu-close" type="button" aria-label="{{ __('ui.preview_composer_privacy_close_aria') }}"><i class="ph ph-x" aria-hidden="true"></i></button>
                            <button class="nav-subitem composer-privacy-choice is-active" type="button" data-hnt-composer-visibility-choice data-value="public" data-label="{{ __('ui.preview_composer_visibility_label_public') }}" data-copy="{{ __('ui.preview_composer_visibility_copy_public') }}">
                                <strong>{{ __('ui.preview_composer_visibility_label_public') }}</strong>
                                <small>{{ __('ui.preview_composer_visibility_public_hint') }}</small>
                            </button>
                            <button class="nav-subitem composer-privacy-choice" type="button" data-hnt-composer-visibility-choice data-value="followers" data-label="{{ __('ui.preview_composer_visibility_label_friends') }}" data-copy="{{ __('ui.preview_composer_visibility_copy_friends') }}">
                                <strong>{{ __('ui.preview_composer_visibility_label_friends') }}</strong>
                                <small>{{ __('ui.preview_composer_visibility_friends_hint') }}</small>
                            </button>
                            <button class="nav-subitem composer-privacy-choice" type="button" data-hnt-composer-visibility-choice data-value="private" data-label="{{ __('ui.preview_composer_visibility_label_private') }}" data-copy="{{ __('ui.preview_composer_visibility_copy_private') }}">
                                <strong>{{ __('ui.preview_composer_visibility_label_private') }}</strong>
                                <small>{{ __('ui.preview_composer_visibility_private_hint') }}</small>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="composer-input-shell" data-hnt-composer-input-shell>
                    <textarea name="body" placeholder="{{ __('ui.preview_composer_placeholder') }}" data-hnt-hashtag-input data-hnt-composer-textarea>{{ old('body') }}</textarea>
                    <button class="hnt-emoji-input-button hnt-composer-emoji-button" type="button" data-hnt-emoji-trigger data-hnt-emoji-target="composer" aria-label="{{ __('ui.preview_emoji_button') }}" title="{{ __('ui.preview_emoji_button') }}"><i class="ph ph-smiley" aria-hidden="true"></i></button>
                    <div class="composer-media-preview" data-hnt-composer-media-preview hidden></div>
                    <div class="hnt-hashtag-preview" data-hnt-hashtag-preview hidden></div>
                    <div class="composer-preview-strip" aria-hidden="true"><span></span><span></span><span></span></div>
                </div>

                <div class="composer-tools" aria-label="{{ __('ui.preview_composer_tools_aria') }}">
                    <label class="composer-tool" for="hntPreviewComposerMedia"><span>+</span>{{ __('ui.preview_composer_media') }}</label>
                    <input class="hnt-preview-file-input" id="hntPreviewComposerMedia" type="file" name="media[]" multiple accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" data-hnt-composer-file-input>
                    <button class="composer-tool" type="button" data-hnt-composer-toggle-panel="feeling"><span>☺</span>{{ __('ui.preview_composer_feeling') }}</button>
                    <button class="composer-tool" type="button" data-hnt-composer-toggle-panel="poll"><span>?</span>{{ __('ui.preview_composer_poll') }}</button>
                    <label class="composer-tool hnt-preview-ai-toggle"><input type="checkbox" name="ai_generated" value="1"> {{ __('ui.preview_composer_ai_content') }}</label>
                </div>

                <div class="composer-option-panels">
                    <div class="composer-option-panel" data-hnt-composer-panel="feeling" hidden>
                        <div class="composer-panel-title">
                            <strong>{{ __('ui.preview_composer_feeling_title') }}</strong>
                            <span>{{ __('ui.preview_composer_feeling_hint') }}</span>
                        </div>
                        <div class="composer-feeling-grid">
                            <button class="composer-feeling-choice is-active" type="button" data-hnt-composer-feeling-choice data-value="none">{{ __('ui.preview_composer_feeling_none') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="happy">{{ __('ui.preview_composer_feeling_happy') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="excited">{{ __('ui.preview_composer_feeling_excited') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="focused">{{ __('ui.preview_composer_feeling_focused') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="chill">{{ __('ui.preview_composer_feeling_chill') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="tired">{{ __('ui.preview_composer_feeling_tired') }}</button>
                            <button class="composer-feeling-choice" type="button" data-hnt-composer-feeling-choice data-value="salty">{{ __('ui.preview_composer_feeling_salty') }}</button>
                        </div>
                    </div>

                    <div class="composer-option-panel" data-hnt-composer-panel="poll" hidden>
                        <div class="composer-panel-title">
                            <strong>{{ __('ui.preview_composer_poll') }}</strong>
                            <span>{{ __('ui.preview_composer_poll_hint') }}</span>
                        </div>
                        <input class="composer-poll-question" type="text" name="poll_question" maxlength="180" placeholder="{{ __('ui.preview_composer_poll_question_placeholder') }}">
                        <div class="composer-poll-options" data-hnt-composer-poll-options>
                            <input type="text" name="poll_options[]" maxlength="180" placeholder="{{ __('ui.preview_composer_poll_answer_placeholder', ['number' => 1]) }}">
                            <input type="text" name="poll_options[]" maxlength="180" placeholder="{{ __('ui.preview_composer_poll_answer_placeholder', ['number' => 2]) }}">
                        </div>
                        <button class="composer-add-poll-option" type="button" data-hnt-composer-add-poll-option>{{ __('ui.preview_composer_add_poll_answer') }}</button>
                    </div>
                </div>
            </div>

            <footer class="composer-footer">
                <button class="btn-following modal-close-secondary" type="button">{{ __('ui.preview_lfg_cancel') }}</button>
                <button class="btn-create composer-submit" type="submit">{{ __('ui.preview_composer_submit') }}</button>
            </footer>
        </form>
    </section>
</div>

@auth
    <div class="modal hh-report-modal" data-hh-report-modal hidden aria-hidden="true">
        <div class="hh-report-modal-backdrop" data-hh-report-close></div>

        <div class="modal-dialog modal-dialog-centered hh-report-dialog" role="document">
            <div class="modal-content hh-report-content" role="dialog" aria-modal="true" aria-labelledby="hh-report-modal-title">
                <button type="button" class="close hh-report-close" data-hh-report-close aria-label="{{ __('ui.report_close_aria') }}">
                    <span aria-hidden="true">×</span>
                </button>

                <div class="modal-body hh-report-body">
                    <div class="hh-report-modal__hero">
                        <div class="image" aria-hidden="true"></div>
                        <div class="logo-rotate" aria-hidden="true">
                            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" focusable="false">
                                <path d="M12 3L20 7V12C20 16.4183 16.4183 20 12 21C7.58172 20 4 16.4183 4 12V7L12 3Z"></path>
                                <path d="M12 8V12"></path>
                                <path d="M12 16H12.01"></path>
                            </svg>
                        </div>
                    </div>

                    <p class="hh-report-eyebrow">{{ __('ui.report_eyebrow') }}</p>
                    <h2 id="hh-report-modal-title">{{ __('ui.report_title') }}</h2>
                    <p class="hh-report-copy">{{ __('ui.report_intro') }}</p>

                    <div class="hh-report-target">
                        <span class="hh-report-target-label">{{ __('ui.report_target') }}</span>
                        <div class="hh-report-target-title" data-hh-report-title>{{ __('ui.report_target_default') }}</div>
                        <div class="hh-report-target-subtitle" data-hh-report-subtitle>{{ __('ui.report_subtitle_default') }}</div>
                    </div>

                    <form action="{{ route('reports.store') }}" method="post" id="hh-report-form" class="hh-report-form" data-hh-report-form data-success-message="{{ __('ui.report_success') }}" data-error-message="{{ __('ui.report_error') }}">
                        @csrf
                        <input type="hidden" name="type" data-hh-report-type>
                        <input type="hidden" name="id" data-hh-report-id>

                        <div class="hh-report-section-title">{{ __('ui.report_section_reason') }}</div>
                        <div class="hh-report-reasons" data-hh-report-reasons>
                            <label class="hh-report-reason is-checked" for="hh-report-reason-spam">
                                <input type="radio" id="hh-report-reason-spam" name="reason" value="spam" checked>
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_spam_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_spam_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-abuse">
                                <input type="radio" id="hh-report-reason-abuse" name="reason" value="abuse">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_abuse_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_abuse_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-hate">
                                <input type="radio" id="hh-report-reason-hate" name="reason" value="hate">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_hate_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_hate_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-nsfw">
                                <input type="radio" id="hh-report-reason-nsfw" name="reason" value="nsfw">
                                <span>
                                    <span class="hh-report-reason__title">NSFW</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_nsfw_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-fraud">
                                <input type="radio" id="hh-report-reason-fraud" name="reason" value="fraud">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_fraud_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_fraud_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-cheating">
                                <input type="radio" id="hh-report-reason-cheating" name="reason" value="cheating">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_cheating_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_cheating_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-privacy">
                                <input type="radio" id="hh-report-reason-privacy" name="reason" value="privacy">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_privacy_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_privacy_desc') }}</span>
                                </span>
                            </label>

                            <label class="hh-report-reason" for="hh-report-reason-other">
                                <input type="radio" id="hh-report-reason-other" name="reason" value="other">
                                <span>
                                    <span class="hh-report-reason__title">{{ __('ui.report_reason_other_title') }}</span>
                                    <span class="hh-report-reason__desc">{{ __('ui.report_reason_other_desc') }}</span>
                                </span>
                            </label>
                        </div>

                        <fieldset class="email hh-report-details-wrap">
                            <textarea class="style-1 hh-report-textarea" id="hh-report-body" name="body" maxlength="2000" placeholder="{{ __('ui.report_body_placeholder') }}"></textarea>
                        </fieldset>

                        <div class="hh-report-actions">
                            <button type="submit" class="button secondary hh-report-submit" data-hh-report-submit>
                                <span>{{ __('ui.report_submit') }}</span>
                                <i class="hh-ph-action-icon ph ph-arrow-up-right" aria-hidden="true"></i>
                            </button>
                        </div>

                        <div class="hh-report-close-inline">
                            <button type="button" data-hh-report-close>{{ __('ui.cancel') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endauth

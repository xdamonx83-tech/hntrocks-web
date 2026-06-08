<div class="modal-backdrop hnt-report-modal-backdrop" id="hntReportModal" aria-hidden="true" data-hnt-report-modal>
    <div class="composer-modal hnt-report-modal" role="dialog" aria-modal="true" aria-labelledby="hntReportModalTitle">
        <span class="composer-orb composer-orb-one" aria-hidden="true"></span>
        <span class="composer-orb composer-orb-two" aria-hidden="true"></span>
        <div class="composer-handle" aria-hidden="true"></div>

        <header class="composer-header hnt-report-header">
            <div>
                <span class="composer-kicker">Moderation</span>
                <h2 id="hntReportModalTitle">{{ __('ui.preview_report_title') }}</h2>
                <p data-hnt-report-label>{{ __('ui.preview_report_intro') }}</p>
            </div>
            <button class="icon-btn modal-close" type="button" aria-label="{{ __('ui.preview_report_close_aria') }}" data-hnt-report-close>
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </header>

        <form method="post" action="{{ route('reports.store') }}" class="hnt-report-form" data-hnt-report-form>
            @csrf
            <input type="hidden" name="type" data-hnt-report-type>
            <input type="hidden" name="id" data-hnt-report-id>

            <div class="composer-body hnt-report-body">
                <label class="hnt-report-field">
                    <span>{{ __('ui.preview_report_reason') }}</span>
                    <select name="reason" required>
                        <option value="spam">{{ __('ui.report_reason_spam_title') }}</option>
                        <option value="abuse">{{ __('ui.preview_report_reason_abuse') }}</option>
                        <option value="hate">{{ __('ui.report_reason_hate_title') }}</option>
                        <option value="nsfw">{{ __('ui.preview_report_reason_nsfw') }}</option>
                        <option value="fraud">{{ __('ui.report_reason_fraud_title') }}</option>
                        <option value="cheating">{{ __('ui.preview_report_reason_cheating') }}</option>
                        <option value="privacy">{{ __('ui.report_reason_privacy_title') }}</option>
                        <option value="other">{{ __('ui.preview_report_reason_other') }}</option>
                    </select>
                </label>

                <label class="hnt-report-field">
                    <span>{{ __('ui.preview_report_details_optional') }}</span>
                    <textarea name="body" rows="4" maxlength="2000" placeholder="{{ __('ui.preview_report_body_placeholder') }}"></textarea>
                </label>

                <p class="hnt-report-status" data-hnt-report-status hidden></p>
            </div>

            <footer class="composer-footer hnt-report-footer">
                <button type="button" class="btn-following modal-close-secondary" data-hnt-report-close>{{ __('ui.preview_lfg_cancel') }}</button>
                <button type="submit" class="btn-create composer-submit hnt-report-submit" data-hnt-report-submit>{{ __('ui.preview_report_submit_short') }}</button>
            </footer>
        </form>
    </div>
</div>

<div aria-hidden="true" class="modal-backdrop" data-comment-modal="" data-rework-report-url="{{ route('reports.store') }}">
<section aria-labelledby="comment-modal-title" aria-modal="true" class="comment-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="{{ __('ui.preview_post_modal_close_aria') }}" class="modal-close post-composer-close" data-comment-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<div class="comment-modal-layout">
<div class="comment-modal-post">
<div class="modal-post-head">
<a data-rework-modal-author-url href="#"><img alt="" src="{{ asset('assets/vikinger/img/default-avatar.svg') }}"/></a>
<div>
<a data-rework-modal-author-url href="#"><strong data-rework-modal-author></strong></a>
<span data-rework-modal-meta></span>
</div>
</div>
<div class="modal-post-media" data-rework-modal-media hidden></div>
<div class="modal-post-body" data-rework-modal-body hidden></div>
<div class="modal-post-stats" data-rework-modal-stats></div>
</div>
<div class="comment-modal-panel">
<div class="comment-modal-head">
<div>
<span>{{ __('ui.feed_post') }}</span>
<h2 id="comment-modal-title">{{ __('ui.comments') }}</h2>
</div>
<strong data-rework-modal-comment-count>0</strong>
</div>
<div class="comment-thread" data-rework-modal-comments></div>
<form class="modal-composer" data-rework-comment-form method="post">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}"/>
<input name="body" placeholder="{{ __('ui.rework_comment_placeholder') }}" autocomplete="off" type="text" data-rework-comment-input/>
<input accept="image/*,video/*" data-rework-comment-media-input multiple name="media[]" type="file" hidden>
<button class="square-icon" data-rework-comment-media-trigger type="button" aria-label="{{ __('ui.preview_comment_add_image') }}"><i aria-hidden="true" class="ph ph-image ph-icon"></i></button>
<button class="square-icon" data-rework-emoji-toggle type="button" aria-label="{{ __('ui.preview_emoji_button') }}"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></button>
<button class="btn" data-rework-comment-submit type="submit">{{ __('ui.send') }}</button>
<div class="rework-emoji-picker rework-comment-emoji-picker" data-rework-emoji-picker hidden></div>
<div class="rework-comment-media-preview" data-rework-comment-media-preview hidden></div>
<p class="rework-comment-status" data-rework-comment-status hidden></p>
<input name="parent_id" type="hidden" data-rework-comment-parent>
</form>
</div>
</div>
</section>
</div>
<div
    aria-hidden="true"
    class="modal-backdrop rework-report-backdrop"
    data-rework-report-modal=""
    data-label-sending="{{ __('ui.js_i18n_sending') }}"
    data-label-report-failed="{{ __('ui.report_could_not_be_sent') }}"
    data-label-report-success="{{ __('ui.report_success') }}"
    data-label-report-default="{{ __('ui.preview_report_default_label') }}"
    data-label-reported="{{ __('ui.preview_comment_reported_short') }}"
>
<section aria-labelledby="rework-report-title" aria-modal="true" class="post-composer-modal rework-report-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<header class="post-composer-header">
<div class="post-composer-titleblock">
<span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>{{ __('ui.rework_report_kicker') }}</span>
<h2 id="rework-report-title">{{ __('ui.preview_report_title') }}</h2>
<p data-rework-report-label>{{ __('ui.preview_report_intro') }}</p>
</div>
<button aria-label="{{ __('ui.preview_report_close_aria') }}" class="post-composer-close" data-rework-report-close type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<form action="{{ route('reports.store') }}" class="rework-report-form" data-rework-report-form method="post">
@csrf
<input name="type" type="hidden" data-rework-report-type>
<input name="id" type="hidden" data-rework-report-id>
<div class="post-composer-body rework-report-body">
<label class="rework-report-field">
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
<label class="rework-report-field">
<span>{{ __('ui.preview_report_details_optional') }}</span>
<textarea maxlength="2000" name="body" placeholder="{{ __('ui.preview_report_body_placeholder') }}" rows="4"></textarea>
</label>
<p class="rework-report-status" data-rework-report-status hidden></p>
</div>
<footer class="post-composer-footer">
<button class="composer-cancel" data-rework-report-close type="button">{{ __('ui.preview_action_cancel') }}</button>
<button class="composer-submit" data-rework-report-submit type="submit">{{ __('ui.preview_report_submit_short') }}</button>
</footer>
</form>
</section>
</div>
<div
    aria-hidden="true"
    class="modal-backdrop reactions-backdrop"
    data-reactions-modal=""
    data-label-reaction="{{ __('ui.reaction_like') }}"
    data-label-reactions="{{ __('ui.rework_reactions') }}"
    data-label-no-reactions="{{ __('ui.rework_no_reactions') }}"
    data-label-reactions-failed="{{ __('ui.rework_reactions_failed') }}"
>
<section aria-labelledby="reactions-modal-title" aria-modal="true" class="reactions-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<button aria-label="{{ __('ui.preview_likes_close_aria') }}" class="modal-close post-composer-close" data-reactions-modal-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
<header class="reactions-modal-head">
<div>
<span>Feed</span>
<h2 id="reactions-modal-title">{{ __('ui.rework_reactions') }}</h2>
</div>
<strong data-reactions-total>0 {{ __('ui.rework_reactions') }}</strong>
</header>
<div class="reactions-stats" data-reactions-stats></div>
<div class="reactions-list" data-reactions-list>
<div class="comment-empty-state">{{ __('ui.rework_no_reactions') }}</div>
</div>
</section>
</div>
<div aria-hidden="true" class="modal-backdrop post-composer-backdrop" data-post-composer-modal="">
<form action="{{ route('feed.store') }}" data-rework-post-composer-form enctype="multipart/form-data" id="reworkPostComposerForm" method="post" hidden>
@csrf
<input name="background_style" type="hidden" value="none">
<input data-rework-composer-visibility-input name="visibility" type="hidden" value="public">
<input data-rework-composer-ai-input name="ai_generated" type="hidden" value="0">
<input data-rework-composer-feeling-input name="feeling_key" type="hidden" value="none">
<input accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/webm,video/quicktime" data-rework-composer-file-input id="reworkComposerMedia" multiple name="media[]" type="file">
</form>
<section aria-labelledby="post-composer-title" aria-modal="true" class="post-composer-modal" role="dialog">
<div aria-hidden="true" class="post-composer-grip"></div>
<header class="post-composer-header">
<div class="post-composer-titleblock">
<span class="composer-eyebrow"><span aria-hidden="true" class="composer-dot"></span>HNT FEED</span>
<h2 id="post-composer-title">Post erstellen</h2>
<p>Teile etwas mit der HNT-Community.</p>
</div>
<button aria-label="Post erstellen schließen" class="post-composer-close" data-post-composer-close="" type="button"><i aria-hidden="true" class="ph ph-x ph-icon"></i></button>
</header>
<div class="post-composer-body">
<div class="post-composer-author-row">
<div class="post-composer-author">
<img alt="{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}" src="{{ $viewer?->avatarUrl() ?: asset('assets/vikinger/img/default-avatar.svg') }}">
<div>
<strong>{{ $viewer?->name ?: ($viewer?->username ?: 'HNT Hunter') }}</strong>
<span>Community · HNT Feed</span>
</div>
</div>
<a class="audience-pill" data-rework-composer-audience href="#">Community <span><i aria-hidden="true" class="ph ph-caret-down ph-icon"></i></span></a>
<div class="rework-composer-audience-menu" data-rework-composer-audience-menu hidden>
<button data-rework-composer-audience-option="public" type="button"><strong>Community</strong><span>Alle eingeloggten Hunter</span></button>
<button data-rework-composer-audience-option="followers" type="button"><strong>Freunde</strong><span>Nur dein Netzwerk</span></button>
<button data-rework-composer-audience-option="private" type="button"><strong>Privat</strong><span>Nur du</span></button>
</div>
</div>
<div class="post-composer-textbox">
<textarea data-rework-composer-textarea form="reworkPostComposerForm" maxlength="5000" name="body" placeholder="Was gibt es Neues im Bayou?"></textarea>
<div class="composer-textbox-footer">
<div aria-hidden="true" class="composer-ghost-actions">
<span></span><span></span><span></span>
</div>
<a aria-label="Emoji hinzufügen" class="composer-emoji" data-rework-composer-emoji href="#"><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></a>
</div>
</div>
<div class="post-composer-tools">
<a data-rework-composer-media-trigger href="#"><span><i aria-hidden="true" class="ph ph-plus ph-icon"></i></span>Medien</a>
<a data-rework-composer-feeling href="#"><span><i aria-hidden="true" class="ph ph-smiley ph-icon"></i></span>Gefühl</a>
<a data-rework-composer-poll href="#"><span><i aria-hidden="true" class="ph ph-question ph-icon"></i></span>Umfrage</a>
<a data-rework-composer-ai-toggle href="#"><span class="composer-check"><i aria-hidden="true" class="ph ph-square ph-icon"></i></span>KI-Inhalt</a>
</div>
<div class="rework-composer-addons" data-rework-composer-addons>
<div class="rework-composer-media-preview" data-rework-composer-media-preview hidden></div>
<div class="rework-composer-selected-feeling" data-rework-composer-feeling-selected hidden></div>
<div class="rework-composer-feeling-panel" data-rework-composer-feeling-panel hidden>
<button data-rework-composer-feeling-option="happy" type="button">😄 Happy</button>
<button data-rework-composer-feeling-option="excited" type="button">🔥 Hype</button>
<button data-rework-composer-feeling-option="focused" type="button">🎯 Fokus</button>
<button data-rework-composer-feeling-option="chill" type="button">😎 Chill</button>
<button data-rework-composer-feeling-option="tired" type="button">💀 Müde</button>
<button data-rework-composer-feeling-option="salty" type="button">🧂 Salty</button>
</div>
<div class="rework-composer-poll-panel" data-rework-composer-poll-panel hidden>
<label><span>Frage</span><input form="reworkPostComposerForm" maxlength="180" name="poll_question" placeholder="Was möchtest du wissen?" type="text"></label>
<label><span>Antwort 1</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 1" type="text"></label>
<label><span>Antwort 2</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 2" type="text"></label>
<label><span>Antwort 3</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 3 optional" type="text"></label>
<label><span>Antwort 4</span><input form="reworkPostComposerForm" maxlength="180" name="poll_options[]" placeholder="Option 4 optional" type="text"></label>
</div>
</div>
</div>
<footer class="post-composer-footer">
<a class="composer-cancel" data-post-composer-close="" href="#">Abbrechen</a>
<a class="composer-submit" data-rework-composer-submit href="#">Posten</a>
</footer>
</section>
</div>

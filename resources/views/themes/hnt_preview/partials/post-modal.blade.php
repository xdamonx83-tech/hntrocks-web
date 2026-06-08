@php
    $currentUser = auth()->user();
    $displayName = $currentUser?->name ?: ($currentUser?->username ?: 'Admin');
    $avatarUrl = $currentUser?->avatarUrl();
@endphp

<div class="post-modal-backdrop" id="postModal" aria-hidden="true" data-hnt-post-modal>
    <section class="post-detail-modal" role="dialog" aria-modal="true" aria-labelledby="postModalTitle">
        <button class="icon-btn post-modal-close" type="button" aria-label="{{ __('ui.preview_post_modal_close_aria') }}">
            <i class="ph ph-x" aria-hidden="true"></i>
        </button>

        <header class="post-modal-topbar">
            <div class="post-modal-title-block">
                <span class="post-modal-kicker">{{ __('ui.preview_post_modal_kicker') }}</span>
                <h2 id="postModalTitle">{{ __('ui.preview_post_modal_title') }}</h2>
                <p data-hnt-post-modal-subtitle>{{ __('ui.preview_post_modal_loading_subtitle') }}</p>
            </div>
        </header>

        <div class="post-modal-scroll" data-hnt-post-modal-content>
            <div class="hnt-comment-modal-loading">{{ __('ui.preview_post_modal_loading') }}</div>
        </div>

        <form class="post-modal-composer" method="post" action="#" data-hnt-comment-form data-post-id="" hidden>
            @csrf
            <span class="avatar avatar-sm hnt-avatar-shell">
                @if($avatarUrl)
                    <img src="{{ $avatarUrl }}" alt="{{ $displayName }}">
                @endif
            </span>
            <div class="post-modal-input">
                <textarea name="body" maxlength="50000" placeholder="{{ __('ui.preview_post_modal_comment_placeholder', ['name' => $displayName]) }}" required data-hnt-comment-textarea></textarea>
                <div class="post-modal-tools">
                    <button class="hnt-emoji-input-button" type="button" data-hnt-emoji-trigger data-hnt-emoji-target="post-comment" aria-label="{{ __('ui.preview_emoji_button') }}" title="{{ __('ui.preview_emoji_button') }}"><i class="ph ph-smiley" aria-hidden="true"></i></button>
                    <button class="post-modal-send" type="submit" aria-label="{{ __('ui.preview_post_modal_send_aria') }}" data-hnt-comment-submit>
                        <i class="ph ph-paper-plane-tilt" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="hnt-comment-modal-status" data-hnt-comment-status hidden></p>
            </div>
        </form>
    </section>
</div>

<div class="hnt-likes-modal-backdrop" id="hntLikesModal" aria-hidden="true" data-hnt-likes-modal>
    <section class="hnt-likes-modal" role="dialog" aria-modal="true" aria-labelledby="hntLikesModalTitle">
        <div class="hnt-likes-modal-orb hnt-likes-modal-orb-one"></div>
        <div class="hnt-likes-modal-orb hnt-likes-modal-orb-two"></div>
        <header class="hnt-likes-modal-header">
            <div>
                <span class="hnt-likes-kicker">HNT Likes</span>
                <h2 id="hntLikesModalTitle" data-hnt-likes-title>{{ __('ui.preview_likes_title') }}</h2>
                <p data-hnt-likes-subtitle>{{ __('ui.preview_likes_subtitle') }}</p>
            </div>
            <button class="icon-btn modal-close hnt-likes-modal-close" type="button" aria-label="{{ __('ui.preview_likes_close_aria') }}" data-hnt-likes-close>
                <i class="ph ph-x" aria-hidden="true"></i>
            </button>
        </header>
        <div class="hnt-likes-modal-body" data-hnt-likes-body>
            <div class="hnt-likes-loading">{{ __('ui.preview_likes_loading') }}</div>
        </div>
    </section>
</div>

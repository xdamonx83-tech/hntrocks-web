<div class="hnt-lightbox-backdrop" id="hntLightbox" aria-hidden="true" data-hnt-lightbox>
    <section class="hnt-lightbox-modal" role="dialog" aria-modal="true" aria-label="{{ __('ui.preview_lightbox_label') }}">
        <button class="icon-btn hnt-lightbox-close" type="button" aria-label="{{ __('ui.preview_lightbox_close_aria') }}" data-hnt-lightbox-close>
            <i class="ph ph-x" aria-hidden="true"></i>
        </button>
        <button class="hnt-lightbox-nav hnt-lightbox-prev" type="button" aria-label="{{ __('ui.preview_lightbox_prev_aria') }}" data-hnt-lightbox-prev>
            <i class="ph ph-caret-left" aria-hidden="true"></i>
        </button>
        <figure class="hnt-lightbox-stage">
            <img src="" alt="" data-hnt-lightbox-image>
            <figcaption data-hnt-lightbox-caption hidden></figcaption>
        </figure>
        <button class="hnt-lightbox-nav hnt-lightbox-next" type="button" aria-label="{{ __('ui.preview_lightbox_next_aria') }}" data-hnt-lightbox-next>
            <i class="ph ph-caret-right" aria-hidden="true"></i>
        </button>
        <span class="hnt-lightbox-count" data-hnt-lightbox-count hidden>1 / 1</span>
    </section>
</div>

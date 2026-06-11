(() => {
    const studio = document.querySelector('[data-hnt-moment-studio]');
    if (!studio) return;

    const input = studio.querySelector('[data-hnt-studio-video-input]');
    const batchInput = studio.querySelector('[data-hnt-studio-batch-input]');
    const payloadInput = studio.querySelector('[data-hnt-studio-payload]');
    const trimStartInput = studio.querySelector('input[name="trim_start_seconds"]');
    const trimEndInput = studio.querySelector('input[name="trim_end_seconds"]');
    const preview = studio.querySelector('[data-hnt-studio-preview]');
    const previewEmpty = studio.querySelector('[data-hnt-studio-preview-empty]');
    const mediaList = studio.querySelector('[data-hnt-studio-media-list]');
    const emptyMedia = studio.querySelector('[data-hnt-studio-empty-media]');
    const timelineTrack = studio.querySelector('[data-hnt-studio-timeline-track]');
    const timelineEmpty = studio.querySelector('[data-hnt-studio-timeline-empty]');
    const publish = studio.querySelector('[data-hnt-studio-publish]');
    const publishDrawer = studio.querySelector('[data-hnt-studio-publish-drawer]');
    const publishSummary = studio.querySelector('[data-hnt-studio-publish-summary]');
    const publishFinal = studio.querySelector('[data-hnt-studio-publish-final]');
    const publishCloseButtons = studio.querySelectorAll('[data-hnt-studio-publish-close]');
    const uploadShell = studio.querySelector('[data-hnt-studio-upload-shell]');
    const uploadFrame = studio.querySelector('[data-hnt-studio-upload-feed]');
    const uploadPreview = studio.querySelector('[data-hnt-studio-upload-preview]');
    const uploadPreviewFallback = studio.querySelector('[data-hnt-studio-upload-preview-fallback]');
    const uploadTitle = studio.querySelector('[data-hnt-studio-upload-title]');
    const uploadText = studio.querySelector('[data-hnt-studio-upload-text]');
    const uploadProgress = studio.querySelector('[data-hnt-studio-upload-progress]');
    const uploadProgressBar = studio.querySelector('[data-hnt-studio-upload-progress-bar]');
    const uploadBackButton = studio.querySelector('[data-hnt-studio-upload-back]');
    const proDrawer = studio.querySelector('[data-hnt-studio-pro-drawer]');
    const proTitle = studio.querySelector('[data-hnt-studio-pro-title]');
    const proDescription = studio.querySelector('[data-hnt-studio-pro-description]');
    const proCloseButtons = studio.querySelectorAll('[data-hnt-studio-pro-close]');
    const proBuyButton = studio.querySelector('[data-hnt-studio-pro-buy]');
    const proPriceNode = studio.querySelector('[data-hnt-studio-pro-price]');
    const proBalanceNode = studio.querySelector('[data-hnt-studio-pro-balance]');
    const proLockButtons = studio.querySelectorAll('[data-hnt-studio-pro-lock]');
    const fadeOpenButtons = studio.querySelectorAll('[data-hnt-studio-fade-open]');
    const fadeDrawer = studio.querySelector('[data-hnt-studio-fade-drawer]');
    const fadeCloseButtons = studio.querySelectorAll('[data-hnt-studio-fade-close]');
    const fadeActiveNode = studio.querySelector('[data-hnt-studio-fade-active]');
    const fadeInInput = studio.querySelector('[data-hnt-studio-fade-in]');
    const fadeOutInput = studio.querySelector('[data-hnt-studio-fade-out]');
    const filterOpenButtons = studio.querySelectorAll('[data-hnt-studio-filter-open]');
    const filterDrawer = studio.querySelector('[data-hnt-studio-filter-drawer]');
    const filterCloseButtons = studio.querySelectorAll('[data-hnt-studio-filter-close]');
    const filterActiveNode = studio.querySelector('[data-hnt-studio-filter-active]');
    const filterSearchInput = studio.querySelector('[data-hnt-studio-filter-search]');
    const filterChoiceButtons = studio.querySelectorAll('[data-hnt-studio-filter-choice]');
    const filterPreviewVideos = studio.querySelectorAll('[data-hnt-studio-filter-preview]');
    const effectOpenButtons = studio.querySelectorAll('[data-hnt-studio-effect-open]');
    const effectDrawer = studio.querySelector('[data-hnt-studio-effect-drawer]');
    const effectCloseButtons = studio.querySelectorAll('[data-hnt-studio-effect-close]');
    const effectActiveNode = studio.querySelector('[data-hnt-studio-effect-active]');
    const effectChoiceButtons = studio.querySelectorAll('[data-hnt-studio-effect-choice]');
    const effectSearchInput = studio.querySelector('[data-hnt-studio-effect-search]');
    const colorsOpenButtons = studio.querySelectorAll('[data-hnt-studio-colors-open]');
    const colorsDrawer = studio.querySelector('[data-hnt-studio-colors-drawer]');
    const colorsCloseButtons = studio.querySelectorAll('[data-hnt-studio-colors-close]');
    const colorsActiveNode = studio.querySelector('[data-hnt-studio-colors-active]');
    const colorInputs = studio.querySelectorAll('[data-hnt-studio-color-control]');
    const colorsResetButton = studio.querySelector('[data-hnt-studio-colors-reset]');
    const phoneFrame = studio.querySelector('.hnt-studio-phone-frame');
    const status = studio.querySelector('[data-hnt-studio-status]');
    const playButton = studio.querySelector('[data-hnt-studio-play]');
    const currentTimeNode = studio.querySelector('[data-hnt-studio-current-time]');
    const durationNode = studio.querySelector('[data-hnt-studio-duration]');
    const timelineSection = studio.querySelector('.hnt-studio-timeline');
    const timeRuler = studio.querySelector('.hnt-studio-time-ruler');
    const totalNode = studio.querySelector('[data-hnt-studio-total]');
    const toolButtons = studio.querySelectorAll('[data-hnt-studio-tool]');
    const toolPanels = studio.querySelectorAll('[data-hnt-studio-panel]');
    const textInput = studio.querySelector('[data-hnt-studio-text-input]');
    const textAddButton = studio.querySelector('[data-hnt-studio-text-add]');
    const textClearButton = studio.querySelector('[data-hnt-studio-text-clear]');
    const transitionActiveNode = studio.querySelector('[data-hnt-studio-transition-active]');
    const transitionChoiceButtons = studio.querySelectorAll('[data-hnt-studio-transition-choice]');
    const textOverlay = studio.querySelector('[data-hnt-studio-text-overlay]');
    const textTrack = studio.querySelector('[data-hnt-studio-text-track]');
    const textEmpty = studio.querySelector('[data-hnt-studio-text-empty]');
    const maxDuration = Number(studio.dataset.maxDuration || 120);
    const maxMedia = Number(studio.dataset.maxMedia || 5);
    const minClipDuration = 1;
    const mediaItems = [];
    const textLayers = [];
    let activeId = '';
    let timelineDrag = null;
    let trimDrag = null;
    let textTimelineDrag = null;
    let textPreviewDrag = null;
    let activeTextId = '';
    let suppressTimelineClickUntil = 0;
    let suppressTextClickUntil = 0;
    let isProgrammaticSeek = false;
    let publishConfirmed = false;
    let sequencePlayback = false;
    let timelineScrubber = null;
    let transitionPreviewTimer = null;
    let transitionPreviewCleanup = null;
    let scrubberRaf = 0;
    let activeProButton = null;
    let crownsBalance = Number(studio.dataset.crownsBalance || 0);

    const localLabels = {
        moment_studio_ready: studio.dataset.labelReady,
        moment_studio_over_duration: studio.dataset.labelOverDuration,
        moment_studio_loading_video: studio.dataset.labelLoading,
        moment_studio_pick_video_first: studio.dataset.labelPickFirst,
        upload_running: studio.dataset.labelUploading,
        moment_studio_media_limit_reached: studio.dataset.labelMediaLimit,
        moment_studio_multi_publish_pending: studio.dataset.labelMultiPublish,
        moment_studio_active_clip_publish: studio.dataset.labelActivePublish,
        moment_studio_reordered: studio.dataset.labelReordered,
        moment_studio_trimmed: studio.dataset.labelTrimmed,
        moment_studio_render_started: studio.dataset.labelRender,
        moment_studio_text_added: studio.dataset.labelTextAdded,
        moment_studio_text_empty: studio.dataset.labelTextEmpty,
        moment_studio_text_removed: studio.dataset.labelTextRemoved,
        moment_studio_text_positioned: studio.dataset.labelTextPositioned,
        moment_studio_text_timed: studio.dataset.labelTextTimed,
        moment_studio_pro_locked_status: studio.dataset.labelProLocked,
        moment_studio_fade_updated: studio.dataset.labelFadeUpdated,
        moment_studio_fade_pick_first: studio.dataset.labelFadePickFirst,
        moment_studio_filter_updated: studio.dataset.labelFilterUpdated,
        moment_studio_filter_pick_first: studio.dataset.labelFilterPickFirst,
        moment_studio_effect_updated: studio.dataset.labelEffectUpdated,
        moment_studio_effect_pick_first: studio.dataset.labelEffectPickFirst,
        moment_studio_colors_updated: studio.dataset.labelColorsUpdated,
        moment_studio_colors_pick_first: studio.dataset.labelColorsPickFirst,
        moment_studio_transition_updated: studio.dataset.labelTransitionUpdated,
        moment_studio_transition_pick_first: studio.dataset.labelTransitionPickFirst,
        moment_studio_transition_need_next: studio.dataset.labelTransitionNeedNext,
        moment_studio_background_upload_title: studio.dataset.labelBackgroundUploadTitle,
        moment_studio_background_upload_processing: studio.dataset.labelBackgroundUploadProcessing,
        moment_studio_background_upload_error: studio.dataset.labelBackgroundUploadError,
        moment_studio_background_upload_back: studio.dataset.labelBackgroundUploadBack,
        moment_studio_background_upload_open_feed: studio.dataset.labelBackgroundUploadOpenFeed,
        moment_studio_crowns_unlock_button: studio.dataset.labelProBuy,
        moment_studio_unlocked: studio.dataset.labelProOwned,
        moment_studio_crowns_unlock_success_generic: studio.dataset.labelProUnlockSuccess,
    };
    const t = (key, fallback) => localLabels[key] || (window.HNT_PREVIEW_I18N && window.HNT_PREVIEW_I18N[key]) || fallback || key;
    const timelineItems = () => mediaItems.filter((item) => item && item.inTimeline);
    const timelineCount = () => timelineItems().length;
    const isInTimeline = (item) => Boolean(item && item.inTimeline);


    const PLAY_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M8 5v14l11-7L8 5Z"/></svg>';
    const PAUSE_ICON = '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 5h4v14H7V5Zm6 0h4v14h-4V5Z"/></svg>';

    const setPlayButtonState = (playing) => {
        if (!playButton) return;
        playButton.innerHTML = playing ? PAUSE_ICON : PLAY_ICON;
        playButton.setAttribute('aria-label', playing ? 'Pause' : 'Abspielen');
    };

    const ensureTimelineScrubber = () => {
        if (!timelineSection || timelineScrubber) return timelineScrubber;
        timelineScrubber = document.createElement('div');
        timelineScrubber.className = 'hnt-studio-timeline-scrubber';
        timelineScrubber.setAttribute('aria-hidden', 'true');
        timelineSection.appendChild(timelineScrubber);
        return timelineScrubber;
    };

    const FILTERS = {
        none: { css: 'none' },
        orange_teal: { css: 'sepia(.18) saturate(1.28) contrast(1.08) hue-rotate(-10deg)' },
        bold_blue: { css: 'saturate(1.18) contrast(1.16) hue-rotate(195deg)' },
        golden_hour: { css: 'sepia(.24) saturate(1.30) brightness(1.04) contrast(1.08)' },
        vivid_vlogger: { css: 'saturate(1.42) brightness(1.04) contrast(1.08)' },
        purple_undertone: { css: 'saturate(1.20) contrast(1.06) hue-rotate(28deg)' },
        winter_sunset_35: { css: 'sepia(.12) saturate(.96) contrast(1.14) hue-rotate(-18deg)' },
        contrast: { css: 'contrast(1.28) saturate(1.06)' },
        autumn: { css: 'sepia(.28) saturate(1.18) contrast(1.08) hue-rotate(-18deg)' },
        winter: { css: 'saturate(.82) brightness(1.03) contrast(1.10) hue-rotate(190deg)' },
        old_western: { css: 'sepia(.52) saturate(.82) contrast(1.16) brightness(.96)' },
        warm_coast: { css: 'sepia(.12) saturate(1.15) brightness(1.04) contrast(1.05)' },
        cool_coast: { css: 'saturate(1.06) brightness(1.03) contrast(1.05) hue-rotate(190deg)' },
        warm_landscape: { css: 'sepia(.17) saturate(1.24) contrast(1.08)' },
        cool_landscape: { css: 'saturate(1.08) contrast(1.10) hue-rotate(200deg)' },
        golden: { css: 'sepia(.30) saturate(1.35) brightness(1.04) contrast(1.12)' },
        dreamscape: { css: 'saturate(1.18) brightness(1.06) contrast(.96) hue-rotate(18deg)' },
    };

    const EFFECTS = {
        none: { css: 'none' },
        flash: { css: 'contrast(1.10) brightness(1.04)' },
        impulse: { css: 'contrast(1.06) saturate(1.04)' },
        rotate: { css: 'none' },
        vhs: { css: 'contrast(1.10) saturate(.82)' },
        vaporwave: { css: 'hue-rotate(285deg) saturate(1.42) contrast(1.08)' },
        chromatic: { css: 'contrast(1.12) saturate(1.18)' },
        fast_zoom: { css: 'contrast(1.05)' },
        slow_zoom: { css: 'none' },
        random_zoom: { css: 'contrast(1.04)' },
        blur: { css: 'blur(2px) brightness(.98)' },
        filmic: { css: 'contrast(1.14) saturate(.92) sepia(.08)' },
        glitch: { css: 'contrast(1.18) saturate(1.22)' },
        disco: { css: 'hue-rotate(160deg) saturate(1.45)' },
        comic: { css: 'contrast(1.55) saturate(.88)' },
        retro: { css: 'contrast(1.16) saturate(.90)' },
        smoke: { css: 'brightness(1.03) contrast(.94) saturate(.86)' },
        shine: { css: 'brightness(1.06) contrast(1.05) saturate(1.06)' },
        spread: { css: 'blur(1.3px) contrast(.98)' },
    };

    const DEFAULT_COLORS = Object.freeze({ exposure: 0, contrast: 0, saturation: 0, temperature: 0, transparency: 0 });
    const TRANSITIONS = Object.freeze({
        none: { css: 'linear-gradient(90deg, rgba(214,168,79,.25), rgba(214,168,79,.08))' },
        crossfade: { css: 'linear-gradient(90deg, rgba(214,168,79,.85), rgba(242,232,216,.35))' },
        fadeblack: { css: 'linear-gradient(90deg, rgba(214,168,79,.72), rgba(0,0,0,.95), rgba(214,168,79,.28))' },
        fadewhite: { css: 'linear-gradient(90deg, rgba(214,168,79,.72), rgba(255,255,255,.90), rgba(214,168,79,.28))' },
        slideleft: { css: 'linear-gradient(90deg, rgba(214,168,79,.9) 0 42%, rgba(242,232,216,.2) 42% 58%, rgba(214,168,79,.35) 58%)' },
        slideright: { css: 'linear-gradient(90deg, rgba(214,168,79,.35) 0 42%, rgba(242,232,216,.2) 42% 58%, rgba(214,168,79,.9) 58%)' },
        smoothleft: { css: 'radial-gradient(circle at 50% 50%, rgba(214,168,79,.95), rgba(214,168,79,.22) 50%, rgba(0,0,0,.5) 100%)' },
    });
    const isRealTransition = (id) => String(id || 'none') !== 'none' && Object.prototype.hasOwnProperty.call(TRANSITIONS, String(id || ''));

    const normalizeColorAdjustments = (value = {}) => ({
        exposure: clamp(value.exposure, -50, 50),
        contrast: clamp(value.contrast, -50, 50),
        saturation: clamp(value.saturation, -50, 50),
        temperature: clamp(value.temperature, -50, 50),
        transparency: clamp(value.transparency, 0, 70),
    });
    const colorAdjustments = (item) => normalizeColorAdjustments(item && item.colors ? item.colors : DEFAULT_COLORS);
    const hasColorEffect = (item) => {
        const colors = colorAdjustments(item);
        return Object.keys(DEFAULT_COLORS).some((key) => Math.abs(colors[key] - DEFAULT_COLORS[key]) > 0.01);
    };
    const colorCss = (item) => {
        const colors = colorAdjustments(item);
        const filters = [];
        const brightness = 1 + (colors.exposure / 180);
        const contrast = 1 + (colors.contrast / 100);
        const saturation = 1 + (colors.saturation / 100);
        if (Math.abs(colors.exposure) > 0.01) filters.push(`brightness(${brightness.toFixed(3)})`);
        if (Math.abs(colors.contrast) > 0.01) filters.push(`contrast(${Math.max(0.2, contrast).toFixed(3)})`);
        if (Math.abs(colors.saturation) > 0.01) filters.push(`saturate(${Math.max(0, saturation).toFixed(3)})`);
        if (colors.temperature > 0.01) {
            // Match Clipchamp-style warmth more closely: temperature is not just sepia,
            // it also pushes saturation and brightness so the warm end feels visibly golden.
            filters.push(`sepia(${Math.min(0.58, colors.temperature / 92).toFixed(3)})`);
            filters.push(`hue-rotate(${(-colors.temperature * 0.42).toFixed(2)}deg)`);
            filters.push(`saturate(${(1 + colors.temperature / 120).toFixed(3)})`);
            filters.push(`brightness(${(1 + colors.temperature / 420).toFixed(3)})`);
        } else if (colors.temperature < -0.01) {
            const cold = Math.abs(colors.temperature);
            filters.push(`hue-rotate(${(cold * 2.2).toFixed(2)}deg)`);
            filters.push(`saturate(${Math.max(0.35, 1 - cold / 260).toFixed(3)})`);
            filters.push(`brightness(${Math.max(0.82, 1 - cold / 520).toFixed(3)})`);
        }
        return filters.length ? filters.join(' ') : 'none';
    };
    const colorOpacity = (item) => {
        const colors = colorAdjustments(item);
        return clamp(1 - (colors.transparency / 100), 0.3, 1);
    };

    const filterCss = (id) => (FILTERS[String(id || 'none')] || FILTERS.none).css;
    const effectCss = (id) => (EFFECTS[String(id || 'none')] || EFFECTS.none).css;
    const combinedPreviewFilter = (item) => {
        const parts = [filterCss(item && item.filter ? item.filter : 'none'), effectCss(item && item.effect ? item.effect : 'none'), colorCss(item)]
            .filter((value) => value && value !== 'none');
        return parts.length ? parts.join(' ') : 'none';
    };
    const isRealFilter = (id) => String(id || 'none') !== 'none' && Object.prototype.hasOwnProperty.call(FILTERS, String(id || ''));
    const isRealEffect = (id) => String(id || 'none') !== 'none' && Object.prototype.hasOwnProperty.call(EFFECTS, String(id || ''));

    const clamp = (value, min, max) => Math.min(Math.max(Number(value) || 0, min), max);

    const snapTime = (value) => Math.round((Number(value) || 0) * 10) / 10;

    const formatTime = (seconds, precise = false) => {
        const safe = Math.max(0, Number(seconds) || 0);
        const mins = Math.floor(safe / 60);
        const secs = safe - mins * 60;
        return precise ? `${mins}:${secs.toFixed(2).padStart(5, '0')}` : `${mins}:${String(Math.floor(secs)).padStart(2, '0')}`;
    };

    const safeName = (value) => String(value || '').replace(/[<>]/g, '');
    const buttonLabel = (button) => button ? (button.querySelector('strong')?.textContent?.trim() || button.textContent.trim()) : '';

    const normalize = (value) => String(value || '')
        .replace(/^#+/, '')
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9_-]/g, '')
        .replace(/^[-_]+|[-_]+$/g, '')
        .slice(0, 50);

    const extractTags = (value) => {
        let matches = [];
        try {
            matches = String(value || '').match(/(?<![\p{L}\p{N}_])#[\p{L}\p{N}_][\p{L}\p{N}_-]{0,49}/gu) || [];
        } catch (error) {
            matches = String(value || '').match(/(^|\s)#[A-Za-z0-9_][A-Za-z0-9_-]{0,49}/g) || [];
        }
        const seen = new Set();
        return matches.map((item) => normalize(item.trim())).filter((tag) => {
            if (!tag || seen.has(tag)) return false;
            seen.add(tag);
            return true;
        }).slice(0, 8);
    };

    const extractTagsFallback = extractTags;

    const syncHashtags = (inputNode) => {
        const previewNode = inputNode.parentElement ? inputNode.parentElement.querySelector('[data-hnt-hashtag-preview]') : null;
        if (!previewNode) return;
        const tags = extractTagsFallback(inputNode.value);
        previewNode.hidden = tags.length === 0;
        previewNode.innerHTML = tags.map((tag) => `<a href="/hashtags/${encodeURIComponent(tag)}">#${tag}</a>`).join('');
    };

    studio.querySelectorAll('[data-hnt-hashtag-input]').forEach((node) => {
        node.addEventListener('input', () => syncHashtags(node));
        syncHashtags(node);
    });

    const closeProDrawer = () => {
        studio.classList.remove('is-pro-open');
        activeProButton = null;
        if (proDrawer) {
            proDrawer.hidden = true;
            proDrawer.setAttribute('aria-hidden', 'true');
        }
    };

    const closeFadeDrawer = () => {
        studio.classList.remove('is-fade-open');
        if (fadeDrawer) {
            fadeDrawer.hidden = true;
            fadeDrawer.setAttribute('aria-hidden', 'true');
        }
    };

    const closeFilterDrawer = () => {
        studio.classList.remove('is-filter-open');
        if (filterDrawer) {
            filterDrawer.hidden = true;
            filterDrawer.setAttribute('aria-hidden', 'true');
        }
    };

    const closeEffectDrawer = () => {
        studio.classList.remove('is-effect-open');
        if (effectDrawer) {
            effectDrawer.hidden = true;
            effectDrawer.setAttribute('aria-hidden', 'true');
        }
    };

    const closeColorsDrawer = () => {
        studio.classList.remove('is-colors-open');
        if (colorsDrawer) {
            colorsDrawer.hidden = true;
            colorsDrawer.setAttribute('aria-hidden', 'true');
        }
    };

    const hasFadeEffect = (item) => Boolean(item && (item.fadeIn || item.fadeOut));
    const hasFilterEffect = (item) => Boolean(item && isRealFilter(item.filter));
    const hasVisualEffect = (item) => Boolean(item && isRealEffect(item.effect));
    const hasTransitionEffect = (item) => Boolean(item && isRealTransition(item.transitionOut) && canTransitionAfter(item));
    const canTransitionAfter = (item) => {
        const ordered = timelineItems();
        const index = ordered.indexOf(item);
        return index >= 0 && index < ordered.length - 1;
    };

    const getTransitionSubject = () => {
        const ordered = timelineItems();
        if (ordered.length < 2) return null;
        const active = getActive();
        if (!active) return ordered[0];
        if (canTransitionAfter(active)) return active;
        const activeIndex = ordered.indexOf(active);
        if (activeIndex > 0) return ordered[activeIndex - 1];
        return ordered[0] || null;
    };

    const hasStudioEffects = () => timelineItems().some((item) => hasFadeEffect(item) || hasFilterEffect(item) || hasVisualEffect(item) || hasColorEffect(item) || hasTransitionEffect(item));

    const syncTransitionControls = () => {
        const subject = getTransitionSubject();
        const selected = subject && subject.transitionOut ? subject.transitionOut : 'none';
        const usable = Boolean(subject && canTransitionAfter(subject));
        if (transitionActiveNode) {
            const next = subject ? mediaItems[mediaItems.indexOf(subject) + 1] : null;
            const selectedButton = Array.from(transitionChoiceButtons).find((button) => button.dataset.hntStudioTransitionChoice === selected);
            const selectedLabel = selectedButton ? buttonLabel(selectedButton) : '';
            transitionActiveNode.classList.toggle('is-ready', usable);
            transitionActiveNode.textContent = usable
                ? `${safeName(subject.file && subject.file.name ? subject.file.name : 'Clip')} → ${safeName(next && next.file ? next.file.name : 'nächster Clip')} · ${selectedLabel || t('moment_studio_transition_none', 'Kein Übergang')}`
                : t('moment_studio_transition_pick_first', 'Importiere mindestens zwei Clips. Dann wählst du hier den Übergang zwischen ihnen.');
        }
        transitionChoiceButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.hntStudioTransitionChoice === selected);
            button.disabled = !usable;
            const previewNode = button.querySelector('[data-transition]');
            if (previewNode) previewNode.style.background = (TRANSITIONS[button.dataset.hntStudioTransitionChoice] || TRANSITIONS.none).css;
        });
    };

    const updateActiveTransition = (transitionId) => {
        const subject = getTransitionSubject();
        if (!subject || !canTransitionAfter(subject)) {
            syncTransitionControls();
            if (status) status.textContent = t('moment_studio_transition_need_next', 'Für einen Übergang brauchst du mindestens zwei Clips.');
            return;
        }
        subject.transitionOut = Object.prototype.hasOwnProperty.call(TRANSITIONS, String(transitionId || 'none')) ? String(transitionId || 'none') : 'none';
        syncTransitionControls();
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_transition_updated', 'Übergang aktualisiert.');
    };

    const syncFadeControls = () => {
        const active = getActive();
        const hasActive = Boolean(active);
        if (fadeActiveNode) {
            fadeActiveNode.textContent = hasActive
                ? `${safeName(active.file && active.file.name ? active.file.name : 'Clip')} · ${formatTime(effectiveDuration(active))}`
                : t('moment_studio_fade_pick_first', 'Bitte zuerst einen Clip auswählen.');
        }
        if (fadeInInput) {
            fadeInInput.checked = Boolean(active && active.fadeIn);
            fadeInInput.disabled = !hasActive;
        }
        if (fadeOutInput) {
            fadeOutInput.checked = Boolean(active && active.fadeOut);
            fadeOutInput.disabled = !hasActive;
        }
    };

    const openFadeDrawer = () => {
        if (!fadeDrawer) return;
        syncFadeControls();
        fadeDrawer.hidden = false;
        fadeDrawer.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-fade-open');
        if (!getActive() && status) status.textContent = t('moment_studio_fade_pick_first', 'Bitte zuerst einen Clip auswählen.');
    };

    const updateActiveFade = () => {
        const active = getActive();
        if (!active) {
            syncFadeControls();
            if (status) status.textContent = t('moment_studio_fade_pick_first', 'Bitte zuerst einen Clip auswählen.');
            return;
        }
        active.fadeIn = Boolean(fadeInInput && fadeInInput.checked);
        active.fadeOut = Boolean(fadeOutInput && fadeOutInput.checked);
        syncFadeControls();
        applyPreviewFade();
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_fade_updated', 'Ein-/Ausblenden aktualisiert.');
    };

    const syncFilterPreviews = () => {
        const active = getActive();
        filterPreviewVideos.forEach((node) => {
            if (!node) return;
            if (!active || !active.url) {
                node.removeAttribute('src');
                return;
            }
            if (node.getAttribute('src') !== active.url) {
                node.src = active.url;
                node.load();
                node.addEventListener('loadedmetadata', () => {
                    try { node.currentTime = Math.min(Math.max(active.trimStart || 0, 0), Math.max(0, (node.duration || 1) - 0.1)); } catch (error) {}
                }, { once: true });
            }
            const parent = node.closest('[data-filter]');
            node.style.filter = filterCss(parent ? parent.dataset.filter : 'none');
        });
    };

    const syncFilterControls = () => {
        const active = getActive();
        const selected = active && active.filter ? active.filter : 'none';
        if (filterActiveNode) {
            const selectedButton = Array.from(filterChoiceButtons).find((button) => button.dataset.hntStudioFilterChoice === selected);
            const selectedLabel = selectedButton ? selectedButton.querySelector('strong')?.textContent : '';
            filterActiveNode.textContent = active
                ? `${safeName(active.file && active.file.name ? active.file.name : 'Clip')} · ${selectedLabel || t('moment_studio_filter_none', 'Ohne Filter')}`
                : t('moment_studio_filter_pick_first', 'Bitte zuerst einen Clip auswählen.');
        }
        filterChoiceButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.hntStudioFilterChoice === selected);
            button.disabled = !active;
        });
        syncFilterPreviews();
    };

    const applyPreviewFilter = () => {
        applyPreviewEffect();
    };

    const openFilterDrawer = () => {
        if (!filterDrawer) return;
        syncFilterControls();
        syncTransitionControls();
        filterDrawer.hidden = false;
        filterDrawer.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-filter-open');
        if (!getActive() && status) status.textContent = t('moment_studio_filter_pick_first', 'Bitte zuerst einen Clip auswählen.');
        if (filterSearchInput) window.setTimeout(() => filterSearchInput.focus({ preventScroll: true }), 60);
    };

    const updateActiveFilter = (filterId) => {
        const active = getActive();
        if (!active) {
            syncFilterControls();
            if (status) status.textContent = t('moment_studio_filter_pick_first', 'Bitte zuerst einen Clip auswählen.');
            return;
        }
        active.filter = Object.prototype.hasOwnProperty.call(FILTERS, String(filterId || 'none')) ? String(filterId || 'none') : 'none';
        applyPreviewFilter();
        syncFilterControls();
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_filter_updated', 'Filter aktualisiert.');
    };

    const filterChoices = (query) => {
        const needle = String(query || '').trim().toLowerCase();
        filterChoiceButtons.forEach((button) => {
            const name = String(button.dataset.filterName || button.textContent || '').toLowerCase();
            button.hidden = needle !== '' && !name.includes(needle);
        });
    };

    const syncEffectControls = () => {
        const active = getActive();
        const selected = active && active.effect ? active.effect : 'none';
        if (effectActiveNode) {
            const selectedButton = Array.from(effectChoiceButtons).find((button) => button.dataset.hntStudioEffectChoice === selected);
            const selectedLabel = selectedButton ? selectedButton.querySelector('strong')?.textContent?.trim() : '';
            effectActiveNode.textContent = active
                ? `${safeName(active.file && active.file.name ? active.file.name : 'Clip')} · ${selectedLabel || t('moment_studio_effect_none', 'Ohne Effekt')}`
                : t('moment_studio_effect_pick_first', 'Bitte zuerst einen Clip auswählen.');
        }
        effectChoiceButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.hntStudioEffectChoice === selected);
            button.disabled = !active;
        });
    };

    const applyPreviewEffect = () => {
        const active = getActive();
        if (preview) preview.style.filter = combinedPreviewFilter(active);
        if (phoneFrame) phoneFrame.dataset.effect = active && isRealEffect(active.effect) ? active.effect : 'none';
    };

    const openEffectDrawer = () => {
        if (!effectDrawer) return;
        syncEffectControls();
        effectDrawer.hidden = false;
        effectDrawer.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-effect-open');
        if (!getActive() && status) status.textContent = t('moment_studio_effect_pick_first', 'Bitte zuerst einen Clip auswählen.');
        if (effectSearchInput) window.setTimeout(() => effectSearchInput.focus({ preventScroll: true }), 60);
    };

    const updateActiveEffect = (effectId) => {
        const active = getActive();
        if (!active) {
            syncEffectControls();
            if (status) status.textContent = t('moment_studio_effect_pick_first', 'Bitte zuerst einen Clip auswählen.');
            return;
        }
        active.effect = Object.prototype.hasOwnProperty.call(EFFECTS, String(effectId || 'none')) ? String(effectId || 'none') : 'none';
        applyPreviewEffect();
        syncEffectControls();
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_effect_updated', 'Effekt aktualisiert.');
    };

    const effectChoices = (query) => {
        const normalized = String(query || '').trim().toLowerCase();
        effectChoiceButtons.forEach((button) => {
            const name = String(button.dataset.effectName || button.textContent || '').toLowerCase();
            button.hidden = normalized !== '' && !name.includes(normalized);
        });
    };

    const syncColorControls = () => {
        const active = getActive();
        const hasActive = Boolean(active);
        const colors = colorAdjustments(active);
        if (colorsActiveNode) {
            colorsActiveNode.textContent = hasActive
                ? `${safeName(active.file && active.file.name ? active.file.name : 'Clip')} · ${t('moment_studio_colors', 'Farben anpassen')}`
                : t('moment_studio_colors_pick_first', 'Bitte zuerst einen Clip auswählen.');
        }
        colorInputs.forEach((inputNode) => {
            const key = inputNode.dataset.hntStudioColorControl;
            inputNode.disabled = !hasActive;
            inputNode.value = String(colors[key] ?? DEFAULT_COLORS[key] ?? 0);
            const label = inputNode.closest('.hnt-studio-color-control');
            if (label) label.style.setProperty('--range-progress', `${((Number(inputNode.value) - Number(inputNode.min || 0)) / Math.max(1, Number(inputNode.max || 100) - Number(inputNode.min || 0))) * 100}%`);
        });
    };

    const openColorsDrawer = () => {
        if (!colorsDrawer) return;
        syncColorControls();
        syncTransitionControls();
        colorsDrawer.hidden = false;
        colorsDrawer.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-colors-open');
        if (!getActive() && status) status.textContent = t('moment_studio_colors_pick_first', 'Bitte zuerst einen Clip auswählen.');
    };

    const updateActiveColors = (announce = true) => {
        const active = getActive();
        if (!active) {
            syncColorControls();
        syncTransitionControls();
            if (status) status.textContent = t('moment_studio_colors_pick_first', 'Bitte zuerst einen Clip auswählen.');
            return;
        }
        const next = { ...DEFAULT_COLORS };
        colorInputs.forEach((inputNode) => {
            const key = inputNode.dataset.hntStudioColorControl;
            if (Object.prototype.hasOwnProperty.call(next, key)) {
                next[key] = Number(inputNode.value) || 0;
                const label = inputNode.closest('.hnt-studio-color-control');
                if (label) label.style.setProperty('--range-progress', `${((Number(inputNode.value) - Number(inputNode.min || 0)) / Math.max(1, Number(inputNode.max || 100) - Number(inputNode.min || 0))) * 100}%`);
            }
        });
        active.colors = normalizeColorAdjustments(next);
        applyPreviewFade();
        applyPreviewFilter();
        syncColorControls();
        syncTransitionControls();
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncStudioPayload();
        if (announce && status) status.textContent = t('moment_studio_colors_updated', 'Farben aktualisiert.');
    };

    const resetActiveColors = () => {
        const active = getActive();
        if (!active) {
            syncColorControls();
        syncTransitionControls();
            return;
        }
        active.colors = { ...DEFAULT_COLORS };
        syncColorControls();
        syncTransitionControls();
        updateActiveColors(true);
    };

    const openProDrawer = (button) => {
        if (!proDrawer || !button) return;
        activeProButton = button;
        const price = Math.max(0, Number(button.dataset.proPrice || 0));
        const unlockUrl = button.dataset.proUnlockUrl || '';
        if (proTitle) proTitle.textContent = button.dataset.proTitle || t('moment_studio_pro_drawer_title', 'Studio Pro');
        if (proDescription) proDescription.textContent = button.dataset.proDescription || t('moment_studio_pro_default_text', 'Dieses Feature wird mit Crowns freigeschaltet.');
        if (proPriceNode) proPriceNode.textContent = String(price);
        if (proBalanceNode) proBalanceNode.textContent = String(crownsBalance);
        if (proBuyButton) {
            proBuyButton.disabled = !unlockUrl || unlockUrl === '#' || price <= 0;
            proBuyButton.textContent = t('moment_studio_crowns_unlock_button', 'Mit Crowns freischalten');
        }
        proDrawer.hidden = false;
        proDrawer.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-pro-open');
        if (status) status.textContent = t('moment_studio_pro_locked_status', 'Dieses Studio-Feature ist noch gesperrt.');
    };

    const unlockActiveProFeature = () => {
        if (!activeProButton || !proBuyButton) return;
        const unlockUrl = activeProButton.dataset.proUnlockUrl || '';
        if (!unlockUrl || unlockUrl === '#') return;

        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        proBuyButton.disabled = true;
        proBuyButton.textContent = t('upload_running', 'Bitte warten ...');

        fetch(unlockUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
            },
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));
                if (!response.ok || data.ok === false) {
                    throw new Error(data.message || 'Freischaltung fehlgeschlagen.');
                }
                return data;
            })
            .then((data) => {
                if (typeof data.balance !== 'undefined') {
                    crownsBalance = Number(data.balance || 0);
                    studio.dataset.crownsBalance = String(crownsBalance);
                }
                if (status) status.textContent = data.message || t('moment_studio_crowns_unlock_success_generic', 'Studio-Feature freigeschaltet.');
                window.setTimeout(() => window.location.reload(), 450);
            })
            .catch((error) => {
                proBuyButton.disabled = false;
                proBuyButton.textContent = t('moment_studio_crowns_unlock_button', 'Mit Crowns freischalten');
                if (status) status.textContent = error.message || 'Freischaltung fehlgeschlagen.';
            });
    };

    const setStudioTool = (tool) => {
        const key = String(tool || 'media');
        const target = Array.from(toolButtons).find((button) => button.dataset.hntStudioTool === key);
        if (target && target.classList.contains('is-locked')) {
            openProDrawer(target);
            return;
        }
        toolButtons.forEach((button) => {
            const isActive = button.dataset.hntStudioTool === key;
            const isLocked = button.classList.contains('is-locked');
            button.classList.toggle('is-active', isActive && !isLocked);
        });
        toolPanels.forEach((panel) => {
            panel.hidden = panel.dataset.hntStudioPanel !== key;
        });
        if (key === 'transitions') syncTransitionControls();
    };

    const getLayerDuration = (layer) => Math.max(1, (Number(layer && layer.end) || 0) - (Number(layer && layer.start) || 0));

    const getClipStartOffset = (mediaId) => {
        let offset = 0;
        for (const item of timelineItems()) {
            if (item.id === mediaId) return offset;
            offset += effectiveDuration(item);
        }
        return 0;
    };

    const getPreviewGlobalTime = () => {
        const active = getActive();
        if (!active || !preview) return 0;
        ensureTrimBounds(active);
        const local = clamp((preview.currentTime || 0) - (active.trimStart || 0), 0, effectiveDuration(active));
        return clamp(getClipStartOffset(active.id) + local, 0, maxDuration);
    };

    const shouldShowTextLayer = (layer) => {
        if (!layer) return false;
        if (layer.id === activeTextId && (!preview || preview.paused)) return true;
        const now = getPreviewGlobalTime();
        return now >= (Number(layer.start) || 0) && now <= (Number(layer.end) || 0);
    };

    const getPreviewFadeOpacity = () => {
        const active = getActive();
        if (!active || !preview) return 1;
        ensureTrimBounds(active);
        const fadeDuration = Math.min(0.75, Math.max(0.2, effectiveDuration(active) / 3));
        const local = clamp((preview.currentTime || 0) - (active.trimStart || 0), 0, effectiveDuration(active));
        let opacity = 1;
        if (active.fadeIn && fadeDuration > 0) {
            opacity = Math.min(opacity, clamp(local / fadeDuration, 0, 1));
        }
        if (active.fadeOut && fadeDuration > 0) {
            opacity = Math.min(opacity, clamp((effectiveDuration(active) - local) / fadeDuration, 0, 1));
        }
        return Math.max(0.08, opacity);
    };

    const applyPreviewFade = () => {
        if (!preview) return;
        preview.style.opacity = String(getPreviewFadeOpacity() * colorOpacity(getActive()));
    };

    const getTextTimelineBounds = (layer) => {
        const start = clamp(Number(layer && layer.start) || 0, 0, maxDuration);
        const duration = getLayerDuration(layer);
        const left = clamp((start / maxDuration) * 100, 0, 100);
        const width = Math.max(4, Math.min(100 - left, (duration / maxDuration) * 100));
        return { left, width };
    };

    const syncTextLayerNode = (node, layer) => {
        if (!node || !layer) return;
        node.style.setProperty('--text-x', `${clamp(layer.x, 5, 95)}%`);
        node.style.setProperty('--text-y', `${clamp(layer.y, 7, 92)}%`);
        node.classList.toggle('is-active', layer.id === activeTextId);
    };

    const syncTextTimelineNode = (node, layer) => {
        if (!node || !layer) return;
        const bounds = getTextTimelineBounds(layer);
        node.style.setProperty('--text-left', `${bounds.left}%`);
        node.style.setProperty('--text-width', `${bounds.width}%`);
        node.classList.toggle('is-active', layer.id === activeTextId);
        const label = node.querySelector('em');
        if (label) label.textContent = `${formatTime(layer.start)} - ${formatTime(layer.end)}`;
    };

    const syncTextLayerVisuals = (layer) => {
        if (!layer) return;
        const overlayNode = textOverlay ? textOverlay.querySelector(`.hnt-studio-preview-text-layer[data-text-id="${CSS.escape(layer.id)}"]`) : null;
        const timelineNode = textTrack ? textTrack.querySelector(`.hnt-studio-text-clip[data-text-id="${CSS.escape(layer.id)}"]`) : null;
        syncTextLayerNode(overlayNode, layer);
        syncTextTimelineNode(timelineNode, layer);
    };

    const seekPreviewToGlobalTime = (globalTime, options = {}) => {
        const ordered = timelineItems();
        if (!ordered.length || !preview) return;
        const shouldAutoPlay = Boolean(options.autoPlay);
        const target = clamp(globalTime, 0, Math.max(0, totalDuration()));
        let offset = 0;
        let selected = ordered[0];
        let local = 0;

        for (const item of ordered) {
            const duration = effectiveDuration(item);
            if (target <= offset + duration || item === ordered[ordered.length - 1]) {
                selected = item;
                local = clamp(target - offset, 0, duration);
                break;
            }
            offset += duration;
        }

        const desiredTime = (selected.trimStart || 0) + local;
        const apply = () => {
            try {
                isProgrammaticSeek = true;
                preview.currentTime = desiredTime;
                window.setTimeout(() => { isProgrammaticSeek = false; }, 0);
            } catch (error) {
                isProgrammaticSeek = false;
            }
            updatePreviewTimeLabels();
            applyPreviewFade();
            applyPreviewFilter();
            syncEffectControls();
            renderTextLayers();
            updateTimelineScrubber();
            if (shouldAutoPlay) {
                sequencePlayback = true;
                preview.play().catch(() => {});
            }
        };

        const same = activeId === selected.id && preview.src === selected.url;
        selectMedia(selected.id, { keepTime: true, silent: true });
        if (same || preview.readyState >= 1) {
            apply();
        } else {
            preview.addEventListener('loadedmetadata', apply, { once: true });
        }
    };


    const getGlobalTimeFromClientX = (clientX) => {
        if (!timelineTrack) return 0;
        const rect = timelineTrack.getBoundingClientRect();
        const ratio = clamp((clientX - rect.left) / Math.max(1, rect.width), 0, 1);
        return clamp(ratio * maxDuration, 0, Math.max(0, totalDuration() || maxDuration));
    };

    const updateTimelineScrubber = (globalTime = getPreviewGlobalTime()) => {
        const scrubber = ensureTimelineScrubber();
        if (!scrubber || !timelineTrack || !timelineSection || timelineCount() < 1) {
            if (scrubber) scrubber.classList.remove('is-visible');
            return;
        }
        const sectionRect = timelineSection.getBoundingClientRect();
        const trackRect = timelineTrack.getBoundingClientRect();
        const usableDuration = Math.max(maxDuration, 0.001);
        const left = (trackRect.left - sectionRect.left) + trackRect.width * clamp(globalTime / usableDuration, 0, 1);
        scrubber.style.left = `${left}px`;
        scrubber.classList.add('is-visible');
    };

    const ensureTransitionPreviewLayer = () => {
        if (!phoneFrame) return null;
        let layer = phoneFrame.querySelector('[data-hnt-studio-transition-layer]');
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'hnt-studio-transition-layer';
            layer.setAttribute('data-hnt-studio-transition-layer', '');
            layer.setAttribute('aria-hidden', 'true');
            phoneFrame.appendChild(layer);
        }
        return layer;
    };

    const clearTransitionPreview = () => {
        window.clearTimeout(transitionPreviewTimer);
        if (transitionPreviewCleanup) {
            transitionPreviewCleanup();
            transitionPreviewCleanup = null;
        }
        if (phoneFrame) {
            phoneFrame.classList.remove('is-transition-preview');
            delete phoneFrame.dataset.transitionPreview;
        }
        const layer = phoneFrame ? phoneFrame.querySelector('[data-hnt-studio-transition-layer]') : null;
        if (layer) {
            layer.className = 'hnt-studio-transition-layer';
            layer.innerHTML = '';
        }
    };

    const previewTransitionDuration = (transitionId) => {
        const key = String(transitionId || 'none');
        if (key === 'none') return 0;
        if (key === 'fadeblack' || key === 'fadewhite') return 620;
        if (key === 'smoothleft') return 520;
        return 680;
    };

    const playPreviewTransition = (transitionId, nextItem, done) => {
        const key = String(transitionId || 'none');
        const callback = typeof done === 'function' ? done : () => {};
        if (!phoneFrame || key === 'none') {
            callback();
            return;
        }

        clearTransitionPreview();
        const layer = ensureTransitionPreviewLayer();
        phoneFrame.dataset.transitionPreview = key;
        phoneFrame.classList.add('is-transition-preview');

        if (layer) {
            layer.dataset.transition = key;
            layer.className = `hnt-studio-transition-layer is-active is-${key}`;

            if (nextItem && (key === 'crossfade' || key === 'slideleft' || key === 'slideright' || key === 'smoothleft')) {
                const nextVideo = document.createElement('video');
                nextVideo.muted = true;
                nextVideo.playsInline = true;
                nextVideo.preload = 'metadata';
                nextVideo.src = nextItem.url;
                nextVideo.className = 'hnt-studio-transition-next-video';
                nextVideo.style.filter = combinedPreviewFilter(nextItem);
                nextVideo.style.opacity = String(colorOpacity(nextItem));
                layer.appendChild(nextVideo);
                const startNext = () => {
                    try { nextVideo.currentTime = nextItem.trimStart || 0; } catch (error) {}
                    nextVideo.play().catch(() => {});
                };
                if (nextVideo.readyState >= 1) startNext();
                else nextVideo.addEventListener('loadedmetadata', startNext, { once: true });
                transitionPreviewCleanup = () => {
                    nextVideo.pause();
                    nextVideo.removeAttribute('src');
                    nextVideo.load();
                };
            } else {
                const veil = document.createElement('span');
                veil.className = 'hnt-studio-transition-veil';
                layer.appendChild(veil);
            }
        }

        // restart CSS animations after setting the new transition type
        void phoneFrame.offsetWidth;
        const duration = previewTransitionDuration(key);
        transitionPreviewTimer = window.setTimeout(() => {
            clearTransitionPreview();
            callback();
        }, duration);
    };

    const startScrubberLoop = () => {
        if (scrubberRaf) return;
        const tick = () => {
            if (!preview || preview.paused) {
                scrubberRaf = 0;
                updateTimelineScrubber();
                return;
            }
            updateTimelineScrubber(getPreviewGlobalTime());
            scrubberRaf = window.requestAnimationFrame(tick);
        };
        scrubberRaf = window.requestAnimationFrame(tick);
    };

    const stopScrubberLoop = () => {
        if (scrubberRaf) {
            window.cancelAnimationFrame(scrubberRaf);
            scrubberRaf = 0;
        }
        updateTimelineScrubber();
    };

    const playActiveClipFromStart = (item) => {
        if (!item || !preview) return;
        const apply = () => {
            try {
                isProgrammaticSeek = true;
                preview.currentTime = item.trimStart || 0;
                window.setTimeout(() => { isProgrammaticSeek = false; }, 0);
            } catch (error) {
                isProgrammaticSeek = false;
            }
            sequencePlayback = true;
            preview.play().catch(() => {});
            updatePreviewTimeLabels();
            updateTimelineScrubber();
        };
        if (preview.readyState >= 1) {
            apply();
        } else {
            preview.addEventListener('loadedmetadata', apply, { once: true });
        }
    };

    const advanceToNextClip = (currentItem) => {
        const ordered = timelineItems();
        const index = ordered.indexOf(currentItem);
        if (index < 0 || index >= ordered.length - 1) return false;
        const transitionId = currentItem && currentItem.transitionOut ? currentItem.transitionOut : 'none';
        const next = ordered[index + 1];
        const continueWithNext = () => {
            selectMedia(next.id, { keepTime: false, silent: true, fromSequence: true });
            playActiveClipFromStart(next);
        };
        try { preview.pause(); } catch (error) {}
        playPreviewTransition(transitionId, next, continueWithNext);
        return true;
    };

    const startTextPreviewDrag = (event) => {
        const node = event.currentTarget;
        const layer = textLayers.find((entry) => entry.id === node.dataset.textId);
        if (!layer || !textOverlay) return;
        if (event.button !== undefined && event.button !== 0) return;

        event.preventDefault();
        event.stopPropagation();
        activeTextId = layer.id;
        textPreviewDrag = { id: layer.id };
        node.classList.add('is-dragging');
        studio.classList.add('is-text-positioning');

        const applyPosition = (clientX, clientY) => {
            const rect = textOverlay.getBoundingClientRect();
            layer.x = snapTime(clamp(((clientX - rect.left) / Math.max(1, rect.width)) * 100, 5, 95));
            layer.y = snapTime(clamp(((clientY - rect.top) / Math.max(1, rect.height)) * 100, 7, 92));
            syncTextLayerNode(node, layer);
        };

        applyPosition(event.clientX, event.clientY);

        const onMove = (moveEvent) => {
            if (!textPreviewDrag) return;
            moveEvent.preventDefault();
            applyPosition(moveEvent.clientX, moveEvent.clientY);
        };

        const onUp = (upEvent) => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointercancel', onCancel);
            upEvent.preventDefault();
            node.classList.remove('is-dragging');
            studio.classList.remove('is-text-positioning');
            textPreviewDrag = null;
            renderTextLayers();
            syncStudioPayload();
            if (status) status.textContent = t('moment_studio_text_positioned', 'Textposition aktualisiert.');
        };

        const onCancel = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            node.classList.remove('is-dragging');
            studio.classList.remove('is-text-positioning');
            textPreviewDrag = null;
            renderTextLayers();
        };

        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp, { once: true });
        document.addEventListener('pointercancel', onCancel, { once: true });
    };

    const startTextTimelineDrag = (event) => {
        const node = event.currentTarget;
        const layer = textLayers.find((entry) => entry.id === node.dataset.textId);
        if (!layer || !textTrack) return;
        if (event.button !== undefined && event.button !== 0) return;

        event.preventDefault();
        event.stopPropagation();
        activeTextId = layer.id;
        suppressTextClickUntil = Date.now() + 600;

        const rect = textTrack.getBoundingClientRect();
        const pointerTime = clamp(((event.clientX - rect.left) / Math.max(1, rect.width)) * maxDuration, 0, maxDuration);
        const duration = Math.min(getLayerDuration(layer), maxDuration);
        textTimelineDrag = {
            id: layer.id,
            duration,
            grabOffset: clamp(pointerTime - layer.start, 0, duration),
        };
        node.classList.add('is-dragging');
        studio.classList.add('is-text-timing');

        const applyTime = (clientX) => {
            const currentPointerTime = clamp(((clientX - rect.left) / Math.max(1, rect.width)) * maxDuration, 0, maxDuration);
            const start = snapTime(clamp(currentPointerTime - textTimelineDrag.grabOffset, 0, Math.max(0, maxDuration - duration)));
            layer.start = start;
            layer.end = snapTime(clamp(start + duration, start + 1, maxDuration));
            syncTextTimelineNode(node, layer);
            renderTextLayers();
        };

        const onMove = (moveEvent) => {
            if (!textTimelineDrag) return;
            moveEvent.preventDefault();
            applyTime(moveEvent.clientX);
        };

        const onUp = (upEvent) => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointercancel', onCancel);
            upEvent.preventDefault();
            node.classList.remove('is-dragging');
            studio.classList.remove('is-text-timing');
            textTimelineDrag = null;
            renderTextLayers();
            syncStudioPayload();
            seekPreviewToGlobalTime(layer.start);
            if (status) status.textContent = t('moment_studio_text_timed', 'Textzeit aktualisiert.');
        };

        const onCancel = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            node.classList.remove('is-dragging');
            studio.classList.remove('is-text-timing');
            textTimelineDrag = null;
            renderTextLayers();
        };

        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp, { once: true });
        document.addEventListener('pointercancel', onCancel, { once: true });
    };

    const renderTextLayers = () => {
        if (textOverlay) {
            textOverlay.innerHTML = '';
            const visibleLayers = textLayers.filter(shouldShowTextLayer);
            textOverlay.hidden = visibleLayers.length === 0;
            visibleLayers.forEach((layer) => {
                const node = document.createElement('button');
                node.className = 'hnt-studio-preview-text-layer';
                node.type = 'button';
                node.dataset.textId = layer.id;
                node.textContent = layer.text;
                syncTextLayerNode(node, layer);
                node.addEventListener('pointerdown', startTextPreviewDrag);
                node.addEventListener('click', (event) => {
                    event.preventDefault();
                    activeTextId = layer.id;
                    if (textInput) textInput.value = layer.text;
                    setStudioTool('text');
                    renderTextLayers();
                });
                textOverlay.appendChild(node);
            });
        }

        if (textTrack && textEmpty) {
            textTrack.querySelectorAll('.hnt-studio-text-clip').forEach((node) => node.remove());
            textEmpty.hidden = textLayers.length > 0;
            textLayers.forEach((layer) => {
                const clip = document.createElement('button');
                clip.className = 'hnt-studio-text-clip';
                clip.type = 'button';
                clip.dataset.textId = layer.id;
                clip.innerHTML = `<strong>${safeName(layer.text)}</strong><em>${formatTime(layer.start)} - ${formatTime(layer.end)}</em>`;
                syncTextTimelineNode(clip, layer);
                clip.addEventListener('pointerdown', startTextTimelineDrag);
                clip.addEventListener('click', (event) => {
                    if (Date.now() < suppressTextClickUntil) {
                        event.preventDefault();
                        return;
                    }
                    activeTextId = layer.id;
                    if (textInput) textInput.value = layer.text;
                    setStudioTool('text');
                    seekPreviewToGlobalTime(layer.start);
                    renderTextLayers();
                });
                textTrack.appendChild(clip);
            });
        }

        if (textClearButton) textClearButton.disabled = textLayers.length === 0;
    };

    const addTextLayer = () => {
        const value = textInput ? String(textInput.value || '').trim().replace(/\s+/g, ' ') : '';
        if (!value) {
            if (status) status.textContent = t('moment_studio_text_empty', 'Bitte zuerst Text eingeben.');
            return;
        }
        const projectDuration = Math.max(1, Math.min(maxDuration, totalDuration() || maxDuration));
        const start = snapTime(clamp(getPreviewGlobalTime(), 0, Math.max(0, projectDuration - 1)));
        const layerDuration = Math.min(5, Math.max(1, projectDuration - start));
        const layer = {
            id: `text-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            text: value.slice(0, 90),
            start,
            end: snapTime(clamp(start + layerDuration, start + 1, projectDuration)),
            x: 50,
            y: 78,
        };
        textLayers.push(layer);
        activeTextId = layer.id;
        if (textInput) textInput.value = '';
        renderTextLayers();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_text_added', 'Textlayer hinzugefügt.');
    };

    const clearTextLayers = () => {
        if (!textLayers.length) return;
        textLayers.splice(0, textLayers.length);
        activeTextId = '';
        renderTextLayers();
        syncStudioPayload();
        if (status) status.textContent = t('moment_studio_text_removed', 'Textlayer entfernt.');
    };

    const getActive = () => mediaItems.find((item) => item.id === activeId) || null;

    const getClipMinDuration = (item) => {
        const duration = Math.max(0, Number(item && item.duration) || 0);
        if (!duration) return minClipDuration;
        return Math.min(minClipDuration, duration);
    };

    const ensureTrimBounds = (item) => {
        if (!item) return;
        const duration = Math.max(0, Number(item.duration) || 0);
        if (!duration) {
            item.trimStart = 0;
            item.trimEnd = 0;
            return;
        }
        const minDuration = getClipMinDuration(item);
        if (!Number.isFinite(Number(item.trimStart))) item.trimStart = 0;
        if (!Number.isFinite(Number(item.trimEnd)) || Number(item.trimEnd) <= 0 || Number(item.trimEnd) > duration) item.trimEnd = duration;
        item.trimStart = clamp(item.trimStart, 0, Math.max(0, duration - minDuration));
        item.trimEnd = clamp(item.trimEnd, item.trimStart + minDuration, duration);
        item.trimStart = snapTime(item.trimStart);
        item.trimEnd = snapTime(item.trimEnd);
    };

    const effectiveDuration = (item) => {
        if (!item) return 0;
        ensureTrimBounds(item);
        return Math.max(0, (Number(item.trimEnd) || 0) - (Number(item.trimStart) || 0));
    };

    const isTrimmed = (item) => {
        if (!item || !item.duration) return false;
        ensureTrimBounds(item);
        return item.trimStart > 0.05 || item.trimEnd < item.duration - 0.05;
    };

    const totalDuration = () => timelineItems().reduce((sum, item) => sum + effectiveDuration(item), 0);

    const canPublishActiveClip = () => timelineCount() > 0 && totalDuration() <= maxDuration + 0.05;

    const updatePublishSummary = () => {
        if (!publishSummary) return;
        const finalLength = totalDuration();
        publishSummary.textContent = `${formatTime(finalLength, true)} / ${formatTime(maxDuration, true)}`;
    };

    const closePublishDrawer = () => {
        publishConfirmed = false;
        studio.classList.remove('is-publish-open');
        if (publishDrawer) {
            publishDrawer.hidden = true;
            publishDrawer.setAttribute('aria-hidden', 'true');
        }
        publishCloseButtons.forEach((node) => { node.hidden = true; });
    };

    const openPublishDrawer = () => {
        const active = getActive();
        const ordered = timelineItems();
        const publishItem = ordered.includes(active) ? active : ordered[0];
        if (!publishItem || !input) {
            studio.classList.add('is-missing-media');
            if (status) status.textContent = t('moment_studio_pick_video_first', 'Bitte zuerst ein Video auswählen.');
            return false;
        }
        if (totalDuration() > maxDuration + 0.05) {
            studio.classList.add('is-over-duration');
            if (status) status.textContent = t('moment_studio_over_duration', 'Endvideo ist länger als 120 Sekunden.');
            return false;
        }
        updatePublishSummary();
        studio.classList.add('is-publish-open');
        if (publishDrawer) {
            publishDrawer.hidden = false;
            publishDrawer.setAttribute('aria-hidden', 'false');
            const firstField = publishDrawer.querySelector('input[name="caption"], textarea[name="description"]');
            if (firstField) window.setTimeout(() => firstField.focus({ preventScroll: true }), 40);
        }
        publishCloseButtons.forEach((node) => { node.hidden = false; });
        if (status) status.textContent = t('moment_studio_publish_opened', 'Beschreibung prüfen und veröffentlichen.');
        return true;
    };

    const setInputFile = (file) => {
        if (!input || !file) return false;
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            return true;
        } catch (error) {
            return false;
        }
    };

    const setBatchFiles = () => {
        if (!batchInput) return false;
        try {
            const transfer = new DataTransfer();
            const ordered = timelineItems();
            ordered.forEach((item) => transfer.items.add(item.file));
            batchInput.files = transfer.files;
            return ordered.length > 0 && batchInput.files.length === ordered.length;
        } catch (error) {
            return false;
        }
    };

    const buildStudioPayload = () => ({
        version: 1,
        max_duration: maxDuration,
        clips: timelineItems().map((item, index) => {
            ensureTrimBounds(item);
            return {
                file_index: index,
                start: Number((item.trimStart || 0).toFixed(3)),
                end: Number((item.trimEnd || item.duration || 0).toFixed(3)),
                duration: Number(effectiveDuration(item).toFixed(3)),
                fade_in: Boolean(item.fadeIn),
                fade_out: Boolean(item.fadeOut),
                filter: item.filter || 'none',
                effect: item.effect || 'none',
                colors: colorAdjustments(item),
                transition_out: item.transitionOut || 'none',
            };
        }),
        text_layers: textLayers.map((layer) => ({
            text: layer.text,
            start: Number((layer.start || 0).toFixed(3)),
            end: Number((layer.end || 0).toFixed(3)),
            x: Number((Number(layer.x) || 50).toFixed(2)),
            y: Number((Number(layer.y) || 78).toFixed(2)),
        })),
    });

    const syncStudioPayload = () => {
        if (!payloadInput) return;
        if (timelineCount() < 1 || (timelineCount() < 2 && textLayers.length < 1 && !hasStudioEffects())) {
            payloadInput.value = '';
            return;
        }
        payloadInput.value = JSON.stringify(buildStudioPayload());
    };

    const setActiveTrimInputs = () => {
        const active = getActive();
        if (!active || !active.duration) {
            if (trimStartInput) trimStartInput.value = '';
            if (trimEndInput) trimEndInput.value = '';
            return;
        }
        ensureTrimBounds(active);
        const start = Math.max(0, Math.floor(active.trimStart || 0));
        const end = Math.max(start + 1, Math.ceil(active.trimEnd || active.duration || start + 1));
        if (trimStartInput) trimStartInput.value = String(start);
        if (trimEndInput) trimEndInput.value = String(end);
    };

    const updatePreviewTimeLabels = () => {
        const active = getActive();
        if (!active) {
            if (currentTimeNode) currentTimeNode.textContent = formatTime(0, true);
            if (durationNode) durationNode.textContent = formatTime(0, true);
            return;
        }
        ensureTrimBounds(active);
        const clipLength = effectiveDuration(active);
        const current = preview ? clamp((preview.currentTime || 0) - (active.trimStart || 0), 0, clipLength) : 0;
        if (timelineCount() > 1) {
            if (currentTimeNode) currentTimeNode.textContent = formatTime(getPreviewGlobalTime(), true);
            if (durationNode) durationNode.textContent = formatTime(totalDuration(), true);
        } else {
            if (currentTimeNode) currentTimeNode.textContent = formatTime(current, true);
            if (durationNode) durationNode.textContent = formatTime(clipLength, true);
        }
        updateTimelineScrubber();
    };

    const seekPreviewToTrimStart = (item) => {
        if (!preview || !item) return;
        ensureTrimBounds(item);
        const apply = () => {
            try {
                isProgrammaticSeek = true;
                preview.currentTime = item.trimStart || 0;
                window.setTimeout(() => { isProgrammaticSeek = false; }, 0);
            } catch (error) {
                isProgrammaticSeek = false;
            }
            updatePreviewTimeLabels();
        };
        if (preview.readyState >= 1) {
            apply();
        } else {
            preview.addEventListener('loadedmetadata', apply, { once: true });
        }
    };

    const updateStatus = () => {
        const active = getActive();
        const total = totalDuration();
        const overDuration = total > maxDuration + 0.05;
        const hasMedia = mediaItems.length > 0;
        const hasTimeline = timelineCount() > 0;
        studio.classList.toggle('is-over-duration', overDuration);
        studio.classList.toggle('has-multiple-media', timelineCount() > 1);
        studio.classList.toggle('has-timeline-media', hasTimeline);
        studio.classList.toggle('has-studio-effects', hasStudioEffects());
        if (totalNode) totalNode.textContent = formatTime(Math.min(total, maxDuration));
        setActiveTrimInputs();
        updatePreviewTimeLabels();
        applyPreviewFilter();
        syncFilterControls();

        const canPublish = canPublishActiveClip();
        if (publish) publish.disabled = !canPublish;
        syncStudioPayload();
        updatePublishSummary();
        renderTextLayers();
        if (!canPublish && studio.classList.contains('is-publish-open')) {
            closePublishDrawer();
        }

        if (!status) return;
        if (!hasMedia) {
            status.textContent = t('moment_studio_pick_video_first', 'Bitte zuerst ein Video auswählen.');
        } else if (!hasTimeline) {
            status.textContent = t('moment_studio_drag_to_timeline', 'Ziehe Clips aus Meine Medien in die Timeline.');
        } else if (overDuration) {
            status.textContent = t('moment_studio_over_duration', 'Endvideo ist länger als 120 Sekunden.');
        } else if (timelineCount() > 1) {
            status.textContent = t('moment_studio_render_started', 'Mehrfachclip bereit; Veröffentlichung rendert ein finales Video.');
        } else {
            status.textContent = t('moment_studio_ready', 'Bereit zum Veröffentlichen');
        }
    };

    const renderMediaList = () => {
        if (!mediaList) return;
        mediaList.querySelectorAll('.hnt-studio-media-item').forEach((item) => item.remove());
        if (emptyMedia) emptyMedia.hidden = mediaItems.length > 0;

        mediaItems.forEach((item, index) => {
            ensureTrimBounds(item);
            const button = document.createElement('button');
            button.className = `hnt-studio-media-item${item.id === activeId ? ' is-active' : ''}${isInTimeline(item) ? ' is-in-timeline' : ''}${isTrimmed(item) ? ' is-trimmed' : ''}${hasFadeEffect(item) ? ' is-faded' : ''}${hasFilterEffect(item) ? ' is-filtered' : ''}${hasVisualEffect(item) ? ' is-effected' : ''}${hasColorEffect(item) ? ' is-colored' : ''}${hasTransitionEffect(item) ? ' is-transitioned' : ''}`;
            button.type = 'button';
            button.dataset.mediaId = item.id;
            button.draggable = true;
            const clipDuration = effectiveDuration(item);
            const durationLabel = item.duration ? (isTrimmed(item) ? `${formatTime(clipDuration)} / ${formatTime(item.duration)}` : formatTime(item.duration)) : '…';
            button.innerHTML = `
                <span class="hnt-studio-media-thumb">
                    <video muted playsinline preload="metadata" src="${item.url}"></video>
                </span>
                <strong>${safeName(item.file.name)}</strong>
                <em>${durationLabel}</em>
                <span class="hnt-studio-media-index">${index + 1}/${maxMedia}</span>
                <span class="hnt-studio-media-state">${isInTimeline(item) ? 'In Timeline' : 'Bereit'}</span>
                <span class="hnt-studio-media-actions">
                    <span class="hnt-studio-media-action" data-hnt-media-add="${item.id}">＋</span>
                    <span class="hnt-studio-media-action danger" data-hnt-media-remove="${item.id}">×</span>
                </span>
            `;
            button.addEventListener('click', (event) => {
                if (event.target && event.target.closest('[data-hnt-media-add], [data-hnt-media-remove]')) return;
                selectMedia(item.id);
            });
            button.addEventListener('dragstart', (event) => {
                event.dataTransfer.effectAllowed = 'copy';
                event.dataTransfer.setData('text/hnt-media-id', item.id);
                event.dataTransfer.setData('text/plain', item.id);
                studio.classList.add('is-media-dragging');
            });
            button.addEventListener('dragend', () => {
                studio.classList.remove('is-media-dragging');
                if (timelineTrack) timelineTrack.classList.remove('is-media-drop-ready');
            });
            const addButton = button.querySelector('[data-hnt-media-add]');
            const removeButton = button.querySelector('[data-hnt-media-remove]');
            if (addButton) addButton.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); addMediaToTimeline(item.id); });
            if (removeButton) removeButton.addEventListener('click', (event) => { event.preventDefault(); event.stopPropagation(); removeMediaFromTimeline(item.id); });
            mediaList.appendChild(button);
        });
    };

    const clearTimelineDropState = () => {
        if (!timelineTrack) return;
        timelineTrack.classList.remove('is-reordering');
        timelineTrack.querySelectorAll('.hnt-studio-timeline-clip').forEach((clip) => {
            clip.classList.remove('is-drop-target', 'is-drag-source');
            clip.removeAttribute('aria-grabbed');
        });
    };

    const getTimelineDropIndex = (clientX) => {
        if (!timelineTrack) return 0;
        const clips = Array.from(timelineTrack.querySelectorAll('.hnt-studio-timeline-clip'));
        if (!clips.length) return 0;

        for (const clip of clips) {
            const rect = clip.getBoundingClientRect();
            const index = Number(clip.dataset.index || 0);
            if (clientX < rect.left + rect.width / 2) {
                return Math.max(0, Math.min(index, timelineCount()));
            }
        }

        return Math.max(0, timelineCount());
    };

    const markTimelineDropTarget = (index) => {
        if (!timelineTrack) return;
        timelineTrack.querySelectorAll('.hnt-studio-timeline-clip').forEach((clip) => {
            clip.classList.toggle('is-drop-target', Number(clip.dataset.index || 0) === index);
        });
    };

    const reorderMedia = (id, targetIndex) => {
        const ordered = timelineItems();
        const fromIndex = ordered.findIndex((item) => item.id === id);
        if (fromIndex < 0) return false;

        const [item] = ordered.splice(fromIndex, 1);
        const safeIndex = Math.max(0, Math.min(targetIndex, ordered.length));
        ordered.splice(safeIndex, 0, item);
        const nonTimeline = mediaItems.filter((entry) => !entry.inTimeline);
        mediaItems.splice(0, mediaItems.length, ...nonTimeline, ...ordered);
        activeId = item.id;
        renderMediaList();
        renderTimeline();
        updateStatus();
        if (status) status.textContent = t('moment_studio_reordered', 'Timeline-Reihenfolge aktualisiert.');
        return true;
    };

    const startTimelineDrag = (event) => {
        const clip = event.currentTarget;
        if (!clip || timelineCount() < 2) return;
        if (event.button !== undefined && event.button !== 0) return;
        if (event.target && event.target.closest('[data-hnt-trim-handle], [data-hnt-timeline-remove]')) return;

        const id = clip.dataset.mediaId;
        if (!id) return;

        timelineDrag = {
            id,
            startX: event.clientX,
            startY: event.clientY,
            dropIndex: Number(clip.dataset.index || 0),
            started: false,
        };

        const onMove = (moveEvent) => {
            if (!timelineDrag) return;
            const distance = Math.hypot(moveEvent.clientX - timelineDrag.startX, moveEvent.clientY - timelineDrag.startY);
            if (!timelineDrag.started && distance > 7) {
                timelineDrag.started = true;
                suppressTimelineClickUntil = Date.now() + 700;
                studio.classList.add('is-timeline-dragging');
                if (timelineTrack) timelineTrack.classList.add('is-reordering');
                clip.classList.add('is-drag-source');
                clip.setAttribute('aria-grabbed', 'true');
            }
            if (!timelineDrag.started) return;

            moveEvent.preventDefault();
            timelineDrag.dropIndex = getTimelineDropIndex(moveEvent.clientX);
            markTimelineDropTarget(timelineDrag.dropIndex);
        };

        const onUp = (upEvent) => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointercancel', onCancel);
            studio.classList.remove('is-timeline-dragging');
            const shouldReorder = timelineDrag && timelineDrag.started;
            const idToMove = timelineDrag ? timelineDrag.id : '';
            const dropIndex = timelineDrag ? timelineDrag.dropIndex : 0;
            clearTimelineDropState();
            timelineDrag = null;

            if (shouldReorder) {
                upEvent.preventDefault();
                suppressTimelineClickUntil = Date.now() + 700;
                reorderMedia(idToMove, dropIndex);
            }
        };

        const onCancel = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            studio.classList.remove('is-timeline-dragging');
            clearTimelineDropState();
            timelineDrag = null;
        };

        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp, { once: true });
        document.addEventListener('pointercancel', onCancel, { once: true });
    };

    const getTimelineClipWidth = (item) => {
        const duration = effectiveDuration(item) || Math.min(Number(item && item.duration) || 3, maxDuration);
        return Math.max(2.8, Math.min(100, (duration / maxDuration) * 100));
    };

    const getClipDurationLabel = (item) => {
        if (!item || !item.duration) return '…';
        const duration = effectiveDuration(item);
        return isTrimmed(item) ? `${formatTime(duration)} / ${formatTime(item.duration)}` : formatTime(duration);
    };

    const syncTimelineClipVisual = (item) => {
        if (!item || !timelineTrack) return;
        ensureTrimBounds(item);
        const clip = timelineTrack.querySelector(`.hnt-studio-timeline-clip[data-media-id="${CSS.escape(item.id)}"]`);
        if (!clip) return;
        const width = getTimelineClipWidth(item);
        clip.style.setProperty('--clip-width', `${width}%`);
        clip.classList.toggle('is-active', item.id === activeId);
        clip.classList.toggle('is-trimmed', isTrimmed(item));
        clip.classList.toggle('is-faded', hasFadeEffect(item));
        clip.classList.toggle('is-filtered', hasFilterEffect(item));
        clip.classList.toggle('is-effected', hasVisualEffect(item));
        clip.classList.toggle('is-colored', hasColorEffect(item));
        clip.classList.toggle('is-live-trimming', trimDrag && trimDrag.id === item.id);
        const label = clip.querySelector('em');
        if (label) label.textContent = getClipDurationLabel(item);
    };

    const syncMediaItemVisual = (item) => {
        if (!item || !mediaList) return;
        const media = mediaList.querySelector(`.hnt-studio-media-item[data-media-id="${CSS.escape(item.id)}"]`);
        if (!media) return;
        media.classList.toggle('is-active', item.id === activeId);
        media.classList.toggle('is-trimmed', isTrimmed(item));
        media.classList.toggle('is-faded', hasFadeEffect(item));
        media.classList.toggle('is-filtered', hasFilterEffect(item));
        media.classList.toggle('is-effected', hasVisualEffect(item));
        media.classList.toggle('is-colored', hasColorEffect(item));
        const label = media.querySelector('em');
        if (label) label.textContent = getClipDurationLabel(item);
    };

    const syncLiveTrimVisuals = (item) => {
        syncTimelineClipVisual(item);
        syncMediaItemVisual(item);
    };
    const addMediaToTimeline = (id, targetIndex = null) => {
        const item = mediaItems.find((entry) => entry.id === id);
        if (!item) return false;
        if (item.inTimeline) {
            selectMedia(item.id, { keepTime: true, silent: true });
            return true;
        }
        item.inTimeline = true;
        const currentIndex = mediaItems.indexOf(item);
        if (currentIndex >= 0) mediaItems.splice(currentIndex, 1);
        const ordered = timelineItems();
        const insertAt = targetIndex === null ? ordered.length : Math.max(0, Math.min(targetIndex, ordered.length));
        let timelineSeen = 0;
        let globalInsert = mediaItems.length;
        for (let i = 0; i < mediaItems.length; i += 1) {
            if (!mediaItems[i].inTimeline) continue;
            if (timelineSeen >= insertAt) {
                globalInsert = i;
                break;
            }
            timelineSeen += 1;
        }
        mediaItems.splice(globalInsert, 0, item);
        activeId = item.id;
        renderMediaList();
        renderTimeline();
        updateStatus();
        selectMedia(item.id, { keepTime: true, silent: true });
        if (status) status.textContent = t('moment_studio_added_to_timeline', 'Clip wurde zur Timeline hinzugefügt.');
        return true;
    };

    const removeMediaFromTimeline = (id) => {
        const item = mediaItems.find((entry) => entry.id === id);
        if (!item || !item.inTimeline) return false;
        item.inTimeline = false;
        item.transitionOut = 'none';
        renderMediaList();
        renderTimeline();
        updateStatus();
        syncTransitionControls();
        if (activeId === id && timelineCount() > 0) {
            selectMedia(timelineItems()[0].id, { keepTime: false, silent: true });
        }
        if (status) status.textContent = t('moment_studio_removed_from_timeline', 'Clip wurde aus der Timeline entfernt.');
        return true;
    };


    const startTrimDrag = (event) => {
        const handle = event.currentTarget;
        const clip = handle.closest('.hnt-studio-timeline-clip');
        if (!clip || !timelineTrack) return;
        if (event.button !== undefined && event.button !== 0) return;

        const item = mediaItems.find((entry) => entry.id === clip.dataset.mediaId);
        if (!item || !item.duration) return;

        event.preventDefault();
        event.stopPropagation();
        suppressTimelineClickUntil = Date.now() + 700;
        selectMedia(item.id, { keepTime: true, silent: true });
        ensureTrimBounds(item);

        const laneRect = timelineTrack.getBoundingClientRect();
        const secondsPerPixel = maxDuration / Math.max(1, laneRect.width);
        const edge = handle.dataset.hntTrimHandle === 'end' ? 'end' : 'start';
        const originalStart = Number(item.trimStart) || 0;
        const originalEnd = Number(item.trimEnd) || Number(item.duration) || 0;
        const minDuration = getClipMinDuration(item);

        trimDrag = {
            id: item.id,
            edge,
            startX: event.clientX,
            originalStart,
            originalEnd,
        };

        studio.classList.add('is-trimming');
        clip.classList.add('is-trim-source');

        const applyTrim = (clientX) => {
            const deltaSeconds = snapTime((clientX - trimDrag.startX) * secondsPerPixel);
            if (edge === 'start') {
                item.trimStart = snapTime(clamp(originalStart + deltaSeconds, 0, Math.max(0, originalEnd - minDuration)));
            } else {
                item.trimEnd = snapTime(clamp(originalEnd + deltaSeconds, originalStart + minDuration, item.duration));
            }
            ensureTrimBounds(item);

            if (preview && item.id === activeId) {
                if (edge === 'start') {
                    seekPreviewToTrimStart(item);
                } else if (preview.currentTime > item.trimEnd) {
                    try { preview.currentTime = item.trimEnd; } catch (error) {}
                }
            }

            syncLiveTrimVisuals(item);
            updateStatus();
        };

        const onMove = (moveEvent) => {
            if (!trimDrag) return;
            moveEvent.preventDefault();
            applyTrim(moveEvent.clientX);
        };

        const onUp = (upEvent) => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointercancel', onCancel);
            studio.classList.remove('is-trimming');
            trimDrag = null;
            suppressTimelineClickUntil = Date.now() + 700;
            upEvent.preventDefault();
            renderMediaList();
            renderTimeline();
            updateStatus();
            if (status) status.textContent = t('moment_studio_trimmed', 'Clip getrimmt.');
        };

        const onCancel = () => {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            studio.classList.remove('is-trimming');
            trimDrag = null;
            renderMediaList();
            renderTimeline();
            updateStatus();
        };

        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp, { once: true });
        document.addEventListener('pointercancel', onCancel, { once: true });
    };

    const renderTimeline = () => {
        if (!timelineTrack || !timelineEmpty) return;
        timelineTrack.querySelectorAll('.hnt-studio-timeline-clip').forEach((clip) => clip.remove());
        const ordered = timelineItems();
        timelineEmpty.hidden = ordered.length > 0;

        ordered.forEach((item, index) => {
            ensureTrimBounds(item);
            const duration = effectiveDuration(item) || Math.min(Number(item.duration) || 3, maxDuration);
            const width = getTimelineClipWidth(item);
            const clip = document.createElement('button');
            clip.className = `hnt-studio-timeline-clip${item.id === activeId ? ' is-active' : ''}${isTrimmed(item) ? ' is-trimmed' : ''}${hasFadeEffect(item) ? ' is-faded' : ''}${hasFilterEffect(item) ? ' is-filtered' : ''}${hasVisualEffect(item) ? ' is-effected' : ''}${hasColorEffect(item) ? ' is-colored' : ''}${hasTransitionEffect(item) ? ' is-transitioned' : ''}`;
            clip.type = 'button';
            clip.dataset.mediaId = item.id;
            clip.dataset.index = String(index);
            clip.style.setProperty('--clip-width', `${width}%`);
            const durationLabel = getClipDurationLabel(item);
            clip.innerHTML = `
                <span class="hnt-studio-trim-handle" data-hnt-trim-handle="start" aria-hidden="true"></span>
                <strong>${index + 1}. ${safeName(item.file.name)}</strong>
                <em>${durationLabel}</em>
                <span class="hnt-studio-timeline-remove" data-hnt-timeline-remove="${item.id}" title="Aus Timeline entfernen">×</span>
                <span class="hnt-studio-trim-handle right" data-hnt-trim-handle="end" aria-hidden="true"></span>
            `;
            clip.addEventListener('pointerdown', startTimelineDrag);
            clip.addEventListener('click', (event) => {
                if (Date.now() < suppressTimelineClickUntil) {
                    event.preventDefault();
                    return;
                }
                selectMedia(item.id);
            });
            clip.querySelectorAll('[data-hnt-trim-handle]').forEach((handle) => {
                handle.addEventListener('pointerdown', startTrimDrag);
            });
            const removeButton = clip.querySelector('[data-hnt-timeline-remove]');
            if (removeButton) removeButton.addEventListener('click', (event) => {
                event.preventDefault();
                event.stopPropagation();
                removeMediaFromTimeline(item.id);
            });
            timelineTrack.appendChild(clip);
        });
        updateTimelineScrubber();
    };

    const selectMedia = (id, options = {}) => {
        const item = mediaItems.find((entry) => entry.id === id);
        if (!item || !preview) return;
        const wasSame = activeId === item.id;
        activeId = item.id;
        setInputFile(item.file);
        ensureTrimBounds(item);
        preview.pause();
        if (!wasSame || preview.src !== item.url) {
            preview.src = item.url;
            preview.load();
        }
        preview.hidden = false;
        preview.muted = true;
        preview.controls = false;
        if (previewEmpty) previewEmpty.hidden = true;
        studio.classList.add('has-media');
        if (playButton) playButton.disabled = false;
        if (!options.keepTime) seekPreviewToTrimStart(item);
        updatePreviewTimeLabels();
        applyPreviewFade();
        applyPreviewFilter();
        syncFadeControls();
        syncFilterControls();
        syncEffectControls();
        syncColorControls();
        syncTransitionControls();
        renderTextLayers();
        renderMediaList();
        renderTimeline();
        updateStatus();
    };

    const probeDuration = (item) => {
        const probe = document.createElement('video');
        probe.preload = 'metadata';
        probe.muted = true;
        probe.playsInline = true;
        probe.src = item.url;
        probe.addEventListener('loadedmetadata', () => {
            item.duration = Number.isFinite(probe.duration) ? probe.duration : 0;
            item.trimStart = 0;
            item.trimEnd = item.duration;
            if (item.id === activeId) {
                seekPreviewToTrimStart(item);
                updatePreviewTimeLabels();
            }
            renderMediaList();
            renderTimeline();
            updateStatus();
            probe.removeAttribute('src');
            probe.load();
        }, { once: true });
        probe.addEventListener('error', () => {
            item.duration = 0;
            item.trimStart = 0;
            item.trimEnd = 0;
            renderMediaList();
            renderTimeline();
            updateStatus();
        }, { once: true });
    };

    const addMedia = (file) => {
        if (!file) return;
        if (!file.type || !file.type.startsWith('video/')) return;
        if (mediaItems.length >= maxMedia) {
            if (status) status.textContent = t('moment_studio_media_limit_reached', 'Maximal 5 Videos.');
            return;
        }
        const item = {
            id: `local-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            file,
            url: URL.createObjectURL(file),
            duration: 0,
            trimStart: 0,
            trimEnd: 0,
            fadeIn: false,
            fadeOut: false,
            filter: 'none',
            effect: 'none',
            colors: { ...DEFAULT_COLORS },
            transitionOut: 'none',
            inTimeline: false,
        };
        mediaItems.push(item);
        activeId = item.id;
        if (status) status.textContent = t('moment_studio_loading_video', 'Video wird geprüft ...');
        renderMediaList();
        renderTimeline();
        selectMedia(item.id);
        if (status) status.textContent = t('moment_studio_drag_to_timeline', 'Ziehe Clips aus Meine Medien in die Timeline.');
        probeDuration(item);
    };

    toolButtons.forEach((button) => {
        button.addEventListener('click', () => {
            if (button.classList.contains('is-locked')) return;
            setStudioTool(button.dataset.hntStudioTool || 'media');
        });
    });

    transitionChoiceButtons.forEach((button) => {
        button.addEventListener('click', () => updateActiveTransition(button.dataset.hntStudioTransitionChoice || 'none'));
    });

    if (textAddButton) {
        textAddButton.addEventListener('click', addTextLayer);
    }

    if (textClearButton) {
        textClearButton.addEventListener('click', clearTextLayers);
    }

    if (textInput) {
        textInput.addEventListener('keydown', (event) => {
            if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') {
                event.preventDefault();
                addTextLayer();
            }
        });
    }

    renderTextLayers();
    setStudioTool('media');
    setPlayButtonState(false);
    ensureTimelineScrubber();
    updateTimelineScrubber();

    if (input) {
        input.addEventListener('click', () => {
            input.value = '';
        });
        input.addEventListener('change', () => {
            const files = input.files ? Array.from(input.files) : [];
            if (!files.length) return;
            files.forEach((file) => addMedia(file));
        });
    }

    if (preview) {
        preview.controls = false;
        preview.addEventListener('loadedmetadata', () => {
            const active = getActive();
            if (active && !active.duration) {
                active.duration = Number.isFinite(preview.duration) ? preview.duration : 0;
                active.trimStart = 0;
                active.trimEnd = active.duration;
                renderMediaList();
                renderTimeline();
                updateStatus();
            }
            updatePreviewTimeLabels();
            applyPreviewFade();
            applyPreviewFilter();
            syncEffectControls();
            renderTextLayers();
        });
        preview.addEventListener('timeupdate', () => {
            const active = getActive();
            if (active && active.duration) {
                ensureTrimBounds(active);
                if (!isProgrammaticSeek && preview.currentTime >= active.trimEnd) {
                    if (sequencePlayback && advanceToNextClip(active)) {
                        return;
                    }
                    sequencePlayback = false;
                    preview.pause();
                    try { preview.currentTime = active.trimEnd; } catch (error) {}
                }
            }
            updatePreviewTimeLabels();
            applyPreviewFade();
            applyPreviewFilter();
            syncEffectControls();
            renderTextLayers();
            updateTimelineScrubber();
        });
        preview.addEventListener('play', () => {
            studio.classList.add('is-playing');
            setPlayButtonState(true);
            startScrubberLoop();
        });
        preview.addEventListener('pause', () => {
            studio.classList.remove('is-playing');
            setPlayButtonState(false);
            stopScrubberLoop();
        });
    }


    const setUploadProgressValue = (percent, label) => {
        const safe = clamp(percent, 0, 100);
        if (uploadProgress) {
            uploadProgress.setAttribute('aria-valuenow', String(Math.round(safe)));
        }
        if (uploadProgressBar) {
            uploadProgressBar.style.width = `${safe.toFixed(1)}%`;
        }
        if (uploadText && label) {
            uploadText.textContent = label;
        }
    };

    const openBackgroundUploadShell = () => {
        if (!uploadShell) return;
        closePublishDrawer();
        closeProDrawer();
        closeFadeDrawer();
        closeFilterDrawer();
        closeEffectDrawer();
        closeColorsDrawer();
        const feedUrl = studio.dataset.feedUrl || '/moments';
        if (uploadFrame && !uploadFrame.getAttribute('src')) {
            uploadFrame.setAttribute('src', feedUrl);
        }
        const active = getActive();
        if (uploadPreview && active && active.url) {
            uploadPreview.src = active.url;
            uploadPreview.hidden = false;
            uploadPreviewFallback.hidden = true;
            uploadPreview.load();
        } else if (uploadPreviewFallback) {
            uploadPreviewFallback.hidden = false;
        }
        if (uploadBackButton) uploadBackButton.hidden = true;
        if (uploadTitle) uploadTitle.textContent = t('moment_studio_background_upload_title', 'Moment wird hochgeladen');
        setUploadProgressValue(0, t('moment_studio_background_upload_open_feed', 'Du kannst währenddessen im Feed bleiben.'));
        uploadShell.hidden = false;
        uploadShell.setAttribute('aria-hidden', 'false');
        studio.classList.add('is-background-uploading');
    };

    const closeBackgroundUploadShell = () => {
        studio.classList.remove('is-background-uploading');
        if (uploadShell) {
            uploadShell.hidden = true;
            uploadShell.setAttribute('aria-hidden', 'true');
        }
        if (uploadBackButton) uploadBackButton.hidden = true;
        if (publish) {
            publish.disabled = !canPublishActiveClip();
            publish.textContent = t('moment_publish', 'Moment veröffentlichen');
        }
        if (publishFinal) {
            publishFinal.disabled = false;
            publishFinal.textContent = t('moment_studio_publish_confirm', 'Jetzt veröffentlichen');
        }
        studio.classList.remove('is-submitting');
        publishConfirmed = false;
    };

    const setUploadShellError = (message) => {
        studio.classList.add('is-upload-error');
        if (uploadTitle) uploadTitle.textContent = t('moment_studio_background_upload_error', 'Upload fehlgeschlagen');
        setUploadProgressValue(0, message || t('moment_studio_background_upload_error', 'Upload fehlgeschlagen'));
        if (uploadBackButton) uploadBackButton.hidden = false;
    };

    const parseUploadResponse = (xhr) => {
        try {
            return JSON.parse(xhr.responseText || '{}');
        } catch (error) {
            return {};
        }
    };

    const uploadErrorMessage = (payload, fallback) => {
        if (payload && payload.message) return String(payload.message);
        if (payload && payload.errors && typeof payload.errors === 'object') {
            const first = Object.values(payload.errors).flat().filter(Boolean)[0];
            if (first) return String(first);
        }
        return fallback;
    };

    const prepareStudioSubmit = () => {
        const active = getActive();
        const ordered = timelineItems();
        const publishItem = ordered.includes(active) ? active : ordered[0];
        if (!publishItem || !input) {
            publishConfirmed = false;
            studio.classList.add('is-missing-media');
            if (status) status.textContent = t('moment_studio_pick_video_first', 'Bitte zuerst ein Video auswählen.');
            return false;
        }
        if (totalDuration() > maxDuration + 0.05) {
            publishConfirmed = false;
            studio.classList.add('is-over-duration');
            if (status) status.textContent = t('moment_studio_over_duration', 'Endvideo ist länger als 120 Sekunden.');
            return false;
        }
        const shouldRenderStudioProject = ordered.length > 1 || textLayers.length > 0 || hasStudioEffects();
        if (shouldRenderStudioProject) {
            if (!setBatchFiles()) {
                publishConfirmed = false;
                if (status) status.textContent = t('moment_studio_pick_video_first', 'Bitte zuerst ein Video auswählen.');
                return false;
            }
            syncStudioPayload();
            setInputFile(publishItem.file);
            if (trimStartInput) trimStartInput.value = '';
            if (trimEndInput) trimEndInput.value = '';
        } else {
            if (batchInput) batchInput.value = '';
            if (payloadInput) payloadInput.value = '';
            activeId = publishItem.id;
            setInputFile(publishItem.file);
            setActiveTrimInputs();
        }
        return true;
    };

    const startBackgroundUpload = () => {
        if (!window.XMLHttpRequest || !window.FormData) return false;
        openBackgroundUploadShell();
        studio.classList.add('is-submitting');
        studio.classList.remove('is-upload-error');
        if (publish) {
            publish.disabled = true;
            publish.textContent = t('upload_running', 'Upload läuft ...');
        }
        if (publishFinal) {
            publishFinal.disabled = true;
            publishFinal.textContent = t('upload_running', 'Upload läuft ...');
        }

        const xhr = new XMLHttpRequest();
        const formData = new FormData(studio);
        xhr.open(String(studio.method || 'POST').toUpperCase(), studio.action, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.upload.addEventListener('progress', (event) => {
            if (!event.lengthComputable) return;
            const percent = (event.loaded / Math.max(1, event.total)) * 100;
            setUploadProgressValue(percent, `${t('upload_running', 'Upload läuft ...')} ${Math.round(percent)}%`);
        });
        xhr.onload = () => {
            const payload = parseUploadResponse(xhr);
            if (xhr.status < 200 || xhr.status >= 300) {
                setUploadShellError(uploadErrorMessage(payload, t('moment_studio_background_upload_error', 'Upload fehlgeschlagen')));
                return;
            }
            const redirectUrl = payload.redirect_url || payload.processing_url || payload.feed_url || '/moments';
            if (payload.status === 'published') {
                if (uploadTitle) uploadTitle.textContent = t('moment_studio_processing_state_published', 'Veröffentlicht');
                setUploadProgressValue(100, payload.message || t('moment_studio_processing_message_published', 'Dein Moment ist fertig.'));
            } else {
                if (uploadTitle) uploadTitle.textContent = t('moment_studio_background_upload_processing', 'Moment wird verarbeitet');
                setUploadProgressValue(100, payload.message || t('moment_studio_background_upload_processing', 'Moment wird verarbeitet'));
            }
            window.setTimeout(() => {
                window.location.href = redirectUrl;
            }, 420);
        };
        xhr.onerror = () => setUploadShellError(t('moment_studio_background_upload_error', 'Upload fehlgeschlagen'));
        xhr.onabort = () => setUploadShellError(t('moment_studio_background_upload_error', 'Upload fehlgeschlagen'));
        xhr.send(formData);
        return true;
    };


    const startTimelineScrub = (event) => {
        if (!preview || !timelineCount()) return;
        if (event.button !== undefined && event.button !== 0) return;
        if (event.target && event.target.closest('.hnt-studio-timeline-clip, .hnt-studio-text-clip, [data-hnt-trim-handle], button')) return;
        event.preventDefault();
        const wasPlaying = sequencePlayback && !preview.paused;
        if (wasPlaying) preview.pause();
        studio.classList.add('is-scrubbing-timeline');
        const apply = (clientX, shouldResume = false) => {
            const globalTime = getGlobalTimeFromClientX(clientX);
            updateTimelineScrubber(globalTime);
            seekPreviewToGlobalTime(globalTime, { autoPlay: shouldResume });
        };
        apply(event.clientX, false);
        const onMove = (moveEvent) => {
            moveEvent.preventDefault();
            apply(moveEvent.clientX, false);
        };
        const onUp = (upEvent) => {
            studio.classList.remove('is-scrubbing-timeline');
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointercancel', onCancel);
            apply(upEvent.clientX || event.clientX, wasPlaying);
        };
        const onCancel = () => {
            studio.classList.remove('is-scrubbing-timeline');
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            if (wasPlaying) preview.play().catch(() => {});
        };
        document.addEventListener('pointermove', onMove, { passive: false });
        document.addEventListener('pointerup', onUp, { once: true });
        document.addEventListener('pointercancel', onCancel, { once: true });
    };

    if (timelineTrack) timelineTrack.addEventListener('pointerdown', startTimelineScrub);
    if (timeRuler) timeRuler.addEventListener('pointerdown', startTimelineScrub);

    if (timelineTrack) {
        timelineTrack.addEventListener('dragover', (event) => {
            const mediaId = event.dataTransfer ? (event.dataTransfer.getData('text/hnt-media-id') || event.dataTransfer.getData('text/plain')) : '';
            // Some browsers only expose custom drag data on drop; still show a generic drop state.
            event.preventDefault();
            timelineTrack.classList.add('is-media-drop-ready');
            markTimelineDropTarget(getTimelineDropIndex(event.clientX));
            if (event.dataTransfer) event.dataTransfer.dropEffect = 'copy';
        });
        timelineTrack.addEventListener('dragleave', (event) => {
            if (event.relatedTarget && timelineTrack.contains(event.relatedTarget)) return;
            timelineTrack.classList.remove('is-media-drop-ready');
            clearTimelineDropState();
        });
        timelineTrack.addEventListener('drop', (event) => {
            event.preventDefault();
            const mediaId = event.dataTransfer ? (event.dataTransfer.getData('text/hnt-media-id') || event.dataTransfer.getData('text/plain')) : '';
            const dropIndex = getTimelineDropIndex(event.clientX);
            timelineTrack.classList.remove('is-media-drop-ready');
            clearTimelineDropState();
            if (mediaId) addMediaToTimeline(mediaId, dropIndex);
        });
    }

    if (playButton && preview) {
        playButton.addEventListener('click', () => {
            if (!preview.src) return;
            const active = getActive();
            if (active) {
                ensureTrimBounds(active);
                if (preview.currentTime < active.trimStart || preview.currentTime >= active.trimEnd - 0.08) {
                    try { preview.currentTime = active.trimStart || 0; } catch (error) {}
                }
            }
            if (preview.paused) {
                sequencePlayback = true;
                preview.play().catch(() => {});
            } else {
                sequencePlayback = false;
                preview.pause();
            }
        });
    }

    if (publish) {
        publish.addEventListener('click', () => {
            openPublishDrawer();
        });
    }

    if (publishFinal) {
        publishFinal.addEventListener('click', () => {
            publishConfirmed = true;
        });
    }

    publishCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closePublishDrawer());
    });

    fadeOpenButtons.forEach((node) => {
        node.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openFadeDrawer();
        });
    });

    fadeCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closeFadeDrawer());
    });

    if (fadeInInput) fadeInInput.addEventListener('change', updateActiveFade);
    if (fadeOutInput) fadeOutInput.addEventListener('change', updateActiveFade);

    filterOpenButtons.forEach((node) => {
        node.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openFilterDrawer();
        });
    });

    filterCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closeFilterDrawer());
    });

    filterChoiceButtons.forEach((node) => {
        node.addEventListener('click', () => updateActiveFilter(node.dataset.hntStudioFilterChoice || 'none'));
    });

    if (filterSearchInput) {
        filterSearchInput.addEventListener('input', () => filterChoices(filterSearchInput.value));
    }

    if (effectSearchInput) {
        effectSearchInput.addEventListener('input', () => effectChoices(effectSearchInput.value));
    }

    effectOpenButtons.forEach((node) => {
        node.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openEffectDrawer();
        });
    });

    effectCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closeEffectDrawer());
    });

    effectChoiceButtons.forEach((node) => {
        node.addEventListener('click', () => updateActiveEffect(node.dataset.hntStudioEffectChoice || 'none'));
    });

    colorsOpenButtons.forEach((node) => {
        node.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openColorsDrawer();
        });
    });

    colorsCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closeColorsDrawer());
    });

    colorInputs.forEach((node) => {
        node.addEventListener('input', () => updateActiveColors(false));
        node.addEventListener('change', () => updateActiveColors(true));
    });

    if (colorsResetButton) colorsResetButton.addEventListener('click', resetActiveColors);

    proLockButtons.forEach((node) => {
        node.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            openProDrawer(node);
        });
    });

    proCloseButtons.forEach((node) => {
        node.addEventListener('click', () => closeProDrawer());
    });

    if (proBuyButton) {
        proBuyButton.addEventListener('click', (event) => {
            event.preventDefault();
            unlockActiveProFeature();
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && studio.classList.contains('is-publish-open')) {
            closePublishDrawer();
        }
        if (event.key === 'Escape' && studio.classList.contains('is-pro-open')) {
            closeProDrawer();
        }
        if (event.key === 'Escape' && studio.classList.contains('is-fade-open')) {
            closeFadeDrawer();
        }
        if (event.key === 'Escape' && studio.classList.contains('is-filter-open')) {
            closeFilterDrawer();
        }
        if (event.key === 'Escape' && studio.classList.contains('is-effect-open')) {
            closeEffectDrawer();
        }
        if (event.key === 'Escape' && studio.classList.contains('is-colors-open')) {
            closeColorsDrawer();
        }
    });

    if (uploadBackButton) {
        uploadBackButton.addEventListener('click', () => closeBackgroundUploadShell());
    }

    studio.addEventListener('submit', (event) => {
        if (!publishConfirmed) {
            event.preventDefault();
            openPublishDrawer();
            return;
        }

        if (!prepareStudioSubmit()) {
            event.preventDefault();
            return;
        }

        if (window.XMLHttpRequest && window.FormData) {
            event.preventDefault();
            publishConfirmed = false;
            startBackgroundUpload();
            return;
        }

        studio.classList.add('is-submitting');
        if (publish) {
            publish.disabled = true;
            publish.textContent = t('upload_running', 'Upload läuft ...');
        }
        if (publishFinal) {
            publishFinal.disabled = true;
            publishFinal.textContent = t('upload_running', 'Upload läuft ...');
        }
    });

    window.addEventListener('beforeunload', () => {
        mediaItems.forEach((item) => {
            if (item.url) URL.revokeObjectURL(item.url);
        });
    });
})();

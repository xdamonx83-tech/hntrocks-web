(() => {
  'use strict';

  const init = () => {
    const summary = document.querySelector('[data-profile-cover-peek]');
    const trigger = document.querySelector('[data-profile-cover-trigger]');
    const toggles = Array.from(document.querySelectorAll('[data-profile-cover-toggle]'));

    if (!summary || !trigger) {
      return;
    }

    const mode = ['auto', 'always', 'hidden'].includes(summary.dataset.coverDisplayMode)
      ? summary.dataset.coverDisplayMode
      : 'auto';

    if (mode === 'hidden') {
      summary.classList.remove('is-cover-peek');
      return;
    }

    const isEnglish = document.documentElement.lang === 'en';
    const labels = isEnglish
      ? { show: 'Show cover image', hide: 'Hide cover image' }
      : { show: 'Titelbild anzeigen', hide: 'Titelbild ausblenden' };
    const hoverCapable = window.matchMedia('(hover: hover) and (pointer: fine)');
    let pinned = false;
    let temporarilyHidden = false;
    let hovering = false;
    let focusWithin = false;
    let hideTimer = 0;

    const isVisible = () => {
      if (mode === 'always') {
        return !temporarilyHidden;
      }

      return pinned || hovering || focusWithin;
    };

    const sync = () => {
      const visible = isVisible();
      summary.classList.toggle('is-cover-peek', visible);

      toggles.forEach((toggle) => {
        toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
        toggle.setAttribute('aria-label', visible ? labels.hide : labels.show);
      });
    };

    const clearHideTimer = () => {
      if (hideTimer) {
        window.clearTimeout(hideTimer);
        hideTimer = 0;
      }
    };

    if (mode === 'auto' && hoverCapable.matches) {
      trigger.addEventListener('mouseenter', () => {
        clearHideTimer();
        hovering = true;
        sync();
      });

      trigger.addEventListener('mouseleave', () => {
        hovering = false;
        clearHideTimer();
        hideTimer = window.setTimeout(sync, 180);
      });
    }

    if (mode === 'auto') {
      trigger.addEventListener('focusin', () => {
        clearHideTimer();
        focusWithin = true;
        sync();
      });

      trigger.addEventListener('focusout', (event) => {
        if (event.relatedTarget && trigger.contains(event.relatedTarget)) {
          return;
        }

        focusWithin = false;
        clearHideTimer();
        hideTimer = window.setTimeout(sync, 120);
      });
    }

    toggles.forEach((toggle) => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearHideTimer();

        if (mode === 'always') {
          temporarilyHidden = !temporarilyHidden;
        } else {
          pinned = !pinned;
        }

        sync();
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape') {
        return;
      }

      if (mode === 'always' && !temporarilyHidden) {
        temporarilyHidden = true;
        sync();
        return;
      }

      if (mode === 'auto' && pinned) {
        pinned = false;
        sync();
      }
    });

    sync();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

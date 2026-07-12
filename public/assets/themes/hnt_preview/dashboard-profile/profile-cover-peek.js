(() => {
  'use strict';

  const init = () => {
    const summary = document.querySelector('[data-profile-cover-peek]');
    const trigger = document.querySelector('[data-profile-cover-trigger]');
    const toggles = Array.from(document.querySelectorAll('[data-profile-cover-toggle]'));

    if (!summary || !trigger || toggles.length === 0) {
      return;
    }

    const hoverCapable = window.matchMedia('(hover: hover) and (pointer: fine)');
    let pinned = false;
    let hovering = false;
    let focusWithin = false;
    let hideTimer = 0;

    const sync = () => {
      const visible = pinned || hovering || focusWithin;
      summary.classList.toggle('is-cover-peek', visible);

      toggles.forEach((toggle) => {
        toggle.setAttribute('aria-pressed', pinned ? 'true' : 'false');
        toggle.setAttribute(
          'aria-label',
          pinned ? 'Titelbild ausblenden' : 'Titelbild anzeigen',
        );
      });
    };

    const clearHideTimer = () => {
      if (hideTimer) {
        window.clearTimeout(hideTimer);
        hideTimer = 0;
      }
    };

    if (hoverCapable.matches) {
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

    toggles.forEach((toggle) => {
      toggle.addEventListener('click', (event) => {
        event.preventDefault();
        event.stopPropagation();
        clearHideTimer();
        pinned = !pinned;
        sync();
      });
    });

    document.addEventListener('keydown', (event) => {
      if (event.key !== 'Escape' || !pinned) {
        return;
      }

      pinned = false;
      sync();
    });

    sync();
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

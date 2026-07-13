(() => {
  'use strict';

  const target = document.querySelector('.map-detail-center');
  const actions = target?.querySelector('.map-toolbar-actions');
  const shareButton = actions?.querySelector('[data-map-share]');

  if (!(target instanceof HTMLElement) || !(actions instanceof HTMLElement)) {
    return;
  }

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const labels = isEnglish ? {
    enter: 'Fullscreen',
    exit: 'Exit fullscreen',
  } : {
    enter: 'Vollbild',
    exit: 'Vollbild beenden',
  };

  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'map-fullscreen-button';
  button.setAttribute('data-map-fullscreen-toggle', '');
  button.setAttribute('aria-pressed', 'false');
  button.innerHTML = '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"></path></svg><span></span>';

  if (shareButton) {
    actions.insertBefore(button, shareButton);
  } else {
    actions.appendChild(button);
  }

  const label = button.querySelector('span');

  const fullscreenElement = () => document.fullscreenElement || document.webkitFullscreenElement || null;
  const pseudoActive = () => target.classList.contains('is-map-pseudo-fullscreen');
  const active = () => fullscreenElement() === target || pseudoActive();

  const sync = () => {
    const isActive = active();
    button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
    button.setAttribute('aria-label', isActive ? labels.exit : labels.enter);
    button.title = isActive ? labels.exit : labels.enter;
    if (label) label.textContent = isActive ? labels.exit : labels.enter;
  };

  const refreshMapSize = () => {
    const resize = () => window.dispatchEvent(new Event('resize'));
    window.requestAnimationFrame(() => {
      resize();
      window.requestAnimationFrame(resize);
    });
    window.setTimeout(resize, 120);
    window.setTimeout(resize, 320);
  };

  const enterPseudo = () => {
    target.classList.add('is-map-pseudo-fullscreen');
    document.body.classList.add('map-pseudo-fullscreen-open');
    sync();
    refreshMapSize();
  };

  const exitPseudo = () => {
    target.classList.remove('is-map-pseudo-fullscreen');
    document.body.classList.remove('map-pseudo-fullscreen-open');
    sync();
    refreshMapSize();
  };

  const enter = async () => {
    try {
      if (typeof target.requestFullscreen === 'function') {
        await target.requestFullscreen();
        return;
      }
      if (typeof target.webkitRequestFullscreen === 'function') {
        target.webkitRequestFullscreen();
        return;
      }
    } catch (_) {
      // Fall back to a fixed full-viewport layout.
    }
    enterPseudo();
  };

  const exit = async () => {
    if (pseudoActive()) {
      exitPseudo();
      return;
    }

    try {
      if (typeof document.exitFullscreen === 'function' && document.fullscreenElement) {
        await document.exitFullscreen();
        return;
      }
      if (typeof document.webkitExitFullscreen === 'function' && document.webkitFullscreenElement) {
        document.webkitExitFullscreen();
      }
    } catch (_) {
      // Browser exit errors are non-fatal; UI will resync on change.
    }
  };

  button.addEventListener('click', () => {
    if (active()) {
      exit();
    } else {
      enter();
    }
  });

  document.addEventListener('fullscreenchange', () => {
    sync();
    refreshMapSize();
  });
  document.addEventListener('webkitfullscreenchange', () => {
    sync();
    refreshMapSize();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && pseudoActive()) {
      exitPseudo();
    }
  });

  sync();
})();

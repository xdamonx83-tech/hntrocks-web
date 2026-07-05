(() => {
  const sheet = document.querySelector('[data-rework-mobile-menu-sheet]');
  const overlay = document.querySelector('[data-rework-mobile-menu-overlay]');
  const openButton = document.querySelector('[data-rework-mobile-menu-open]');
  const closeButton = document.querySelector('[data-rework-mobile-menu-close]');

  if (!sheet || !overlay || !openButton) return;

  const openSheet = () => {
    sheet.removeAttribute('inert');
    overlay.hidden = false;

    document.body.classList.add('rework-mobile-menu-open');
    openButton.setAttribute('aria-expanded', 'true');
    sheet.setAttribute('aria-hidden', 'false');

    window.requestAnimationFrame(() => {
      overlay.classList.add('is-open');
      sheet.classList.add('is-open');
    });
  };

  const closeSheet = () => {
    if (sheet.contains(document.activeElement)) {
      try {
        openButton.focus({ preventScroll: true });
      } catch (_) {
        openButton.focus();
      }
    }

    overlay.classList.remove('is-open');
    sheet.classList.remove('is-open');
    document.body.classList.remove('rework-mobile-menu-open');
    openButton.setAttribute('aria-expanded', 'false');
    sheet.setAttribute('aria-hidden', 'true');
    sheet.setAttribute('inert', '');

    window.setTimeout(() => {
      if (!sheet.classList.contains('is-open')) {
        overlay.hidden = true;
      }
    }, 240);
  };

  sheet.setAttribute('inert', '');

  openButton.addEventListener('click', (event) => {
    event.preventDefault();
    openSheet();
  });

  overlay.addEventListener('click', closeSheet);
  closeButton?.addEventListener('click', closeSheet);

  sheet.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', closeSheet);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && sheet.classList.contains('is-open')) {
      closeSheet();
    }
  });
})();

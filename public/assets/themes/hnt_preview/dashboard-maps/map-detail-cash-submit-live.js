(() => {
  'use strict';

  const configNode = document.getElementById('hntMapConfig');
  const form = document.querySelector('[data-map-cash-spot-form]');
  const modal = document.querySelector('[data-map-cash-spot-modal]');
  const toggle = document.querySelector('[data-map-cash-spot-toggle]');
  const status = form?.querySelector('[data-map-cash-spot-status]');
  const submit = form?.querySelector('button[type="submit"]');
  const imageInput = form?.querySelector('input[name="image"]');
  const toast = document.querySelector('[data-map-toast]');

  if (!(configNode instanceof HTMLElement)
      || !(form instanceof HTMLFormElement)
      || !(modal instanceof HTMLElement)
      || !(toggle instanceof HTMLButtonElement)
      || !(status instanceof HTMLElement)
      || !(submit instanceof HTMLButtonElement)
      || !(imageInput instanceof HTMLInputElement)) {
    return;
  }

  let config = {};
  try {
    config = JSON.parse(configNode.textContent || '{}');
  } catch (_) {
    return;
  }

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const copy = isEnglish ? {
    choose: 'Select a position on the map',
    invalidPosition: 'Please select a valid position on the map first.',
    imageRequired: 'Please select a screenshot.',
    imageType: 'Only JPG, PNG and WebP images are allowed.',
    imageSize: 'The screenshot may not exceed 5 MB.',
    running: config.cashSpotRunningText || 'Uploading cash spot …',
    pending: config.cashSpotPendingText || 'Cash spot submitted and awaiting review.',
    error: config.cashSpotErrorText || 'The cash spot could not be submitted.',
  } : {
    choose: 'Position auf der Karte auswählen',
    invalidPosition: 'Wähle zuerst eine gültige Position auf der Karte aus.',
    imageRequired: 'Wähle bitte einen Screenshot aus.',
    imageType: 'Erlaubt sind nur JPG-, PNG- und WebP-Bilder.',
    imageSize: 'Der Screenshot darf höchstens 5 MB groß sein.',
    running: config.cashSpotRunningText || 'Cash Spot wird hochgeladen …',
    pending: config.cashSpotPendingText || 'Cash Spot wurde eingereicht und wartet auf Prüfung.',
    error: config.cashSpotErrorText || 'Der Cash Spot konnte nicht eingereicht werden.',
  };

  const setStatus = (message = '', state = '') => {
    status.textContent = message;
    if (state) {
      status.dataset.state = state;
    } else {
      delete status.dataset.state;
    }
  };

  const showToast = (message) => {
    if (!(toast instanceof HTMLElement)) return;

    toast.textContent = message;
    toast.hidden = false;
    toast.classList.add('is-visible');
    window.setTimeout(() => {
      toast.classList.remove('is-visible');
      toast.hidden = true;
    }, 4200);
  };

  const syncModeUi = () => {
    const active = toggle.getAttribute('aria-pressed') === 'true';
    toggle.classList.toggle('is-cash-mode-active', active);
    toggle.title = active ? copy.choose : '';
  };

  toggle.addEventListener('click', () => window.requestAnimationFrame(syncModeUi));
  new MutationObserver(syncModeUi).observe(toggle, {
    attributes: true,
    attributeFilter: ['aria-pressed'],
  });

  imageInput.addEventListener('change', () => setStatus());

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    event.stopImmediatePropagation();

    if (submit.dataset.cashSubmitBusy === '1') return;

    const x = Number(form.elements.x?.value);
    const y = Number(form.elements.y?.value);
    const width = Number(config.width);
    const height = Number(config.height);
    const image = imageInput.files?.[0];

    if (!Number.isFinite(x) || !Number.isFinite(y)
        || x < 0 || y < 0
        || (Number.isFinite(width) && x > width)
        || (Number.isFinite(height) && y > height)) {
      setStatus(copy.invalidPosition, 'error');
      return;
    }

    if (!image) {
      setStatus(copy.imageRequired, 'error');
      imageInput.focus();
      return;
    }

    const allowedTypes = new Set(['image/jpeg', 'image/png', 'image/webp']);
    const allowedExtension = /\.(jpe?g|png|webp)$/i.test(image.name || '');
    if ((image.type && !allowedTypes.has(image.type)) || (!image.type && !allowedExtension)) {
      setStatus(copy.imageType, 'error');
      return;
    }

    if (image.size > 5 * 1024 * 1024) {
      setStatus(copy.imageSize, 'error');
      return;
    }

    if (!config.cashSpotSubmissionUrl) {
      setStatus(copy.error, 'error');
      return;
    }

    submit.dataset.cashSubmitBusy = '1';
    submit.disabled = true;
    submit.setAttribute('aria-busy', 'true');
    setStatus(copy.running, 'loading');

    try {
      const response = await fetch(config.cashSpotSubmissionUrl, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          'X-Requested-With': 'XMLHttpRequest',
        },
        body: new FormData(form),
        credentials: 'same-origin',
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || payload.ok !== true) {
        const errors = payload.errors || {};
        const firstError = Object.values(errors).flat().find((value) => typeof value === 'string');
        throw new Error(firstError || payload.message || copy.error);
      }

      setStatus(copy.pending, 'success');
      showToast(copy.pending);

      window.setTimeout(() => {
        modal.querySelector('[data-map-cash-spot-cancel]')?.click();
        syncModeUi();
      }, 350);
    } catch (error) {
      setStatus(error instanceof Error && error.message ? error.message : copy.error, 'error');
    } finally {
      delete submit.dataset.cashSubmitBusy;
      submit.disabled = false;
      submit.removeAttribute('aria-busy');
    }
  }, true);

  syncModeUi();
})();

(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const titleField = document.querySelector('[data-manage-edit] .hnt-moment-manage-field:first-child');
  const composerForm = document.querySelector('.hnt-moment-composer-form');
  const manageForm = document.querySelector('.hnt-moment-manage-body');
  let deleteSlide = null;

  const labels = isEnglish ? {
    format: 'Format',
    portrait: 'Portrait 9:16',
    landscape: 'Landscape 16:9',
    feed: 'Also publish in feed',
    feedHelp: 'Creates a regular feed post using the same uploaded video.',
  } : {
    format: 'Format',
    portrait: 'Hochformat 9:16',
    landscape: 'Querformat 16:9',
    feed: 'Zusätzlich im Feed posten',
    feedHelp: 'Erstellt mit demselben Video zusätzlich einen normalen Beitrag.',
  };

  if (titleField) {
    titleField.style.display = 'none';
  }

  const fields = composerForm?.querySelector('.hnt-moment-composer-fields');
  if (fields && !fields.querySelector('[data-hnt-moment-publish-options]')) {
    const options = document.createElement('section');
    options.className = 'hnt-moment-publish-options';
    options.dataset.hntMomentPublishOptions = '1';
    options.innerHTML = `
      <div class="hnt-moment-format-field">
        <span>${labels.format}</span>
        <div class="hnt-moment-format-choice" role="radiogroup" aria-label="${labels.format}">
          <label><input type="radio" name="aspect_ratio" value="9:16" checked><span>${labels.portrait}</span></label>
          <label><input type="radio" name="aspect_ratio" value="16:9"><span>${labels.landscape}</span></label>
        </div>
      </div>
      <label class="hnt-moment-feed-toggle">
        <input type="checkbox" name="publish_to_feed" value="1">
        <span class="hnt-moment-feed-switch" aria-hidden="true"><i></i></span>
        <span><strong>${labels.feed}</strong><small>${labels.feedHelp}</small></span>
      </label>`;

    const coverField = fields.querySelector('input[name="cover"]')?.closest('.hnt-moment-composer-field');
    fields.insertBefore(options, coverField || null);
  }

  const preview = composerForm?.querySelector('.hnt-moment-upload-preview');
  let previewPlayer = preview?.closest('.hnt-composer-video-player') || null;
  if (preview && !previewPlayer) {
    previewPlayer = document.createElement('div');
    previewPlayer.className = 'hnt-composer-video-player is-ratio-9-16';
    previewPlayer.dataset.aspectRatio = '9:16';
    preview.parentNode?.insertBefore(previewPlayer, preview);
    previewPlayer.appendChild(preview);
  }

  composerForm?.querySelectorAll('input[name="aspect_ratio"]').forEach((input) => {
    input.addEventListener('change', () => {
      if (!input.checked || !previewPlayer) return;
      const ratio = input.value === '16:9' ? '16:9' : '9:16';
      previewPlayer.dataset.aspectRatio = ratio;
      previewPlayer.classList.toggle('is-ratio-16-9', ratio === '16:9');
      previewPlayer.classList.toggle('is-ratio-9-16', ratio !== '16:9');
      document.dispatchEvent(new CustomEvent('hnt:video-player-refresh'));
    });
  });

  if (composerForm && !composerForm.querySelector('.hnt-moment-composer-scroll')) {
    const footer = composerForm.querySelector('.hnt-moment-composer-footer');
    const scrollArea = document.createElement('div');
    scrollArea.className = 'hnt-moment-composer-scroll';

    [...composerForm.children].forEach((child) => {
      if (child !== footer) scrollArea.appendChild(child);
    });

    composerForm.insertBefore(scrollArea, footer || null);
    scrollArea.addEventListener('wheel', (event) => event.stopPropagation(), { passive: true });
    scrollArea.addEventListener('touchmove', (event) => event.stopPropagation(), { passive: true });
  }

  document.dispatchEvent(new CustomEvent('hnt:video-player-refresh'));

  document.addEventListener('click', (event) => {
    if (event.target.closest('[data-moment-option="edit"]')) {
      window.setTimeout(() => {
        const heading = document.getElementById('hntMomentManageTitle');
        if (heading) heading.textContent = isEnglish ? 'Edit description' : 'Beschreibung bearbeiten';
      }, 0);
    }

    const deleteOption = event.target.closest('[data-moment-option="delete"]');
    if (deleteOption) {
      deleteSlide = deleteOption.closest('.moment-slide');
    }
  });

  manageForm?.addEventListener('submit', async (event) => {
    const deleteSection = manageForm.querySelector('[data-manage-delete]');
    if (!deleteSection || deleteSection.hidden || !deleteSlide) return;

    event.preventDefault();
    event.stopImmediatePropagation();

    const submit = manageForm.querySelector('.hnt-moment-manage-submit');
    const status = manageForm.querySelector('.hnt-moment-manage-status');
    const url = deleteSlide.dataset.shareUrl;

    if (!url) return;

    if (submit) submit.disabled = true;
    if (status) {
      status.classList.remove('is-error');
      status.textContent = isEnglish ? 'Deleting …' : 'Wird gelöscht …';
    }

    try {
      const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        cache: 'no-store',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrf,
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
        },
        body: new URLSearchParams({ _method: 'DELETE' }).toString(),
      });

      if (!response.ok) {
        let payload = {};
        try {
          payload = await response.json();
        } catch (_error) {
          payload = {};
        }
        throw new Error(payload.message || (isEnglish ? 'The moment could not be deleted.' : 'Der Moment konnte nicht gelöscht werden.'));
      }

      window.location.assign('/moments');
    } catch (error) {
      if (status) {
        status.classList.add('is-error');
        status.textContent = error.message || (isEnglish ? 'The moment could not be deleted.' : 'Der Moment konnte nicht gelöscht werden.');
      }
      if (submit) submit.disabled = false;
    }
  }, true);
})();

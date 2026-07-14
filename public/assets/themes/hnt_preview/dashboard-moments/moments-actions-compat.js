(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const titleField = document.querySelector('[data-manage-edit] .hnt-moment-manage-field:first-child');
  const composerForm = document.querySelector('.hnt-moment-composer-form');
  const manageForm = document.querySelector('.hnt-moment-manage-body');
  let deleteSlide = null;

  if (titleField) {
    titleField.style.display = 'none';
  }

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

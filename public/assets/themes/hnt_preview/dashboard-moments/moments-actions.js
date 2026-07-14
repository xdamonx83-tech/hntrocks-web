(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const viewerHandle = String(window.HNT_MOMENTS_VIEWER_HANDLE || '').trim().toLowerCase();
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const text = isEnglish ? {
    more: 'More',
    edit: 'Edit',
    remove: 'Delete',
    report: 'Report',
    editTitle: 'Edit moment',
    reportTitle: 'Report moment',
    deleteTitle: 'Delete moment',
    title: 'Title',
    description: 'Description',
    reason: 'Reason',
    details: 'Details',
    detailsPlaceholder: 'Describe the problem briefly …',
    cancel: 'Cancel',
    save: 'Save changes',
    sendReport: 'Send report',
    confirmDelete: 'Delete moment',
    deleteCopy: 'This moment will be permanently removed. This action cannot be undone.',
    saving: 'Saving …',
    deleting: 'Deleting …',
    reporting: 'Sending report …',
    saved: 'Moment updated.',
    deleted: 'Moment deleted.',
    reported: 'Report submitted.',
    failed: 'The action could not be completed.',
    reasons: {
      spam: 'Spam', abuse: 'Harassment', hate: 'Hate speech', nsfw: 'Adult content', fraud: 'Fraud', cheating: 'Cheating', privacy: 'Privacy', other: 'Other',
    },
  } : {
    more: 'Mehr',
    edit: 'Bearbeiten',
    remove: 'Löschen',
    report: 'Melden',
    editTitle: 'Moment bearbeiten',
    reportTitle: 'Moment melden',
    deleteTitle: 'Moment löschen',
    title: 'Titel',
    description: 'Beschreibung',
    reason: 'Grund',
    details: 'Details',
    detailsPlaceholder: 'Beschreibe das Problem kurz …',
    cancel: 'Abbrechen',
    save: 'Änderungen speichern',
    sendReport: 'Meldung senden',
    confirmDelete: 'Moment löschen',
    deleteCopy: 'Dieser Moment wird dauerhaft entfernt. Das kann nicht rückgängig gemacht werden.',
    saving: 'Wird gespeichert …',
    deleting: 'Wird gelöscht …',
    reporting: 'Meldung wird gesendet …',
    saved: 'Moment aktualisiert.',
    deleted: 'Moment gelöscht.',
    reported: 'Meldung gesendet.',
    failed: 'Die Aktion konnte nicht ausgeführt werden.',
    reasons: {
      spam: 'Spam', abuse: 'Belästigung', hate: 'Hassrede', nsfw: 'Nicht jugendfrei', fraud: 'Betrug', cheating: 'Cheating', privacy: 'Privatsphäre', other: 'Sonstiges',
    },
  };

  const toast = (message) => {
    if (typeof window.showToast === 'function') {
      window.showToast(message);
      return;
    }
    const node = document.getElementById('toast');
    if (!node) return;
    node.textContent = message;
    node.classList.add('show');
    window.clearTimeout(toast.timer);
    toast.timer = window.setTimeout(() => node.classList.remove('show'), 1900);
  };

  const escapeHtml = (value = '') => String(value).replace(/[&<>"']/g, (character) => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;',
  })[character]);

  const requestJson = async (url, options = {}) => {
    const response = await fetch(url, {
      credentials: 'same-origin',
      cache: 'no-store',
      ...options,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
        ...(options.headers || {}),
      },
    });

    let payload = {};
    try {
      payload = await response.json();
    } catch (_error) {
      payload = {};
    }

    if (!response.ok) {
      const validation = payload.errors ? Object.values(payload.errors).flat()[0] : null;
      throw new Error(validation || payload.message || text.failed);
    }

    return payload;
  };

  const normalizeHandle = (value = '') => String(value).trim().toLowerCase();

  const extractTags = (value = '') => {
    const matches = String(value).match(/#[\p{L}\p{N}_-]+/gu) || [];
    return [...new Set(matches.map((tag) => tag.trim()))].slice(0, 4);
  };

  const renderTags = (slide) => {
    const container = slide.querySelector('.moment-tags');
    if (!container) return;

    const tags = extractTags(`${slide.dataset.caption || ''} ${slide.dataset.description || ''}`);
    const visibleTags = tags.length ? tags : ['#HNTMoment'];
    container.innerHTML = visibleTags.map((tag) => {
      const slug = tag.slice(1);
      return `<a href="/hashtags/${encodeURIComponent(slug)}">${escapeHtml(tag)}</a>`;
    }).join('');
  };

  const dotsIcon = `
    <svg viewBox="0 0 24 24" aria-hidden="true">
      <circle cx="5" cy="12" r="1.7"></circle>
      <circle cx="12" cy="12" r="1.7"></circle>
      <circle cx="19" cy="12" r="1.7"></circle>
    </svg>`;

  const pencilIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 20l4.6-1 10-10a2.1 2.1 0 0 0-3-3l-10 10L4 20Z"></path><path d="m14.5 7.5 3 3"></path></svg>';
  const trashIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V4h6v3M7 7l1 13h8l1-13M10 11v5M14 11v5"></path></svg>';
  const reportIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 21V4m0 1h10l-2 4 2 4H6"></path></svg>';

  let activeSlide = null;
  let activeMode = '';

  const backdrop = document.createElement('div');
  backdrop.className = 'hnt-moment-manage-backdrop';
  backdrop.setAttribute('aria-hidden', 'true');
  backdrop.innerHTML = `
    <section class="hnt-moment-manage-modal" role="dialog" aria-modal="true" aria-labelledby="hntMomentManageTitle">
      <header class="hnt-moment-manage-head">
        <div><span>HNT.ROCKS</span><h2 id="hntMomentManageTitle"></h2></div>
        <button class="hnt-moment-manage-close" type="button" aria-label="${escapeHtml(text.cancel)}">×</button>
      </header>
      <form class="hnt-moment-manage-body">
        <div data-manage-edit hidden>
          <label class="hnt-moment-manage-field"><span>${escapeHtml(text.title)}</span><input name="caption" maxlength="220"></label>
          <label class="hnt-moment-manage-field"><span>${escapeHtml(text.description)}</span><textarea name="description" maxlength="2000"></textarea></label>
        </div>
        <div data-manage-report hidden>
          <label class="hnt-moment-manage-field"><span>${escapeHtml(text.reason)}</span><select name="reason">${Object.entries(text.reasons).map(([value, label]) => `<option value="${value}">${escapeHtml(label)}</option>`).join('')}</select></label>
          <label class="hnt-moment-manage-field"><span>${escapeHtml(text.details)}</span><textarea name="body" maxlength="2000" placeholder="${escapeHtml(text.detailsPlaceholder)}"></textarea></label>
        </div>
        <div data-manage-delete hidden><p class="hnt-moment-manage-copy">${escapeHtml(text.deleteCopy)}</p></div>
        <div class="hnt-moment-manage-status" role="status"></div>
        <div class="hnt-moment-manage-actions">
          <button class="hnt-moment-manage-cancel" type="button">${escapeHtml(text.cancel)}</button>
          <button class="hnt-moment-manage-submit" type="submit"></button>
        </div>
      </form>
    </section>`;
  document.body.appendChild(backdrop);

  const form = backdrop.querySelector('form');
  const modalTitle = backdrop.querySelector('h2');
  const status = backdrop.querySelector('.hnt-moment-manage-status');
  const submit = backdrop.querySelector('.hnt-moment-manage-submit');
  const editSection = backdrop.querySelector('[data-manage-edit]');
  const reportSection = backdrop.querySelector('[data-manage-report]');
  const deleteSection = backdrop.querySelector('[data-manage-delete]');

  const setStatus = (message = '', isError = false) => {
    status.textContent = message;
    status.classList.toggle('is-error', isError);
  };

  const closeModal = () => {
    backdrop.classList.remove('is-open');
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hnt-moment-manage-open');
    activeSlide = null;
    activeMode = '';
    form.reset();
    setStatus('');
    submit.disabled = false;
    submit.classList.remove('is-danger');
  };

  const openModal = (slide, mode) => {
    activeSlide = slide;
    activeMode = mode;
    editSection.hidden = mode !== 'edit';
    reportSection.hidden = mode !== 'report';
    deleteSection.hidden = mode !== 'delete';
    setStatus('');

    if (mode === 'edit') {
      modalTitle.textContent = text.editTitle;
      submit.textContent = text.save;
      form.elements.caption.value = slide.dataset.caption || '';
      form.elements.description.value = slide.dataset.description || '';
    } else if (mode === 'report') {
      modalTitle.textContent = text.reportTitle;
      submit.textContent = text.sendReport;
    } else {
      modalTitle.textContent = text.deleteTitle;
      submit.textContent = text.confirmDelete;
      submit.classList.add('is-danger');
    }

    backdrop.classList.add('is-open');
    backdrop.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hnt-moment-manage-open');
    window.setTimeout(() => backdrop.querySelector('input, textarea, select, .hnt-moment-manage-submit')?.focus(), 40);
  };

  const closeMenus = (except = null) => {
    document.querySelectorAll('.moment-options.is-open').forEach((menu) => {
      if (menu === except) return;
      menu.classList.remove('is-open');
      menu.querySelector('.moment-options-trigger')?.setAttribute('aria-expanded', 'false');
    });
  };

  document.querySelectorAll('.moment-slide[data-moment-id]').forEach((slide) => {
    renderTags(slide);

    const rail = slide.querySelector('.moment-action-rail');
    const saveButton = slide.querySelector('.moment-live-save');
    if (!rail || !saveButton || rail.querySelector('.moment-options')) return;

    const isOwn = viewerHandle !== '' && normalizeHandle(slide.dataset.authorHandle) === viewerHandle;
    const wrapper = document.createElement('div');
    wrapper.className = 'moment-options';
    wrapper.innerHTML = `
      <button class="moment-action moment-options-trigger" type="button" aria-label="${escapeHtml(text.more)}" aria-expanded="false">
        <span>${dotsIcon}</span><b>${escapeHtml(text.more)}</b>
      </button>
      <div class="moment-options-menu" role="menu">
        ${isOwn
          ? `<button data-moment-option="edit" type="button">${pencilIcon}<span>${escapeHtml(text.edit)}</span></button><button class="is-danger" data-moment-option="delete" type="button">${trashIcon}<span>${escapeHtml(text.remove)}</span></button>`
          : `<button data-moment-option="report" type="button">${reportIcon}<span>${escapeHtml(text.report)}</span></button>`}
      </div>`;
    saveButton.insertAdjacentElement('afterend', wrapper);
  });

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('.moment-options-trigger');
    if (trigger) {
      const wrapper = trigger.closest('.moment-options');
      const willOpen = !wrapper.classList.contains('is-open');
      closeMenus(wrapper);
      wrapper.classList.toggle('is-open', willOpen);
      trigger.setAttribute('aria-expanded', String(willOpen));
      event.stopPropagation();
      return;
    }

    const option = event.target.closest('[data-moment-option]');
    if (option) {
      const slide = option.closest('.moment-slide');
      const mode = option.dataset.momentOption;
      closeMenus();
      openModal(slide, mode);
      return;
    }

    if (!event.target.closest('.moment-options-menu')) closeMenus();
  });

  backdrop.querySelector('.hnt-moment-manage-close')?.addEventListener('click', closeModal);
  backdrop.querySelector('.hnt-moment-manage-cancel')?.addEventListener('click', closeModal);
  backdrop.addEventListener('click', (event) => {
    if (event.target === backdrop) closeModal();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;
    closeMenus();
    if (backdrop.classList.contains('is-open')) closeModal();
  });

  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (!activeSlide || !activeMode) return;

    submit.disabled = true;

    try {
      if (activeMode === 'edit') {
        setStatus(text.saving);
        const caption = form.elements.caption.value.trim();
        const description = form.elements.description.value.trim();
        await requestJson(activeSlide.dataset.shareUrl, {
          method: 'PATCH',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ caption: caption || null, description: description || null }),
        });

        activeSlide.dataset.caption = caption;
        activeSlide.dataset.description = description;
        const titleNode = activeSlide.querySelector('.moment-caption h2');
        if (titleNode) titleNode.textContent = caption || description || 'HNT Moment';

        let descriptionNode = activeSlide.querySelector('.moment-caption > p');
        const tagsNode = activeSlide.querySelector('.moment-tags');
        if (description && description !== caption) {
          if (!descriptionNode && tagsNode) {
            descriptionNode = document.createElement('p');
            tagsNode.insertAdjacentElement('beforebegin', descriptionNode);
          }
          if (descriptionNode) descriptionNode.textContent = description;
        } else {
          descriptionNode?.remove();
        }

        renderTags(activeSlide);
        closeModal();
        toast(text.saved);
        return;
      }

      if (activeMode === 'delete') {
        setStatus(text.deleting);
        await requestJson(activeSlide.dataset.shareUrl, { method: 'DELETE' });
        toast(text.deleted);
        window.setTimeout(() => window.location.reload(), 350);
        return;
      }

      setStatus(text.reporting);
      await requestJson('/reports', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          type: 'moment',
          id: Number(activeSlide.dataset.momentId),
          reason: form.elements.reason.value,
          body: form.elements.body.value.trim() || null,
        }),
      });
      closeModal();
      toast(text.reported);
    } catch (error) {
      setStatus(error.message || text.failed, true);
      submit.disabled = false;
    }
  });
})();

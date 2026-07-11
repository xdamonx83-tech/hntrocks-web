/* Visible upload state for image comments in the isolated dashboard preview. */
(() => {
  if (!window.fetch || window.__hntCommentUploadProgressInstalled) return;
  window.__hntCommentUploadProgressInstalled = true;

  const style = document.createElement('style');
  style.id = 'real-comment-upload-progress-styles';
  style.textContent = `
    .real-comment-upload-status {
      display: flex;
      align-items: center;
      gap: 10px;
      margin: 9px 0 2px;
      padding: 9px 11px;
      border: 1px solid rgba(214, 208, 188, .85);
      border-radius: 11px;
      background: rgba(255, 253, 246, .96);
      color: #555047;
      font-size: 12px;
      font-weight: 700;
    }

    .real-comment-upload-status[hidden] {
      display: none !important;
    }

    .real-comment-upload-status__track {
      position: relative;
      flex: 1;
      height: 6px;
      overflow: hidden;
      border-radius: 999px;
      background: #e4dfd2;
    }

    .real-comment-upload-status__bar {
      position: absolute;
      inset: 0 auto 0 -42%;
      width: 42%;
      border-radius: inherit;
      background: #d5a92f;
      animation: hntCommentUploadMove 1s ease-in-out infinite;
    }

    .real-comment-upload-status.is-done .real-comment-upload-status__bar {
      inset: 0;
      width: 100%;
      animation: none;
    }

    .real-comment-upload-status.is-error {
      border-color: rgba(184, 70, 58, .35);
      color: #9e3f35;
    }

    .real-comment-upload-status.is-error .real-comment-upload-status__bar {
      inset: 0;
      width: 100%;
      animation: none;
      background: #b8463a;
    }

    .real-comment-upload-status__label {
      min-width: max-content;
    }

    .real-comment-upload-busy textarea,
    .real-comment-upload-busy .real-comment-media-trigger,
    .real-comment-upload-busy .real-comment-upload-remove {
      pointer-events: none;
      opacity: .62;
    }

    @keyframes hntCommentUploadMove {
      from { transform: translateX(0); }
      to { transform: translateX(338%); }
    }
  `;
  document.head.appendChild(style);

  let pendingForm = null;
  let activeForm = null;
  let hideTimer = 0;

  const hasSelectedMedia = (form) => Boolean(
    form?.querySelector('.real-comment-upload-preview:not([hidden]) .real-comment-upload-item')
  );

  const statusFor = (form) => {
    let status = form.querySelector(':scope > .real-comment-upload-status, .comments-input-shell > .real-comment-upload-status');
    if (status) return status;

    status = document.createElement('div');
    status.className = 'real-comment-upload-status';
    status.hidden = true;
    status.setAttribute('role', 'status');
    status.setAttribute('aria-live', 'polite');
    status.innerHTML = `
      <span class="real-comment-upload-status__label">Bild wird hochgeladen …</span>
      <span class="real-comment-upload-status__track" aria-hidden="true">
        <span class="real-comment-upload-status__bar"></span>
      </span>
    `;

    if (form.id === 'commentsComposer') {
      const shell = form.querySelector('.comments-input-shell');
      const actions = shell?.querySelector('.comments-compose-actions');
      if (shell && actions) shell.insertBefore(status, actions);
      else form.appendChild(status);
    } else {
      const footer = form.querySelector('footer');
      if (footer) form.insertBefore(status, footer);
      else form.appendChild(status);
    }

    return status;
  };

  const setState = (form, mode) => {
    if (!(form instanceof HTMLFormElement)) return;
    window.clearTimeout(hideTimer);

    const status = statusFor(form);
    const label = status.querySelector('.real-comment-upload-status__label');
    status.hidden = false;
    status.classList.remove('is-done', 'is-error');

    if (mode === 'loading') {
      form.classList.add('real-comment-upload-busy');
      if (label) label.textContent = 'Bild wird hochgeladen …';
      return;
    }

    form.classList.remove('real-comment-upload-busy');

    if (mode === 'done') {
      status.classList.add('is-done');
      if (label) label.textContent = 'Veröffentlicht';
      hideTimer = window.setTimeout(() => {
        status.hidden = true;
        status.classList.remove('is-done');
      }, 650);
      return;
    }

    status.classList.add('is-error');
    if (label) label.textContent = 'Upload fehlgeschlagen';
    hideTimer = window.setTimeout(() => {
      status.hidden = true;
      status.classList.remove('is-error');
    }, 2200);
  };

  document.addEventListener('pointerdown', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;
    const submit = target.closest('#commentsComposer [type="submit"], .real-comment-reply-form [type="submit"]');
    const form = submit?.closest('form');
    pendingForm = form && hasSelectedMedia(form) ? form : null;
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form instanceof HTMLFormElement && hasSelectedMedia(form)) pendingForm = form;
  }, true);

  const originalFetch = window.fetch.bind(window);
  window.fetch = async (input, init = {}) => {
    const url = typeof input === 'string' ? input : input?.url || '';
    const body = init?.body;
    const isCommentUpload = /\/feed\/\d+\/comments(?:\?|$)/.test(url)
      && body instanceof FormData
      && body.getAll('media[]').length > 0;

    if (!isCommentUpload) return originalFetch(input, init);

    activeForm = pendingForm || document.activeElement?.closest?.('form') || null;
    pendingForm = null;
    if (activeForm) setState(activeForm, 'loading');

    try {
      const response = await originalFetch(input, init);
      if (activeForm) setState(activeForm, response.ok ? 'done' : 'error');
      return response;
    } catch (error) {
      if (activeForm) setState(activeForm, 'error');
      throw error;
    } finally {
      activeForm = null;
    }
  };
})();

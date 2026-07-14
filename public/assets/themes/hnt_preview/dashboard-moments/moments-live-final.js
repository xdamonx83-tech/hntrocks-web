(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const viewerHandle = String(window.HNT_MOMENTS_VIEWER_HANDLE || '').trim().toLowerCase();
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  const text = isEnglish ? {
    friends: 'Friends',
    createTitle: 'Create moment',
    createIntro: 'Upload a Hunt clip directly or open the full Studio for editing.',
    chooseVideo: 'Select a video',
    uploadHelp: 'MP4, WebM or MOV',
    caption: 'Title',
    captionPlaceholder: 'What happens in this moment?',
    description: 'Description',
    descriptionPlaceholder: 'Add context, hashtags or details …',
    visibility: 'Visibility',
    public: 'Public',
    registered: 'Members only',
    private: 'Private',
    cover: 'Optional cover',
    chooseCover: 'Select cover image',
    coverHelp: 'JPG, PNG or WebP',
    noCover: 'No image selected',
    removeCover: 'Remove',
    studio: 'Open Studio',
    cancel: 'Cancel',
    publish: 'Publish moment',
    uploading: 'Uploading …',
    selectVideo: 'Please select a video.',
    uploadError: 'The moment could not be uploaded.',
    close: 'Close',
  } : {
    friends: 'Befreundet',
    createTitle: 'Moment erstellen',
    createIntro: 'Lade einen Hunt-Clip direkt hoch oder öffne für die Bearbeitung das vollständige Studio.',
    chooseVideo: 'Video auswählen',
    uploadHelp: 'MP4, WebM oder MOV',
    caption: 'Titel',
    captionPlaceholder: 'Was passiert in diesem Moment?',
    description: 'Beschreibung',
    descriptionPlaceholder: 'Kontext, Hashtags oder Details ergänzen …',
    visibility: 'Sichtbarkeit',
    public: 'Öffentlich',
    registered: 'Nur Mitglieder',
    private: 'Privat',
    cover: 'Optionales Titelbild',
    chooseCover: 'Titelbild auswählen',
    coverHelp: 'JPG, PNG oder WebP',
    noCover: 'Kein Bild ausgewählt',
    removeCover: 'Entfernen',
    studio: 'Studio öffnen',
    cancel: 'Abbrechen',
    publish: 'Moment veröffentlichen',
    uploading: 'Wird hochgeladen …',
    selectVideo: 'Bitte zuerst ein Video auswählen.',
    uploadError: 'Der Moment konnte nicht hochgeladen werden.',
    close: 'Schließen',
  };

  const normalize = (value = '') => String(value).trim().replace(/\s+/g, ' ').toLowerCase();

  document.querySelectorAll('.moment-slide[data-moment-id]').forEach((slide) => {
    const creator = slide.querySelector('.moment-creator-action');
    const authorHandle = normalize(slide.dataset.authorHandle);
    const isOwnMoment = viewerHandle !== '' && authorHandle === viewerHandle;

    if (creator && !creator.querySelector('.moment-follow-button') && slide.dataset.following === '1' && !isOwnMoment) {
      const button = document.createElement('button');
      button.type = 'button';
      button.disabled = true;
      button.className = 'moment-follow-button is-friend';
      button.setAttribute('aria-label', text.friends);
      button.title = text.friends;
      button.innerHTML = '<svg aria-hidden="true"><use href="#i-check"></use></svg>';
      creator.appendChild(button);
    }

    const description = slide.querySelector('.moment-caption > p');
    const tags = slide.querySelector('.moment-tags');
    if (description && tags) {
      const descriptionText = normalize(description.textContent);
      const tagText = normalize(tags.textContent);
      const onlyHashtags = descriptionText !== '' && descriptionText.split(' ').every((part) => part.startsWith('#'));
      if (onlyHashtags || descriptionText === tagText) description.remove();
    }
  });

  const createLink = document.querySelector('.moments-create-button[href*="/moments/create"], .moments-empty-real a[href*="/moments/create"]');
  if (!createLink) return;

  const createUrl = new URL(createLink.href, window.location.origin);
  const storeUrl = new URL(createUrl.href);
  storeUrl.pathname = storeUrl.pathname.replace(/\/create\/?$/, '');
  storeUrl.search = '';
  storeUrl.hash = '';

  const backdrop = document.createElement('div');
  backdrop.className = 'hnt-moment-composer-backdrop';
  backdrop.id = 'hntMomentComposer';
  backdrop.setAttribute('aria-hidden', 'true');
  backdrop.innerHTML = `
    <section class="hnt-moment-composer" role="dialog" aria-modal="true" aria-labelledby="hntMomentComposerTitle">
      <header class="hnt-moment-composer-header">
        <div>
          <span class="hnt-moment-composer-kicker">HNT.ROCKS</span>
          <h2 id="hntMomentComposerTitle">${text.createTitle}</h2>
          <p>${text.createIntro}</p>
        </div>
        <button class="hnt-moment-composer-close" type="button" aria-label="${text.close}">×</button>
      </header>
      <form class="hnt-moment-composer-form" enctype="multipart/form-data" novalidate>
        <label class="hnt-moment-upload-zone">
          <input name="video" type="file" accept="video/mp4,video/webm,video/quicktime" required>
          <span class="hnt-moment-upload-placeholder">
            <span class="hnt-moment-upload-icon">+</span>
            <strong>${text.chooseVideo}</strong>
            <small>${text.uploadHelp}</small>
          </span>
          <video class="hnt-moment-upload-preview" muted playsinline controls></video>
        </label>
        <div class="hnt-moment-composer-fields">
          <label class="hnt-moment-composer-field">
            <span>${text.caption}</span>
            <input name="caption" type="text" maxlength="220" placeholder="${text.captionPlaceholder}">
          </label>
          <label class="hnt-moment-composer-field">
            <span>${text.visibility}</span>
            <select name="visibility">
              <option value="public">${text.public}</option>
              <option value="registered">${text.registered}</option>
              <option value="private">${text.private}</option>
            </select>
          </label>
          <label class="hnt-moment-composer-field is-wide">
            <span>${text.description}</span>
            <textarea name="description" maxlength="2000" placeholder="${text.descriptionPlaceholder}"></textarea>
          </label>
          <div class="hnt-moment-composer-field is-wide hnt-moment-cover-field">
            <span>${text.cover}</span>
            <input id="hntMomentCoverInput" name="cover" type="file" accept="image/jpeg,image/png,image/webp" hidden>
            <label class="hnt-moment-cover-picker" for="hntMomentCoverInput">
              <span class="hnt-moment-cover-picker-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M4 6.5h16v11H4z"></path><path d="m7 15 3.4-3.5 2.5 2.4 1.8-1.8L18 15"></path><circle cx="16.5" cy="9" r="1.4"></circle></svg>
              </span>
              <span class="hnt-moment-cover-picker-copy"><strong>${text.chooseCover}</strong><small>${text.coverHelp}</small></span>
              <span class="hnt-moment-cover-file-name" data-hnt-cover-file-name>${text.noCover}</span>
            </label>
            <div class="hnt-moment-cover-preview" data-hnt-cover-preview hidden>
              <img alt="" data-hnt-cover-preview-image>
              <button type="button" data-hnt-cover-remove>${text.removeCover}</button>
            </div>
          </div>
        </div>
        <div class="hnt-moment-upload-progress" aria-hidden="true"><i></i></div>
        <div class="hnt-moment-composer-status" role="status"></div>
        <footer class="hnt-moment-composer-footer">
          <a class="hnt-moment-composer-studio" href="${createUrl.href}">${text.studio}</a>
          <div>
            <button class="hnt-moment-composer-cancel" type="button">${text.cancel}</button>
            <button class="hnt-moment-composer-submit" type="submit">${text.publish}</button>
          </div>
        </footer>
      </form>
    </section>`;
  document.body.appendChild(backdrop);

  const form = backdrop.querySelector('form');
  const videoInput = form.querySelector('input[name="video"]');
  const coverInput = form.querySelector('input[name="cover"]');
  const coverFileName = form.querySelector('[data-hnt-cover-file-name]');
  const coverPreview = form.querySelector('[data-hnt-cover-preview]');
  const coverPreviewImage = form.querySelector('[data-hnt-cover-preview-image]');
  const coverRemove = form.querySelector('[data-hnt-cover-remove]');
  const uploadZone = backdrop.querySelector('.hnt-moment-upload-zone');
  const preview = backdrop.querySelector('.hnt-moment-upload-preview');
  const status = backdrop.querySelector('.hnt-moment-composer-status');
  const progress = backdrop.querySelector('.hnt-moment-upload-progress > i');
  const submit = backdrop.querySelector('.hnt-moment-composer-submit');
  let previewUrl = '';
  let coverPreviewUrl = '';

  const setStatus = (message = '', isError = false) => {
    status.textContent = message;
    status.classList.toggle('is-error', isError);
  };

  const resetPreview = () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = '';
    preview.removeAttribute('src');
    uploadZone.classList.remove('has-preview');
  };

  const showVideo = (file) => {
    resetPreview();
    if (!file) return;
    previewUrl = URL.createObjectURL(file);
    preview.src = previewUrl;
    uploadZone.classList.add('has-preview');
    preview.load();
  };

  const resetCover = ({ clearInput = false } = {}) => {
    if (coverPreviewUrl) URL.revokeObjectURL(coverPreviewUrl);
    coverPreviewUrl = '';
    if (coverPreviewImage) coverPreviewImage.removeAttribute('src');
    if (coverPreview) coverPreview.hidden = true;
    if (coverFileName) coverFileName.textContent = text.noCover;
    if (clearInput && coverInput) coverInput.value = '';
  };

  const showCover = (file) => {
    resetCover();
    if (!file) return;
    coverPreviewUrl = URL.createObjectURL(file);
    if (coverPreviewImage) coverPreviewImage.src = coverPreviewUrl;
    if (coverPreview) coverPreview.hidden = false;
    if (coverFileName) coverFileName.textContent = file.name;
  };

  const open = () => {
    backdrop.classList.add('is-open');
    backdrop.setAttribute('aria-hidden', 'false');
    document.body.classList.add('hnt-moment-composer-open');
    window.setTimeout(() => videoInput.focus(), 40);
  };

  const close = () => {
    backdrop.classList.remove('is-open');
    backdrop.setAttribute('aria-hidden', 'true');
    document.body.classList.remove('hnt-moment-composer-open');
    form.reset();
    progress.style.width = '0%';
    setStatus('');
    resetPreview();
    resetCover();
  };

  document.addEventListener('click', (event) => {
    const link = event.target.closest('.moments-create-button[href*="/moments/create"], .moments-empty-real a[href*="/moments/create"]');
    if (!link) return;
    event.preventDefault();
    open();
  });

  backdrop.querySelector('.hnt-moment-composer-close')?.addEventListener('click', close);
  backdrop.querySelector('.hnt-moment-composer-cancel')?.addEventListener('click', close);
  backdrop.addEventListener('click', (event) => {
    if (event.target === backdrop) close();
  });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && backdrop.classList.contains('is-open')) close();
  });

  videoInput.addEventListener('change', () => showVideo(videoInput.files?.[0]));
  coverInput?.addEventListener('change', () => showCover(coverInput.files?.[0]));
  coverRemove?.addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    resetCover({ clearInput: true });
  });

  ['dragenter', 'dragover'].forEach((name) => uploadZone.addEventListener(name, (event) => {
    event.preventDefault();
    uploadZone.classList.add('is-dragging');
  }));
  ['dragleave', 'drop'].forEach((name) => uploadZone.addEventListener(name, (event) => {
    event.preventDefault();
    uploadZone.classList.remove('is-dragging');
  }));
  uploadZone.addEventListener('drop', (event) => {
    const file = [...(event.dataTransfer?.files || [])].find((entry) => entry.type.startsWith('video/'));
    if (!file) return;
    const transfer = new DataTransfer();
    transfer.items.add(file);
    videoInput.files = transfer.files;
    showVideo(file);
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    if (!videoInput.files?.length) {
      setStatus(text.selectVideo, true);
      return;
    }

    const data = new FormData(form);
    const xhr = new XMLHttpRequest();
    submit.disabled = true;
    setStatus(text.uploading);
    progress.style.width = '0%';

    xhr.open('POST', storeUrl.href);
    xhr.setRequestHeader('Accept', 'application/json');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    if (csrf) xhr.setRequestHeader('X-CSRF-TOKEN', csrf);

    xhr.upload.addEventListener('progress', (progressEvent) => {
      if (!progressEvent.lengthComputable) return;
      const percent = Math.max(0, Math.min(100, Math.round((progressEvent.loaded / progressEvent.total) * 100)));
      progress.style.width = `${percent}%`;
      setStatus(`${text.uploading} ${percent}%`);
    });

    xhr.addEventListener('load', () => {
      let payload = {};
      try {
        payload = JSON.parse(xhr.responseText || '{}');
      } catch (_error) {
        payload = {};
      }

      if (xhr.status >= 200 && xhr.status < 300) {
        progress.style.width = '100%';
        window.location.assign(payload.redirect_url || window.location.href);
        return;
      }

      const firstError = payload.errors ? Object.values(payload.errors).flat()[0] : null;
      setStatus(firstError || payload.message || text.uploadError, true);
      submit.disabled = false;
    });

    xhr.addEventListener('error', () => {
      setStatus(text.uploadError, true);
      submit.disabled = false;
    });

    xhr.send(data);
  });
})();

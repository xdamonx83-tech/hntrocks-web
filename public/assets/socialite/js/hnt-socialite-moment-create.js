(() => {
  const ready = (fn) => {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn, { once: true });
    } else {
      fn();
    }
  };

  const formatBytes = (bytes) => {
    if (!Number.isFinite(bytes) || bytes <= 0) return '';
    const mb = bytes / 1024 / 1024;
    if (mb >= 1) return `${mb.toFixed(mb >= 10 ? 0 : 1)} MB`;
    return `${Math.max(1, Math.round(bytes / 1024))} KB`;
  };

  ready(() => {
    const form = document.querySelector('[data-moment-create-form]');
    if (!form) return;

    const drop = form.querySelector('[data-moment-video-drop]');
    const videoInput = form.querySelector('[data-moment-video-input]');
    const videoPreview = form.querySelector('[data-moment-video-preview]');
    const videoName = form.querySelector('[data-moment-video-name]');
    const videoMeta = form.querySelector('[data-moment-video-meta]');
    const coverInput = form.querySelector('[data-moment-cover-input]');
    const coverPreview = form.querySelector('[data-moment-cover-preview]');
    const coverImg = form.querySelector('[data-moment-cover-img]');

    const updateCount = (input) => {
      const key = input.getAttribute('data-moment-count-input');
      const target = form.querySelector(`[data-moment-count="${key}"]`);
      if (target) target.textContent = String(input.value.length);
    };

    form.querySelectorAll('[data-moment-count-input]').forEach((input) => {
      updateCount(input);
      input.addEventListener('input', () => updateCount(input));
    });

    const setVideo = (file) => {
      if (!file || !videoPreview || !drop) return;
      if (videoPreview.dataset.objectUrl) {
        URL.revokeObjectURL(videoPreview.dataset.objectUrl);
      }
      const url = URL.createObjectURL(file);
      videoPreview.dataset.objectUrl = url;
      videoPreview.src = url;
      videoPreview.muted = true;
      videoPreview.loop = true;
      videoPreview.playsInline = true;
      drop.classList.add('has-preview');
      if (videoName) videoName.textContent = file.name;
      if (videoMeta) videoMeta.textContent = `${file.type || 'Video'}${file.size ? ' · ' + formatBytes(file.size) : ''}`;
      videoPreview.play().catch(() => {});
    };

    videoInput?.addEventListener('change', () => {
      const file = videoInput.files?.[0];
      if (file) setVideo(file);
    });

    ['dragenter', 'dragover'].forEach((eventName) => {
      drop?.addEventListener(eventName, (event) => {
        event.preventDefault();
        drop.classList.add('is-dragging');
      });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
      drop?.addEventListener(eventName, (event) => {
        event.preventDefault();
        drop.classList.remove('is-dragging');
      });
    });

    drop?.addEventListener('drop', (event) => {
      const file = event.dataTransfer?.files?.[0];
      if (!file || !videoInput) return;
      const transfer = new DataTransfer();
      transfer.items.add(file);
      videoInput.files = transfer.files;
      setVideo(file);
    });

    coverInput?.addEventListener('change', () => {
      const file = coverInput.files?.[0];
      if (!file || !coverPreview || !coverImg) return;
      if (coverImg.dataset.objectUrl) {
        URL.revokeObjectURL(coverImg.dataset.objectUrl);
      }
      const url = URL.createObjectURL(file);
      coverImg.dataset.objectUrl = url;
      coverImg.src = url;
      coverPreview.classList.add('has-image');
    });

    form.addEventListener('submit', () => {
      form.classList.add('is-submitting');
      const submit = form.querySelector('button[type="submit"]');
      if (submit) submit.disabled = true;
    });
  });
})();

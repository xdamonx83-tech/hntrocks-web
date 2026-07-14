(() => {
  'use strict';

  if (document.body?.dataset.page !== 'moments') return;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');

  const relabelFormatChoice = () => {
    const landscape = document.querySelector('input[name="aspect_ratio"][value="16:9"] + span');
    if (landscape) {
      landscape.textContent = isEnglish ? '16:9 inside portrait' : '16:9 im Hochformat';
    }
  };

  const ratioForHost = (host) => {
    const explicit = String(host.dataset.aspectRatio || '').trim();
    if (explicit === '16:9' || explicit === '9:16') return explicit;

    const slide = host.closest('.moment-slide[data-moment-id]');
    const id = String(slide?.dataset.momentId || '');
    const stored = String(window.HNT_MOMENT_ASPECTS?.[id] || '').trim();
    if (stored === '16:9' || stored === '9:16') return stored;

    const video = host.querySelector('video');
    if (video?.videoWidth > 0 && video?.videoHeight > 0) {
      return video.videoWidth >= video.videoHeight ? '16:9' : '9:16';
    }

    return '9:16';
  };

  const normalizeHost = (host) => {
    if (!(host instanceof Element)) return;

    const ratio = ratioForHost(host);
    const landscape = ratio === '16:9';

    host.dataset.aspectRatio = ratio;
    host.dataset.contentRatio = ratio;
    host.classList.remove('is-ratio-16-9');
    host.classList.add('is-ratio-9-16');
    host.classList.toggle('is-content-16-9', landscape);
    host.classList.toggle('is-content-9-16', !landscape);
  };

  const scan = (root = document) => {
    relabelFormatChoice();

    if (root instanceof Element && root.matches('.moment-video-card, .hnt-composer-video-player')) {
      normalizeHost(root);
    }

    root.querySelectorAll?.('.moment-video-card, .hnt-composer-video-player').forEach(normalizeHost);
  };

  scan();

  document.addEventListener('hnt:video-player-refresh', () => {
    window.requestAnimationFrame(() => scan());
  });

  document.addEventListener('change', (event) => {
    const input = event.target;
    if (!(input instanceof HTMLInputElement) || input.name !== 'aspect_ratio') return;
    window.requestAnimationFrame(() => scan(document));
  });

  document.addEventListener('loadedmetadata', (event) => {
    if (!(event.target instanceof HTMLVideoElement)) return;
    window.setTimeout(() => {
      const host = event.target.closest('.moment-video-card, .hnt-composer-video-player');
      if (host) normalizeHost(host);
    }, 0);
  }, true);

  const observer = new MutationObserver((records) => {
    records.forEach((record) => {
      record.addedNodes.forEach((node) => {
        if (!(node instanceof Element)) return;
        scan(node);
      });
    });
  });

  observer.observe(document.documentElement, { childList: true, subtree: true });
})();

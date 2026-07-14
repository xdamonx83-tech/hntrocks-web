(() => {
  'use strict';

  if (window.HNT_SHARED_VIDEO_PLAYER_READY) return;
  window.HNT_SHARED_VIDEO_PLAYER_READY = true;

  const isEnglish = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const copy = isEnglish ? {
    play: 'Play video',
    pause: 'Pause video',
    mute: 'Mute video',
    unmute: 'Unmute video',
    fullscreen: 'Fullscreen',
    progress: 'Video progress',
  } : {
    play: 'Video abspielen',
    pause: 'Video pausieren',
    mute: 'Ton ausschalten',
    unmute: 'Ton einschalten',
    fullscreen: 'Vollbild',
    progress: 'Videofortschritt',
  };

  const cssHref = '/assets/themes/hnt_preview/dashboard-feed/shared-video-player.css?v=1';
  if (!document.querySelector('link[data-hnt-shared-video-player]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = cssHref;
    link.dataset.hntSharedVideoPlayer = '1';
    document.head.appendChild(link);
  }

  const icons = {
    play: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14l11-7z"></path></svg>',
    pause: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 5v14M16 5v14"></path></svg>',
    volume: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 10v4h4l5 4V6L9 10H5Z"></path><path d="M17 9a4 4 0 0 1 0 6M19 6a8 8 0 0 1 0 12"></path></svg>',
    muted: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 10v4h4l5 4V6L9 10H5Z"></path><path d="m17 9 5 5M22 9l-5 5"></path></svg>',
    fullscreen: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"></path></svg>',
  };

  const formatTime = (value) => {
    const seconds = Math.max(0, Math.floor(Number(value) || 0));
    const minutes = Math.floor(seconds / 60);
    return `${minutes}:${String(seconds % 60).padStart(2, '0')}`;
  };

  const applyRatio = (host, video, explicit = '') => {
    let ratio = String(explicit || host.dataset.aspectRatio || '').trim();

    if (!ratio && host.closest('.moment-slide')) {
      const id = String(host.closest('.moment-slide')?.dataset.momentId || '');
      ratio = String(window.HNT_MOMENT_ASPECTS?.[id] || '');
    }

    if (!ratio && video.videoWidth > 0 && video.videoHeight > 0) {
      ratio = video.videoWidth >= video.videoHeight ? '16:9' : '9:16';
    }

    host.classList.toggle('is-ratio-16-9', ratio === '16:9');
    host.classList.toggle('is-ratio-9-16', ratio !== '16:9');
    host.dataset.aspectRatio = ratio === '16:9' ? '16:9' : '9:16';
  };

  const enhanceExisting = (host, video) => {
    if (host.dataset.hntSharedPlayerReady === '1') return;
    host.dataset.hntSharedPlayerReady = '1';
    host.classList.add('hnt-shared-video-player');
    applyRatio(host, video);
    video.addEventListener('loadedmetadata', () => applyRatio(host, video), { once: true });
  };

  const enhanceMoment = (host, video) => {
    if (host.dataset.hntSharedPlayerReady === '1') return;
    host.dataset.hntSharedPlayerReady = '1';
    host.classList.add('hnt-shared-moment-player');
    applyRatio(host, video);
    video.addEventListener('loadedmetadata', () => applyRatio(host, video), { once: true });
  };

  const enhanceCustom = (host, video) => {
    if (host.dataset.hntSharedPlayerReady === '1') return;
    host.dataset.hntSharedPlayerReady = '1';
    host.classList.add('hnt-shared-video-player');
    video.controls = false;
    video.setAttribute('playsinline', '');

    const center = document.createElement('button');
    center.type = 'button';
    center.className = 'hnt-shared-video-center';
    center.setAttribute('aria-label', copy.play);
    center.innerHTML = icons.play;

    const controls = document.createElement('div');
    controls.className = 'hnt-shared-video-controls';
    controls.innerHTML = `
      <button class="hnt-shared-video-control" type="button" data-hnt-shared-play aria-label="${copy.play}">${icons.play}</button>
      <span class="hnt-shared-video-time" data-hnt-shared-time>0:00 / 0:00</span>
      <input class="hnt-shared-video-seek" type="range" min="0" max="100" step="0.1" value="0" aria-label="${copy.progress}" data-hnt-shared-seek>
      <button class="hnt-shared-video-control" type="button" data-hnt-shared-mute aria-label="${copy.mute}">${video.muted ? icons.muted : icons.volume}</button>
      <button class="hnt-shared-video-control" type="button" data-hnt-shared-fullscreen aria-label="${copy.fullscreen}">${icons.fullscreen}</button>`;

    host.append(center, controls);

    const playButton = controls.querySelector('[data-hnt-shared-play]');
    const muteButton = controls.querySelector('[data-hnt-shared-mute]');
    const fullscreenButton = controls.querySelector('[data-hnt-shared-fullscreen]');
    const seek = controls.querySelector('[data-hnt-shared-seek]');
    const time = controls.querySelector('[data-hnt-shared-time]');
    let idleTimer = 0;

    const wake = () => {
      host.classList.remove('is-idle');
      window.clearTimeout(idleTimer);
      if (!video.paused) idleTimer = window.setTimeout(() => host.classList.add('is-idle'), 2200);
    };

    const syncPlay = () => {
      const playing = !video.paused && !video.ended;
      host.classList.toggle('is-playing', playing);
      center.innerHTML = playing ? icons.pause : icons.play;
      center.setAttribute('aria-label', playing ? copy.pause : copy.play);
      playButton.innerHTML = playing ? icons.pause : icons.play;
      playButton.setAttribute('aria-label', playing ? copy.pause : copy.play);
      wake();
    };

    const syncTime = () => {
      const duration = Number.isFinite(video.duration) ? video.duration : 0;
      const current = Number.isFinite(video.currentTime) ? video.currentTime : 0;
      time.textContent = `${formatTime(current)} / ${formatTime(duration)}`;
      seek.value = duration > 0 ? String((current / duration) * 100) : '0';
    };

    const togglePlay = (event) => {
      event?.preventDefault();
      event?.stopPropagation();
      if (video.paused) video.play().catch(() => {});
      else video.pause();
    };

    center.addEventListener('click', togglePlay);
    playButton.addEventListener('click', togglePlay);
    video.addEventListener('click', togglePlay);
    video.addEventListener('play', syncPlay);
    video.addEventListener('pause', syncPlay);
    video.addEventListener('ended', syncPlay);
    video.addEventListener('timeupdate', syncTime);
    video.addEventListener('durationchange', syncTime);
    video.addEventListener('loadedmetadata', () => {
      applyRatio(host, video);
      syncTime();
    });

    seek.addEventListener('input', (event) => {
      event.stopPropagation();
      if (!Number.isFinite(video.duration) || video.duration <= 0) return;
      video.currentTime = (Number(seek.value) / 100) * video.duration;
      syncTime();
    });

    muteButton.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      video.muted = !video.muted;
      muteButton.innerHTML = video.muted ? icons.muted : icons.volume;
      muteButton.setAttribute('aria-label', video.muted ? copy.unmute : copy.mute);
      wake();
    });

    fullscreenButton.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();
      try {
        if (document.fullscreenElement) await document.exitFullscreen();
        else if (host.requestFullscreen) await host.requestFullscreen();
      } catch (_error) {
        // Some browsers reject fullscreen outside a trusted user gesture.
      }
    });

    host.addEventListener('pointermove', wake, { passive: true });
    host.addEventListener('pointerenter', wake, { passive: true });
    controls.addEventListener('click', (event) => event.stopPropagation());
    applyRatio(host, video);
    syncPlay();
    syncTime();
  };

  const enhanceVideo = (video) => {
    if (!(video instanceof HTMLVideoElement) || video.dataset.hntSharedVideoReady === '1') return;

    const momentHost = video.closest('.moment-video-card');
    if (momentHost && video.classList.contains('moment-poster')) {
      video.dataset.hntSharedVideoReady = '1';
      enhanceMoment(momentHost, video);
      return;
    }

    const existingHost = video.closest('[data-hnt-video-player], .hnt-video-player');
    if (existingHost) {
      video.dataset.hntSharedVideoReady = '1';
      enhanceExisting(existingHost, video);
      return;
    }

    const customHost = video.closest('.hnt-composer-video-player, .real-post-video-item, .real-media-viewer__media');
    if (!customHost) return;

    video.dataset.hntSharedVideoReady = '1';
    enhanceCustom(customHost, video);
  };

  const scan = (root = document) => {
    root.querySelectorAll?.('video').forEach(enhanceVideo);
    if (root instanceof HTMLVideoElement) enhanceVideo(root);
  };

  scan();

  const observer = new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
      if (!(node instanceof Element)) return;
      scan(node);
    }));
  });
  observer.observe(document.documentElement, { childList: true, subtree: true });

  document.addEventListener('hnt:video-player-refresh', () => scan());
})();

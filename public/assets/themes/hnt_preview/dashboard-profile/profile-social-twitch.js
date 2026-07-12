(() => {
  const panel = document.querySelector('[data-profile-twitch-panel]');
  const twitchTab = document.querySelector('[data-profile-tab="twitch"]');
  const openButtons = [...document.querySelectorAll('[data-open-profile-twitch]')];

  if (!panel || !twitchTab) return;

  const channel = (panel.dataset.channel || '').trim();
  const parent = (panel.dataset.parent || window.location.hostname || 'hnt.rocks').trim();
  const playerTarget = document.getElementById('profileTwitchPlayer');
  const statusNodes = [...document.querySelectorAll('[data-profile-twitch-status]')];
  const socialLinks = [...document.querySelectorAll('[data-profile-twitch-link]')];
  const liveStrip = document.querySelector('[data-profile-live-strip]');
  let player = null;
  let loadingPromise = null;

  const setStatus = (mode) => {
    const labels = {
      idle: 'Im Tab prüfen',
      loading: 'Wird geprüft',
      live: 'LIVE',
      offline: 'Offline',
      ready: 'Bereit',
      error: 'Nicht verfügbar',
    };

    statusNodes.forEach((node) => {
      node.textContent = labels[mode] || labels.idle;
      node.classList.toggle('is-live', mode === 'live');
      node.classList.toggle('is-offline', mode === 'offline');
    });

    socialLinks.forEach((link) => {
      link.classList.toggle('is-live', mode === 'live');
      link.dataset.twitchStatus = mode;
      const label = link.querySelector('[data-profile-twitch-label]');
      if (label) label.textContent = mode === 'live' ? 'LIVE' : (mode === 'offline' ? 'Offline' : 'Stream');
    });

    twitchTab.classList.toggle('is-live', mode === 'live');
    if (liveStrip) liveStrip.hidden = mode !== 'live';
  };

  const loadTwitchApi = () => {
    if (window.Twitch?.Player) return Promise.resolve(window.Twitch);
    if (loadingPromise) return loadingPromise;

    loadingPromise = new Promise((resolve, reject) => {
      const existing = document.querySelector('script[data-hnt-twitch-player]');
      if (existing) {
        existing.addEventListener('load', () => resolve(window.Twitch), { once: true });
        existing.addEventListener('error', reject, { once: true });
        return;
      }

      const script = document.createElement('script');
      script.src = 'https://player.twitch.tv/js/embed/v1.js';
      script.async = true;
      script.dataset.hntTwitchPlayer = '1';
      script.addEventListener('load', () => resolve(window.Twitch), { once: true });
      script.addEventListener('error', reject, { once: true });
      document.head.appendChild(script);
    });

    return loadingPromise;
  };

  const initPlayer = async () => {
    if (player || !channel || !playerTarget) return;

    if (window.innerWidth < 400) {
      setStatus('ready');
      return;
    }

    setStatus('loading');

    try {
      await loadTwitchApi();

      player = new window.Twitch.Player(playerTarget.id, {
        width: '100%',
        height: '100%',
        channel,
        parent: [parent],
        autoplay: false,
        muted: true,
      });

      player.addEventListener(window.Twitch.Player.READY, () => setStatus('ready'));
      player.addEventListener(window.Twitch.Player.ONLINE, () => setStatus('live'));
      player.addEventListener(window.Twitch.Player.OFFLINE, () => setStatus('offline'));
      player.addEventListener(window.Twitch.Player.ENDED, () => setStatus('offline'));
      player.addEventListener(window.Twitch.Player.PLAYBACK_BLOCKED, () => setStatus('ready'));
    } catch (error) {
      console.error('HNT Twitch player could not be initialized.', error);
      setStatus('error');
    }
  };

  twitchTab.addEventListener('click', () => {
    window.setTimeout(initPlayer, 0);
  });

  openButtons.forEach((button) => {
    button.addEventListener('click', () => {
      twitchTab.click();
      twitchTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    });
  });

  setStatus('idle');
})();

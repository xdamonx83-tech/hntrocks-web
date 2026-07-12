(() => {
  const status = window.HNT_PROFILE_TWITCH_STATUS;

  if (!status || !status.channel) return;

  const mode = status.state === 'live'
    ? 'live'
    : (status.state === 'offline' ? 'offline' : 'loading');

  const labels = {
    live: 'LIVE',
    offline: 'Offline',
    loading: 'Wird geprüft',
  };

  document.querySelectorAll('[data-profile-twitch-status]').forEach((node) => {
    node.textContent = labels[mode] || labels.loading;
    node.classList.toggle('is-live', mode === 'live');
    node.classList.toggle('is-offline', mode === 'offline');
  });

  document.querySelectorAll('[data-profile-twitch-link]').forEach((link) => {
    link.classList.toggle('is-live', mode === 'live');
    link.dataset.twitchStatus = mode;

    const label = link.querySelector('[data-profile-twitch-label]');
    if (label) label.textContent = labels[mode] || labels.loading;

    const name = link.querySelector('[data-profile-twitch-name]');
    if (name) {
      const displayName = (link.dataset.profileDisplayName || 'Twitch').trim();
      name.textContent = mode === 'live' ? `${displayName} ist live` : 'Twitch';
    }
  });

  document.querySelectorAll('[data-profile-tab="twitch"]').forEach((button) => {
    button.classList.toggle('is-live', mode === 'live');
  });
})();

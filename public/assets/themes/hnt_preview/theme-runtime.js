(() => {
  const valid = new Set(['light', 'dark', 'system']);
  const media = window.matchMedia('(prefers-color-scheme: dark)');

  const resolve = (preference) => preference === 'system'
    ? (media.matches ? 'dark' : 'light')
    : preference;

  const apply = (preference, { persist = true } = {}) => {
    const normalized = valid.has(preference) ? preference : 'system';
    const resolved = resolve(normalized);

    document.documentElement.dataset.themePreference = normalized;
    document.documentElement.dataset.theme = resolved;
    document.documentElement.style.colorScheme = resolved;

    if (persist) {
      try {
        localStorage.setItem('hnt_theme_preference', normalized);
      } catch (_error) {
        // localStorage can be unavailable in strict privacy modes.
      }
    }

    document.dispatchEvent(new CustomEvent('hnt:theme-applied', {
      detail: { preference: normalized, resolved },
    }));

    return resolved;
  };

  const currentPreference = () => {
    const value = document.documentElement.dataset.themePreference;
    return valid.has(value) ? value : 'system';
  };

  window.HNTTheme = Object.freeze({
    applyPreference: apply,
    getPreference: currentPreference,
    resolvePreference: resolve,
  });

  document.addEventListener('hnt:theme-preview', (event) => {
    apply(event.detail?.preference || 'system');
  });

  const handleSystemChange = () => {
    if (currentPreference() === 'system') apply('system', { persist: false });
  };

  if (typeof media.addEventListener === 'function') {
    media.addEventListener('change', handleSystemChange);
  } else if (typeof media.addListener === 'function') {
    media.addListener(handleSystemChange);
  }

  window.addEventListener('storage', (event) => {
    if (event.key !== 'hnt_theme_preference' || !valid.has(event.newValue)) return;
    apply(event.newValue, { persist: false });
  });
})();

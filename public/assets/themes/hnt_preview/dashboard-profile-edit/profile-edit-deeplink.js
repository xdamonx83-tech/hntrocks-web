(() => {
  'use strict';

  const init = () => {
    if (document.querySelector('[data-field-error]')) {
      return;
    }

    const requestedTab = new URL(window.location.href).searchParams.get('tab');
    const allowedTabs = new Set(['general', 'media', 'hunt', 'social', 'privacy']);

    if (!requestedTab || !allowedTabs.has(requestedTab)) {
      return;
    }

    const trigger = document.querySelector(`[data-profile-tab="${requestedTab}"]`);

    if (!trigger) {
      return;
    }

    trigger.click();
    trigger.setAttribute('aria-selected', 'true');
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init, { once: true });
  } else {
    init();
  }
})();

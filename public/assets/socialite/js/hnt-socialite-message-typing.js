(() => {
  const forms = document.querySelectorAll('[data-hh-message-typing-form]');

  if (!forms.length) {
    return;
  }

  const trueThrottleMs = 2000;
  const states = new WeakMap();

  const csrfFor = (form) => (
    form.getAttribute('data-csrf-token')
    || form.querySelector('input[name="_token"]')?.value
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || ''
  );

  const postTyping = (form, isTyping, options = {}) => {
    const url = form.getAttribute('data-typing-url');

    if (!url) {
      return;
    }

    const body = JSON.stringify({ is_typing: Boolean(isTyping) });

    try {
      fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        keepalive: Boolean(options.keepalive),
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrfFor(form),
          'X-Requested-With': 'XMLHttpRequest'
        },
        body
      }).catch(() => {});
    } catch (error) {
      if (!options.keepalive || !navigator.sendBeacon) {
        return;
      }

      try {
        navigator.sendBeacon(url, new Blob([body], { type: 'application/json' }));
      } catch (beaconError) {
        // Best-effort shutdown signal only.
      }
    }
  };

  const sendFalse = (form, keepalive = false) => {
    const state = states.get(form);

    if (state) {
      state.isTyping = false;
    }

    postTyping(form, false, { keepalive });
  };

  forms.forEach((form) => {
    const textarea = form.querySelector('[data-hh-message-typing-input], textarea[name="body"]');

    if (!textarea) {
      return;
    }

    const state = { isTyping: false, lastTrueAt: 0 };
    states.set(form, state);

    textarea.addEventListener('input', () => {
      const hasBody = textarea.value.trim() !== '';

      if (!hasBody) {
        if (state.isTyping) {
          sendFalse(form);
        }

        return;
      }

      const now = Date.now();

      if (!state.isTyping || now - state.lastTrueAt >= trueThrottleMs) {
        state.isTyping = true;
        state.lastTrueAt = now;
        postTyping(form, true);
      }
    });

    textarea.addEventListener('blur', () => sendFalse(form));
    form.addEventListener('submit', () => sendFalse(form));
  });

  const flushTyping = () => {
    forms.forEach((form) => sendFalse(form, true));
  };

  window.addEventListener('pagehide', flushTyping);
  window.addEventListener('beforeunload', flushTyping);
})();

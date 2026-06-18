(() => {
  const formSelector = '[data-hh-message-typing-form]';
  const inputSelector = '[data-hh-message-typing-input], textarea[name="body"], input[name="body"]';
  const trueThrottleMs = 2000;
  const states = new WeakMap();

  const stateFor = (form) => {
    if (!states.has(form)) {
      states.set(form, { isTyping: false, lastTrueAt: 0 });
    }

    return states.get(form);
  };

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
    const state = stateFor(form);
    state.isTyping = false;

    postTyping(form, false, { keepalive });
  };

  const formForEventTarget = (target) => {
    const field = target?.closest?.(inputSelector);
    const form = field?.closest?.(formSelector);

    if (!field || !form || !form.contains(field)) {
      return null;
    }

    return { form, field };
  };

  document.addEventListener('input', (event) => {
    const target = formForEventTarget(event.target);

    if (!target) {
      return;
    }

    const { form, field } = target;
    const state = stateFor(form);
    const hasBody = field.value.trim() !== '';

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

  document.addEventListener('blur', (event) => {
    const target = formForEventTarget(event.target);

    if (target) {
      sendFalse(target.form);
    }
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target?.closest?.(formSelector);

    if (form) {
      sendFalse(form);
    }
  });

  const flushTyping = () => {
    document.querySelectorAll(formSelector).forEach((form) => sendFalse(form, true));
  };

  window.addEventListener('pagehide', flushTyping);
  window.addEventListener('beforeunload', flushTyping);
})();

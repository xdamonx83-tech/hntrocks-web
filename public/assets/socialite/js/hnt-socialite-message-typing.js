(() => {
  const formSelector = '[data-hh-message-typing-form]';
  const inputSelector = '[data-hh-message-typing-input], textarea[name="body"], input[name="body"]';
  const indicatorSelector = '[data-hh-message-typing-indicator]';
  const trueThrottleMs = 2000;
  const states = new WeakMap();
  const hideTimers = new Map();

  const cssEscape = (value) => {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(String(value));
    }

    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  };

  const isGerman = () => String(document.documentElement.lang || '').toLowerCase().startsWith('de');

  const playerFallback = () => (isGerman() ? 'Spieler' : 'Player');

  const typingMessage = (name) => (
    isGerman()
      ? `${name} schreibt gerade...`
      : `${name} is typing...`
  );

  const injectIndicatorStyle = () => {
    if (document.getElementById('hh-message-typing-indicator-style')) {
      return;
    }

    const style = document.createElement('style');
    style.id = 'hh-message-typing-indicator-style';
    style.textContent = `
      .hh-message-typing-indicator {
        color: #64748b;
        font-size: 12px;
        font-weight: 600;
        line-height: 1.2;
        padding: 0 14px 8px;
      }
      .dark .hh-message-typing-indicator {
        color: rgba(255,255,255,.62);
      }
      .hnt-chat-tab__typing-indicator {
        background: #fff;
      }
      .dark .hnt-chat-tab__typing-indicator {
        background: #1f1f23;
      }
    `;
    document.head.appendChild(style);
  };

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

  const conversationIdFor = (node) => (
    node?.getAttribute?.('data-conversation-id')
    || node?.getAttribute?.('data-hnt-chat-tab')
    || ''
  );

  const findTypingTargets = (conversationId) => {
    const escapedId = cssEscape(conversationId);
    const targets = new Set();

    document.querySelectorAll(`${formSelector}[data-conversation-id="${escapedId}"], form[data-conversation-id="${escapedId}"]`).forEach((form) => {
      const tab = form.closest('[data-hnt-chat-tab]');
      const panel = form.closest('[data-hh-chat-panel]');

      targets.add(tab || panel || form);
    });

    document.querySelectorAll(`[data-hnt-chat-tab="${escapedId}"], [data-conversation-id="${escapedId}"], [data-hh-chat-panel="conversation-${escapedId}"]`).forEach((node) => {
      if (node.closest(indicatorSelector)) {
        return;
      }

      targets.add(node.closest('[data-hnt-chat-tab]') || node.closest('[data-hh-chat-panel]') || node);
    });

    return Array.from(targets).filter((target) => (
      target
      && target.nodeType === 1
      && (conversationIdFor(target) === conversationId || target.querySelector?.(`[data-conversation-id="${escapedId}"]`))
    ));
  };

  const ensureIndicator = (target, conversationId) => {
    let indicator = target.querySelector?.(`${indicatorSelector}[data-conversation-id="${cssEscape(conversationId)}"]`);

    if (indicator) {
      return indicator;
    }

    const form = target.matches?.('form') ? target : target.querySelector?.('form[data-conversation-id], [data-hh-chat-send-form], [data-hnt-chat-tab-form]');

    if (target.matches?.('form')) {
      indicator = target.previousElementSibling?.matches?.(`${indicatorSelector}[data-conversation-id="${cssEscape(conversationId)}"]`)
        ? target.previousElementSibling
        : null;

      if (indicator) {
        return indicator;
      }
    }

    indicator = document.createElement('div');
    indicator.className = 'hh-message-typing-indicator';
    indicator.setAttribute('data-hh-message-typing-indicator', '');
    indicator.setAttribute('data-conversation-id', conversationId);
    indicator.hidden = true;

    if (target.matches?.('[data-hnt-chat-tab]')) {
      indicator.classList.add('hnt-chat-tab__typing-indicator');
    }

    if (form && form.parentNode) {
      form.parentNode.insertBefore(indicator, form);
    } else {
      target.appendChild(indicator);
    }

    return indicator;
  };

  const hideIndicators = (conversationId) => {
    if (!conversationId) {
      return;
    }

    window.clearTimeout(hideTimers.get(conversationId));
    hideTimers.delete(conversationId);

    const escapedId = cssEscape(conversationId);
    document.querySelectorAll(`${indicatorSelector}[data-conversation-id="${escapedId}"]`).forEach((indicator) => {
      indicator.hidden = true;
      indicator.textContent = '';
    });
  };

  const showIndicators = (detail) => {
    const conversationId = String(detail.conversationId || '');

    if (!conversationId) {
      return;
    }

    if (!detail.isTyping) {
      hideIndicators(conversationId);
      return;
    }

    injectIndicatorStyle();

    const name = String(detail.displayName || detail.username || playerFallback()).trim() || playerFallback();
    findTypingTargets(conversationId).forEach((target) => {
      const indicator = ensureIndicator(target, conversationId);
      indicator.textContent = typingMessage(name);
      indicator.hidden = false;
    });

    const seconds = Number(detail.expiresInSeconds);
    const hideAfterMs = Number.isFinite(seconds) && seconds > 0 ? seconds * 1000 : 4000;
    window.clearTimeout(hideTimers.get(conversationId));
    hideTimers.set(conversationId, window.setTimeout(() => hideIndicators(conversationId), hideAfterMs));
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
      hideIndicators(String(form.getAttribute('data-conversation-id') || ''));
      sendFalse(form);
    }
  });

  document.addEventListener('hnt:conversation-typing', (event) => {
    showIndicators(event.detail || {});
  });

  const flushTyping = () => {
    document.querySelectorAll(formSelector).forEach((form) => sendFalse(form, true));
  };

  window.addEventListener('pagehide', flushTyping);
  window.addEventListener('beforeunload', flushTyping);
})();

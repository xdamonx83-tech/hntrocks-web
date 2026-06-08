(function () {
  'use strict';

  const shell = document.querySelector('[data-hnt-chat-tabs-shell]');
  if (!shell) return;

  const storageKey = 'hntSocialiteOpenChatTabsV1';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

  function i18n(key, fallback) {
    return shell.getAttribute(`data-hnt-i18n-${key}`) || fallback;
  }

  function escapeHtml(value) {
    return String(value || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }


  function cssEscape(value) {
    if (window.CSS && typeof window.CSS.escape === 'function') {
      return window.CSS.escape(String(value));
    }

    return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
  }

  function readStoredTabs() {
    try {
      const parsed = JSON.parse(window.sessionStorage.getItem(storageKey) || '[]');
      return Array.isArray(parsed) ? parsed.filter((item) => item && item.id && item.url) : [];
    } catch (error) {
      return [];
    }
  }

  function writeStoredTabs() {
    const tabs = Array.from(shell.querySelectorAll('[data-hnt-chat-tab]')).map((tab) => ({
      id: tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab'),
      url: tab.getAttribute('data-hnt-chat-tab-url') || '',
    })).filter((item) => item.id && item.url);

    try {
      window.sessionStorage.setItem(storageKey, JSON.stringify(tabs));
    } catch (error) {
      // Storage is optional. Chat tabs still work without persistence.
    }
  }

  function updateMessageBadge(count) {
    if (typeof count === 'undefined' || count === null) return;

    document.querySelectorAll('[data-hh-message-count], [data-hh-live-badge="messages"]').forEach((node) => {
      const value = Number(count) || 0;
      node.textContent = value > 99 ? '99+' : String(value);
      node.classList.toggle('hidden', value <= 0);
    });
  }

  function scrollMessages(tab) {
    const messages = tab?.querySelector('[data-hnt-chat-tab-messages]');
    if (messages) {
      messages.scrollTop = messages.scrollHeight;
    }
  }

  function activateTab(tab) {
    if (!tab) return;
    tab.classList.remove('is-minimized');
    shell.appendChild(tab);
    scrollMessages(tab);
    writeStoredTabs();
  }

  function renderTab(html, url) {
    const template = document.createElement('template');
    template.innerHTML = String(html || '').trim();
    const tab = template.content.firstElementChild;
    if (!tab) return null;

    const conversationId = tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab');
    const existing = conversationId ? shell.querySelector(`[data-hnt-chat-tab="${cssEscape(conversationId)}"]`) : null;

    tab.setAttribute('data-hnt-chat-tab-url', url || '');

    if (existing) {
      existing.replaceWith(tab);
    } else {
      shell.appendChild(tab);
    }

    scrollMessages(tab);
    writeStoredTabs();
    return tab;
  }

  async function openChatTab(url, trigger) {
    if (!url) return;

    const existingId = trigger?.getAttribute('data-hnt-chat-conversation-id');
    if (existingId) {
      const existing = shell.querySelector(`[data-hnt-chat-tab="${cssEscape(existingId)}"]`);
      if (existing) {
        activateTab(existing);
        return;
      }
    }

    if (trigger) {
      trigger.setAttribute('aria-busy', 'true');
    }

    try {
      const response = await fetch(url, {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.message || i18n('open-error', 'Chat could not be opened'));
      }

      const tab = renderTab(payload.html, url);
      if (tab) {
        updateMessageBadge(payload.unread_messages);
        const conversationId = tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab');
        document.querySelectorAll(`[data-hnt-chat-conversation-id="${cssEscape(conversationId)}"] .bg-blue-600`).forEach((node) => node.remove());
      }
    } catch (error) {
      if (trigger && trigger.href) {
        window.location.href = trigger.href;
      } else {
        console.error(error);
      }
    } finally {
      if (trigger) {
        trigger.removeAttribute('aria-busy');
      }
    }
  }

  function appendOwnMessage(form, payload) {
    const tab = form.closest('[data-hnt-chat-tab]');
    const list = tab?.querySelector('[data-hnt-chat-tab-messages]');
    if (!tab || !list) return;

    tab.querySelector('[data-hnt-chat-tab-empty]')?.remove();

    const body = payload?.body || '';
    const time = payload?.created_at_label || '';
    const wrapper = document.createElement('div');
    wrapper.className = 'hnt-chat-tab__message is-own';
    wrapper.innerHTML = `
      <div class="hnt-chat-tab__bubble-wrap">
        <p class="hnt-chat-tab__bubble">${escapeHtml(body)}</p>
        <span class="hnt-chat-tab__time">${escapeHtml(time)}</span>
      </div>
    `;
    list.appendChild(wrapper);
    scrollMessages(tab);
  }

  document.addEventListener('click', (event) => {
    const opener = event.target.closest('[data-hnt-chat-tab-open]');
    if (opener) {
      event.preventDefault();
      const drop = opener.closest('[uk-drop]');
      if (drop && window.UIkit && typeof window.UIkit.drop === 'function') {
        try { window.UIkit.drop(drop).hide(false); } catch (error) {}
      }
      openChatTab(opener.getAttribute('data-hnt-chat-tab-url') || opener.href, opener);
      return;
    }

    const close = event.target.closest('[data-hnt-chat-tab-close]');
    if (close) {
      event.preventDefault();
      close.closest('[data-hnt-chat-tab]')?.remove();
      writeStoredTabs();
      return;
    }

    const minimize = event.target.closest('[data-hnt-chat-tab-minimize]');
    if (minimize) {
      event.preventDefault();
      minimize.closest('[data-hnt-chat-tab]')?.classList.add('is-minimized');
      writeStoredTabs();
      return;
    }

    const fullLink = event.target.closest('[data-hnt-chat-tab-full]');
    if (fullLink) return;

    const header = event.target.closest('[data-hnt-chat-tab-toggle]');
    if (header) {
      const tab = header.closest('[data-hnt-chat-tab]');
      if (tab?.classList.contains('is-minimized')) {
        event.preventDefault();
        activateTab(tab);
      }
    }
  });

  document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-hnt-chat-tab-form]');
    if (!form) return;

    event.preventDefault();

    const input = form.querySelector('input[name="body"]');
    const button = form.querySelector('button[type="submit"]');
    const body = String(input?.value || '').trim();
    if (!body) return;

    button.disabled = true;

    try {
      const formData = new FormData(form);
      const response = await fetch(form.action, {
        method: 'POST',
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': csrfToken,
        },
        credentials: 'same-origin',
        body: formData,
      });

      const payload = await response.json();
      if (!response.ok) {
        throw new Error(payload?.message || i18n('send-error', 'Message could not be sent'));
      }

      appendOwnMessage(form, payload);
      if (input) input.value = '';
      updateMessageBadge(payload.unread_messages);
    } catch (error) {
      form.submit();
    } finally {
      button.disabled = false;
      input?.focus();
    }
  });

  readStoredTabs().forEach((item) => openChatTab(item.url));
})();

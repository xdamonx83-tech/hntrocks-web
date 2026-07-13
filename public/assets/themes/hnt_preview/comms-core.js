(() => {
    if (window.HNT_COMMS_CORE_READY) return;
    window.HNT_COMMS_CORE_READY = true;

    document.addEventListener('DOMContentLoaded', () => {
        const shell = document.querySelector('[data-hnt-chat-tabs-shell]');
        if (!shell) return;

        const i18n = window.HNT_PREVIEW_I18N?.comms || {};
        const labels = {
            openFailed: String(i18n.open_failed || ''),
            sendFailed: String(i18n.send_failed || ''),
        };
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const storageKey = 'hntPreviewOpenChatTabsV2';
        const legacyStorageKey = 'hntPreviewOpenChatTabsV1';
        const pollInterval = 4500;

        const cssEscape = (value) => {
            if (window.CSS && typeof window.CSS.escape === 'function') return window.CSS.escape(String(value));
            return String(value).replace(/[^a-zA-Z0-9_-]/g, '\\$&');
        };

        const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (character) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;',
        })[character] || character);

        const notifyBadges = () => {
            document.dispatchEvent(new CustomEvent('hnt:preview-live-badges-refresh'));
            document.dispatchEvent(new CustomEvent('hnt:message-created'));
        };

        const readStoredTabs = () => {
            try {
                const stored = JSON.parse(
                    window.sessionStorage.getItem(storageKey)
                    || window.sessionStorage.getItem(legacyStorageKey)
                    || '[]'
                );

                return Array.isArray(stored)
                    ? stored.filter((item) => item && item.id && item.url).map((item) => ({
                        id: String(item.id),
                        url: String(item.url),
                        minimized: Boolean(item.minimized),
                    }))
                    : [];
            } catch (_error) {
                return [];
            }
        };

        const writeStoredTabs = () => {
            const tabs = Array.from(shell.querySelectorAll('[data-hnt-chat-tab]')).map((tab) => ({
                id: tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab'),
                url: tab.getAttribute('data-hnt-chat-tab-url') || '',
                minimized: tab.classList.contains('is-minimized') || tab.classList.contains('hnt-comms-signal'),
            })).filter((item) => item.id && item.url);

            try {
                window.sessionStorage.setItem(storageKey, JSON.stringify(tabs));
                window.sessionStorage.removeItem(legacyStorageKey);
            } catch (_error) {
                // Persistence is optional.
            }
        };

        const scrollMessagesToBottom = (tab) => {
            const list = tab?.querySelector('[data-hnt-chat-tab-messages]');
            if (!list) return;

            const apply = () => {
                list.scrollTop = list.scrollHeight;
            };

            apply();
            window.requestAnimationFrame(() => {
                apply();
                window.requestAnimationFrame(apply);
            });
            window.setTimeout(apply, 80);
            window.setTimeout(apply, 180);
        };

        const isNearBottom = (list) => !list || (list.scrollHeight - list.scrollTop - list.clientHeight) < 64;

        const messagesUrlFor = (tab) => {
            if (!tab) return '';
            const explicit = tab.getAttribute('data-hnt-chat-tab-messages-url');
            if (explicit) return explicit;
            const tabUrl = tab.getAttribute('data-hnt-chat-tab-url') || '';
            return tabUrl.includes('/chat-tab') ? tabUrl.replace(/\/?$/, '/messages') : '';
        };

        const renderTab = (html, url) => {
            const template = document.createElement('template');
            template.innerHTML = String(html || '').trim();
            const tab = template.content.firstElementChild;
            if (!tab) return null;

            const conversationId = tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab');
            const existing = conversationId
                ? shell.querySelector('[data-hnt-chat-tab="' + cssEscape(conversationId) + '"]')
                : null;

            tab.setAttribute('data-hnt-chat-tab-url', url || '');

            if (existing) {
                existing.replaceWith(tab);
            } else {
                shell.appendChild(tab);
            }

            scrollMessagesToBottom(tab);
            writeStoredTabs();
            return tab;
        };

        const activateTab = (tab) => {
            if (!tab) return;
            tab.classList.remove('is-minimized', 'hnt-comms-signal');
            shell.appendChild(tab);
            scrollMessagesToBottom(tab);
            writeStoredTabs();
            document.dispatchEvent(new CustomEvent('hnt:comms-tab-activated', { detail: { tab } }));
        };

        const openTab = async (url, trigger = null, options = {}) => {
            if (!url) return null;

            const existingId = trigger?.getAttribute('data-hnt-chat-conversation-id') || options.id || '';
            if (existingId) {
                const existing = shell.querySelector('[data-hnt-chat-tab="' + cssEscape(existingId) + '"]');
                if (existing) {
                    if (options.minimized) {
                        existing.classList.add('is-minimized');
                        writeStoredTabs();
                    } else {
                        activateTab(existing);
                    }
                    return existing;
                }
            }

            trigger?.setAttribute('aria-busy', 'true');

            try {
                let response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                let payload = await response.json();
                if (!response.ok) throw new Error(payload?.message || labels.openFailed);

                if (!payload.html && payload.conversation_id) {
                    const showUrl = payload.show_url
                        ? String(payload.show_url).replace(/\/$/, '')
                        : window.location.origin + '/messages/' + encodeURIComponent(payload.conversation_id);
                    const chatTabUrl = payload.chat_tab_url || showUrl + '/chat-tab';

                    response = await fetch(chatTabUrl, {
                        method: 'GET',
                        credentials: 'same-origin',
                        cache: 'no-store',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    payload = await response.json();
                    if (!response.ok) throw new Error(payload?.message || labels.openFailed);
                    url = chatTabUrl;
                }

                const tab = renderTab(payload.html, payload.chat_tab_url || url);
                if (!tab) return null;

                if (options.minimized) {
                    tab.classList.add('is-minimized');
                    writeStoredTabs();
                    document.dispatchEvent(new CustomEvent('hnt:comms-tab-restored', { detail: { tab } }));
                } else {
                    activateTab(tab);
                }

                trigger?.classList.remove('unread', 'is-unread');
                notifyBadges();
                return tab;
            } catch (error) {
                console.error('HNT Comms:', error);
                if (trigger?.href && !options.restoring) window.location.assign(trigger.href);
                return null;
            } finally {
                trigger?.removeAttribute('aria-busy');
            }
        };

        const refreshTab = async (tab, force = false) => {
            if (!tab || document.hidden || tab.dataset.hntCommsRefreshing === '1') return;
            if (tab.hasAttribute('data-hnt-chat-draft')) return;
            if (!force && tab.classList.contains('is-minimized')) return;

            const list = tab.querySelector('[data-hnt-chat-tab-messages]');
            const url = messagesUrlFor(tab);
            if (!list || !url) return;

            tab.dataset.hntCommsRefreshing = '1';
            const stickToBottom = isNearBottom(list);

            try {
                const response = await fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    cache: 'no-store',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                if (!response.ok) return;
                const payload = await response.json();
                if (typeof payload.html === 'string') {
                    list.innerHTML = payload.html;
                    if (stickToBottom) scrollMessagesToBottom(tab);
                }
            } catch (_error) {
                // Retry on next interval.
            } finally {
                delete tab.dataset.hntCommsRefreshing;
            }
        };

        const appendOwnMessage = (form, payload) => {
            const tab = form.closest('[data-hnt-chat-tab]');
            const list = tab?.querySelector('[data-hnt-chat-tab-messages]');
            if (!tab || !list) return;

            tab.querySelector('[data-hnt-chat-tab-empty]')?.remove();
            const row = document.createElement('div');
            row.className = 'hnt-chat-tab__message is-own';
            row.innerHTML = '<div class="hnt-chat-tab__bubble-wrap"><p class="hnt-chat-tab__bubble">'
                + escapeHtml(payload?.body || '')
                + '</p><span class="hnt-chat-tab__time">'
                + escapeHtml(payload?.created_at_label || '')
                + '</span></div>';
            list.appendChild(row);
            scrollMessagesToBottom(tab);
        };

        const prepareHeaderMessageLinks = (scope = document) => {
            scope.querySelectorAll?.('.header-message-list a.header-message-item:not([data-hnt-chat-tab-open])').forEach((link) => {
                const href = link.getAttribute('href') || '';
                const match = href.match(/\/messages\/(\d+)(?:[/?#]|$)/);
                if (!match) return;

                const conversationId = match[1];
                const showUrl = new URL(href, window.location.origin);
                showUrl.search = '';
                showUrl.hash = '';

                link.setAttribute('data-hnt-chat-tab-open', '');
                link.setAttribute('data-hnt-chat-conversation-id', conversationId);
                link.setAttribute('data-hnt-chat-tab-url', showUrl.pathname.replace(/\/$/, '') + '/chat-tab');
            });
        };

        prepareHeaderMessageLinks();
        const headerObserver = new MutationObserver((records) => {
            records.forEach((record) => {
                record.addedNodes.forEach((node) => {
                    if (node instanceof Element) prepareHeaderMessageLinks(node.matches('.header-message-item') ? node.parentElement : node);
                });
            });
        });
        document.querySelectorAll('.header-message-list').forEach((list) => {
            headerObserver.observe(list, { childList: true, subtree: true });
        });

        document.addEventListener('click', (event) => {
            const opener = event.target.closest('[data-hnt-chat-tab-open]');
            if (opener) {
                event.preventDefault();
                event.stopPropagation();
                openTab(opener.getAttribute('data-hnt-chat-tab-url') || opener.href, opener);
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
                const tab = minimize.closest('[data-hnt-chat-tab]');
                tab?.classList.add('is-minimized');
                writeStoredTabs();
                document.dispatchEvent(new CustomEvent('hnt:comms-tab-minimized', { detail: { tab } }));
                return;
            }

            const header = event.target.closest('[data-hnt-chat-tab-toggle]');
            const tab = header?.closest('[data-hnt-chat-tab]');
            if (tab?.classList.contains('is-minimized') && !event.target.closest('[data-hnt-chat-tab-full]')) {
                event.preventDefault();
                activateTab(tab);
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

            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: new FormData(form),
                });
                const payload = await response.json();
                if (!response.ok) throw new Error(payload?.message || labels.sendFailed);

                const previousTab = form.closest('[data-hnt-chat-tab]');
                if (typeof payload.html === 'string' && payload.html.trim() !== '') {
                    const realTab = renderTab(payload.html, payload.chat_tab_url || '');
                    if (realTab) {
                        if (previousTab && previousTab !== realTab) previousTab.remove();
                        activateTab(realTab);
                    }
                } else {
                    appendOwnMessage(form, payload);
                    if (input) input.value = '';
                    const tab = form.closest('[data-hnt-chat-tab]');
                    if (tab) window.setTimeout(() => refreshTab(tab, true), 250);
                }

                notifyBadges();
                writeStoredTabs();
            } catch (error) {
                console.error('HNT Comms send:', error);
            } finally {
                if (button) button.disabled = false;
                input?.focus();
            }
        });

        const restoreStoredTabs = async () => {
            for (const item of readStoredTabs()) {
                await openTab(item.url, null, {
                    id: item.id,
                    minimized: item.minimized,
                    restoring: true,
                });
            }
        };

        restoreStoredTabs();

        window.setInterval(() => {
            shell.querySelectorAll('[data-hnt-chat-tab]').forEach((tab) => refreshTab(tab, false));
        }, pollInterval);

        window.addEventListener('focus', () => {
            shell.querySelectorAll('[data-hnt-chat-tab]').forEach((tab) => refreshTab(tab, true));
        });

        document.addEventListener('hnt:comms-tab-activated', (event) => {
            scrollMessagesToBottom(event.detail?.tab);
        });

        window.HNT_COMMS_OPEN = openTab;
    });
})();
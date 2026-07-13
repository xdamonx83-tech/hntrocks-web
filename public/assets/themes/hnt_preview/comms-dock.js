document.addEventListener('DOMContentLoaded', function () {
    const shell = document.querySelector('[data-hnt-chat-tabs-shell]');
    if (!shell || shell.dataset.hntCommsReady === '1') return;

    shell.dataset.hntCommsReady = '1';
    shell.classList.add('hnt-comms-dock');

    if (!document.querySelector('link[data-hnt-comms-overview-style]')) {
        const style = document.createElement('link');
        style.rel = 'stylesheet';
        style.href = '/assets/themes/hnt_preview/comms-overview.css?v=20260713-1';
        style.dataset.hntCommsOverviewStyle = '1';
        document.head.appendChild(style);
    }

    const locale = String(document.documentElement.lang || 'de').toLowerCase();
    const isEnglish = locale.startsWith('en');
    const storageKey = 'hntPreviewOpenChatTabsV2';
    const labels = {
        rail: isEnglish ? 'Active conversations' : 'Aktive Gespräche',
        launcher: isEnglish ? 'Open message overview' : 'Nachrichtenübersicht öffnen',
        launcherTitle: isEnglish ? 'Messages' : 'Nachrichten',
        eyebrow: 'INBOX',
        title: isEnglish ? 'Messages' : 'Nachrichten',
        search: isEnglish ? 'Search conversations' : 'Gespräche suchen',
        emptyTitle: isEnglish ? 'No conversations yet' : 'Noch keine Unterhaltungen',
        emptyBody: isEnglish
            ? 'Your existing private conversations will appear here.'
            : 'Deine bestehenden privaten Unterhaltungen erscheinen hier.',
        allMessages: isEnglish ? 'View all messages' : 'Alle Nachrichten ansehen',
        conversation: isEnglish ? 'Conversation' : 'Gespräch',
        close: isEnglish ? 'Close overview' : 'Übersicht schließen',
    };

    const panelSlot = document.createElement('div');
    panelSlot.className = 'hnt-comms-panel-slot';
    panelSlot.setAttribute('data-hnt-comms-panel-slot', '');

    const rail = document.createElement('aside');
    rail.className = 'hnt-comms-rail';
    rail.setAttribute('aria-label', labels.rail);

    const railList = document.createElement('div');
    railList.className = 'hnt-comms-rail-list';
    railList.setAttribute('data-hnt-comms-rail-list', '');

    const launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'hnt-comms-launcher';
    launcher.setAttribute('data-hnt-comms-launcher', '');
    launcher.setAttribute('aria-label', labels.launcher);
    launcher.setAttribute('title', labels.launcherTitle);
    launcher.innerHTML = ''
        + '<i class="ph ph-chats-circle" aria-hidden="true"></i>'
        + '<span class="hnt-comms-launcher__count" data-hnt-comms-count hidden>0</span>';

    const overview = document.createElement('section');
    overview.className = 'hnt-comms-overview';
    overview.setAttribute('data-hnt-comms-overview', '');
    overview.setAttribute('aria-label', labels.title);
    overview.innerHTML = ''
        + '<header class="hnt-comms-overview__head">'
        + '  <div>'
        + '    <span class="hnt-comms-overview__eyebrow">' + labels.eyebrow + '</span>'
        + '    <h2>' + labels.title + '</h2>'
        + '    <span class="hnt-comms-overview__meta" data-hnt-comms-overview-meta></span>'
        + '  </div>'
        + '  <button class="hnt-comms-overview__close" type="button" data-hnt-comms-overview-close aria-label="' + labels.close + '">'
        + '    <i class="ph ph-x" aria-hidden="true"></i>'
        + '  </button>'
        + '</header>'
        + '<label class="hnt-comms-overview__search">'
        + '  <i class="ph ph-magnifying-glass" aria-hidden="true"></i>'
        + '  <input type="search" data-hnt-comms-overview-search placeholder="' + labels.search + '" autocomplete="off">'
        + '</label>'
        + '<div class="hnt-comms-overview__list" data-hnt-comms-overview-list></div>'
        + '<a class="hnt-comms-overview__footer" data-hnt-comms-overview-footer href="/messages">'
        + '  <span>' + labels.allMessages + '</span>'
        + '  <i class="ph ph-arrow-up-right" aria-hidden="true"></i>'
        + '</a>';

    rail.appendChild(railList);
    rail.appendChild(launcher);
    shell.appendChild(panelSlot);
    shell.appendChild(rail);

    let arranging = false;
    let arrangeQueued = false;
    let forceNextOpen = false;
    let preferredTab = null;
    let overviewOpen = false;

    function allTabs() {
        return Array.from(shell.querySelectorAll('[data-hnt-chat-tab]'));
    }

    function tabName(tab) {
        return String(
            tab.getAttribute('data-hnt-comms-name')
            || tab.querySelector('.hnt-chat-tab__meta strong')?.textContent
            || labels.conversation
        ).trim();
    }

    function decorateTab(tab) {
        if (!tab) return;
        const name = tabName(tab);
        tab.setAttribute('title', name);
        tab.setAttribute('aria-label', name);
        tab.dataset.hntCommsDecorated = '1';
    }

    function headerUnreadCount() {
        const badge = document.querySelector('[data-header-badge="messages"]');
        return Math.max(0, Number.parseInt(badge?.textContent || '0', 10) || 0);
    }

    function updateLauncherCount() {
        const countNode = launcher.querySelector('[data-hnt-comms-count]');
        const count = headerUnreadCount();
        if (!countNode) return;
        countNode.textContent = count > 99 ? '99+' : String(count);
        countNode.hidden = count <= 0;
    }

    function persistTabs() {
        const tabs = allTabs().map(function (tab) {
            return {
                id: tab.getAttribute('data-conversation-id') || tab.getAttribute('data-hnt-chat-tab'),
                url: tab.getAttribute('data-hnt-chat-tab-url') || '',
                minimized: tab.classList.contains('is-minimized') || tab.classList.contains('hnt-comms-signal'),
            };
        }).filter(function (item) {
            return item.id && item.url;
        });

        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify(tabs));
        } catch (_error) {
            // Session persistence is optional.
        }
    }

    function overviewSourceLinks() {
        return Array.from(document.querySelectorAll('.header-message-list a.header-message-item'));
    }

    function overviewSearchValue() {
        return String(overview.querySelector('[data-hnt-comms-overview-search]')?.value || '').trim().toLowerCase();
    }

    function renderOverview() {
        const list = overview.querySelector('[data-hnt-comms-overview-list]');
        const meta = overview.querySelector('[data-hnt-comms-overview-meta]');
        const footer = overview.querySelector('[data-hnt-comms-overview-footer]');
        if (!list || !meta || !footer) return;

        const footerSource = document.querySelector('#messagesDropdown .header-dropdown-footer');
        if (footerSource?.href) footer.href = footerSource.href;

        const query = overviewSearchValue();
        const sourceLinks = overviewSourceLinks();
        const rows = sourceLinks.map(function (link) {
            const href = link.getAttribute('href') || '/messages';
            const chatUrl = link.getAttribute('data-hnt-chat-tab-url') || href;
            const conversationId = link.getAttribute('data-hnt-chat-conversation-id') || '';
            const title = String(link.querySelector('strong')?.childNodes?.[0]?.textContent || link.querySelector('strong')?.textContent || labels.conversation).trim();
            const preview = String(link.querySelector('small')?.textContent || '').trim();
            const time = String(link.querySelector('time')?.textContent || '').trim();
            const avatar = link.querySelector('img')?.getAttribute('src') || '/assets/vikinger/img/default-avatar.svg';
            const unread = link.classList.contains('unread') || link.classList.contains('is-unread');
            const haystack = (title + ' ' + preview).toLowerCase();

            return {
                href,
                chatUrl,
                conversationId,
                title,
                preview,
                time,
                avatar,
                unread,
                hidden: query !== '' && !haystack.includes(query),
            };
        });

        meta.textContent = String(sourceLinks.length) + (isEnglish ? ' conversations' : ' Gespräche');

        if (rows.length === 0) {
            list.innerHTML = ''
                + '<div class="hnt-comms-overview__empty">'
                + '  <i class="ph ph-chat-circle-dots" aria-hidden="true"></i>'
                + '  <strong>' + labels.emptyTitle + '</strong>'
                + '  <span>' + labels.emptyBody + '</span>'
                + '</div>';
            return;
        }

        list.innerHTML = rows.map(function (row) {
            return ''
                + '<a class="hnt-comms-overview__item' + (row.unread ? ' unread' : '') + '"'
                + ' href="' + row.href.replace(/"/g, '&quot;') + '"'
                + ' data-hnt-chat-tab-open'
                + (row.conversationId ? ' data-hnt-chat-conversation-id="' + row.conversationId.replace(/"/g, '&quot;') + '"' : '')
                + ' data-hnt-chat-tab-url="' + row.chatUrl.replace(/"/g, '&quot;') + '"'
                + (row.hidden ? ' hidden' : '') + '>'
                + '  <span class="hnt-comms-overview__avatar"><img src="' + row.avatar.replace(/"/g, '&quot;') + '" alt=""></span>'
                + '  <span class="hnt-comms-overview__copy">'
                + '    <strong>' + escapeHtml(row.title) + '</strong>'
                + '    <small>' + escapeHtml(row.preview) + '</small>'
                + '  </span>'
                + '  <time>' + escapeHtml(row.time) + '</time>'
                + '</a>';
        }).join('');
    }

    function escapeHtml(value) {
        return String(value || '').replace(/[&<>"']/g, function (character) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            }[character] || character;
        });
    }

    function closeOverview(queue = true) {
        if (!overviewOpen) return;
        overviewOpen = false;
        overview.remove();
        shell.classList.remove('has-overview');
        launcher.setAttribute('aria-expanded', 'false');
        if (queue) queueArrange();
    }

    function openOverview() {
        if (overviewOpen) return;
        overviewOpen = true;
        renderOverview();
        allTabs().forEach(function (tab) {
            tab.classList.add('is-minimized');
        });
        panelSlot.appendChild(overview);
        shell.classList.add('has-overview');
        launcher.setAttribute('aria-expanded', 'true');
        queueArrange();
        window.setTimeout(function () {
            overview.querySelector('[data-hnt-comms-overview-search]')?.focus();
        }, 30);
    }

    function arrangeTabs(preferred) {
        if (arranging) return;
        arranging = true;

        try {
            const tabs = allTabs();
            const directTabs = Array.from(shell.children).filter(function (child) {
                return child.matches
                    && child.matches('[data-hnt-chat-tab]')
                    && !child.classList.contains('is-minimized');
            });

            let active = null;

            if (!overviewOpen) {
                if (forceNextOpen && preferred && tabs.includes(preferred)) {
                    preferred.classList.remove('is-minimized');
                    active = preferred;
                    forceNextOpen = false;
                } else if (directTabs.length) {
                    active = directTabs[directTabs.length - 1];
                    active.classList.remove('is-minimized');
                } else {
                    const current = panelSlot.querySelector('[data-hnt-chat-tab]');
                    if (current && !current.classList.contains('is-minimized')) {
                        active = current;
                    }
                }
            }

            tabs.forEach(function (tab) {
                decorateTab(tab);

                if (tab === active && !tab.classList.contains('is-minimized')) {
                    tab.classList.add('hnt-comms-active');
                    tab.classList.remove('hnt-comms-signal', 'is-minimized');
                    if (tab.parentElement !== panelSlot) panelSlot.appendChild(tab);
                    return;
                }

                tab.classList.remove('hnt-comms-active');
                tab.classList.add('hnt-comms-signal', 'is-minimized');
                if (tab.parentElement !== railList) railList.appendChild(tab);
            });

            const hasActive = Boolean(panelSlot.querySelector('[data-hnt-chat-tab]:not(.is-minimized)'));
            shell.classList.toggle('has-active', hasActive);
            shell.classList.toggle('has-signals', Boolean(railList.querySelector('[data-hnt-chat-tab]')));
            updateLauncherCount();
            persistTabs();
        } finally {
            arranging = false;
        }
    }

    function queueArrange(preferred) {
        if (preferred) preferredTab = preferred;
        if (arrangeQueued) return;
        arrangeQueued = true;

        window.requestAnimationFrame(function () {
            arrangeQueued = false;
            const nextPreferred = preferredTab;
            preferredTab = null;
            arrangeTabs(nextPreferred);
        });
    }

    const observer = new MutationObserver(function (records) {
        if (arranging) return;

        let addedTab = null;
        records.forEach(function (record) {
            Array.from(record.addedNodes || []).forEach(function (node) {
                if (!(node instanceof Element)) return;
                if (node.matches('[data-hnt-chat-tab]')) addedTab = node;
                const nested = node.querySelector?.('[data-hnt-chat-tab]');
                if (nested) addedTab = nested;
            });

            if (record.type === 'attributes' && record.target.matches?.('[data-hnt-chat-tab]')) {
                addedTab = record.target;
            }
        });

        queueArrange(addedTab);
    });

    observer.observe(shell, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['class'],
    });

    const headerLiveObserver = new MutationObserver(function () {
        updateLauncherCount();
        if (overviewOpen) renderOverview();
    });

    document.querySelectorAll('.header-message-list, [data-header-badge="messages"]').forEach(function (node) {
        headerLiveObserver.observe(node, {
            childList: true,
            subtree: true,
            characterData: true,
            attributes: true,
            attributeFilter: ['class'],
        });
    });

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-hnt-chat-tab-open]');
        if (opener) {
            forceNextOpen = true;
            closeOverview(false);

            const openUrl = opener.getAttribute('data-hnt-chat-tab-url') || opener.href || '';
            if (openUrl && typeof window.HNT_COMMS_OPEN === 'function') {
                event.preventDefault();
                event.stopImmediatePropagation();
                window.HNT_COMMS_OPEN(openUrl, opener);
            }

            if (opener.closest('#hntMessageShell')) {
                window.setTimeout(function () {
                    const close = document.querySelector('#hntMessageShell [data-hnt-messages-close]');
                    if (close) close.click();
                }, 0);
            }

            if (opener.closest('#messagesDropdown')) {
                window.setTimeout(function () {
                    const trigger = document.querySelector('#messagesMenuTrigger');
                    if (trigger?.getAttribute('aria-expanded') === 'true') trigger.click();
                }, 0);
            }
            return;
        }

        const railTab = event.target.closest('.hnt-comms-rail-list [data-hnt-chat-tab]');
        if (railTab) {
            closeOverview(false);
            forceNextOpen = true;
            queueArrange(railTab);
        }
    }, true);

    launcher.addEventListener('click', function () {
        if (overviewOpen) {
            closeOverview();
        } else {
            openOverview();
        }
    });

    overview.querySelector('[data-hnt-comms-overview-close]')?.addEventListener('click', function () {
        closeOverview();
    });

    overview.querySelector('[data-hnt-comms-overview-search]')?.addEventListener('input', function () {
        renderOverview();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && overviewOpen) closeOverview();
    });

    document.addEventListener('hnt:comms-tab-minimized', function (event) {
        queueArrange(event.detail?.tab || null);
    });

    document.addEventListener('hnt:comms-tab-restored', function (event) {
        queueArrange(event.detail?.tab || null);
    });

    document.addEventListener('hnt:comms-tab-activated', function (event) {
        closeOverview(false);
        forceNextOpen = true;
        queueArrange(event.detail?.tab || null);
    });

    updateLauncherCount();
    queueArrange();
});
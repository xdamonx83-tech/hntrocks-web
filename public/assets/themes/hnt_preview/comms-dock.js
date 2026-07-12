document.addEventListener('DOMContentLoaded', function () {
    const shell = document.querySelector('[data-hnt-chat-tabs-shell]');
    if (!shell || shell.dataset.hntCommsReady === '1') return;

    shell.dataset.hntCommsReady = '1';
    shell.classList.add('hnt-comms-dock');

    const locale = String(document.documentElement.lang || 'de').toLowerCase();
    const isEnglish = locale.startsWith('en');

    const panelSlot = document.createElement('div');
    panelSlot.className = 'hnt-comms-panel-slot';
    panelSlot.setAttribute('data-hnt-comms-panel-slot', '');

    const rail = document.createElement('aside');
    rail.className = 'hnt-comms-rail';
    rail.setAttribute('aria-label', isEnglish ? 'Active conversations' : 'Aktive Gespräche');

    const railList = document.createElement('div');
    railList.className = 'hnt-comms-rail-list';
    railList.setAttribute('data-hnt-comms-rail-list', '');

    const launcher = document.createElement('button');
    launcher.type = 'button';
    launcher.className = 'hnt-comms-launcher';
    launcher.setAttribute('data-hnt-comms-launcher', '');
    launcher.setAttribute('aria-label', isEnglish ? 'Open communications' : 'Kommunikation öffnen');
    launcher.innerHTML = ''
        + '<i class="ph ph-chats-circle" aria-hidden="true"></i>'
        + '<span class="hnt-comms-launcher__label">Comms</span>'
        + '<span class="hnt-comms-launcher__count" data-hnt-comms-count hidden>0</span>';

    rail.appendChild(railList);
    rail.appendChild(launcher);
    shell.appendChild(panelSlot);
    shell.appendChild(rail);

    let arranging = false;
    let arrangeQueued = false;
    let forceNextOpen = false;
    let preferredTab = null;

    function allTabs() {
        return Array.from(shell.querySelectorAll('[data-hnt-chat-tab]'));
    }

    function tabName(tab) {
        return String(
            tab.getAttribute('data-hnt-comms-name')
            || tab.querySelector('.hnt-chat-tab__meta strong')?.textContent
            || (isEnglish ? 'Conversation' : 'Gespräch')
        ).trim();
    }

    function decorateTab(tab) {
        if (!tab) return;
        const name = tabName(tab);
        tab.setAttribute('title', name);
        tab.setAttribute('aria-label', name);
        tab.dataset.hntCommsDecorated = '1';
    }

    function updateLauncherCount() {
        const countNode = launcher.querySelector('[data-hnt-comms-count]');
        const count = allTabs().length;
        if (!countNode) return;
        countNode.textContent = count > 99 ? '99+' : String(count);
        countNode.hidden = count <= 0;
    }

    function visibleHeaderMessageTrigger() {
        const selectors = '[data-hnt-messages-open], #messagesMenuTrigger';
        return Array.from(document.querySelectorAll(selectors)).find(function (trigger) {
            return trigger.offsetParent !== null;
        }) || document.querySelector(selectors);
    }

    function arrangeTabs(preferred) {
        if (arranging) return;
        arranging = true;

        try {
            const tabs = allTabs();
            const directTabs = Array.from(shell.children).filter(function (child) {
                return child.matches && child.matches('[data-hnt-chat-tab]');
            });

            let active = null;

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
        attributeFilter: ['class']
    });

    document.addEventListener('click', function (event) {
        const opener = event.target.closest('[data-hnt-chat-tab-open]');
        if (opener) {
            forceNextOpen = true;

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
            forceNextOpen = true;
            queueArrange(railTab);
        }
    }, true);

    launcher.addEventListener('click', function () {
        const active = panelSlot.querySelector('[data-hnt-chat-tab]:not(.is-minimized)');
        if (active) {
            const minimize = active.querySelector('[data-hnt-chat-tab-minimize]');
            if (minimize) {
                minimize.click();
            } else {
                active.classList.add('is-minimized');
                queueArrange(active);
            }
            return;
        }

        const lastSignal = railList.querySelector('[data-hnt-chat-tab]');
        if (lastSignal) {
            forceNextOpen = true;
            lastSignal.classList.remove('is-minimized');
            shell.appendChild(lastSignal);
            queueArrange(lastSignal);
            return;
        }

        const messageTrigger = visibleHeaderMessageTrigger();
        if (messageTrigger) messageTrigger.click();
    });

    queueArrange();
});

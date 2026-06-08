(() => {
    const config = window.hntHeaderLiveBadges || {};
    const endpoint = config.endpoint;
    const interval = Number(config.interval || 15000);

    if (!endpoint) {
        return;
    }

    let inFlight = false;

    const formatCount = (value) => {
        const count = Math.max(0, Number.parseInt(value, 10) || 0);
        return count > 99 ? '99+' : String(count);
    };

    const setBadge = (name, value) => {
        const badge = document.querySelector(`[data-hh-live-badge="${name}"]`);
        if (!badge) {
            return;
        }

        const count = Math.max(0, Number.parseInt(value, 10) || 0);
        badge.textContent = formatCount(count);
        badge.dataset.count = String(count);
        badge.classList.toggle('hidden', count <= 0);
    };

    const refreshBadges = async () => {
        if (inFlight || document.hidden) {
            return;
        }

        inFlight = true;

        try {
            const response = await fetch(endpoint, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            setBadge('notifications', payload.notifications_unread);
            setBadge('messages', payload.messages_unread);
        } catch (error) {
            // Keep the header stable. The next interval/focus event retries quietly.
        } finally {
            inFlight = false;
        }
    };

    window.hntRefreshHeaderBadges = refreshBadges;

    refreshBadges();
    window.setInterval(refreshBadges, Math.max(5000, interval));
    window.addEventListener('focus', refreshBadges);
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            refreshBadges();
        }
    });
    document.addEventListener('hnt:header-badges-refresh', refreshBadges);
})();

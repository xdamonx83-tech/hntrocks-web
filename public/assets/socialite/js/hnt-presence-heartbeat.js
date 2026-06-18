(function () {
    'use strict';

    if (window.HH_PRESENCE_HEARTBEAT_STARTED) {
        return;
    }

    window.HH_PRESENCE_HEARTBEAT_STARTED = true;

    var endpoint = (window.HH_PRESENCE && window.HH_PRESENCE.endpoint) || '/presence/heartbeat';
    var csrfToken = document.querySelector('meta[name="csrf-token"]')
        ? document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        : '';
    var intervalMs = 60000;
    var initialDelayMs = 3000;
    var minFocusGapMs = 15000;
    var lastSentAt = 0;
    var pending = false;

    function canSend() {
        return !pending && !document.hidden && csrfToken;
    }

    function sendHeartbeat(force) {
        var now = Date.now();

        if (!canSend()) {
            return;
        }

        if (!force && now - lastSentAt < minFocusGapMs) {
            return;
        }

        pending = true;

        fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken,
            },
        }).then(function () {
            lastSentAt = Date.now();
        }).catch(function () {
            // Presence heartbeat is best-effort and must not interrupt the page.
        }).finally(function () {
            pending = false;
        });
    }

    function sendWhenVisible() {
        sendHeartbeat(false);
    }

    window.setTimeout(function () {
        sendHeartbeat(true);
    }, initialDelayMs);

    window.setInterval(function () {
        sendHeartbeat(false);
    }, intervalMs);

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            sendWhenVisible();
        }
    });

    window.addEventListener('focus', sendWhenVisible);
})();

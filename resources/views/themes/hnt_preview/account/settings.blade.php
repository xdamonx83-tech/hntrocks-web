@php
    $payloadDirectory = resource_path('views/themes/hnt_preview/account/demo/settings-compact');
    $payloadFiles = ['part1.txt', 'part2.txt'];

    $encodedPayload = '';

    foreach ($payloadFiles as $payloadFile) {
        $payloadPath = $payloadDirectory.DIRECTORY_SEPARATOR.$payloadFile;
        abort_unless(is_file($payloadPath), 500, 'Settings demo payload is incomplete.');
        $encodedPayload .= trim((string) file_get_contents($payloadPath));
    }

    abort_unless(strlen($encodedPayload) === 15552, 500, 'Settings demo payload has an invalid length.');

    $compressedPayload = base64_decode($encodedPayload, true);
    abort_unless(is_string($compressedPayload), 500, 'Settings demo payload is not valid base64.');

    $settingsDemoHtml = gzdecode($compressedPayload);
    abort_unless(
        is_string($settingsDemoHtml) && str_contains($settingsDemoHtml, 'class="settings-stage"'),
        500,
        'Settings demo payload could not be decoded.'
    );

    /*
     * The supplied static demo placed the blocked-user form inside a second,
     * page-wide form. Nested forms are invalid HTML and browsers discard the
     * inner form element. Keep the demo controller container, but make it a
     * neutral div so the real blocked-user form and its original CSS survive.
     */
    $settingsDemoHtml = str_replace(
        '<form id="settingsDemoForm" novalidate="">',
        '<div id="settingsDemoForm">',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        "</footer>\n</form>\n</section>\n<aside class=\"settings-status-card\">",
        "</footer>\n</div>\n</section>\n<aside class=\"settings-status-card\">",
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        "  form.addEventListener(\"submit\", (event) => {\n    event.preventDefault();\n    saveDemo();\n  });\n",
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        "  document.getElementById(\"settingsStatusSave\").addEventListener(\"click\", saveDemo);\n",
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        [
            "  document.getElementById(\"settingsSaveTop\").addEventListener(\"click\", saveDemo);\n",
            "  document.getElementById(\"settingsDiscardTop\").addEventListener(\"click\", discardDemo);\n",
            "  document.getElementById(\"settingsDiscardBottom\").addEventListener(\"click\", discardDemo);\n",
        ],
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        "  document.querySelectorAll(\".settings-choice-options label\").forEach((label) => {\n    label.addEventListener(\"click\", () => {\n      document.querySelectorAll(\".settings-choice-options label\").forEach((item) => item.classList.remove(\"active\"));\n      label.classList.add(\"active\");\n    });\n  });\n",
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        "  const notificationMaster = document.getElementById(\"notificationMaster\");\n  const notificationToggles = [...document.querySelectorAll(\".notification-toggle\")];\n\n  notificationMaster.addEventListener(\"change\", () => {\n    notificationToggles.forEach((toggle) => toggle.checked = notificationMaster.checked);\n    markDirty();\n  });\n\n  notificationToggles.forEach((toggle) => {\n    toggle.addEventListener(\"change\", () => {\n      notificationMaster.checked = notificationToggles.every((item) => item.checked);\n    });\n  });\n",
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        "  function updateBlockedCount() {\n    const count = document.querySelectorAll(\"#blockedUsersList > article\").length;\n    document.getElementById(\"blockedCountMain\").textContent = count;\n    document.getElementById(\"blockedCountNav\").textContent = count;\n  }\n\n  function bindUnblockButtons() {\n    document.querySelectorAll(\".unblock-button\").forEach((button) => {\n      button.onclick = () => {\n        button.closest(\"article\").remove();\n        updateBlockedCount();\n        markDirty();\n        showToast(\"Blockierung aufgehoben\");\n      };\n    });\n  }\n\n  document.getElementById(\"blockUserForm\").addEventListener(\"submit\", (event) => {\n    event.preventDefault();\n    const usernameInput = document.getElementById(\"blockUsername\");\n    const reasonInput = document.getElementById(\"blockReason\");\n    const username = usernameInput.value.trim().replace(/^@/, \"\");\n    if (!username) return;\n\n    const row = document.createElement(\"article\");\n    row.innerHTML = `\n      <span class=\"settings-generated-avatar\">\${username.charAt(0).toUpperCase()}</span>\n      <div><strong>\${username}</strong><span>@\${username.toLowerCase()}</span><small></small></div>\n      <button type=\"button\" class=\"unblock-button\">Blockierung aufheben</button>\n    `;\n    row.querySelector(\"small\").textContent = reasonInput.value.trim() || \"Keine Notiz gespeichert\";\n    document.getElementById(\"blockedUsersList\").prepend(row);\n    usernameInput.value = \"\";\n    reasonInput.value = \"\";\n    bindUnblockButtons();\n    updateBlockedCount();\n    markDirty();\n    showToast(\"Nutzer in der Demo blockiert\");\n  });\n\n  bindUnblockButtons();\n",
        '',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        'document.getElementById("blockUserForm").addEventListener',
        'document.getElementById("blockUserForm")?.addEventListener',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        "  updateBlockedCount();\n",
        '',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        '/assets/themes/hnt_preview/dashboard-feed/assets/noah.jpg',
        '/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg',
        $settingsDemoHtml
    );

    /*
     * Replace only the first complete site-header element. The static header
     * contains nested <header> elements inside its dropdowns, so a simple
     * regular expression would stop too early and corrupt the document.
     */
    $staticHeaderStart = strpos($settingsDemoHtml, '<header class="site-header">');
    abort_unless($staticHeaderStart !== false, 500, 'Settings demo header could not be located.');

    $headerFragment = substr($settingsDemoHtml, $staticHeaderStart);
    preg_match_all('/<\/?header\b[^>]*>/i', $headerFragment, $headerTokens, PREG_OFFSET_CAPTURE);

    $headerDepth = 0;
    $staticHeaderLength = null;

    foreach ($headerTokens[0] ?? [] as [$headerToken, $headerOffset]) {
        if (str_starts_with(strtolower($headerToken), '</header')) {
            $headerDepth--;

            if ($headerDepth === 0) {
                $staticHeaderLength = $headerOffset + strlen($headerToken);
                break;
            }

            continue;
        }

        $headerDepth++;
    }

    abort_unless(is_int($staticHeaderLength) && $staticHeaderLength > 0, 500, 'Settings demo header is incomplete.');

    $sharedHeaderHtml = view('themes.hnt_preview.partials.header')->render();
    $sharedHeaderHtml = str_replace(
        'class="settings-button header-dropdown-trigger" id="settingsMenuTrigger"',
        'class="settings-button header-dropdown-trigger settings-page-active" id="settingsMenuTrigger"',
        $sharedHeaderHtml
    );

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $sharedHeaderHtml,
        $staticHeaderStart,
        $staticHeaderLength
    );

    abort_unless(isset($generalSettings) && is_array($generalSettings), 500, 'General settings data is missing.');

    $generalPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel active" data-settings-panel="general">'
    );
    abort_unless($generalPanelStart !== false, 500, 'General settings panel could not be located.');

    $notificationsPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="notifications" hidden="">',
        $generalPanelStart
    );
    abort_unless($notificationsPanelStart !== false, 500, 'Notifications settings panel could not be located.');

    $realGeneralHtml = view('themes.hnt_preview.account.partials.general-settings', [
        'generalSettings' => $generalSettings,
    ])->render();

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realGeneralHtml,
        $generalPanelStart,
        $notificationsPanelStart - $generalPanelStart
    );

    $notificationsPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="notifications" hidden="">'
    );
    abort_unless($notificationsPanelStart !== false, 500, 'Notifications settings panel could not be located.');

    $privacyPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="privacy" hidden="">',
        $notificationsPanelStart
    );
    abort_unless($privacyPanelStart !== false, 500, 'Privacy settings panel could not be located.');

    $realNotificationsHtml = view('themes.hnt_preview.account.partials.notification-settings', [
        'settings' => $settings,
        'notificationGroups' => $notificationGroups,
    ])->render();

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realNotificationsHtml,
        $notificationsPanelStart,
        $privacyPanelStart - $notificationsPanelStart
    );

    abort_unless(isset($privacySettings), 500, 'Privacy settings data is missing.');

    $privacyPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="privacy" hidden="">'
    );
    abort_unless($privacyPanelStart !== false, 500, 'Privacy settings panel could not be located.');

    $blockedPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="blocked" hidden="">',
        $privacyPanelStart
    );
    abort_unless($blockedPanelStart !== false, 500, 'Blocked users settings panel could not be located.');

    $realPrivacyHtml = view('themes.hnt_preview.account.partials.privacy-settings', [
        'privacySettings' => $privacySettings,
    ])->render();

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realPrivacyHtml,
        $privacyPanelStart,
        $blockedPanelStart - $privacyPanelStart
    );

    abort_unless(isset($blockedUsers), 500, 'Blocked users data is missing.');

    $blockedPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="blocked" hidden="">'
    );
    abort_unless($blockedPanelStart !== false, 500, 'Blocked users settings panel could not be located.');

    $securityPanelStart = strpos(
        $settingsDemoHtml,
        '<section class="settings-panel" data-settings-panel="security" hidden="">',
        $blockedPanelStart
    );
    abort_unless($securityPanelStart !== false, 500, 'Security settings panel could not be located.');

    $realBlockedUsersHtml = view('themes.hnt_preview.account.partials.blocked-users-settings', [
        'blockedUsers' => $blockedUsers,
    ])->render();

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realBlockedUsersHtml,
        $blockedPanelStart,
        $securityPanelStart - $blockedPanelStart
    );

    $settingsDemoHtml = str_replace(
        '<button class="active" data-settings-tab="general" data-title="Allgemein" type="button">',
        '<button class="active" data-settings-tab="general" data-title="'.e(__('settings.general_tab')).'" type="button">',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<span><strong>Allgemein</strong><small>Sprache, Darstellung und Konto</small></span><i>Basis</i>',
        '<span><strong>'.e(__('settings.general_tab')).'</strong><small>'.e(__('settings.general_nav_description')).'</small></span><i>'.e(__('settings.general_badge')).'</i>',
        $settingsDemoHtml
    );

    $settingsDemoHtml = str_replace(
        '<button data-settings-tab="notifications" data-title="Benachrichtigungen" type="button">',
        '<button data-settings-tab="notifications" data-title="'.e(__('settings.notifications_tab')).'" type="button">',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<span><strong>Benachrichtigungen</strong><small>Feed, Freunde, LFG und Cups</small></span><i>9</i>',
        '<span><strong>'.e(__('settings.notifications_tab')).'</strong><small>'.e(__('settings.notifications_nav_description')).'</small></span><i>'.e(__('settings.notifications_badge')).'</i>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<button data-settings-tab="privacy" data-title="Privatsphäre" type="button">',
        '<button data-settings-tab="privacy" data-title="'.e(__('settings.privacy_tab')).'" type="button">',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<span><strong>Privatsphäre</strong><small>Sichtbarkeit und Kontaktregeln</small></span><i>8</i>',
        '<span><strong>'.e(__('settings.privacy_tab')).'</strong><small>'.e(__('settings.privacy_nav_description')).'</small></span><i>'.e(__('settings.privacy_badge')).'</i>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<button data-settings-tab="blocked" data-title="Blockierte Nutzer" type="button">',
        '<button data-settings-tab="blocked" data-title="'.e(__('settings.blocked_tab')).'" type="button">',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<span><strong>Blockierte Nutzer</strong><small>Blockierungen verwalten</small></span>',
        '<span><strong>'.e(__('settings.blocked_tab')).'</strong><small>'.e(__('settings.blocked_nav_description')).'</small></span>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<i id="blockedCountNav">2</i>',
        '<i id="blockedCountNav">'.e((string) $blockedUsers->count()).'</i>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<h2 id="settingsPanelTitle">Allgemein</h2>',
        '<h2 id="settingsPanelTitle">'.e(__('settings.general_tab')).'</h2>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        '<button id="settingsDiscardTop" type="button">Verwerfen</button>'."\n".
        '<button class="primary" id="settingsSaveTop" type="button">Änderungen speichern</button>',
        '<button id="settingsDiscardTop" type="reset" form="settingsGeneralForm">'.e(__('settings.discard')).'</button>'."\n".
        '<button class="primary" id="settingsSaveTop" type="submit" form="settingsGeneralForm">'.e(__('settings.save_changes')).'</button>',
        $settingsDemoHtml
    );
    $saveFooterStart = strpos($settingsDemoHtml, '<footer class="settings-save-footer">');
    abort_unless($saveFooterStart !== false, 500, 'Settings save footer could not be located.');

    $saveFooterEnd = strpos($settingsDemoHtml, '</footer>', $saveFooterStart);
    abort_unless($saveFooterEnd !== false, 500, 'Settings save footer is incomplete.');

    $realGeneralSaveFooter = view('themes.hnt_preview.account.partials.general-save-footer')->render();
    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realGeneralSaveFooter,
        $saveFooterStart,
        ($saveFooterEnd + strlen('</footer>')) - $saveFooterStart
    );
    $settingsDemoHtml = str_replace(
        '<span class="settings-save-state" id="settingsSaveState"><i></i>Keine offenen Änderungen</span>',
        '<span class="settings-save-state" id="settingsSaveState"><i></i>'.e(__('settings.no_changes')).'</span>',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        'saveState.innerHTML = "<i></i>Ungespeicherte Änderungen";',
        'saveState.innerHTML = "<i></i>'.e(__('settings.unsaved_changes')).'";',
        $settingsDemoHtml
    );
    $settingsDemoHtml = str_replace(
        'saveState.innerHTML = "<i></i>Keine offenen Änderungen";',
        'saveState.innerHTML = "<i></i>'.e(__('settings.no_changes')).'";',
        $settingsDemoHtml
    );

    /* Replace only the static right-hand security widget with real account data. */
    abort_unless(isset($securityStatus) && is_array($securityStatus), 500, 'Settings security status data is missing.');
    $staticStatusStart = strpos($settingsDemoHtml, '<aside class="settings-status-card">');
    abort_unless($staticStatusStart !== false, 500, 'Settings security status widget could not be located.');

    $staticStatusEnd = strpos($settingsDemoHtml, '</aside>', $staticStatusStart);
    abort_unless($staticStatusEnd !== false, 500, 'Settings security status widget is incomplete.');

    $realStatusHtml = view('themes.hnt_preview.account.partials.security-status', [
        'securityStatus' => $securityStatus,
    ])->render();

    $settingsDemoHtml = substr_replace(
        $settingsDemoHtml,
        $realStatusHtml,
        $staticStatusStart,
        ($staticStatusEnd + strlen('</aside>')) - $staticStatusStart
    );

    if (! str_contains($settingsDemoHtml, 'real-general-settings.css')) {
        $settingsDemoHtml = str_replace(
            '</head>',
            '<link href="'.asset('assets/themes/hnt_preview/settings/real-general-settings.css').'?v=20260718-1" rel="stylesheet"/>' . "\n" . '</head>',
            $settingsDemoHtml
        );
    }

    if (! str_contains($settingsDemoHtml, 'name="csrf-token"')) {
        $settingsDemoHtml = str_replace(
            '</head>',
            '<meta name="csrf-token" content="'.e(csrf_token()).'"/>' . "\n" . '</head>',
            $settingsDemoHtml
        );
    }

    $sharedHeaderRuntime = implode("\n", [
        '<script>window.HNT_DASHBOARD_HEADER_ENDPOINT = '.json_encode(route('feed.index')).';</script>',
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header.js').'?v=20260714-1"></script>',
        '<script src="'.asset('assets/themes/hnt_preview/dashboard-feed/real-dashboard-header-live.js').'?v=20260714-1"></script>',
        '<script src="'.asset('assets/themes/hnt_preview/settings/real-general-settings.js').'?v=20260717-4"></script>',
    ]);

    $settingsDemoHtml = str_replace(
        '</body>',
        $sharedHeaderRuntime."\n".'</body>',
        $settingsDemoHtml
    );
@endphp
{!! $settingsDemoHtml !!}

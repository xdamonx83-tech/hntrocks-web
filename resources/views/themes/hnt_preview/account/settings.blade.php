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
        '/assets/themes/hnt_preview/dashboard-feed/assets/noah.jpg',
        '/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg',
        $settingsDemoHtml
    );
@endphp
{!! $settingsDemoHtml !!}

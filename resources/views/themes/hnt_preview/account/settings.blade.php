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

    $settingsDemoHtml = str_replace(
        '/assets/themes/hnt_preview/dashboard-feed/assets/noah.jpg',
        '/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg',
        $settingsDemoHtml
    );
@endphp
{!! $settingsDemoHtml !!}

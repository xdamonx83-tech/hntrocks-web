@php
    $payloadDirectory = resource_path('views/themes/hnt_preview/account/demo/settings-payload');
    $payloadFiles = [
        'part1.txt',
        'part2.txt',
        'part3.txt',
        'part4.txt',
        'part5a.txt',
        'part5b.txt',
        'part6.txt',
        'part7.txt',
    ];

    $encodedPayload = '';

    foreach ($payloadFiles as $payloadFile) {
        $payloadPath = $payloadDirectory.DIRECTORY_SEPARATOR.$payloadFile;
        abort_unless(is_file($payloadPath), 500, 'Settings demo payload is incomplete.');
        $encodedPayload .= trim((string) file_get_contents($payloadPath));
    }

    abort_unless(strlen($encodedPayload) === 59092, 500, 'Settings demo payload has an invalid length.');

    $compressedPayload = base64_decode($encodedPayload, true);
    abort_unless(is_string($compressedPayload), 500, 'Settings demo payload is not valid base64.');

    $settingsDemoHtml = @gzdecode($compressedPayload);

    if (! is_string($settingsDemoHtml) && strlen($compressedPayload) > 18) {
        $settingsDemoHtml = @gzinflate(substr($compressedPayload, 10, -8));
    }

    abort_unless(
        is_string($settingsDemoHtml) && str_contains($settingsDemoHtml, 'class="settings-stage"'),
        500,
        'Settings demo payload could not be decoded.'
    );

    // The supplied template references one avatar filename that is not part of
    // the shared dashboard assets. Use the matching existing demo avatar only
    // for that missing image; the visual structure remains untouched.
    $settingsDemoHtml = str_replace(
        '/assets/themes/hnt_preview/dashboard-feed/assets/noah.jpg',
        '/assets/themes/hnt_preview/dashboard-feed/assets/feed-jonathan.jpg',
        $settingsDemoHtml
    );
@endphp
{!! $settingsDemoHtml !!}

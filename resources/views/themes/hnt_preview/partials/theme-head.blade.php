@php
    $hntThemePreference = $themePreference
        ?? auth()->user()?->theme_preference
        ?? request()->cookie('hnt_theme_preference', 'light');
    $hntThemePilot = $themePilot ?? null;

    if (! in_array($hntThemePreference, ['light', 'dark', 'system'], true)) {
        $hntThemePreference = 'light';
    }
@endphp
<script>
(() => {
    const valid = ['light', 'dark', 'system'];
    const serverPreference = @json($hntThemePreference);
    const preference = valid.includes(serverPreference) ? serverPreference : 'light';

    try {
        localStorage.setItem('hnt_theme_preference', preference);
    } catch (_error) {
        // localStorage can be unavailable in strict privacy modes.
    }

    const resolved = preference === 'system'
        ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light')
        : preference;

    document.documentElement.dataset.themePreference = preference;
    document.documentElement.dataset.theme = resolved;
    document.documentElement.style.colorScheme = resolved;
})();
</script>
<link
    data-hnt-theme-tokens
    href="{{ asset('assets/themes/hnt_preview/theme-tokens.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-tokens.css')) ?: time() }}"
    rel="stylesheet"
>
<link
    data-hnt-dark-pilot
    href="{{ asset('assets/themes/hnt_preview/dark-mode-pilot.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dark-mode-pilot.css')) ?: time() }}"
    rel="stylesheet"
>
@if($hntThemePilot === 'settings')
<link
    data-hnt-settings-appearance
    href="{{ asset('assets/themes/hnt_preview/settings/appearance-theme.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/settings/appearance-theme.css')) ?: time() }}"
    rel="stylesheet"
>
@endif
<script
    data-hnt-theme-runtime
    src="{{ asset('assets/themes/hnt_preview/theme-runtime.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-runtime.js')) ?: time() }}"
    defer
></script>

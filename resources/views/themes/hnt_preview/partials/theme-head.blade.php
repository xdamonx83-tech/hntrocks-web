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
<link
    data-hnt-dark-pilot-fixes
    href="{{ asset('assets/themes/hnt_preview/dark-mode-pilot-fixes.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dark-mode-pilot-fixes.css')) ?: time() }}"
    rel="stylesheet"
>
<link
    data-hnt-dark-pilot-polish
    href="{{ asset('assets/themes/hnt_preview/dark-mode-pilot-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dark-mode-pilot-polish.css')) ?: time() }}"
    rel="stylesheet"
>
@if($hntThemePilot === 'settings')
<link
    data-hnt-settings-appearance
    href="{{ asset('assets/themes/hnt_preview/settings/appearance-theme.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/settings/appearance-theme.css')) ?: time() }}"
    rel="stylesheet"
>
<link
    data-hnt-settings-dark-mode
    href="{{ asset('assets/themes/hnt_preview/settings/dark-mode.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/settings/dark-mode.css')) ?: time() }}"
    rel="stylesheet"
>
<link
    data-hnt-settings-dark-tabs-topbar
    href="{{ asset('assets/themes/hnt_preview/settings/dark-mode-tabs-topbar.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/settings/dark-mode-tabs-topbar.css')) ?: time() }}"
    rel="stylesheet"
>
@elseif($hntThemePilot === 'profile')
<link
    data-hnt-profile-dark-mode
    href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-dark-mode.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-dark-mode.css')) ?: time() }}"
    rel="stylesheet"
>
<link
    data-hnt-profile-dark-mode-polish
    href="{{ asset('assets/themes/hnt_preview/dashboard-profile/profile-dark-mode-polish.css') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/dashboard-profile/profile-dark-mode-polish.css')) ?: time() }}"
    rel="stylesheet"
>
@endif
<script
    data-hnt-theme-runtime
    src="{{ asset('assets/themes/hnt_preview/theme-runtime.js') }}?v={{ @filemtime(public_path('assets/themes/hnt_preview/theme-runtime.js')) ?: time() }}"
    defer
></script>

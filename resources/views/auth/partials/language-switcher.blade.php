@php
    $hhCurrentLocale = app()->getLocale();
    $hhAuthLocaleOptions = [
        'de' => 'DE',
        'en' => 'EN',
    ];
@endphp

<nav class="hh-auth-language-switcher" aria-label="{{ __('ui.auth_language_switch_label') }}">
    @foreach ($hhAuthLocaleOptions as $locale => $label)
        <a
            class="hh-auth-language-link {{ $hhCurrentLocale === $locale ? 'is-active' : '' }}"
            href="{{ route('locale.switch', ['locale' => $locale]) }}"
            lang="{{ $locale }}"
            hreflang="{{ $locale }}"
            @if ($hhCurrentLocale === $locale) aria-current="true" @endif
        >{{ $label }}</a>
    @endforeach
</nav>

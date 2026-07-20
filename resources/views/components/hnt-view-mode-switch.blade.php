@props([
    'returnUrl' => request()->getRequestUri(),
    'showAuto' => true,
])

@php
    $currentPreference = (string) request()->attributes->get('hnt.view_preference', 'auto');
    $modes = $showAuto
        ? ['auto' => 'Auto', 'desktop' => 'Desktop', 'mobile' => 'Mobile']
        : ['desktop' => 'Desktop', 'mobile' => 'Mobile'];
@endphp

<nav {{ $attributes->class(['hnt-view-mode-switch']) }} aria-label="Ansicht wechseln" data-hnt-view-mode-switch>
    @foreach($modes as $mode => $label)
        <a
            href="{{ route('view-mode.set', ['mode' => $mode, 'return' => $returnUrl]) }}"
            data-hnt-view-mode="{{ $mode }}"
            @if($currentPreference === $mode) aria-current="true" @endif
        >{{ $label }}</a>
    @endforeach
</nav>

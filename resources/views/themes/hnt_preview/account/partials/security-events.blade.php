@foreach ($events as $event)
@php
    $eventKey = 'settings.security_events.'.$event->event;
    $eventTranslation = __($eventKey);
    $eventLabel = $eventTranslation === $eventKey
        ? \Illuminate\Support\Str::headline($event->event)
        : $eventTranslation;
    $location = data_get($event->meta, 'location', []);
    $countryCode = strtoupper((string) data_get($location, 'country_code', ''));
    $countryName = $countryCode;

    if ($countryCode !== '' && function_exists('locale_get_display_region')) {
        $displayCountry = locale_get_display_region('_'.$countryCode, app()->getLocale());

        if (is_string($displayCountry) && $displayCountry !== '') {
            $countryName = $displayCountry;
        }
    }

    $locationParts = array_values(array_unique(array_filter([
        data_get($location, 'city'),
        data_get($location, 'region') ?: data_get($location, 'region_code'),
        $countryName,
    ], static fn ($value) => filled($value))));
    $isLoginEvent = str_contains($event->event, 'login') || str_contains($event->event, 'two_factor');
    $isWarningEvent = str_contains($event->event, 'failed') || str_contains($event->event, 'blocked');
@endphp
<article data-security-event-id="{{ $event->id }}" class="{{ $isWarningEvent ? 'is-warning' : '' }}">
<span class="settings-log-icon">
@if ($isWarningEvent)
<strong aria-hidden="true">!</strong>
@else
<svg><use href="#i-check"></use></svg>
@endif
</span>
<div>
<strong>{{ $eventLabel }}</strong>
<small>
<span>IP: {{ $event->ip_address ?: __('ui.unknown') }}</span>
@if ($locationParts !== [])
<span title="{{ __('settings.security_location_notice') }}"> · {{ __('settings.security_location_label') }}: {{ implode(', ', $locationParts) }}</span>
@elseif ($isLoginEvent)
<span> · {{ __('settings.security_location_unknown') }}</span>
@endif
<span> · {{ \Illuminate\Support\Str::limit($event->user_agent ?: __('ui.no_user_agent_saved'), 90) }}</span>
</small>
</div>
<span>{{ $event->created_at->locale(app()->getLocale())->diffForHumans() }}</span>
</article>
@endforeach

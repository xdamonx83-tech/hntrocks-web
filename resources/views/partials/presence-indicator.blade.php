@php
    $user = $user ?? null;
    $viewer = $viewer ?? auth()->user();
    $showLabel = (bool) ($showLabel ?? false);
    $indicatorSize = $size ?? 'sm';
    $size = in_array($indicatorSize, ['xs', 'sm'], true) ? $indicatorSize : 'sm';
    $class = trim($class ?? '');

    $canShowPresence = $user && method_exists($user, 'allowsOnlineStatusVisibility')
        ? $user->allowsOnlineStatusVisibility($viewer)
        : (($user?->privacySettings?->show_online_status ?? true) !== false);
    $isOnline = $user && $canShowPresence && $user->isOnline();
    $label = $isOnline ? __('ui.presence_online') : __('ui.presence_offline');
    $dotSize = $size === 'xs' ? 'w-2 h-2' : 'w-3 h-3';
@endphp

@if ($user && $canShowPresence)
    <span class="inline-flex items-center gap-1.5 {{ $class }}" aria-label="{{ $label }}" title="{{ $label }}">
        <span class="{{ $dotSize }} rounded-full border border-white dark:border-slate-800 {{ $isOnline ? 'bg-green-500' : 'bg-slate-300 dark:bg-slate-600' }}" aria-hidden="true"></span>
        @if ($showLabel)
            <span class="text-xs font-semibold {{ $isOnline ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-white/70' }}">{{ $label }}</span>
        @endif
    </span>
@endif

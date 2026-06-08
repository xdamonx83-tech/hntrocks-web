@php
    $settingsUser = $settingsUser ?? auth()->user();
    $settingsAvatarUrl = $settingsAvatarUrl ?? ($settingsUser?->avatarUrl() ?? asset('assets/images/avatars/avatar-3.jpg'));
    $activeSettingsTab = $activeSettingsTab ?? 'notifications';
    $classicUrl = $classicUrl ?? null;

    $socialiteSettingsNav = [
        ['key' => 'profile', 'label' => __('ui.edit_profile'), 'url' => route('profile.edit')],
        ['key' => 'notifications', 'label' => __('ui.notification_settings'), 'url' => route('account.settings.edit')],
        ['key' => 'privacy', 'label' => __('ui.privacy'), 'url' => route('settings.privacy.edit')],
        ['key' => 'blocks', 'label' => 'Blockierte Nutzer', 'url' => route('settings.privacy.blocks')],
        ['key' => 'security', 'label' => __('ui.security'), 'url' => route('settings.security.index')],
    ];
@endphp

<style>
    .hnt-settings-toggle {
        position: relative;
        display: inline-flex;
        width: 52px;
        height: 30px;
        flex: 0 0 auto;
        cursor: pointer;
        align-items: center;
    }

    .hnt-settings-toggle input {
        position: absolute;
        width: 1px;
        height: 1px;
        opacity: 0;
        pointer-events: none;
    }

    .hnt-settings-toggle-track {
        position: relative;
        display: block;
        width: 52px;
        height: 30px;
        border-radius: 999px;
        background: rgba(255, 255, 255, .14);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
        transition: background .18s ease, box-shadow .18s ease;
    }

    .hnt-settings-toggle-dot {
        position: absolute;
        left: 4px;
        top: 4px;
        width: 22px;
        height: 22px;
        border-radius: 999px;
        background: #fff;
        box-shadow: 0 4px 10px rgba(15, 23, 42, .20);
        transition: transform .18s ease;
    }

    .hnt-settings-toggle input:checked + .hnt-settings-toggle-track {
        background: linear-gradient(135deg, #d8a439, #8f2b25);
        box-shadow: inset 0 0 0 1px rgba(216, 164, 57, .34), 0 8px 18px rgba(143, 43, 37, .22);
    }

    .hnt-settings-toggle input:checked + .hnt-settings-toggle-track .hnt-settings-toggle-dot {
        transform: translateX(22px);
    }

    .hnt-settings-toggle input:focus-visible + .hnt-settings-toggle-track {
        outline: 3px solid rgba(216, 164, 57, .28);
        outline-offset: 3px;
    }

    .dark .hnt-settings-toggle-track {
        background: rgba(255, 255, 255, .14);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .08);
    }
</style>

<div class="flex md:gap-8 gap-4 items-center md:p-8 p-6 md:pb-4">
    <div class="relative md:w-20 md:h-20 w-12 h-12 shrink-0">
        <a href="{{ route('profile.edit') }}" class="block w-full h-full">
            <img src="{{ $settingsAvatarUrl }}" class="object-cover w-full h-full rounded-full" alt="{{ $settingsUser?->name ?? $settingsUser?->username ?? 'User' }}">
        </a>
        <a href="{{ route('profile.edit') }}" class="md:p-1 p-0.5 rounded-full bg-slate-600 md:border-4 border-white absolute -bottom-2 -right-2 cursor-pointer dark:border-slate-700">
            <ion-icon name="camera" class="md:text-base text-xs text-white"></ion-icon>
        </a>
    </div>

    <div class="flex-1 min-w-0">
        <h3 class="md:text-xl text-base font-semibold text-black dark:text-white truncate">
            {{ $settingsUser?->name ?: $settingsUser?->username }}
        </h3>
        <p class="text-sm text-blue-600 mt-1 font-normal truncate">&#64;{{ $settingsUser?->username }}</p>
    </div>

</div>

<div class="relative border-b dark:border-slate-700" tabindex="-1" uk-slider="finite: true">
    <nav class="uk-slider-container overflow-hidden nav__underline px-6 p-0 border-transparent -mb-px">
        <ul class="uk-slider-items w-[calc(100%+10px)] !overflow-hidden">
            @foreach ($socialiteSettingsNav as $item)
                <li class="w-auto pr-2.5 {{ $activeSettingsTab === $item['key'] ? 'uk-active' : '' }}">
                    <a href="{{ $item['url'] }}" class="inline-flex items-center {{ $activeSettingsTab === $item['key'] ? 'text-blue-600' : '' }}">
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    <a class="absolute -translate-y-1/2 top-1/2 left-0 flex items-center w-20 h-full p-2 py-1 justify-start bg-gradient-to-r from-white via-white dark:from-slate-800 dark:via-slate-800" href="#" uk-slider-item="previous">
        <ion-icon name="chevron-back" class="text-2xl ml-1"></ion-icon>
    </a>
    <a class="absolute right-0 -translate-y-1/2 top-1/2 flex items-center w-20 h-full p-2 py-1 justify-end bg-gradient-to-l from-white via-white dark:from-slate-800 dark:via-slate-800" href="#" uk-slider-item="next">
        <ion-icon name="chevron-forward" class="text-2xl mr-1"></ion-icon>
    </a>
</div>

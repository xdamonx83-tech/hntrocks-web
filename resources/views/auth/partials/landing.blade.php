@php
    $hhAuthStart = ($hhAuthMode ?? 'login') === 'register' ? 'register' : 'login';
    $hhIsRegister = $hhAuthStart === 'register';
    $hhLogo = asset('assets/vikinger/img/brand/hunthub-logo.svg');
    $hhSlideIcon = asset('assets/vikinger/img/brand/hunthub-logo.svg');

    $hhAuthHeroImage = asset('assets/socialite/images/post/img-3.jpg');

    $hhCoreSocialProviderKeys = ['google', 'discord', 'twitch', 'microsoft'];

    $hhSocialProviderButtons = collect([
        [
            'key' => 'google',
            'label' => 'Google',
            'class' => 'bg-white text-slate-900 border border-slate-200 hover:bg-slate-50 dark:bg-white/5 dark:text-white dark:border-slate-800',
            'icon' => 'ph ph-google-logo',
        ],
        [
            'key' => 'discord',
            'label' => 'Discord',
            'class' => 'bg-[#5865f2] text-white hover:brightness-95',
            'icon' => 'ph ph-discord-logo',
        ],
        [
            'key' => 'twitch',
            'label' => 'Twitch',
            'class' => 'bg-[#9146ff] text-white hover:brightness-95',
            'icon' => 'ph ph-twitch-logo',
        ],
        [
            'key' => 'microsoft',
            'label' => 'Microsoft',
            'class' => 'bg-slate-800 text-white hover:bg-slate-900',
            'icon' => 'ph ph-microsoft-logo',
        ],
        [
            'key' => 'facebook',
            'label' => 'Facebook',
            'class' => 'bg-primary text-white hover:brightness-95',
            'icon' => 'ph ph-facebook-logo',
        ],
        [
            'key' => 'steam',
            'label' => 'Steam',
            'class' => 'bg-black text-white hover:bg-slate-950',
            'icon' => 'ph ph-steam-logo',
        ],
    ])->filter(function ($provider) use ($hhCoreSocialProviderKeys) {
        if (! (bool) config('social.enabled', true)) {
            return false;
        }

        $key = $provider['key'];

        // These are the real HNT login providers and must stay visible on the auth screen.
        // Runtime config still decides whether the redirect itself can complete successfully.
        if (in_array($key, $hhCoreSocialProviderKeys, true)) {
            return true;
        }

        $providerConfig = config("social.providers.{$key}", []);
        $serviceConfig = config("services.{$key}", []);

        $isEnabled = (bool) data_get($providerConfig, 'enabled', false);
        $hasProviderCredentials = filled(data_get($providerConfig, 'client_id')) || filled(data_get($providerConfig, 'client_secret'));
        $hasServiceCredentials = filled(data_get($serviceConfig, 'client_id')) || filled(data_get($serviceConfig, 'client_secret'));

        return $isEnabled || $hasProviderCredentials || $hasServiceCredentials;
    })->values();
@endphp

<div class="sm:flex">
    <div class="relative lg:w-[580px] md:w-96 w-full p-10 min-h-screen bg-white shadow-xl flex items-center pt-10 dark:bg-slate-900 z-10">
        <div class="w-full lg:max-w-sm mx-auto space-y-10" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: true">
            <div class="absolute top-10 left-10 right-10 flex items-start justify-between gap-4">
                <a href="{{ url('/') }}" class="inline-flex items-center min-w-0">
                    <img src="{{ $hhLogo }}" class="h-14 w-auto max-w-[9rem] object-contain dark:hidden" alt="hnt.rocks">
                    <img src="{{ $hhLogo }}" class="h-14 w-auto max-w-[9rem] object-contain hidden dark:!block" alt="hnt.rocks">
                </a>

                <nav class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/90 p-1 text-xs font-bold uppercase shadow-sm dark:border-slate-800 dark:bg-slate-900/90" aria-label="{{ __('ui.auth_language_switch_label') }}">
                    <a href="{{ route('locale.switch', ['locale' => 'de']) }}" lang="de" hreflang="de" class="rounded-full px-2.5 py-1 transition {{ app()->getLocale() === 'de' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10' }}" @if (app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
                    <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" hreflang="en" class="rounded-full px-2.5 py-1 transition {{ app()->getLocale() === 'en' ? 'bg-primary text-white' : 'text-slate-500 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-white/10' }}" @if (app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                </nav>
            </div>

            <div class="hidden">
                <img class="w-12" src="{{ $hhLogo }}" alt="hnt.rocks">
            </div>

            <div>
                <h2 class="text-2xl font-semibold mb-1.5">
                    {{ $hhIsRegister ? __('ui.create_account') : __('ui.auth_account_login_title') }}
                </h2>
                <p class="text-sm text-gray-700 font-normal dark:text-gray-300">
                    @if ($hhIsRegister)
                        {{ __('ui.login_title') }}?
                        <a href="{{ route('login') }}" class="text-blue-700">{{ __('ui.login_submit') }}</a>
                    @else
                        {{ __('ui.create_account') }}?
                        <a href="{{ route('register') }}" class="text-blue-700">{{ __('ui.register_now') }}</a>
                    @endif
                </p>
            </div>

            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
            @endif

            @if (session('referral_referrer_name'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ __('ui.invitation_from_detected', ['name' => session('referral_referrer_name')]) }}</div>
            @endif

            @error('social')
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">{{ $message }}</div>
            @enderror

            @if ($hhIsRegister)
                <form method="POST" action="{{ route('register.store') }}" class="space-y-7 text-sm text-black font-medium dark:text-white" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: true" novalidate>
                    @csrf

                    <div class="grid grid-cols-2 gap-4 gap-y-7">
                        <div>
                            <label for="register-username">{{ __('ui.username') }}</label>
                            <div class="mt-2.5">
                                <input id="register-username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" autofocus placeholder="KrispieTest" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                            </div>
                            @error('username') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="register-email">{{ __('ui.email') }}</label>
                            <div class="mt-2.5">
                                <input id="register-email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" placeholder="Email" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                            </div>
                            @error('email') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="register-password">{{ __('ui.password') }}</label>
                            <div class="mt-2.5">
                                <input id="register-password" name="password" type="password" autocomplete="new-password" placeholder="***" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                            </div>
                            @error('password') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="register-password-repeat">{{ __('ui.repeat_password') }}</label>
                            <div class="mt-2.5">
                                <input id="register-password-repeat" name="password_confirmation" type="password" autocomplete="new-password" placeholder="***" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                            </div>
                        </div>

                        <div class="col-span-2">
                            <label class="inline-flex items-start" for="register-legal-terms">
                                <input type="checkbox" id="register-legal-terms" name="legal_terms" value="1" @checked(old('legal_terms')) required class="!rounded-md accent-red-800 mt-0.5">
                                <span class="ml-2 font-normal text-gray-700 dark:text-gray-300">
                                    {{ __('ui.register_legal_accept_prefix') }}
                                    <a href="{{ route('legal.nutzungsbedingungen') }}" target="_blank" rel="noopener" class="text-blue-700 hover:underline">{{ __('ui.register_legal_accept_terms') }}</a>,
                                    <a href="{{ route('legal.datenschutz') }}" target="_blank" rel="noopener" class="text-blue-700 hover:underline">{{ __('ui.register_legal_accept_privacy') }}</a>
                                    {{ __('ui.register_legal_accept_connector') }}
                                    <a href="{{ route('legal.netiquette') }}" target="_blank" rel="noopener" class="text-blue-700 hover:underline">{{ __('ui.register_legal_accept_netiquette') }}</a>.
                                </span>
                            </label>
                            @error('legal_terms') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div class="col-span-2">
                            <button type="submit" class="button bg-primary text-white w-full">{{ __('ui.register_now') }}</button>
                        </div>
                    </div>

                    @if ($hhSocialProviderButtons->isNotEmpty())
                        <div class="text-center flex items-center gap-6">
                            <hr class="flex-1 border-slate-200 dark:border-slate-800">
                            {{ __('ui.auth_social_register_text') }}
                            <hr class="flex-1 border-slate-200 dark:border-slate-800">
                        </div>

                        <div class="hh-social-login-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(112px,1fr));gap:8px;" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 400 ;repeat: true">
                            @foreach ($hhSocialProviderButtons as $provider)
                                <a href="{{ route('social.redirect', ['provider' => $provider['key']]) }}" class="button !w-full !min-w-0 !h-11 !px-3 !m-0 flex items-center justify-center gap-2 text-sm font-semibold leading-none {{ $provider['class'] }}" title="{{ __('ui.start_with_provider', ['provider' => $provider['label']]) }}" aria-label="{{ __('ui.start_with_provider', ['provider' => $provider['label']]) }}">
                                    <i class="{{ $provider['icon'] }} text-lg shrink-0" aria-hidden="true"></i>
                                    <span class="truncate">{{ $provider['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </form>
            @else
                <form method="POST" action="{{ route('login.store') }}" class="space-y-7 text-sm text-black font-medium dark:text-white" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: true" novalidate>
                    @csrf

                    <div>
                        <label for="login-username">{{ __('ui.auth_username_or_email') }}</label>
                        <div class="mt-2.5">
                            <input id="login-username" name="login" type="text" value="{{ old('login') }}" autocomplete="username" autofocus placeholder="Email" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                        </div>
                        @error('login') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="login-password">{{ __('ui.password') }}</label>
                        <div class="mt-2.5">
                            <input id="login-password" name="password" type="password" autocomplete="current-password" placeholder="***" required class="!w-full !rounded-lg !bg-transparent !shadow-sm !border-slate-200 dark:!border-slate-800 dark:!bg-white/5">
                        </div>
                        @error('password') <p class="mt-2 text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <input id="login-remember" name="remember" type="checkbox" value="1" @checked(old('remember', true))>
                            <label for="login-remember" class="font-normal">{{ __('ui.remember_me') }}</label>
                        </div>
                        <a href="{{ route('password.request') }}" class="text-blue-700">{{ __('ui.forgot_password_question') }}</a>
                    </div>

                    <div>
                        <button type="submit" class="button bg-primary text-white w-full">{{ __('ui.login_submit') }}</button>
                    </div>

                    @if ($hhSocialProviderButtons->isNotEmpty())
                        <div class="text-center flex items-center gap-6">
                            <hr class="flex-1 border-slate-200 dark:border-slate-800">
                            {{ __('ui.continue_with') }}
                            <hr class="flex-1 border-slate-200 dark:border-slate-800">
                        </div>

                        <div class="hh-social-login-grid" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(112px,1fr));gap:8px;" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 400 ;repeat: true">
                            @foreach ($hhSocialProviderButtons as $provider)
                                <a href="{{ route('social.redirect', ['provider' => $provider['key']]) }}" class="button !w-full !min-w-0 !h-11 !px-3 !m-0 flex items-center justify-center gap-2 text-sm font-semibold leading-none {{ $provider['class'] }}" title="{{ __('ui.login_with_provider', ['provider' => $provider['label']]) }}" aria-label="{{ __('ui.login_with_provider', ['provider' => $provider['label']]) }}">
                                    <i class="{{ $provider['icon'] }} text-lg shrink-0" aria-hidden="true"></i>
                                    <span class="truncate">{{ $provider['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </form>
            @endif
        </div>
    </div>

    <div class="flex-1 relative bg-primary max-md:hidden">
        <div class="relative w-full h-full min-h-screen overflow-hidden">
            <img src="{{ $hhAuthHeroImage }}" alt="" class="w-full h-full object-cover uk-animation-kenburns uk-animation-reverse uk-transform-origin-center-left">
            <div class="absolute bottom-0 w-full z-10">
                <div class="max-w-xl w-full mx-auto pb-32 px-5 z-30 relative" uk-scrollspy="target: > *; cls: uk-animation-scale-up; delay: 100 ;repeat: true">
                    <img class="w-12" src="{{ $hhSlideIcon }}" alt="hnt.rocks">
                    <h4 class="!text-white text-2xl font-semibold mt-7">HNT.rocks verbindet Hunter.</h4>
                    <p class="!text-white text-lg mt-7 leading-8">Newsfeed, LFG, Teams, Cups und gemeinsame Jagden an einem Ort.</p>
                </div>
            </div>
            <div class="w-full h-96 bg-gradient-to-t from-black absolute bottom-0 left-0"></div>
        </div>
    </div>

</div>

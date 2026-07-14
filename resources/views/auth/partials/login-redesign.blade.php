@php
    $hntLoginLogo = asset('assets/themes/hnt_preview/images/hnt-brand-logo.svg');
    $hntLoginPanelImage = asset('assets/socialite/images/post/img-3.jpg');
    $hntSocialService = app(\App\Services\Auth\SocialProviderService::class);
    $hntSocialDefinitions = collect([
        ['key' => 'google', 'label' => 'Google', 'icon' => 'ph-google-logo'],
        ['key' => 'discord', 'label' => 'Discord', 'icon' => 'ph-discord-logo'],
        ['key' => 'twitch', 'label' => 'Twitch', 'icon' => 'ph-twitch-logo'],
        ['key' => 'microsoft', 'label' => 'Microsoft', 'icon' => 'ph-microsoft-logo'],
        ['key' => 'steam', 'label' => 'Steam', 'icon' => 'ph-steam-logo'],
        ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ph-facebook-logo'],
    ])->filter(function (array $provider) use ($hntSocialService): bool {
        try {
            $hntSocialService->assertUsable($provider['key']);

            return true;
        } catch (\RuntimeException) {
            return false;
        }
    })->values();
@endphp

<main class="hnt-login-shell">
    <section class="hnt-login-frame" aria-labelledby="hnt-login-title">
        <section class="hnt-login-panel">
            <header class="hnt-login-header">
                <a class="hnt-login-brand" href="{{ route('home') }}" aria-label="{{ __('auth.home_label') }}">
                    <img src="{{ $hntLoginLogo }}" alt="" aria-hidden="true">
                    <span>HNT.ROCKS</span>
                </a>

                <div class="hnt-login-header-actions">
                    <nav class="hnt-login-language" aria-label="{{ __('auth.language_label') }}">
                        <a href="{{ route('locale.switch', ['locale' => 'de']) }}"
                           lang="de"
                           hreflang="de"
                           @if(app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
                        <a href="{{ route('locale.switch', ['locale' => 'en']) }}"
                           lang="en"
                           hreflang="en"
                           @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
                    </nav>

                    <a class="hnt-login-close" href="{{ route('home') }}" aria-label="{{ __('auth.home_label') }}">
                        <i class="ph ph-x" aria-hidden="true"></i>
                    </a>
                </div>
            </header>

            <div class="hnt-login-center">
                <div class="hnt-login-content">
                    <header class="hnt-login-heading">
                        <span>{{ __('auth.kicker') }}</span>
                        <h1 id="hnt-login-title">{{ __('auth.login_heading') }}</h1>
                        <p>{{ __('auth.login_intro') }}</p>
                    </header>

                    @if (session('status'))
                        <div class="hnt-login-alert hnt-login-alert-success" role="status">{{ session('status') }}</div>
                    @endif

                    @error('social')
                        <div class="hnt-login-alert hnt-login-alert-error" role="alert">{{ $message }}</div>
                    @enderror

                    <form class="hnt-login-form" method="POST" action="{{ route('login.store') }}" data-hnt-login-form data-loading-label="{{ __('auth.login_loading') }}">
                        @csrf

                        <div class="hnt-login-field">
                            <label for="login-identity">{{ __('auth.identity_label') }}</label>
                            <div class="hnt-login-control @error('login') is-invalid @enderror">
                                <input id="login-identity"
                                       name="login"
                                       type="text"
                                       value="{{ old('login') }}"
                                       autocomplete="username"
                                       autocapitalize="none"
                                       spellcheck="false"
                                       placeholder="{{ __('auth.identity_placeholder') }}"
                                       required
                                       autofocus
                                       @error('login') aria-describedby="login-identity-error" @enderror>
                            </div>
                            @error('login')
                                <p class="hnt-login-field-error" id="login-identity-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="hnt-login-field">
                            <label for="login-password">{{ __('auth.password_label') }}</label>
                            <div class="hnt-login-control hnt-login-password-control @error('password') is-invalid @enderror">
                                <input id="login-password"
                                       name="password"
                                       type="password"
                                       autocomplete="current-password"
                                       placeholder="{{ __('auth.password_placeholder') }}"
                                       required
                                       @error('password') aria-describedby="login-password-error" @enderror>
                                <button type="button"
                                        data-hnt-password-toggle
                                        data-show-label="{{ __('auth.show_password') }}"
                                        data-hide-label="{{ __('auth.hide_password') }}"
                                        aria-label="{{ __('auth.show_password') }}"
                                        aria-controls="login-password"
                                        aria-pressed="false">
                                    <i class="ph ph-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="hnt-login-field-error" id="login-password-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="hnt-login-options">
                            <label class="hnt-login-check" for="login-remember">
                                <input id="login-remember"
                                       name="remember"
                                       type="checkbox"
                                       value="1"
                                       @checked(old('remember', true))>
                                <span aria-hidden="true"><i class="ph ph-check"></i></span>
                                <b>{{ __('auth.remember_me') }}</b>
                            </label>

                            <a href="{{ route('password.request') }}">{{ __('auth.forgot_password') }}</a>
                        </div>

                        <button class="hnt-login-primary" type="submit" data-hnt-login-submit>
                            <span data-hnt-login-submit-label>{{ __('auth.login_submit') }}</span>
                            <span class="hnt-login-spinner" aria-hidden="true"></span>
                        </button>

                        @if ($hntSocialDefinitions->isNotEmpty())
                            <div class="hnt-login-divider"><span>{{ __('auth.continue_with') }}</span></div>

                            <div class="hnt-login-social">
                                @foreach ($hntSocialDefinitions as $provider)
                                    <a href="{{ route('social.redirect', ['provider' => $provider['key']]) }}"
                                       aria-label="{{ __('auth.social_login', ['provider' => $provider['label']]) }}">
                                        <i class="ph {{ $provider['icon'] }}" aria-hidden="true"></i>
                                        <span>{{ $provider['label'] }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    </form>
                </div>
            </div>

            <footer class="hnt-login-footer">
                <div>
                    <span>{{ __('auth.no_account') }}</span>
                    <a href="{{ route('register') }}">{{ __('auth.register_now') }}</a>
                </div>
                <nav aria-label="{{ __('auth.legal_navigation') }}">
                    <a href="{{ route('legal.datenschutz') }}">{{ __('auth.privacy') }}</a>
                    <a href="{{ route('legal.impressum') }}">{{ __('auth.imprint') }}</a>
                </nav>
            </footer>
        </section>

        <aside class="hnt-login-photo" aria-hidden="true">
            <img src="{{ $hntLoginPanelImage }}" alt="">
            <div class="hnt-login-photo-overlay"></div>
        </aside>
    </section>
</main>

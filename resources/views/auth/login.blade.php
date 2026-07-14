@php
  $socialProviderService = app(\App\Services\Auth\SocialProviderService::class);
  $socialProviders = collect([
    ['key' => 'google', 'label' => 'Google', 'icon' => 'ph-google-logo'],
    ['key' => 'microsoft', 'label' => 'Microsoft', 'icon' => 'ph-microsoft-logo'],
    ['key' => 'discord', 'label' => 'Discord', 'icon' => 'ph-discord-logo'],
    ['key' => 'twitch', 'label' => 'Twitch', 'icon' => 'ph-twitch-logo'],
    ['key' => 'steam', 'label' => 'Steam', 'icon' => 'ph-steam-logo'],
    ['key' => 'facebook', 'label' => 'Facebook', 'icon' => 'ph-facebook-logo'],
  ])->filter(function (array $provider) use ($socialProviderService): bool {
    try {
      $socialProviderService->assertUsable($provider['key']);

      return true;
    } catch (\RuntimeException) {
      return false;
    }
  })->values();
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex,follow">
  <title>{{ __('hnt_auth.title') }} — HNT.ROCKS</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" sizes="96x96">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/common.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/feed-alignment.css') }}?v=2">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/showcase.css') }}?v=1">
</head>
<body data-page="auth-login">
<svg aria-hidden="true" class="svg-defs">
  <symbol id="i-check" viewBox="0 0 24 24"><path d="m6 12 4 4 8-8"></path></symbol>
  <symbol id="i-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
  <symbol id="i-eye" viewBox="0 0 24 24"><path d="M2.8 12s3.3-6 9.2-6 9.2 6 9.2 6-3.3 6-9.2 6-9.2-6-9.2-6"></path><circle cx="12" cy="12" r="2.6"></circle></symbol>
  <symbol id="i-eye-off" viewBox="0 0 24 24"><path d="m4 4 16 16"></path><path d="M10.4 6.2A9.6 9.6 0 0 1 12 6c5.9 0 9.2 6 9.2 6a15.2 15.2 0 0 1-2.5 3.2"></path><path d="M6.2 7.2A15 15 0 0 0 2.8 12s3.3 6 9.2 6c1 0 1.9-.2 2.7-.4"></path><path d="M9.8 9.8a3.1 3.1 0 0 0 4.4 4.4"></path></symbol>
</svg>

<main class="auth-shell">
  <section class="auth-frame">
    <section class="auth-left">
      <header class="auth-header">
        <a class="auth-brand" href="{{ route('home') }}" aria-label="HNT.ROCKS">
          <img src="{{ asset('assets/themes/hnt_preview/images/hnt-brand-logo.svg') }}" alt="" aria-hidden="true">
          <span>HNT.ROCKS</span>
        </a>

        <div class="auth-header-actions">
          <nav class="auth-language" aria-label="{{ __('hnt_auth.language') }}">
            <a href="{{ route('locale.switch', ['locale' => 'de']) }}" lang="de" hreflang="de" @if(app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" hreflang="en" @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
          </nav>
          <a class="auth-mobile-close" href="{{ route('home') }}" aria-label="{{ __('hnt_auth.close') }}">
            <svg><use href="#i-x"></use></svg>
          </a>
        </div>
      </header>

      <div class="auth-center">
        <div class="auth-content">
          <header class="auth-heading">
            <h1>{{ __('hnt_auth.title') }}</h1>
            <p>{{ __('hnt_auth.intro') }}</p>
          </header>

          @if (session('status'))
            <div class="auth-alert auth-alert-success" role="status">{{ session('status') }}</div>
          @endif

          @error('social')
            <div class="auth-alert auth-alert-error" role="alert">{{ $message }}</div>
          @enderror

          <form class="auth-form" id="loginForm" method="POST" action="{{ route('login.store') }}" data-auth-login-form data-loading-label="{{ __('hnt_auth.submitting') }}">
            @csrf

            <label class="auth-field">
              <span>{{ __('hnt_auth.identity') }}</span>
              <div @class(['is-invalid' => $errors->has('login')])>
                <input id="loginIdentity" name="login" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="{{ __('hnt_auth.identity_placeholder') }}" value="{{ old('login') }}" required autofocus @if($errors->has('login')) aria-invalid="true" aria-describedby="loginIdentityError" @endif>
              </div>
              <small id="loginIdentityError">@error('login'){{ $message }}@enderror</small>
            </label>

            <label class="auth-field">
              <span>{{ __('hnt_auth.password') }}</span>
              <div @class(['is-invalid' => $errors->has('password')])>
                <input id="loginPassword" name="password" type="password" autocomplete="current-password" placeholder="{{ __('hnt_auth.password_placeholder') }}" required @if($errors->has('password')) aria-invalid="true" aria-describedby="loginPasswordError" @endif>
                <button type="button" data-password-toggle="loginPassword" data-show-label="{{ __('hnt_auth.show_password') }}" data-hide-label="{{ __('hnt_auth.hide_password') }}" aria-label="{{ __('hnt_auth.show_password') }}" aria-controls="loginPassword" aria-pressed="false">
                  <svg><use href="#i-eye"></use></svg>
                </button>
              </div>
              <small id="loginPasswordError">@error('password'){{ $message }}@enderror</small>
            </label>

            <div class="auth-options">
              <label class="auth-check" for="rememberLogin">
                <input id="rememberLogin" name="remember" type="checkbox" value="1" @checked(old('remember', true))>
                <i aria-hidden="true"><svg><use href="#i-check"></use></svg></i>
                <span>{{ __('hnt_auth.remember') }}</span>
              </label>
              <a href="{{ route('password.request') }}">{{ __('hnt_auth.forgot') }}</a>
            </div>

            <button class="auth-primary" type="submit" data-auth-submit>
              <span data-auth-submit-label>{{ __('hnt_auth.submit') }}</span>
              <i class="auth-spinner" aria-hidden="true"></i>
            </button>

            @if ($socialProviders->isNotEmpty())
              <div class="auth-divider"><span>{{ __('hnt_auth.continue_with') }}</span></div>

              <div class="auth-social">
                @foreach ($socialProviders as $provider)
                  <a href="{{ route('social.redirect', ['provider' => $provider['key']]) }}" class="auth-provider auth-provider-{{ $provider['key'] }}" aria-label="{{ __('hnt_auth.social_login', ['provider' => $provider['label']]) }}">
                    <i class="ph {{ $provider['icon'] }}" aria-hidden="true"></i>
                    <span>{{ $provider['label'] }}</span>
                  </a>
                @endforeach
              </div>
            @endif
          </form>
        </div>
      </div>

      <footer class="auth-footer">
        <div><span>{{ __('hnt_auth.no_account') }}</span> <a href="{{ route('register') }}">{{ __('hnt_auth.register') }}</a></div>
        <nav aria-label="{{ __('hnt_auth.legal_navigation') }}">
          <a href="{{ route('legal.datenschutz') }}">{{ __('hnt_auth.privacy') }}</a>
          <a href="{{ route('legal.impressum') }}">{{ __('hnt_auth.imprint') }}</a>
        </nav>
      </footer>
    </section>

    <aside class="auth-photo" aria-label="HNT.ROCKS">
      <img src="{{ asset('assets/themes/hnt_preview/auth-demo/auth-reference-panel.jpg') }}" alt="HNT.ROCKS Community Motiv">
      @include('auth.partials.showcase-overlay')
    </aside>
  </section>
</main>

@include('partials.cookie-consent')
<script src="{{ asset('assets/themes/hnt_preview/auth-demo/auth.js') }}?v=2" defer></script>
</body>
</html>

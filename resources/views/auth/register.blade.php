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
  <title>{{ __('hnt_register.page_title') }} — HNT.ROCKS</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" sizes="96x96">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/vikinger/fonts/phosphor/regular/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/common.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/feed-alignment.css') }}?v=3">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/showcase.css') }}?v=1">
</head>
<body data-page="auth-register">
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
          <nav class="auth-language" aria-label="{{ __('hnt_register.language') }}">
            <a href="{{ route('locale.switch', ['locale' => 'de']) }}" lang="de" hreflang="de" @if(app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" hreflang="en" @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
          </nav>
          <a class="auth-mobile-close" href="{{ route('home') }}" aria-label="{{ __('hnt_register.close') }}">
            <svg><use href="#i-x"></use></svg>
          </a>
        </div>
      </header>

      <div class="auth-center">
        <div class="auth-content">
          <header class="auth-heading">
            <h1>{{ __('hnt_register.title') }}</h1>
            <p>{{ __('hnt_register.intro') }}</p>
          </header>

          @if (session('referral_referrer_name'))
            <div class="auth-alert auth-alert-success" role="status">{{ __('hnt_register.referral_detected', ['name' => session('referral_referrer_name')]) }}</div>
          @endif

          @error('social')
            <div class="auth-alert auth-alert-error" role="alert">{{ $message }}</div>
          @enderror

          <form class="auth-form auth-register" id="registerForm" method="POST" action="{{ route('register.store') }}" data-auth-form data-loading-label="{{ __('hnt_register.submitting') }}">
            @csrf

            <label class="auth-field">
              <span>{{ __('hnt_register.username') }}</span>
              <div @class(['is-invalid' => $errors->has('username')])>
                <input id="registerUsername" name="username" type="text" autocomplete="username" autocapitalize="none" spellcheck="false" placeholder="{{ __('hnt_register.username_placeholder') }}" value="{{ old('username') }}" minlength="3" maxlength="32" required autofocus @if($errors->has('username')) aria-invalid="true" aria-describedby="registerUsernameError" @endif>
              </div>
              <small id="registerUsernameError">@error('username'){{ $message }}@enderror</small>
            </label>

            <label class="auth-field">
              <span>{{ __('hnt_register.email') }}</span>
              <div @class(['is-invalid' => $errors->has('email')])>
                <input id="registerEmail" name="email" type="email" autocomplete="email" inputmode="email" autocapitalize="none" spellcheck="false" placeholder="{{ __('hnt_register.email_placeholder') }}" value="{{ old('email') }}" required @if($errors->has('email')) aria-invalid="true" aria-describedby="registerEmailError" @endif>
              </div>
              <small id="registerEmailError">@error('email'){{ $message }}@enderror</small>
            </label>

            <label class="auth-field">
              <span>{{ __('hnt_register.password') }}</span>
              <div @class(['is-invalid' => $errors->has('password')])>
                <input id="registerPassword" name="password" type="password" autocomplete="new-password" placeholder="{{ __('hnt_register.password_placeholder') }}" minlength="8" required @if($errors->has('password')) aria-invalid="true" aria-describedby="registerPasswordError" @endif>
                <button type="button" data-password-toggle="registerPassword" data-show-label="{{ __('hnt_register.show_password') }}" data-hide-label="{{ __('hnt_register.hide_password') }}" aria-label="{{ __('hnt_register.show_password') }}" aria-controls="registerPassword" aria-pressed="false">
                  <svg><use href="#i-eye"></use></svg>
                </button>
              </div>
              <small id="registerPasswordError">@error('password'){{ $message }}@enderror</small>
            </label>

            <label class="auth-field">
              <span>{{ __('hnt_register.password_confirmation') }}</span>
              <div>
                <input id="registerPasswordConfirmation" name="password_confirmation" type="password" autocomplete="new-password" placeholder="{{ __('hnt_register.password_confirmation_placeholder') }}" minlength="8" required>
                <button type="button" data-password-toggle="registerPasswordConfirmation" data-show-label="{{ __('hnt_register.show_password') }}" data-hide-label="{{ __('hnt_register.hide_password') }}" aria-label="{{ __('hnt_register.show_password') }}" aria-controls="registerPasswordConfirmation" aria-pressed="false">
                  <svg><use href="#i-eye"></use></svg>
                </button>
              </div>
              <small></small>
            </label>

            <label class="auth-check auth-terms" for="registerTerms">
              <input id="registerTerms" name="legal_terms" type="checkbox" value="1" @checked(old('legal_terms')) required @if($errors->has('legal_terms')) aria-invalid="true" aria-describedby="registerTermsError" @endif>
              <i aria-hidden="true"><svg><use href="#i-check"></use></svg></i>
              <span>
                {{ __('hnt_register.legal_prefix') }}
                <a href="{{ route('legal.nutzungsbedingungen') }}" target="_blank" rel="noopener">{{ __('hnt_register.terms') }}</a>,
                <a href="{{ route('legal.datenschutz') }}" target="_blank" rel="noopener">{{ __('hnt_register.privacy') }}</a>
                {{ __('hnt_register.legal_and') }}
                <a href="{{ route('legal.netiquette') }}" target="_blank" rel="noopener">{{ __('hnt_register.netiquette') }}</a>.
              </span>
            </label>
            <small class="auth-terms-error" id="registerTermsError">@error('legal_terms'){{ $message }}@enderror</small>

            <button class="auth-primary" type="submit" data-auth-submit>
              <span data-auth-submit-label>{{ __('hnt_register.submit') }}</span>
              <i class="auth-spinner" aria-hidden="true"></i>
            </button>

            @if ($socialProviders->isNotEmpty())
              <div class="auth-social">
                @foreach ($socialProviders as $provider)
                  <a href="{{ route('social.redirect', ['provider' => $provider['key']]) }}" class="auth-provider auth-provider-{{ $provider['key'] }}" aria-label="{{ __('hnt_register.social_register', ['provider' => $provider['label']]) }}">
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
        <div><span>{{ __('hnt_register.has_account') }}</span> <a href="{{ route('login') }}">{{ __('hnt_register.login') }}</a></div>
        <nav aria-label="{{ __('hnt_register.legal_navigation') }}">
          <a href="{{ route('legal.datenschutz') }}">{{ __('hnt_register.privacy') }}</a>
          <a href="{{ route('legal.impressum') }}">{{ __('hnt_register.imprint') }}</a>
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
<script src="{{ asset('assets/themes/hnt_preview/auth-demo/auth.js') }}?v=3" defer></script>
</body>
</html>

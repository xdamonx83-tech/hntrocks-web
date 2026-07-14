<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex,follow">
  <title>{{ __('hnt_two_factor.page_title') }} — HNT.ROCKS</title>
  <link rel="icon" type="image/png" href="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" sizes="96x96">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/theme-colors.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/common.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/auth.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/themes/hnt_preview/auth-demo/two-factor.css') }}?v=1">
</head>
<body data-page="auth-2fa">
<svg aria-hidden="true" class="svg-defs">
  <symbol id="i-x" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18"></path></symbol>
  <symbol id="i-arrow-left" viewBox="0 0 24 24"><path d="M19 12H5"></path><path d="m10 7-5 5 5 5"></path></symbol>
  <symbol id="i-shield" viewBox="0 0 24 24"><path d="M12 3 19 6v5c0 4.5-2.8 8-7 10-4.2-2-7-5.5-7-10V6l7-3Z"></path><path d="m9 12 2 2 4-4"></path></symbol>
  <symbol id="i-key" viewBox="0 0 24 24"><circle cx="8" cy="12" r="4"></circle><path d="m12 12 8-8"></path><path d="m17 7 2 2"></path><path d="m15 9 2 2"></path></symbol>
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
          <nav class="auth-language" aria-label="{{ __('hnt_two_factor.language') }}">
            <a href="{{ route('locale.switch', ['locale' => 'de']) }}" lang="de" hreflang="de" @if(app()->getLocale() === 'de') aria-current="true" @endif>DE</a>
            <a href="{{ route('locale.switch', ['locale' => 'en']) }}" lang="en" hreflang="en" @if(app()->getLocale() === 'en') aria-current="true" @endif>EN</a>
          </nav>
          <a class="auth-mobile-close" href="{{ route('login') }}" aria-label="{{ __('hnt_two_factor.close') }}">
            <svg><use href="#i-x"></use></svg>
          </a>
        </div>
      </header>

      <div class="auth-center">
        <div class="auth-content">
          <header class="auth-heading">
            <h1>{{ __('hnt_two_factor.title') }}</h1>
            <p>{{ __('hnt_two_factor.intro') }}</p>
          </header>

          <form
            class="auth-form auth-two-factor"
            method="POST"
            action="{{ route('login.two-factor.confirm') }}"
            data-two-factor-form
            data-code-incomplete="{{ __('hnt_two_factor.code_incomplete') }}"
            data-recovery-required="{{ __('hnt_two_factor.recovery_required') }}"
            data-loading-label="{{ __('hnt_two_factor.verifying') }}"
            novalidate
          >
            @csrf
            <input type="hidden" data-two-factor-code>

            <article class="auth-security-note">
              <span aria-hidden="true"><svg><use href="#i-shield"></use></svg></span>
              <div>
                <strong>{{ __('hnt_two_factor.security_title') }}</strong>
                <small>{{ __('hnt_two_factor.security_hint') }}</small>
              </div>
            </article>

            <div
              @class(['auth-code', 'is-invalid' => $errors->has('code')])
              role="group"
              aria-label="{{ __('hnt_two_factor.code_group') }}"
              data-two-factor-digits
            >
              @for ($digit = 1; $digit <= 6; $digit++)
                <input
                  type="text"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  maxlength="1"
                  @if($digit === 1) autocomplete="one-time-code" autofocus @else autocomplete="off" @endif
                  aria-label="{{ __('hnt_two_factor.digit_label', ['number' => $digit]) }}"
                  data-two-factor-digit
                  required
                >
              @endfor
            </div>

            <small class="auth-code-error" data-two-factor-error role="alert">@error('code'){{ $message }}@enderror</small>

            <noscript>
              <label class="auth-field">
                <span>{{ __('hnt_two_factor.code_group') }}</span>
                <div><input name="code" type="text" inputmode="numeric" autocomplete="one-time-code" required></div>
              </label>
            </noscript>

            <button class="auth-primary" type="submit" data-two-factor-submit>
              <span data-two-factor-submit-label>{{ __('hnt_two_factor.verify') }}</span>
              <i class="auth-spinner" aria-hidden="true"></i>
            </button>

            <button
              class="auth-recovery-toggle"
              type="button"
              aria-expanded="false"
              aria-controls="twoFactorRecoveryField"
              data-two-factor-toggle
              data-recovery-label="{{ __('hnt_two_factor.use_recovery') }}"
              data-authenticator-label="{{ __('hnt_two_factor.use_authenticator') }}"
            >
              <svg aria-hidden="true"><use href="#i-key"></use></svg>
              <span data-two-factor-toggle-label>{{ __('hnt_two_factor.use_recovery') }}</span>
            </button>

            <label class="auth-field auth-backup" id="twoFactorRecoveryField" data-two-factor-recovery-field hidden>
              <span>{{ __('hnt_two_factor.recovery_label') }}</span>
              <div>
                <input type="text" autocomplete="one-time-code" autocapitalize="characters" spellcheck="false" placeholder="{{ __('hnt_two_factor.recovery_placeholder') }}" data-two-factor-recovery>
              </div>
            </label>
          </form>
        </div>
      </div>

      <footer class="auth-footer">
        <div>
          <a class="auth-back" href="{{ route('login') }}">
            <svg aria-hidden="true"><use href="#i-arrow-left"></use></svg>
            {{ __('hnt_two_factor.back_to_login') }}
          </a>
        </div>
        <nav aria-label="{{ __('hnt_two_factor.legal_navigation') }}">
          <a href="{{ route('legal.datenschutz') }}">{{ __('hnt_two_factor.privacy') }}</a>
          <a href="{{ route('legal.impressum') }}">{{ __('hnt_two_factor.imprint') }}</a>
        </nav>
      </footer>
    </section>

    <aside class="auth-photo" aria-label="HNT.ROCKS">
      <img src="{{ asset('assets/themes/hnt_preview/auth-demo/auth-reference-panel.jpg') }}" alt="HNT.ROCKS Community Motiv">
    </aside>
  </section>
</main>

@include('partials.cookie-consent')
<script src="{{ asset('assets/themes/hnt_preview/auth-demo/two-factor.js') }}?v=1" defer></script>
</body>
</html>

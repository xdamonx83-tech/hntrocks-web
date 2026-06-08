@php
    $hhAppPromoUrl = route('app-beta.index');
@endphp

<section class="hh-app-promo" data-hh-app-promo hidden aria-hidden="true">
    <button type="button" class="hh-app-promo__backdrop" data-hh-app-promo-close aria-label="{{ __('ui.app_promo_close') }}"></button>

    <article class="hh-app-promo__card" role="dialog" aria-modal="true" aria-labelledby="hh-app-promo-title" aria-describedby="hh-app-promo-text" tabindex="-1">
        <button type="button" class="hh-app-promo__close" data-hh-app-promo-close aria-label="{{ __('ui.app_promo_close') }}">
            <span aria-hidden="true">×</span>
        </button>

        <div class="hh-app-promo__mark" aria-hidden="true">
            <img src="{{ asset('assets/vikinger/img/favicon-96x96.png') }}" alt="">
        </div>

        <div class="hh-app-promo__content">
            <p class="hh-app-promo__kicker">{{ __('ui.app_promo_kicker') }}</p>
            <h2 id="hh-app-promo-title">{{ __('ui.app_promo_title') }}</h2>
            <p id="hh-app-promo-text">{{ __('ui.app_promo_text') }}</p>
            <p class="hh-app-promo__beta">{{ __('ui.app_promo_beta_note') }}</p>
            <div class="hh-app-promo__actions">
                <a class="hh-app-promo__cta" href="{{ $hhAppPromoUrl }}" data-hh-app-promo-cta>
                    {{ __('ui.app_promo_cta') }}
                </a>
                <button type="button" class="hh-app-promo__later" data-hh-app-promo-close>
                    {{ __('ui.app_promo_later') }}
                </button>
            </div>
        </div>
    </article>
</section>

@once
<script>
(() => {
  const root = document.querySelector('[data-hh-app-promo]');
  if (!root) return;

  const storageKey = 'hnt_app_promo_dismissed_until';
  const dismissMs = 7 * 24 * 60 * 60 * 1000;
  const delayMs = 4200;
  const forceOpen = new URLSearchParams(window.location.search).get('app_promo') === '1';

  const getDismissedUntil = () => {
    try { return Number(window.localStorage.getItem(storageKey) || 0); }
    catch (error) { return 0; }
  };

  const dismiss = () => {
    try { window.localStorage.setItem(storageKey, String(Date.now() + dismissMs)); }
    catch (error) {}
  };

  const open = () => {
    root.hidden = false;
    root.setAttribute('aria-hidden', 'false');
    document.documentElement.classList.add('hh-app-promo-open');
    window.setTimeout(() => root.querySelector('.hh-app-promo__card')?.focus?.({ preventScroll: true }), 30);
  };

  const close = () => {
    dismiss();
    root.setAttribute('aria-hidden', 'true');
    document.documentElement.classList.remove('hh-app-promo-open');
    root.hidden = true;
  };

  root.addEventListener('click', (event) => {
    if (event.target.closest('[data-hh-app-promo-close]')) {
      event.preventDefault();
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !root.hidden) close();
  });

  root.querySelector('[data-hh-app-promo-cta]')?.addEventListener('click', dismiss);

  if (forceOpen || Date.now() > getDismissedUntil()) {
    window.setTimeout(open, forceOpen ? 150 : delayMs);
  }
})();
</script>
@endonce

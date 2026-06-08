@php
    $hhCookieConsentValue = request()->cookie('hh_cookie_consent');
    $hhHasCookieDecision = is_string($hhCookieConsentValue) && $hhCookieConsentValue !== '';
@endphp

@once
    <style>
        .hh-cookie-banner {
            position: fixed;
            inset: auto 18px 18px 18px;
            z-index: 2147483000;
            display: flex;
            justify-content: center;
            pointer-events: none;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .hh-cookie-banner[hidden] {
            display: none !important;
        }

        .hh-cookie-card {
            width: min(940px, calc(100vw - 28px));
            padding: 22px;
            border: 1px solid rgba(214, 168, 79, .24);
            border-radius: 24px;
            background:
                radial-gradient(circle at 92% 8%, rgba(214, 168, 79, .15), transparent 30%),
                linear-gradient(145deg, rgba(32, 32, 30, .98), rgba(20, 20, 18, .98));
            box-shadow: 0 24px 80px rgba(0, 0, 0, .62), inset 0 1px 0 rgba(255, 255, 255, .04);
            color: #f2e8d8;
            pointer-events: auto;
            transform: translateY(0);
        }

        .hh-cookie-copy {
            max-width: 760px;
        }

        .hh-cookie-copy h2 {
            margin: 2px 0 8px !important;
            color: #f2e8d8 !important;
            font-size: clamp(1.15rem, 2vw, 1.45rem) !important;
            font-weight: 900 !important;
            line-height: 1.18 !important;
            letter-spacing: -.025em;
        }

        .hh-cookie-copy p {
            margin: 0 !important;
            color: #a79c8e !important;
            font-size: .94rem !important;
            font-weight: 650 !important;
            line-height: 1.58 !important;
        }

        .hh-cookie-eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px !important;
            color: #d6a84f !important;
            font-size: .72rem !important;
            font-weight: 950 !important;
            text-transform: uppercase !important;
            letter-spacing: .13em !important;
        }

        .hh-cookie-eyebrow::before {
            content: '';
            width: 9px;
            height: 9px;
            border-radius: 999px;
            background: #9be447;
            box-shadow: 0 0 18px rgba(155, 228, 71, .42);
        }

        .hh-cookie-options {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 18px;
        }

        .hh-cookie-option {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin: 0;
            padding: 14px;
            border: 1px solid rgba(214, 168, 79, .16);
            border-radius: 18px;
            background: rgba(10, 10, 9, .52);
            color: #f2e8d8;
        }

        .hh-cookie-option input {
            width: 18px;
            height: 18px;
            margin-top: 2px;
            accent-color: #d6a84f;
        }

        .hh-cookie-option strong {
            display: block;
            color: #f2e8d8;
            font-size: .9rem;
            font-weight: 900;
            line-height: 1.25;
        }

        .hh-cookie-option small {
            display: block;
            margin-top: 5px;
            color: #a79c8e;
            font-size: .78rem;
            font-weight: 650;
            line-height: 1.45;
        }

        .hh-cookie-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .hh-cookie-button,
        .hh-cookie-link-button {
            min-height: 44px;
            padding: 0 18px;
            border-radius: 14px;
            font-size: .82rem;
            font-weight: 950;
            line-height: 1;
            cursor: pointer;
            transition: transform .18s ease, border-color .18s ease, background .18s ease, color .18s ease, filter .18s ease;
        }

        .hh-cookie-button:hover,
        .hh-cookie-link-button:hover {
            transform: translateY(-1px);
            filter: brightness(1.05);
        }

        .hh-cookie-button.is-primary {
            border: 1px solid rgba(214, 168, 79, .78);
            background: linear-gradient(135deg, #d6a84f, #c79332);
            color: #0f0e0c;
            box-shadow: 0 14px 34px rgba(214, 168, 79, .18);
        }

        .hh-cookie-button.is-secondary {
            border: 1px solid rgba(242, 232, 216, .12);
            background: rgba(255, 255, 255, .06);
            color: #f2e8d8;
        }

        .hh-cookie-link-button {
            border: 1px solid transparent;
            background: transparent;
            color: #d6a84f;
        }

        .hh-cookie-link-button:hover {
            border-color: rgba(214, 168, 79, .22);
            background: rgba(214, 168, 79, .08);
        }

        @media (min-width: 860px) {
            .hh-cookie-card {
                display: grid;
                grid-template-columns: minmax(0, 1fr) auto;
                align-items: end;
                column-gap: 22px;
            }

            .hh-cookie-options,
            .hh-cookie-card.is-open-settings .hh-cookie-options {
                grid-column: 1 / -1;
            }

            .hh-cookie-card .hh-cookie-actions {
                margin-top: 0;
            }

            .hh-cookie-banner.is-open-settings .hh-cookie-card .hh-cookie-actions {
                grid-column: 1 / -1;
                margin-top: 18px;
            }
        }

        @media (max-width: 720px) {
            .hh-cookie-banner {
                inset: auto 10px 10px 10px;
            }

            .hh-cookie-card {
                width: 100%;
                padding: 18px;
                border-radius: 20px;
            }

            .hh-cookie-options {
                grid-template-columns: 1fr;
            }

            .hh-cookie-actions {
                display: grid;
                grid-template-columns: 1fr;
                gap: 9px;
            }

            .hh-cookie-button,
            .hh-cookie-link-button {
                width: 100%;
            }
        }
    </style>
@endonce

<section class="hh-cookie-banner" data-hh-cookie-banner {{ $hhHasCookieDecision ? 'hidden' : '' }} aria-live="polite">
    <div class="hh-cookie-card">
        <div class="hh-cookie-copy">
            <p class="hh-cookie-eyebrow">{{ __('ui.cookie_eyebrow') }}</p>
            <h2>{{ __('ui.cookie_title') }}</h2>
            <p>{{ __('ui.cookie_intro') }}</p>
        </div>

        <div class="hh-cookie-options" data-hh-cookie-options hidden>
            <label class="hh-cookie-option is-required">
                <input type="checkbox" checked disabled>
                <span>
                    <strong>{{ __('ui.cookie_necessary_title') }}</strong>
                    <small>{{ __('ui.cookie_necessary_text') }}</small>
                </span>
            </label>

            <label class="hh-cookie-option">
                <input type="checkbox" data-hh-cookie-analytics>
                <span>
                    <strong>{{ __('ui.cookie_analytics_title') }}</strong>
                    <small>{{ __('ui.cookie_analytics_text') }}</small>
                </span>
            </label>
        </div>

        <div class="hh-cookie-actions">
            <button type="button" class="hh-cookie-link-button" data-hh-cookie-options-toggle>{{ __('ui.cookie_customize') }}</button>
            <button type="button" class="hh-cookie-button is-secondary" data-hh-cookie-necessary>{{ __('ui.cookie_necessary_only') }}</button>
            <button type="button" class="hh-cookie-button is-primary" data-hh-cookie-accept>{{ __('ui.cookie_accept_all') }}</button>
            <button type="button" class="hh-cookie-button is-primary" data-hh-cookie-save hidden>{{ __('ui.cookie_save_selection') }}</button>
        </div>
    </div>
</section>

<script>
(function () {
    const banner = document.querySelector('[data-hh-cookie-banner]');
    const openButtons = document.querySelectorAll('[data-hh-cookie-settings-open]');
    const options = document.querySelector('[data-hh-cookie-options]');
    const card = banner?.querySelector('.hh-cookie-card');
    const optionsToggle = document.querySelector('[data-hh-cookie-options-toggle]');
    const analyticsInput = document.querySelector('[data-hh-cookie-analytics]');
    const necessaryButton = document.querySelector('[data-hh-cookie-necessary]');
    const acceptButton = document.querySelector('[data-hh-cookie-accept]');
    const saveButton = document.querySelector('[data-hh-cookie-save]');

    if (!banner) {
        return;
    }

    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    const maxAge = 60 * 60 * 24 * 180;

    function readCookie(name) {
        const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        const match = document.cookie.match(new RegExp('(?:^|; )' + escapedName + '=([^;]*)'));
        return match ? decodeURIComponent(match[1]) : '';
    }

    function readConsent() {
        return readCookie('hh_cookie_consent');
    }

    function hasDecision() {
        return readConsent().includes('necessary=1');
    }

    function hasAnalytics() {
        return readConsent().includes('analytics=1');
    }

    function setCookie(name, value, seconds) {
        document.cookie = name + '=' + encodeURIComponent(value) + '; Path=/; Max-Age=' + seconds + '; SameSite=Lax' + secure;
    }

    function forgetCookie(name) {
        document.cookie = name + '=; Path=/; Max-Age=0; SameSite=Lax' + secure;
    }

    function closeBanner() {
        banner.setAttribute('hidden', 'hidden');
        banner.classList.remove('is-open-settings');
        card?.classList.remove('is-open-settings');
    }

    function openBanner(showOptions) {
        banner.removeAttribute('hidden');

        if (analyticsInput) {
            analyticsInput.checked = hasAnalytics();
        }

        if (showOptions && options) {
            options.removeAttribute('hidden');
            banner.classList.add('is-open-settings');
            card?.classList.add('is-open-settings');
            if (saveButton) saveButton.removeAttribute('hidden');
        }
    }

    function saveConsent(analytics) {
        setCookie('hh_cookie_consent', 'necessary=1&analytics=' + (analytics ? '1' : '0') + '&version=1', maxAge);

        if (!analytics) {
            forgetCookie('hh_vid');
        }

        closeBanner();
    }

    if (hasDecision()) {
        closeBanner();
    }

    openButtons.forEach((button) => {
        button.addEventListener('click', () => openBanner(true));
    });

    if (optionsToggle && options) {
        optionsToggle.addEventListener('click', () => {
            options.removeAttribute('hidden');
            banner.classList.add('is-open-settings');
            card?.classList.add('is-open-settings');
            if (saveButton) saveButton.removeAttribute('hidden');
        });
    }

    necessaryButton?.addEventListener('click', () => saveConsent(false));
    acceptButton?.addEventListener('click', () => saveConsent(true));
    saveButton?.addEventListener('click', () => saveConsent(Boolean(analyticsInput?.checked)));
})();
</script>

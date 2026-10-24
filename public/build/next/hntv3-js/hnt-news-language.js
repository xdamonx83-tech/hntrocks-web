/* HNTv3 News: real localized article navigation, not demo language simulation. */
(() => {
  'use strict';
  if (!document.body?.hasAttribute('data-hnt-next-news')) return;
  const holder = document.getElementById('hntLanguage');
  const trigger = document.getElementById('hntLanguageToggle');
  const current = document.getElementById('hntLanguageCurrent');
  const menu = document.getElementById('hntLanguageMenu');
  if (!holder || !trigger || !current || !menu) return;

  const languages = new Set(['de', 'en', 'es', 'ru']);
  const active = document.documentElement.lang.toLowerCase();
  const locale = languages.has(active) ? active : 'de';
  const labels = { de:'Sprache wählen', en:'Choose language',
    es:'Elegir idioma', ru:'Выбрать язык' };

  // URL locale takes precedence over the shared demo switcher's localStorage.
  const paint = code => {
    current.textContent = code.toUpperCase();
    holder.dataset.locale = code;
    trigger.setAttribute('aria-label', labels[code]);
    trigger.title = labels[code];
    menu.querySelectorAll('[data-lang]').forEach(option => {
      const selected = option.dataset.lang === code;
      option.classList.toggle('is-current', selected);
      option.setAttribute('aria-checked', String(selected));
    });
  };
  paint(locale);

  const targetFor = code => {
    const node = document.querySelector('link[rel="alternate"][hreflang="'+code+'"]');
    if (node?.href) {
      const candidate = new URL(node.href, location.origin);
      if (candidate.origin === location.origin &&
          /^\/news\/(de|en|es|ru)(\/|$)/.test(candidate.pathname)) {
        return candidate.href;
      }
    }
    // Never invent a translated slug when a given article is unavailable.
    return new URL('/news/'+code, location.origin).href;
  };

  menu.addEventListener('click', event => {
    const option = event.target.closest('[data-lang]');
    if (!option || !menu.contains(option)) return;
    const code = option.dataset.lang;
    if (!languages.has(code)) return;
    event.preventDefault();
    event.stopImmediatePropagation(); // override demo-only language selection
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    if (code === locale) { paint(locale); trigger.focus(); return; }
    const destination = targetFor(code);
    trigger.disabled = true;
    paint(code);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const follow = () => location.assign(destination);
    if (!csrf) { follow(); return; }
    fetch('/next/language/'+encodeURIComponent(code), {
      method:'POST', credentials:'same-origin',
      headers:{'Accept':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'}
    }).then(res => {
      if (!res.ok) throw Error('Could not update session locale');
      return res.json();
    }).catch(() => {}).finally(follow);
  }, true);
})();

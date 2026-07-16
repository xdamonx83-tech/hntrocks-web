/* Shared feeling picker and post-label bridge for the exact feed/profile design. */
(() => {
  if (window.HNT_REAL_FEELINGS_READY) return;
  window.HNT_REAL_FEELINGS_READY = true;

  const modal = document.getElementById('postComposerModal');
  const trigger = document.getElementById('composerEmojiButton');
  const tools = modal?.querySelector('.composer-tools');

  if (!modal || !trigger || !tools || !window.fetch) return;

  const locale = document.documentElement.lang?.toLowerCase().startsWith('en') ? 'en' : 'de';
  const feelings = {
    happy: { emoji: '😊', de: 'glücklich', en: 'happy' },
    excited: { emoji: '🔥', de: 'motiviert', en: 'excited' },
    focused: { emoji: '🎯', de: 'fokussiert', en: 'focused' },
    chill: { emoji: '🌙', de: 'entspannt', en: 'chill' },
    tired: { emoji: '😴', de: 'müde', en: 'tired' },
    salty: { emoji: '🧂', de: 'salzig', en: 'salty' },
  };

  const labels = locale === 'en'
    ? {
        button: 'Choose feeling',
        title: 'How are you feeling?',
        none: 'No feeling',
        selected: 'Feeling',
        verb: 'is',
      }
    : {
        button: 'Gefühl auswählen',
        title: 'Wie fühlst du dich?',
        none: 'Kein Gefühl',
        selected: 'Gefühl',
        verb: 'ist',
      };

  const state = { value: 'none' };

  const escapeRegExp = (value) => String(value).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  const labelFor = (key) => feelings[key]?.[locale] || feelings[key]?.de || '';
  const phraseFor = (key) => key && feelings[key]
    ? `${labels.verb} ${labelFor(key)} ${feelings[key].emoji}`
    : '';

  const inferFeeling = (text = '') => {
    const normalized = String(text).toLowerCase();

    for (const [key, feeling] of Object.entries(feelings)) {
      if (String(text).includes(feeling.emoji)) return key;
      if (normalized.includes(feeling.de.toLowerCase())) return key;
      if (normalized.includes(feeling.en.toLowerCase())) return key;
    }

    return 'none';
  };

  const stripFeelingSuffix = (text = '') => {
    let value = String(text).trim();

    Object.values(feelings).forEach((feeling) => {
      const emoji = escapeRegExp(feeling.emoji);
      const de = escapeRegExp(feeling.de);
      const en = escapeRegExp(feeling.en);
      value = value
        .replace(new RegExp(`\\s*·\\s*(?:ist|is)\\s+(?:${de}|${en})\\s*${emoji}?\\s*$`, 'iu'), '')
        .replace(new RegExp(`\\s*·\\s*${emoji}\\s*(?:${de}|${en})\\s*$`, 'iu'), '');
    });

    return value.trim();
  };

  const decorateArticle = (article) => {
    if (!(article instanceof Element)) return;
    const meta = article.querySelector('.post-author > span');
    if (!meta) return;

    const key = article.dataset.feelingKey || inferFeeling(meta.textContent || '');
    const base = meta.dataset.feelingBase || stripFeelingSuffix(meta.textContent || '');
    meta.dataset.feelingBase = base;

    if (!key || key === 'none' || !feelings[key]) {
      article.dataset.feelingKey = 'none';
      meta.textContent = base;
      return;
    }

    article.dataset.feelingKey = key;
    meta.textContent = `${base} · ${phraseFor(key)}`;
  };

  const decorateAll = (root = document) => {
    if (root instanceof Element && root.matches('[data-real-feed-post], .profile-real-post')) {
      decorateArticle(root);
    }

    root.querySelectorAll?.('[data-real-feed-post], .profile-real-post').forEach(decorateArticle);
  };

  const current = document.createElement('div');
  current.className = 'composer-feeling-current';
  current.hidden = true;
  current.innerHTML = '<span></span><button type="button" aria-label="Gefühl entfernen">×</button>';
  tools.parentNode?.insertBefore(current, tools);

  const picker = document.createElement('section');
  picker.className = 'composer-feeling-picker';
  picker.hidden = true;
  picker.setAttribute('aria-label', labels.title);
  picker.innerHTML = `
    <header><strong>${labels.title}</strong><button type="button" data-feeling-close aria-label="Schließen">×</button></header>
    <div class="composer-feeling-options">
      <button type="button" data-feeling-value="none"><span>—</span><b>${labels.none}</b></button>
      ${Object.entries(feelings).map(([key, feeling]) => `
        <button type="button" data-feeling-value="${key}"><span>${feeling.emoji}</span><b>${labelFor(key)}</b></button>
      `).join('')}
    </div>
  `;
  tools.insertAdjacentElement('afterend', picker);

  const updateUi = () => {
    const key = state.value;
    const selected = key !== 'none' && feelings[key];
    trigger.classList.toggle('is-active', Boolean(selected));
    trigger.setAttribute('aria-pressed', selected ? 'true' : 'false');

    picker.querySelectorAll('[data-feeling-value]').forEach((button) => {
      button.classList.toggle('is-active', button.dataset.feelingValue === key);
    });

    if (!selected) {
      current.hidden = true;
      current.querySelector('span').textContent = '';
      return;
    }

    current.hidden = false;
    current.querySelector('span').textContent = `${labels.selected}: ${feelings[key].emoji} ${labelFor(key)}`;
  };

  const setFeeling = (value = 'none') => {
    state.value = feelings[value] ? value : 'none';
    updateUi();
  };

  const closePicker = () => {
    picker.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
  };

  const openPicker = () => {
    picker.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    picker.querySelector(`[data-feeling-value="${state.value}"]`)?.focus();
  };

  trigger.setAttribute('aria-label', labels.button);
  trigger.setAttribute('title', labels.button);
  trigger.setAttribute('aria-haspopup', 'dialog');
  trigger.setAttribute('aria-expanded', 'false');

  window.HNT_COMPOSER_FEELING = {
    get value() { return state.value; },
    set: setFeeling,
    reset: () => setFeeling('none'),
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    if (target.closest('#composerEmojiButton')) {
      event.preventDefault();
      event.stopImmediatePropagation();
      picker.hidden ? openPicker() : closePicker();
      return;
    }

    const option = target.closest('[data-feeling-value]');
    if (option) {
      event.preventDefault();
      setFeeling(option.dataset.feelingValue || 'none');
      closePicker();
      return;
    }

    if (target.closest('[data-feeling-close]')) {
      event.preventDefault();
      closePicker();
      return;
    }

    if (target.closest('.composer-feeling-current button')) {
      event.preventDefault();
      setFeeling('none');
      return;
    }

    const edit = target.closest('[data-compose-edit], [data-profile-menu-edit]');
    if (edit) {
      const article = edit.closest('[data-real-feed-post], .profile-real-post');
      setFeeling(article?.dataset.feelingKey || inferFeeling(article?.querySelector('.post-author > span')?.textContent || ''));
      closePicker();
      return;
    }

    if (target.closest('#openPostComposer')) {
      setFeeling('none');
      closePicker();
      return;
    }

    if (!picker.hidden && !picker.contains(target)) closePicker();
  }, true);

  const nativeFetch = window.fetch.bind(window);
  window.fetch = (input, init = {}) => {
    try {
      const source = input instanceof Request ? input.url : String(input);
      const url = new URL(source, window.location.href);
      const method = String(init?.method || (input instanceof Request ? input.method : 'GET')).toUpperCase();
      const body = init?.body;
      const isFeedWrite = method === 'POST'
        && (url.pathname === '/feed' || /^\/feed\/\d+$/u.test(url.pathname));

      if (isFeedWrite && body instanceof FormData) {
        body.set('feeling_key', state.value || 'none');
      }
    } catch (_) {
      // Unrelated requests keep their original payload.
    }

    return nativeFetch(input, init);
  };

  const modalObserver = new MutationObserver(() => {
    if (!modal.classList.contains('is-open')) {
      closePicker();
      setFeeling('none');
    }
  });
  modalObserver.observe(modal, { attributes: true, attributeFilter: ['class'] });

  const postObserver = new MutationObserver((mutations) => {
    mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
      if (node instanceof Element) decorateAll(node);
    }));
  });
  postObserver.observe(document.body, { childList: true, subtree: true });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !picker.hidden) closePicker();
  });

  setFeeling('none');
  decorateAll();
})();

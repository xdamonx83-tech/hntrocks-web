(() => {
  'use strict';

  if (window.HNT_REAL_FEED_TRANSLATION_READY) return;
  window.HNT_REAL_FEED_TRANSLATION_READY = true;

  const isEnglishUi = (document.documentElement.lang || '').toLowerCase().startsWith('en');
  const targetLocale = isEnglishUi ? 'en' : 'de';
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const copy = isEnglishUi ? {
    translate: 'Translate',
    translating: 'Translating…',
    original: 'Show original',
    automatic: 'Automatically translated',
    unavailable: 'Translation is currently unavailable',
  } : {
    translate: 'Übersetzen',
    translating: 'Übersetze…',
    original: 'Original anzeigen',
    automatic: 'Automatisch übersetzt',
    unavailable: 'Übersetzung ist gerade nicht verfügbar',
  };

  const cssHref = '/assets/themes/hnt_preview/dashboard-feed/real-feed-translation.css?v=1';
  if (!document.querySelector('link[data-hnt-feed-translation]')) {
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = cssHref;
    link.dataset.hntFeedTranslation = '1';
    document.head.appendChild(link);
  }

  const toast = (message) => {
    if (typeof window.showToast === 'function') window.showToast(message);
    else console.info(message);
  };

  const detectLanguage = (value = '') => {
    const text = String(value).replace(/https?:\/\/\S+/gi, ' ').toLowerCase();
    if (!text.trim()) return null;
    if (/[äöüß]/i.test(text)) return 'de';

    const words = text.match(/[a-z]{2,}/g) || [];
    const deWords = new Set(['der','die','das','den','dem','und','oder','aber','nicht','kein','keine','ist','sind','war','wird','werden','ich','du','wir','ihr','mein','dein','mit','für','auf','zum','zur','von','wie','was','wenn','dann','auch','noch','schon','einen','eine','habe','hat','haben','kann','heute','morgen','spieler','jagd','beute','punkte','gewinnen','teilnehmen']);
    const enWords = new Set(['the','and','or','but','not','is','are','was','were','will','would','can','could','you','we','they','my','your','with','for','on','to','from','of','in','this','that','if','then','also','just','already','have','has','had','player','hunt','bounty','points','upload','win','join','today','tomorrow','community','challenge']);
    let de = 0;
    let en = 0;

    words.forEach((word) => {
      if (deWords.has(word)) de += 2;
      if (enWords.has(word)) en += 2;
    });

    if (de >= en + 2) return 'de';
    if (en >= de + 2) return 'en';
    return null;
  };

  const requestTranslation = async (url) => {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
      },
      body: JSON.stringify({ locale: targetLocale }),
    });

    let payload = null;
    try { payload = await response.json(); } catch (_) { payload = null; }
    if (!response.ok) throw new Error(payload?.message || copy.unavailable);
    return payload || {};
  };

  const ensureMetaRow = (article) => {
    const author = article.querySelector('.post-author');
    const meta = author?.querySelector(':scope > span');
    if (!author || !meta) return null;

    let row = author.querySelector(':scope > .hnt-post-meta-row');
    if (!row) {
      row = document.createElement('div');
      row.className = 'hnt-post-meta-row';
      author.insertBefore(row, meta);
      row.appendChild(meta);
    }
    return row;
  };

  const enhance = (article) => {
    if (!(article instanceof Element) || article.dataset.hntTranslationReady === '1') return;

    const postId = Number.parseInt(article.dataset.realFeedPost || article.dataset.profilePostId || '0', 10);
    const body = article.querySelector('.post-body > p');
    if (!postId || !body || !body.textContent.trim()) return;

    const source = detectLanguage(body.textContent);
    if (!source || source === targetLocale) return;

    const row = ensureMetaRow(article);
    if (!row) return;

    article.dataset.hntTranslationReady = '1';
    const originalHtml = body.innerHTML;
    let translatedHtml = '';
    let translated = false;

    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'hnt-post-translate';
    button.textContent = copy.translate;
    button.setAttribute('aria-label', copy.translate);

    const note = document.createElement('span');
    note.className = 'hnt-post-translation-note';
    note.hidden = true;

    row.append(button, note);

    button.addEventListener('click', async (event) => {
      event.preventDefault();
      event.stopPropagation();

      if (translated) {
        body.innerHTML = originalHtml;
        translated = false;
        button.textContent = copy.translate;
        button.setAttribute('aria-label', copy.translate);
        note.hidden = true;
        return;
      }

      if (translatedHtml) {
        body.innerHTML = translatedHtml;
        translated = true;
        button.textContent = copy.original;
        button.setAttribute('aria-label', copy.original);
        note.hidden = false;
        return;
      }

      button.setAttribute('aria-busy', 'true');
      button.textContent = copy.translating;

      try {
        const url = article.dataset.feedTranslationUrl || `/feed/${encodeURIComponent(String(postId))}/translation`;
        const payload = await requestTranslation(url);
        translatedHtml = String(payload.translated_html || '').trim();
        if (!translatedHtml) throw new Error(copy.unavailable);

        body.innerHTML = translatedHtml;
        translated = true;
        button.textContent = copy.original;
        button.setAttribute('aria-label', copy.original);
        note.textContent = String(payload.provider_label || copy.automatic);
        note.hidden = false;
      } catch (error) {
        button.textContent = copy.translate;
        toast(error?.message || copy.unavailable);
      } finally {
        button.removeAttribute('aria-busy');
      }
    });
  };

  const scan = (root = document) => {
    if (root instanceof Element && root.matches('[data-real-feed-post], [data-profile-post-id]')) enhance(root);
    root.querySelectorAll?.('[data-real-feed-post], [data-profile-post-id]').forEach(enhance);
  };

  scan();

  const observer = new MutationObserver((records) => {
    records.forEach((record) => record.addedNodes.forEach((node) => {
      if (node instanceof Element) scan(node);
    }));
  });
  observer.observe(document.documentElement, { childList: true, subtree: true });

  document.addEventListener('hnt:feed-translation-refresh', () => scan());
})();

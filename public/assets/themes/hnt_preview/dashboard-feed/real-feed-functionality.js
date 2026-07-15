/* Feed-only functionality fixes. No visual/CSS overrides live in this file. */
(() => {
  const internalUrlPattern = /(?:https?:\/\/)?(?:www\.)?hnt\.rocks(?:\/[^\s<>"']*)?/giu;
  const contentSelector = '.post-body p, [data-comment-text], .comment-bubble p';
  const previewCache = new Map();

  const normalizeInternalHref = (candidate) => {
    let value = String(candidate || '').trim();
    if (!value) return null;

    if (!/^https?:\/\//i.test(value)) {
      value = `https://${value}`;
    }

    try {
      const url = new URL(value);
      const allowedHosts = new Set(['hnt.rocks', 'www.hnt.rocks', window.location.hostname.toLowerCase()]);
      if (!allowedHosts.has(url.hostname.toLowerCase())) return null;

      return `${url.pathname || '/'}${url.search}${url.hash}`;
    } catch (_) {
      return null;
    }
  };

  const splitTrailingPunctuation = (value) => {
    const clean = String(value || '').replace(/[.,!?;:]+$/u, '');
    return [clean || value, String(value || '').slice(clean.length)];
  };

  const linkifyTextNode = (textNode) => {
    const text = textNode.nodeValue || '';
    if (!text || !/hnt\.rocks/i.test(text)) return;

    const parent = textNode.parentElement;
    if (!parent || parent.closest('a, button, script, style, textarea, input, code, pre')) return;

    internalUrlPattern.lastIndex = 0;
    const matches = [...text.matchAll(internalUrlPattern)];
    if (!matches.length) return;

    const fragment = document.createDocumentFragment();
    let offset = 0;

    matches.forEach((match) => {
      const candidate = match[0];
      const position = match.index ?? 0;

      if (position > offset) {
        fragment.appendChild(document.createTextNode(text.slice(offset, position)));
      }

      const [clean, trailing] = splitTrailingPunctuation(candidate);
      const href = normalizeInternalHref(clean);

      if (href) {
        const link = document.createElement('a');
        link.href = href;
        link.className = 'hnt-internal-link font-semibold text-blue-500 hover:underline';
        link.textContent = clean;
        fragment.appendChild(link);
      } else {
        fragment.appendChild(document.createTextNode(clean));
      }

      if (trailing) fragment.appendChild(document.createTextNode(trailing));
      offset = position + candidate.length;
    });

    if (offset < text.length) {
      fragment.appendChild(document.createTextNode(text.slice(offset)));
    }

    textNode.replaceWith(fragment);
  };

  const linkifyElement = (element) => {
    if (!(element instanceof Element)) return;

    const roots = element.matches(contentSelector)
      ? [element]
      : [...element.querySelectorAll(contentSelector)];

    roots.forEach((root) => {
      const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
      const nodes = [];
      let current = walker.nextNode();

      while (current) {
        nodes.push(current);
        current = walker.nextNode();
      }

      nodes.forEach(linkifyTextNode);
    });
  };

  const titleize = (value) => String(value || '')
    .split(/[-_]+/u)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');

  const fallbackPreviewFor = (href) => {
    const url = new URL(href, window.location.origin);
    const segments = url.pathname.split('/').filter(Boolean);
    const first = segments[0] || '';
    const second = segments[1] || '';
    const displayUrl = `hnt.rocks${url.pathname}${url.search}${url.hash}`;

    if (first === 'cups') {
      return {
        url: `${url.pathname}${url.search}${url.hash}`,
        display_url: displayUrl,
        label: 'HNT.ROCKS · CUP',
        title: second ? titleize(second) : 'Community Cups',
        description: segments[2] === 'teams'
          ? 'Cup-Teams, Einladungen und Teamverwaltung auf HNT.ROCKS.'
          : 'Cup-Details, Regeln, Wertung und Teilnehmer auf HNT.ROCKS.',
        icon: 'ph-trophy',
      };
    }

    if (first === 'teams') {
      return {
        url: `${url.pathname}${url.search}${url.hash}`,
        display_url: displayUrl,
        label: 'HNT.ROCKS · TEAM',
        title: second ? titleize(second) : 'Community Teams',
        description: 'Teamprofil, Mitglieder und Aktivitäten auf HNT.ROCKS.',
        icon: 'ph-users-three',
      };
    }

    if (first === 'u' && second) {
      return {
        url: `${url.pathname}${url.search}${url.hash}`,
        display_url: displayUrl,
        label: 'HNT.ROCKS · PROFIL',
        title: `@${decodeURIComponent(second)}`,
        description: 'Hunter-Profil und Community-Aktivitäten auf HNT.ROCKS.',
        icon: 'ph-user-circle',
      };
    }

    if (first === 'feed' && segments[1] === 'posts') {
      return {
        url: `${url.pathname}${url.search}${url.hash}`,
        display_url: displayUrl,
        label: 'HNT.ROCKS · BEITRAG',
        title: 'Community-Beitrag',
        description: 'Beitrag, Reaktionen und Kommentare auf HNT.ROCKS.',
        icon: 'ph-newspaper',
      };
    }

    if (first === 'moments') {
      return {
        url: `${url.pathname}${url.search}${url.hash}`,
        display_url: displayUrl,
        label: 'HNT.ROCKS · MOMENT',
        title: 'HNT.ROCKS Moment',
        description: 'Moment aus der HNT.ROCKS Community.',
        icon: 'ph-play-circle',
      };
    }

    return {
      url: `${url.pathname}${url.search}${url.hash}`,
      display_url: displayUrl,
      label: 'HNT.ROCKS',
      title: titleize(segments.at(-1)) || 'HNT.ROCKS',
      description: 'Interner Bereich der HNT.ROCKS Community.',
      icon: 'ph-link-simple',
    };
  };

  const meaningfulMeta = (documentNode, selector) => {
    const value = documentNode.querySelector(selector)?.getAttribute('content')?.trim() || '';
    return value.length > 1 ? value : '';
  };

  const cleanPageTitle = (value, fallback) => {
    const clean = String(value || '')
      .replace(/\s*[|·—-]\s*HNT\.?ROCKS.*$/iu, '')
      .trim();

    if (!clean || /^HNT\.?ROCKS$/iu.test(clean)) return fallback;
    return clean;
  };

  const fetchInternalPreview = (href) => {
    const normalized = normalizeInternalHref(href);
    if (!normalized) return Promise.resolve(null);
    if (previewCache.has(normalized)) return previewCache.get(normalized);

    const request = (async () => {
      const fallback = fallbackPreviewFor(normalized);

      try {
        const response = await fetch(normalized, {
          method: 'GET',
          credentials: 'same-origin',
          headers: {
            Accept: 'text/html,application/xhtml+xml',
            'X-Requested-With': 'XMLHttpRequest',
          },
        });

        if (!response.ok) return fallback;

        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('text/html') && !contentType.includes('application/xhtml+xml')) {
          return fallback;
        }

        const html = await response.text();
        const documentNode = new DOMParser().parseFromString(html, 'text/html');
        const metadataTitle = meaningfulMeta(documentNode, 'meta[property="og:title"]')
          || documentNode.querySelector('title')?.textContent?.trim()
          || '';
        const metadataDescription = meaningfulMeta(documentNode, 'meta[property="og:description"]')
          || meaningfulMeta(documentNode, 'meta[name="description"]');

        return {
          ...fallback,
          title: cleanPageTitle(metadataTitle, fallback.title),
          description: metadataDescription || fallback.description,
        };
      } catch (_) {
        return fallback;
      }
    })();

    previewCache.set(normalized, request);
    return request;
  };

  const createPreviewCard = (preview) => {
    const wrapper = document.createElement('div');
    wrapper.dataset.hntInternalLinkPreviews = '';

    const card = document.createElement('a');
    card.className = 'hnt-internal-link-preview';
    card.href = preview.url;
    card.setAttribute('aria-label', `${preview.title} öffnen`);

    const icon = document.createElement('span');
    icon.className = 'hnt-internal-link-icon';
    icon.setAttribute('aria-hidden', 'true');

    const iconGlyph = document.createElement('i');
    iconGlyph.className = `ph ${preview.icon || 'ph-link-simple'}`;
    iconGlyph.setAttribute('aria-hidden', 'true');
    icon.appendChild(iconGlyph);

    const copy = document.createElement('span');
    copy.className = 'hnt-internal-link-copy';

    const label = document.createElement('span');
    label.textContent = preview.label || 'HNT.ROCKS';

    const title = document.createElement('strong');
    title.textContent = preview.title || 'HNT.ROCKS';

    const description = document.createElement('em');
    description.textContent = preview.description || 'Interner Bereich der HNT.ROCKS Community.';

    const displayUrl = document.createElement('small');
    displayUrl.textContent = preview.display_url || `hnt.rocks${preview.url || '/'}`;

    copy.append(label, title, description, displayUrl);
    card.append(icon, copy);
    wrapper.appendChild(card);

    return wrapper;
  };

  const previewHostFor = (article) => article.querySelector('.post-body')
    || article.querySelector('[data-hnt-post-body-wrap]')?.parentElement
    || null;

  const hydratePostPreview = async (article) => {
    if (!(article instanceof Element)) return;
    if (article.querySelector('.hnt-internal-link-preview')) return;
    if (article.dataset.hntInternalPreviewState) return;

    const link = article.querySelector('.post-body a.hnt-internal-link, [data-hnt-post-body] a.hnt-internal-link');
    const host = previewHostFor(article);
    if (!link || !host) return;

    article.dataset.hntInternalPreviewState = 'loading';
    const preview = await fetchInternalPreview(link.getAttribute('href') || link.href);

    if (!preview || !article.isConnected || article.querySelector('.hnt-internal-link-preview')) {
      article.dataset.hntInternalPreviewState = 'done';
      return;
    }

    host.appendChild(createPreviewCard(preview));
    article.dataset.hntInternalPreviewState = 'done';
  };

  const hydrateInternalPreviews = (element) => {
    if (!(element instanceof Element)) return;

    const posts = element.matches('[data-real-feed-post], [data-hnt-preview-post], .social-post')
      ? [element]
      : [...element.querySelectorAll('[data-real-feed-post], [data-hnt-preview-post], .social-post')];

    posts.forEach((post) => {
      void hydratePostPreview(post);
    });
  };

  const profileRouteFor = (target) => {
    if (target.closest('#profilePanel .profile-settings')) return '/account/settings';
    if (target.closest('#profilePanel .edit-profile-button')) return '/profile/edit';

    const profileLink = target.closest('#profilePanel .profile-links button');
    if (!profileLink) return null;

    const buttons = [...document.querySelectorAll('#profilePanel .profile-links button')];
    const index = buttons.indexOf(profileLink);

    return ['/profile', '/messages', '/crowns/inventory'][index] || null;
  };

  document.addEventListener('click', (event) => {
    const target = event.target;
    if (!(target instanceof Element)) return;

    const route = profileRouteFor(target);
    if (!route) return;

    event.preventDefault();
    event.stopImmediatePropagation();
    window.location.assign(route);
  }, true);

  const processElement = (element) => {
    if (!(element instanceof Element)) return;
    linkifyElement(element);
    hydrateInternalPreviews(element);
  };

  const start = () => {
    processElement(document.body);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node instanceof Element) {
            processElement(node);
          } else if (node.nodeType === Node.TEXT_NODE && node.parentElement?.matches(contentSelector)) {
            linkifyTextNode(node);
            const article = node.parentElement.closest('[data-real-feed-post], [data-hnt-preview-post], .social-post');
            if (article) hydrateInternalPreviews(article);
          }
        });
      });
    });

    observer.observe(document.body, { childList: true, subtree: true });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start, { once: true });
  } else {
    start();
  }
})();
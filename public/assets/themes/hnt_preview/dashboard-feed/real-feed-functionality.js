/* Feed-only functionality fixes. No visual/CSS overrides live in this file. */
(() => {
  const internalUrlPattern = /(?:https?:\/\/)?(?:www\.)?hnt\.rocks(?:\/[^\s<>"']*)?/giu;
  const contentSelector = '.post-body p, [data-comment-text], .comment-bubble p';

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

  const start = () => {
    linkifyElement(document.body);

    const observer = new MutationObserver((mutations) => {
      mutations.forEach((mutation) => {
        mutation.addedNodes.forEach((node) => {
          if (node instanceof Element) {
            linkifyElement(node);
          } else if (node.nodeType === Node.TEXT_NODE && node.parentElement?.matches(contentSelector)) {
            linkifyTextNode(node);
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

/* HNTv3 Feed adapter: original HTML/CSS, real Laravel posts. No demo writes. */
(() => {
  'use strict';
  const list = document.getElementById('feedList');
  const modal = document.getElementById('hntNextModal');
  const modalBody = document.getElementById('hntNextModalContent');
  const feedback = document.getElementById('hntNextFeedback');
  if (!list || !modal || !modalBody) return;

  let toastTimer;
  function toast(message) {
    if (!feedback) return;
    feedback.textContent = message;
    feedback.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => (feedback.hidden = true), 3200);
  }
  function svg(id) {
    const e = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    e.setAttribute('class', 'ph');
    e.setAttribute('viewBox', '0 0 256 256');
    const u = document.createElementNS('http://www.w3.org/2000/svg', 'use');
    u.setAttribute('href', '#ph-' + id);
    e.appendChild(u);
    return e;
  }

  function close() {
    modal.hidden = true;
    modalBody.replaceChildren();
    document.body.style.removeProperty('overflow');
  }
  function open(title, children) {
    modalBody.replaceChildren();
    const h = document.createElement('h2');
    h.textContent = title;
    modalBody.append(h, ...children);
    modal.hidden = false;
    document.body.style.overflow = 'hidden';
    document.getElementById('hntNextModalClose')?.focus();
  }
  document.getElementById('hntNextModalClose')?.addEventListener('click', close);
  modal.addEventListener('click', e => { if (e.target === modal) close(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) close(); });

  const composer = document.getElementById('composerOverlay');
  function showComposer() {
    if (composer) {
      composer.hidden = false;
      document.body.style.overflow = 'hidden';
      composer.querySelector('textarea')?.focus();
    }
  }
  function closeComposer() {
    if (composer) composer.hidden = true;
    document.body.style.removeProperty('overflow');
  }
  document.getElementById('headerPostBtn')?.addEventListener('click', showComposer);
  document.getElementById('composerStart')?.addEventListener('click', showComposer);
  document.getElementById('composerMediaBtn')?.addEventListener('click', showComposer);
  document.getElementById('composerClose')?.addEventListener('click', closeComposer);
  document.getElementById('composerMomentBtn')?.addEventListener('click', () => location.assign('/moments'));
  composer?.addEventListener('click', e => { if (e.target === composer) closeComposer(); });
  document.getElementById('newPostForm')?.addEventListener('submit', e => {
    e.preventDefault();
    toast('Beiträge erstellen verbinden wir als Nächstes mit Laravel.');
  });

  list.addEventListener('click', async e => {
    const el = e.target.closest('[data-next-media],[data-next-comments],[data-next-share],[data-next-like-preview]');
    if (!el) return;
    e.preventDefault();
    const post = el.closest('[data-next-feed-post]');
    if (!post) return;
    if (el.hasAttribute('data-next-like-preview')) {
      toast('Reaktionen werden in der nächsten Integrationsstufe freigeschaltet.');
      return;
    }
    if (el.hasAttribute('data-next-comments')) {
      const tmpl = post.querySelector('.hnt-next-comments-template');
      if (!tmpl) return;
      const container = document.createElement('div');
      container.className = 'hnt-next-comments';
      container.appendChild(tmpl.content.cloneNode(true));
      const hint = document.createElement('p');
      hint.className = 'hnt-next-message';
      hint.textContent = 'Kommentare aus dem vorhandenen Laravel-Feed. Antworten schalten wir als Nächstes frei.';
      open('Kommentare', [container, hint]);
      return;
    }
    if (el.hasAttribute('data-next-media')) {
      const url = el.dataset.mediaUrl;
      if (!url) return;
      let media;
      if (el.dataset.mediaType === 'video') {
        media = document.createElement('video');
        media.controls = true;
        media.autoplay = false;
      } else {
        media = document.createElement('img');
        media.alt = 'Medienbeitrag';
      }
      media.src = url;
      open('Bayou Focus', [media]);
      return;
    }
    if (el.hasAttribute('data-next-share')) {
      const url = post.dataset.shareUrl;
      if (!url) return;
      try {
        await navigator.clipboard.writeText(url);
        toast('Beitragslink kopiert.');
      } catch {
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.textContent = 'Beitrag öffnen ↗';
        open('Teilen', [anchor]);
      }
    }
  });

  const allFilters = document.querySelectorAll('.feed-filter[data-sort="latest"]');
  for (const btn of allFilters) {
    btn.addEventListener('click', e => {
      e.preventDefault();
      const posts = [...list.querySelectorAll('[data-next-feed-post]')];
      posts.sort((a,b) => +b.dataset.time - +a.dataset.time);
      list.append(...posts);
      document.querySelectorAll('.feed-filter').forEach(x => x.classList.remove('is-selected'));
      btn.classList.add('is-selected');
    });
  }

  // Original HNTv3 nav visual remains; these are functioning navigation elements.
  for (const btn of document.querySelectorAll('[data-more]')) {
    btn.addEventListener('click', () => toast('Beitragsoptionen im Live-Feed folgen.'));
  }

  let loading = false;
  let nextLink = document.getElementById('nextLoadMore');
  const ids = new Set([...list.querySelectorAll('[data-next-feed-post]')].map(x => x.dataset.nextFeedPost));
  async function loadMore() {
    if (loading || !nextLink) return;
    loading = true;
    const link = nextLink;
    link.textContent = 'Beiträge laden …';
    try {
      const response = await fetch(link.href, {credentials:'same-origin',headers:{Accept:'text/html'}});
      if (!response.ok || !response.url.startsWith(location.origin + '/next/')) throw Error('Fetch failed');
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      let count = 0;
      for (const card of doc.querySelectorAll('#feedList > [data-next-feed-post]')) {
        if (ids.has(card.dataset.nextFeedPost)) continue;
        ids.add(card.dataset.nextFeedPost);
        list.append(document.importNode(card,true));
        count++;
      }
      const more = doc.querySelector('#nextLoadMore');
      if (more && count) {
        link.href = more.href;
        link.textContent = 'Weitere Beiträge laden';
      } else {
        link.remove();
        nextLink = null;
      }
    } catch {
      link.textContent = 'Laden fehlgeschlagen – erneut versuchen';
    } finally {
      loading = false;
    }
  }
  nextLink?.addEventListener('click', e => {e.preventDefault(); void loadMore();});
})();

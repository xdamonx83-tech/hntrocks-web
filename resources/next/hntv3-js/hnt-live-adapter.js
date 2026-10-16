/* Real Laravel data adapter – original HNTv3 UI modules remain intact.
   This stage is read-only until reactions, composer and comment writes are
   connected to Laravel and protected by their existing authorization logic. */
(() => {
  'use strict';
  const root = document.querySelector('[data-hnt-next-live-posts="true"]');
  const list = document.querySelector('#feedList');
  if (!root || !list) return;

  const bridge = window.HNTCommentBridge;
  if (bridge) {
    const previous = bridge.getComments;
    bridge.getComments = (postId) => {
      const card = document.getElementById(postId);
      const json = card?.querySelector('.hnt-live-comments');
      if (!json) return previous(postId);
      try {
        const rows = JSON.parse(json.textContent || '[]');
        return Array.isArray(rows) ? rows.map(row => ({...row})) : [];
      } catch {
        return [];
      }
    };
  }

  let toastTimer = 0;
  function toast(message) {
    let e = document.getElementById('hntLiveReadOnlyToast');
    if (!e) {
      e = document.createElement('div');
      e.id = 'hntLiveReadOnlyToast';
      e.className = 'hnt-live-action-toast';
      e.setAttribute('role','status');
      document.body.appendChild(e);
    }
    e.textContent = message;
    e.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {e.hidden = true}, 3200);
  }
  // In the original demo these controls mutate only local memory. On a page
  // displaying real posts that would be misleading, so keep them read-only
  // until their real production endpoints are deliberately integrated.
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-next-server-filter]');
    if (link) {
      event.stopImmediatePropagation();
      return; // browser follows the legitimate Laravel filter link
    }
    const action = event.target.closest('[data-like],[data-save],#hntMvLike,#hntMvSave,#hntPdLike,#hntPdSave');
    if (!action) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    toast('Reaktionen und Merkliste werden als Nächstes mit Laravel verbunden.');
  }, true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('#newPostForm,.comment-form,#hntPdCommentForm,#hntMvCommentForm,.hnt-share-form')) return;
    event.preventDefault();
    event.stopImmediatePropagation();
    toast('Die Schreibfunktionen sind während der Feed-Umstellung noch deaktiviert.');
  }, true);

  // Retain the existing modal and media viewer for real posts. The underlying
  // comments are preloaded with the same visibility rules as the legacy feed.
  document.getElementById('resetFeed')?.addEventListener('click', (e) => {
    e.preventDefault();
    location.href = '/next/feed';
  }, true);

  let loading = false;
  let more = document.querySelector('#hntNextLoadMore');
  const shownIds = new Set([...list.querySelectorAll('[data-next-feed-post]')].map(x=>x.dataset.nextFeedPost));
  async function loadNext() {
    if (loading || !more) return;
    loading = true;
    const link = more;
    link.textContent = 'Weitere Beiträge laden …';
    try {
      const response = await fetch(link.href,{credentials:'same-origin',headers:{Accept:'text/html'}});
      if (!response.ok || !response.url.startsWith(location.origin+'/next/')) throw new Error('Feed-Antwort fehlt');
      const doc = new DOMParser().parseFromString(await response.text(),'text/html');
      let count = 0;
      for (const article of doc.querySelectorAll('#feedList > [data-next-feed-post]')) {
        const id = article.dataset.nextFeedPost;
        if (!id || shownIds.has(id)) continue;
        shownIds.add(id);
        list.appendChild(document.importNode(article,true));
        count++;
      }
      const nextLink = doc.querySelector('#hntNextLoadMore');
      if (nextLink && count > 0) {
        link.href = nextLink.href;
        link.textContent = 'Weitere Beiträge laden';
      } else {
        link.remove();
        more = null;
        observer?.disconnect();
      }
      document.querySelector('#feedEmpty')?.toggleAttribute('hidden', shownIds.size > 0);
    } catch {
      link.textContent = 'Laden fehlgeschlagen – erneut versuchen';
    } finally {
      loading = false;
    }
  }
  let observer = null;
  if (more && 'IntersectionObserver' in window) {
    observer = new IntersectionObserver((entries)=>{
      if(entries.some(e=>e.isIntersecting))void loadNext();
    },{rootMargin:'0px 0px 300px 0px'});
    observer.observe(more);
  }
  more?.addEventListener('click',(e)=>{e.preventDefault();void loadNext()});

  // Mark the feed as read-only; demo widgets and dropdowns are visual
  // placeholders until their live data migration takes place.
  root.dataset.nextIntegration = 'posts';
})();

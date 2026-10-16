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
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  const requestsInFlight = new Set();

  async function mutate(post, action, clicked) {
    const id = post?.dataset.nextFeedPost;
    if (!id || !csrf) { toast('Anmeldung oder CSRF-Schutz fehlt. Seite neu laden.'); return; }
    const lock = id + ':' + action;
    if (requestsInFlight.has(lock)) return;
    requestsInFlight.add(lock);
    clicked.disabled = true;
    const path = action === 'like' ? '/reaction' : '/bookmark';
    try {
      const response = await fetch('/feed/' + encodeURIComponent(id) + path, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'X-CSRF-TOKEN':csrf, 'Accept':'application/json',
          'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'
        },
        body: JSON.stringify(action==='like' ? {type:'like'} : {})
      });
      if (response.status===401 || response.status===419) throw Error('Sitzung abgelaufen. Bitte neu anmelden.');
      if (response.status===403) throw Error('Für diesen Beitrag fehlen die Berechtigungen.');
      if (!response.ok) throw Error('Aktion fehlgeschlagen ('+response.status+').');
      const payload = await response.json();
      if(action==='like'){
        const reacted=Boolean(payload.reacted),count=Number(payload.count);
        if(!Number.isFinite(count)) throw Error('Unerwartete Serverantwort');
        post.dataset.likes=String(count);
        post.classList.toggle('is-liked',reacted);
        post.querySelectorAll('[data-like]').forEach(b=>{
          b.classList.toggle('is-liked',reacted);
          b.setAttribute('aria-pressed',String(reacted));
          const n=b.querySelector('.like-count');if(n)n.textContent=String(count);
        });
        const modalCount=document.querySelector('#hntPdLikes');
        if(modalCount && !document.querySelector('#hntPostDialog')?.hidden)modalCount.textContent=String(count);
        if(reacted){
          document.dispatchEvent(new CustomEvent('hnt:like-confirmed',{detail:{button:clicked,reacted:true,postId:id}}));
        }
      } else {
        const saved=Boolean(payload.bookmarked);
        post.classList.toggle('user-saved',saved);
        const menuLabel=post.querySelector('.post-more-menu [data-save] .post-option-title');
        const menuHint=post.querySelector('.post-more-menu [data-save] .post-option-description');
        const menuCheck=post.querySelector('.post-more-menu [data-save] .post-option-check');
        if(menuLabel)menuLabel.textContent=saved?'Beitrag gemerkt':'Beitrag speichern';
        if(menuHint)menuHint.textContent=saved?'Aus Merkliste entfernen':'Für später in Merkliste sichern';
        if(menuCheck)menuCheck.hidden=!saved;
        post.querySelectorAll('[data-save]').forEach(b=>{
          b.classList.toggle('is-saved',saved);
          b.setAttribute('aria-pressed',String(saved));
          const t=b.querySelector('span');if(t && !b.closest('.post-more-menu'))t.textContent=saved?'Gemerkt':'Merken';
        });
      }
    } catch(e) {toast(e?.message || 'Server nicht erreichbar');}
    finally{clicked.disabled=false;requestsInFlight.delete(lock);}
  }

  document.addEventListener('click',(event)=>{
    const filter=event.target.closest('a[data-next-server-filter]');
    if(filter){event.stopImmediatePropagation();return;}
    const btn=event.target.closest('.feed-post[data-next-feed-post] [data-like],.feed-post[data-next-feed-post] [data-save]');
    if(btn){
      event.preventDefault();event.stopImmediatePropagation();
      const post=btn.closest('.feed-post[data-next-feed-post]');
      void mutate(post,btn.hasAttribute('data-like')?'like':'bookmark',btn);
      return;
    }
    // Dialog controls use the original modal's forwarding click events.
    // Until the exact active-card contract is verified, keep direct actions
    // disabled instead of potentially mutating the wrong user's post.
    if(event.target.closest('#hntMvLike,#hntMvSave,#hntPdLike,#hntPdSave')){
      event.preventDefault();event.stopImmediatePropagation();
      toast('Diese Aktion im Medienviewer wird als Nächstes angebunden.');
    }
  },true);

  document.addEventListener('submit', (event) => {
    const form = event.target;
    if (!form.matches('.hnt-share-form')) return;
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

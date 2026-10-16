/* HNT.ROCKS BAYOU FOCUS — local feed media viewer; original feed interactions stay authoritative. */
(() => {
  'use strict';
  const root = document.querySelector('#feedList');
  if (!root) return;
  const select = (s, r = document) => r.querySelector(s);
  const all = (s, r = document) => Array.from(r.querySelectorAll(s));
  const icon = n => '<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-' + n + '"></use></svg>';
  const viewer = document.createElement('div');
  viewer.id = 'hntMediaViewer';
  viewer.className = 'hnt-viewer';
  viewer.hidden = true;
  viewer.setAttribute('role', 'dialog');
  viewer.setAttribute('aria-modal', 'true');
  viewer.setAttribute('aria-label', 'Bayou Focus Medienviewer');
  viewer.innerHTML = [
    '<div class="hnt-viewer-top">',
      '<div class="hnt-viewer-brand"><span class="hnt-viewer-mark">' + icon('crosshair') + '</span>',
      '<div class="hnt-viewer-brand-text"><strong>HNT.ROCKS</strong><small><i></i>BAYOU FOCUS</small></div></div>',
      '<div class="hnt-viewer-head-right">',
      '<span class="hnt-viewer-counter"><strong id="hntMvIndex">01</strong> / <span id="hntMvTotal">03</span></span>',
      '<button class="hnt-viewer-icon" type="button" id="hntMvZoom" title="Zoom wechseln (+ / −)" aria-label="Bild vergrößern">' + icon('magnifying-glass') + '</button>',
      '<button class="hnt-viewer-icon hnt-viewer-close" type="button" id="hntMvClose" title="Schließen (Esc)" aria-label="Medienviewer schließen">' + icon('x') + '</button>',
      '</div></div>',
    '<div class="hnt-viewer-shell">',
      '<div class="hnt-viewer-main">',
        '<div class="hnt-viewer-stage" id="hntMvStage">',
          '<div class="hnt-viewer-backdrop" id="hntMvBackdrop" aria-hidden="true"></div>',
          '<span class="hnt-viewer-focus-label">HUNTER CAPTURE</span>',
          '<button class="hnt-viewer-nav prev" type="button" id="hntMvPrev" aria-label="Vorheriges Bild">' + icon('arrow-left') + '</button>',
          '<div class="hnt-viewer-picture" id="hntMvPicture"><img class="hnt-viewer-image" id="hntMvImage" alt="" draggable="false"></div>',
          '<button class="hnt-viewer-nav next" type="button" id="hntMvNext" aria-label="Nächstes Bild">' + icon('arrow-right') + '</button>',
          '<span class="hnt-viewer-zoom-status" id="hntMvZoomLabel">100%</span><span class="hnt-viewer-stamp">HNT.ROCKS / CAPTURE</span>',
        '</div>',
        '<div class="hnt-viewer-tray" id="hntMvTray">',
          '<div class="hnt-viewer-tray-label"><strong>GALERIE</strong><small id="hntMvGalleryCount">3 Aufnahmen</small></div>',
        '</div>',
      '</div>',
      '<aside class="hnt-viewer-aside">',
        '<div class="hnt-viewer-aside-bar"></div>',
        '<div class="hnt-viewer-aside-inner">',
          '<div class="hnt-viewer-eyebrow">' + icon('crosshair') + ' DIREKT AUS DEM BAYOU</div>',
          '<div class="hnt-viewer-person"><img class="hnt-viewer-avatar" id="hntMvAvatar" src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""><div>',
          '<strong id="hntMvAuthor">HNT.ROCKS</strong><small id="hntMvAuthorMeta">Community · gerade eben</small></div></div>',
          '<div class="hnt-viewer-divider"></div>',
          '<div class="hnt-viewer-info-kicker" id="hntMvKicker">COMMUNITY CAPTURE</div>',
          '<h2 class="hnt-viewer-title" id="hntMvTitle">Ein Moment aus dem Bayou</h2>',
          '<p class="hnt-viewer-description" id="hntMvDescription"></p>',
          '<p class="hnt-viewer-meta" id="hntMvMeta"></p>',
          '<div class="hnt-viewer-stat-row">',
            '<span class="hnt-viewer-stat">' + icon('heart') + ' <strong id="hntMvLikes">0</strong> Likes</span>',
            '<span class="hnt-viewer-stat">' + icon('chat-circle-dots') + ' <strong id="hntMvComments">0</strong> Kommentare</span>',
          '</div>',
          '<div class="hnt-viewer-actions">',
            '<button type="button" class="hnt-viewer-action" id="hntMvLike">' + icon('heart') + '<span>Gefällt mir</span></button>',
            '<button type="button" class="hnt-viewer-action" id="hntMvComment">' + icon('chat-circle-dots') + '<span>Kommentare</span></button>',
            '<button type="button" class="hnt-viewer-action" id="hntMvSave">' + icon('bookmark-simple') + '<span>Merken</span></button>',
            '<button type="button" class="hnt-viewer-action" id="hntMvShare">' + icon('share-fat') + '<span>Teilen</span></button>',
          '</div>',
        '</div>',
        '<div class="hnt-viewer-aside-bottom"><strong>HUNTER-STEUERUNG</strong>',
          '<p><kbd>←</kbd> <kbd>→</kbd> Bild wechseln &nbsp; <kbd>+</kbd> <kbd>−</kbd> Zoom<br><kbd>Esc</kbd> Schließen · Doppelklick zoomt</p>',
        '</div>',
      '</aside>',
    '</div>'
  ].join('');
  document.body.appendChild(viewer);
  const el = id => document.getElementById(id);
  const mainImage = el('hntMvImage');
  const picture = el('hntMvPicture');
  const tray = el('hntMvTray');
  let entries = [], index = 0, zoom = 1, dx = 0, dy = 0, open = false, busy = false;
  let restoreFocus = null, oldOverflow = '', closeTimer = 0, openingTimer = 0;
  let drag = null, touchStart = null;

  // Only actual media attachments are interactive. Cash-register illustrations,
  // map previews and other decorative artwork are deliberately excluded.
  function enhanceImages() {
    all('#feedList .media-art, #feedList [data-post-media]').forEach(art => {
      if (!select('img',art) || art.classList.contains('hnt-viewer-launch')) return;
      art.classList.add('hnt-viewer-launch');
      art.tabIndex = 0;
      art.setAttribute('role','button');
      art.setAttribute('aria-label','Medien dieses Beitrags öffnen');
      art.insertAdjacentHTML('beforeend','<span class="hnt-viewer-launch-icon" aria-hidden="true">' + icon('magnifying-glass') + '</span>');
    });
  }

  // Assemble media exclusively from one post, not the complete feed.
  // Additional post attachments can use data-post-media.
  function buildEntries(card) {
    if (!card) return [];
    const author = select('.post-author strong', card)?.textContent?.trim() || 'Hunter';
    const meta = select('.post-author > span', card)?.textContent?.trim() || 'Aus dem Bayou';
    const avatar = select('.post-avatar img', card)?.getAttribute('src') || '/build/next/hntv3-assets/avatar-hnt.webp';
    const common = {card,author,meta,avatar};
    const caption = select('.post-caption',card)?.textContent?.trim() || '';
    return all('.media-art > img, [data-post-media] > img',card)
      .filter(img => img.getAttribute('src') && img.dataset.decorative!=='true' && !img.closest('[data-decorative-media]'))
      .map(img => ({
        ...common,type:'capture',node:img.closest('.media-art, [data-post-media]'),
        src:img.getAttribute('src'),alt:img.alt,
        title:card.id === 'post-bayou' ? 'Ruhe nach dem letzten Schuss.' : 'Ein Moment aus dem Bayou.',
        kicker:'COMMUNITY CAPTURE',description:caption
      }));
  }

  function updateActions() {
    const card = entries[index]?.card;
    if (!card) return;
    const l = select('[data-like]',card), s = select('.post-save[data-save]',card);
    el('hntMvLikes').textContent = select('.like-count',card)?.textContent || '0';
    el('hntMvComments').textContent = select('.comment-count',card)?.textContent || '0';
    el('hntMvLike').classList.toggle('is-active', !!l?.classList.contains('is-liked'));
    el('hntMvSave').classList.toggle('is-active', !!s?.classList.contains('is-saved'));
    select('span',el('hntMvLike')).textContent = l?.classList.contains('is-liked') ? 'Gefällt dir' : 'Gefällt mir';
    select('span',el('hntMvSave')).textContent = s?.classList.contains('is-saved') ? 'Gemerkt' : 'Merken';
  }

  function setZoom(value) {
    zoom = Math.max(1, Math.min(2.5,value));
    if (zoom === 1) {dx = 0; dy = 0;}
    picture.classList.toggle('is-zoomed',zoom > 1);
    mainImage.style.transform = 'translate(' + dx + 'px,' + dy + 'px) scale(' + zoom + ')';
    el('hntMvZoomLabel').textContent = Math.round(zoom*100)+'%';
    el('hntMvZoom').setAttribute('aria-label',zoom > 1 ? 'Zoom zurücksetzen' : 'Bild vergrößern');
  }
  function rebuildTray() {
    tray.hidden=entries.length<=1;
    all('.hnt-viewer-thumb',tray).forEach(b=>b.remove());
    entries.forEach((entry,i)=>{
      const b=document.createElement('button');b.type='button';b.className='hnt-viewer-thumb';
      b.title=entry.title;b.setAttribute('aria-label','Bild '+(i+1)+': '+entry.title);
      const img=document.createElement('img');img.src=entry.src;img.alt='';img.loading='lazy';
      b.appendChild(img);b.addEventListener('click',()=>render(i,true));tray.appendChild(b);
    });
    el('hntMvGalleryCount').textContent=entries.length+' Aufnahmen';
    el('hntMvTotal').textContent=String(entries.length).padStart(2,'0');
  }

  function render(i, animate) {
    if (!entries.length) return;
    index=(i+entries.length)%entries.length;
    const e=entries[index];
    viewer.dispatchEvent(new CustomEvent('hnt:viewer-media',{detail:{card:e.card}}));
    setZoom(1);
    mainImage.src=e.src;
    mainImage.alt=e.alt || e.title;
    el('hntMvBackdrop').style.backgroundImage='url("' + e.src.replace(/"/g,'') + '")';
    el('hntMvIndex').textContent=String(index+1).padStart(2,'0');
    el('hntMvAvatar').src=e.avatar;
    el('hntMvAuthor').textContent=e.author;
    el('hntMvAuthorMeta').textContent=e.meta;
    el('hntMvKicker').textContent=e.kicker;
    el('hntMvTitle').textContent=e.title;
    el('hntMvDescription').textContent=e.description;
    el('hntMvMeta').textContent='HNT.ROCKS · Hunt: Showdown';
    all('.hnt-viewer-thumb',tray).forEach((b,n)=>{
      b.classList.toggle('is-current',n===index);
      b.setAttribute('aria-current',n===index?'true':'false');
    });
    updateActions();
    const plural=entries.length>1;
    el('hntMvPrev').hidden=!plural;el('hntMvNext').hidden=!plural;
    const tips=select('.hnt-viewer-aside-bottom p',viewer);
    if(tips) tips.innerHTML=plural
      ? '<kbd>←</kbd> <kbd>→</kbd> Bild wechseln &nbsp; <kbd>+</kbd> <kbd>−</kbd> Zoom<br><kbd>Esc</kbd> Schließen · Doppelklick zoomt'
      : '<kbd>+</kbd> <kbd>−</kbd> Zoom &nbsp; <kbd>Esc</kbd> Schließen<br>Doppelklick auf das Bild zum Vergrößern';
    if (animate && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      mainImage.animate([{opacity:.22,transform:'translateX(16px) scale(.96)'},{opacity:1,transform:'none'}],{duration:400,easing:'cubic-bezier(.18,.85,.24,1)'});
    }
  }
  function show(entryIndex, from, card) {
    if (open) {render(entryIndex,true);return}
    clearTimeout(closeTimer);
    entries=buildEntries(card);
    if (!entries.length) return;
    restoreFocus=from||document.activeElement;
    oldOverflow=document.body.style.overflow;
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'media-viewer'}}));
    rebuildTray();
    viewer.hidden=false;open=true;
    render(entryIndex,false);
    document.body.style.overflow='hidden';
    requestAnimationFrame(()=>{
      viewer.classList.add('is-open','is-opening');
      el('hntMvClose').focus({preventScroll:true});
    });
    clearTimeout(openingTimer);
    openingTimer=setTimeout(()=>viewer.classList.remove('is-opening'),740);
  }
  function hide() {
    if (!open) return;
    open=false;
    clearTimeout(openingTimer);
    viewer.classList.remove('is-open','is-opening');
    document.body.style.overflow=oldOverflow;
    closeTimer=setTimeout(()=>{viewer.hidden=true;viewer.classList.remove('is-open');if(restoreFocus?.isConnected)restoreFocus.focus({preventScroll:true})},290);
  }
  function showBy(node) {
    const card=node.closest('.feed-post');
    window.__hntShareMediaCard=card;
    const current=buildEntries(card);
    const pos=current.findIndex(entry=>entry.node===node);
    if (pos<0) return;
    show(pos,node,card);
  }
  root.addEventListener('click',event=>{
    if(open)return;
    const art=event.target.closest('#feedList .media-art, #feedList [data-post-media]');
    if(!art || !select('img',art))return;
    event.preventDefault();
    showBy(art);
  });
  root.addEventListener('keydown',event=>{
    if(event.key!=='Enter' && event.key!==' ')return;
    const art=event.target.closest('.hnt-viewer-launch');
    if(!art)return;
    event.preventDefault();
    showBy(art);
  });
  viewer.addEventListener('click',e=>{if(e.target===viewer)hide()});
  el('hntMvClose').addEventListener('click',hide);
  el('hntMvPrev').addEventListener('click',()=>render(index-1,true));
  el('hntMvNext').addEventListener('click',()=>render(index+1,true));
  el('hntMvZoom').addEventListener('click',()=>setZoom(zoom>1?1:1.75));
  picture.addEventListener('dblclick',()=>setZoom(zoom>1?1:1.9));
  el('hntMvLike').addEventListener('click',()=>{select('[data-like]',entries[index].card)?.click();updateActions()});
  el('hntMvSave').addEventListener('click',()=>{select('.post-save[data-save]',entries[index].card)?.click();updateActions()});
  el('hntMvComment').addEventListener('click',()=>{
    const card=entries[index].card;hide();
    setTimeout(()=>{
      const box=select('.post-comment-box',card);
      if(box?.hidden)select('[data-comments-toggle]',card)?.click();
      card.scrollIntoView({behavior:'smooth',block:'center'});
      setTimeout(()=>select('.comment-form input',card)?.focus({preventScroll:true}),390);
    },310);
  });
  el('hntMvShare').addEventListener('click',async()=>{
    const share='https://hnt.rocks/feed#'+entries[index].card.id;
    const label=select('span',el('hntMvShare'));
    try{await navigator.clipboard.writeText(share);label.textContent='Kopiert';}
    catch{label.textContent='Link teilen';select('[data-share]',entries[index].card)?.click()}
    setTimeout(()=>label.textContent='Teilen',1500);
  });
  document.addEventListener('keydown',e=>{
    if(!open)return;
    if(e.key==='Escape'){e.preventDefault();hide();return}
    if(e.target.closest('input,textarea,[contenteditable="true"]') && e.key!=='Tab')return;
    if(e.key==='ArrowRight' && entries.length>1){e.preventDefault();render(index+1,true)}
    if(e.key==='ArrowLeft' && entries.length>1){e.preventDefault();render(index-1,true)}
    if(e.key==='+'||e.key==='='){e.preventDefault();setZoom(zoom+.3)}
    if(e.key==='-'){e.preventDefault();setZoom(zoom-.3)}
    if(e.key==='Tab'){
      const focusables=all('button:not([hidden]):not(:disabled)',viewer).filter(n=>n.getClientRects().length);
      if(!focusables.length)return;
      if(e.shiftKey && document.activeElement===focusables[0]){e.preventDefault();focusables.at(-1).focus()}
      else if(!e.shiftKey && document.activeElement===focusables.at(-1)){e.preventDefault();focusables[0].focus()}
    }
  },true);
  picture.addEventListener('pointerdown',e=>{
    if(e.pointerType==='touch')touchStart={x:e.clientX,y:e.clientY};
    if(zoom===1 || e.button!==0)return;
    drag={x:e.clientX-dx,y:e.clientY-dy};picture.classList.add('is-dragging');
    picture.setPointerCapture(e.pointerId);mainImage.style.transition='none';
  });
  picture.addEventListener('pointermove',e=>{
    if(!drag)return;
    dx=Math.max(-320,Math.min(320,e.clientX-drag.x));dy=Math.max(-240,Math.min(240,e.clientY-drag.y));
    setZoom(zoom);
  });
  const endDrag=e=>{
    if(drag){drag=null;picture.classList.remove('is-dragging');mainImage.style.removeProperty('transition')}
    if(touchStart && zoom===1 && e.pointerType==='touch'){
      const delta=e.clientX-touchStart.x;const vertical=e.clientY-touchStart.y;
      if(Math.abs(delta)>70 && Math.abs(vertical)<65)render(index+(delta<0?1:-1),true);
    }
    touchStart=null;
  };
  picture.addEventListener('pointerup',endDrag);
  picture.addEventListener('pointercancel',endDrag);
  picture.addEventListener('wheel',e=>{if(!open||!e.ctrlKey)return;e.preventDefault();setZoom(zoom+(e.deltaY<0?.15:-.15))},{passive:false});
  enhanceImages();
  new MutationObserver(()=>enhanceImages()).observe(root,{childList:true,subtree:false});
})();

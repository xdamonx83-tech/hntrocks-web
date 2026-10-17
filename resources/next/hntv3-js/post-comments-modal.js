/* HNT.ROCKS / Post Discussions — standalone post-details modal.
   Media itself continues to open Bayou Focus. Shared local comments/likes remain authoritative. */
(() => {
  'use strict';
  const feed = document.getElementById('feedList');
  if (!feed) return;
  const $ = (s,root=document)=>root.querySelector(s);
  const $$ = (s,root=document)=>Array.from(root.querySelectorAll(s));
  const glyph = name => '<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+name+'"></use></svg>';
  const bridge = ()=>window.HNTCommentBridge;
  const viewer = document.getElementById('hntMediaViewer');
  let active=null, opener=null, oldOverflow='', replyTo=null, justSubmitted=null, index=0;
  let closing=0;
  const threadPrefs = new Map();
  const modal=document.createElement('div');
  modal.id='hntPostDialog';
  modal.className='hnt-post-dialog';
  modal.hidden=true;
  modal.setAttribute('role','dialog');
  modal.setAttribute('aria-modal','true');
  modal.setAttribute('aria-labelledby','hntPdTitle');
  modal.innerHTML=[
    '<div class="hnt-pd-panel" role="document">',
      '<header class="hnt-pd-top">',
        '<div class="hnt-pd-brand"><span class="hnt-pd-brand-icon">'+glyph('crosshair')+'</span><span><small>HNT.ROCKS / COMMUNITY</small><strong id="hntPdTitle">Beitrag</strong></span></div>',
        '<button type="button" class="hnt-pd-close" id="hntPdClose" title="Schließen (Esc)" aria-label="Schließen">'+glyph('x')+'</button>',
      '</header>',
      '<div class="hnt-pd-scroll" id="hntPdScroll">',
        '<section class="hnt-pd-post" aria-label="Beitrag">',
          '<div class="hnt-pd-author"><img id="hntPdAvatar" src="/build/next/hntv3-assets/avatar-hnt.webp" alt=""><div><strong id="hntPdName"></strong><small id="hntPdMeta"></small></div><span class="hnt-pd-post-tag" id="hntPdTag">COMMUNITY</span></div>',
          '<div class="hnt-pd-content" id="hntPdContent"></div>',
          '<div class="hnt-pd-stats"><div><span class="hnt-pd-stat-heart">'+glyph('heart')+'</span><strong id="hntPdLikes">0</strong> Likes</div><span id="hntPdCommentTotal">0 Kommentare</span></div>',
          '<div class="hnt-pd-actions">',
            '<button type="button" id="hntPdLike" aria-pressed="false">'+glyph('heart')+'<span>Gefällt mir</span></button>',
            '<button type="button" id="hntPdFocusComment">'+glyph('chat-circle-dots')+'<span>Kommentieren</span></button>',
            '<button type="button" id="hntPdShare">'+glyph('share-fat')+'<span>Teilen</span></button>',
            '<button type="button" id="hntPdSave" aria-pressed="false">'+glyph('bookmark-simple')+'<span>Merken</span></button>',
          '</div>',
        '</section>',
        '<section class="hnt-pd-conversation" aria-label="Diskussion">',
          '<div class="hnt-pd-comments-heading"><strong>'+glyph('chat-circle-dots')+' Diskussion</strong><span>Neueste zuerst</span></div>',
          '<div class="hnt-pd-comments" id="hntPdComments"></div>',
        '</section>',
      '</div>',
      '<form class="hnt-pd-compose" id="hntPdForm">',
        '<div class="hnt-pd-compose-person"><img src="/build/next/hntv3-assets/avatar-profile.webp" alt=""><span>Kommentieren als <strong>KrispieArmy</strong></span><small id="hntPdChars">0 / 280</small></div>',
        '<div class="hnt-pd-reply" id="hntPdReply" hidden><span id="hntPdReplyName"></span><button type="button" id="hntPdCancelReply" aria-label="Antwort abbrechen">'+glyph('x')+'</button></div>',
        '<div class="hnt-pd-compose-field"><textarea id="hntPdText" maxlength="280" rows="2" placeholder="Schreib einen Kommentar ..." aria-label="Kommentar schreiben" required></textarea><button type="submit" id="hntPdSend" aria-label="Kommentar senden" disabled>'+glyph('paper-plane-tilt')+'</button></div>',
        '<div class="hnt-pd-compose-hint"><span>'+glyph('shield-check')+' Deine Antwort erscheint auch im Feed.</span><span>Enter senden · Shift + Enter Umbruch</span></div>',
      '</form>',
    '</div>'
  ].join('');
  document.body.append(modal);
  const name=$('#hntPdName'),meta=$('#hntPdMeta'),avatar=$('#hntPdAvatar'),content=$('#hntPdContent');
  const comments=$('#hntPdComments'),scroll=$('#hntPdScroll'),form=$('#hntPdForm'),textarea=$('#hntPdText');
  const replyBadge=$('#hntPdReply'),send=$('#hntPdSend');
  if(document.body.hasAttribute('data-hnt-next-live-posts')){
    const who=$('.hnt-pd-compose-person strong',modal);
    const photo=$('.hnt-pd-compose-person img',modal);
    if(who && document.body.dataset.nextViewerName)who.textContent=document.body.dataset.nextViewerName;
    if(photo && document.body.dataset.nextViewerAvatar)photo.src=document.body.dataset.nextViewerAvatar;
  }
  const currentlyOpen=()=>!modal.hidden && modal.classList.contains('is-open');
  const safeMedia=(src,alt)=>{const img=document.createElement('img');img.src=src;img.alt=alt||'';img.loading='eager';return img;};
  function setCount(){
    if(!active)return;
    const l=$('.like-count',active)?.textContent.trim()||'0';
    const c=$('.comment-count',active)?.textContent.trim()||'0';
    $('#hntPdLikes').textContent=l;
    $('#hntPdCommentTotal').textContent=c+' '+(Number(c)===1?'Kommentar':'Kommentare');
    const like=$('[data-like]',active);
    const liked=like?.getAttribute('aria-pressed')==='true';
    $('#hntPdLike').setAttribute('aria-pressed',String(liked));
    $('#hntPdLike').classList.toggle('is-liked',liked);
    $('#hntPdLike span').textContent=liked?'Gefällt dir':'Gefällt mir';
    const saved=$('.post-save[data-save]',active)?.getAttribute('aria-pressed')==='true';
    $('#hntPdSave').setAttribute('aria-pressed',String(saved));
    $('#hntPdSave').classList.toggle('is-saved',saved);
    $('#hntPdSave span').textContent=saved?'Gemerkt':'Merken';
  }
  function buildPost(card){
    const author=$('.post-author strong',card)?.textContent.trim()||'Hunter';
    name.textContent=author;
    $('#hntPdTitle').textContent=author+'s Beitrag';
    meta.textContent=$('.post-author > span',card)?.textContent.trim()||'Aus dem Bayou';
    avatar.src=$('.post-avatar img',card)?.getAttribute('src')||'/build/next/hntv3-assets/avatar-hnt.webp';
    const type=card.dataset.postType||'community';
    const category=card.dataset.postCategory || 'story';
    const tagName={story:'ERLEBNIS',question:'FRAGE',tip:'TIPP',discovery:'KASSENSPOT',loadout:'LOADOUT'};
    $('#hntPdTag').textContent=type==='poll'?'UMFRAGE':(type==='media'||type==='clip')?'MEDIUM':tagName[category]||'COMMUNITY';
    content.replaceChildren();
    const caption=$('.post-caption',card);
    if(caption){
      const p=document.createElement('p');
      p.className='hnt-pd-caption';
      p.textContent=caption.textContent.trim();
      content.append(p);
    }
    const story=$('.discovery-story',card);
    if(story){
      const title=document.createElement('h2');
      title.className='hnt-pd-discovery-title';
      title.textContent=$('h2',story)?.textContent.trim()||'Neuer Kassenspot gefunden.';
      content.append(title);
      const location=document.createElement('p');location.className='hnt-pd-discovery-location';
      location.innerHTML=glyph('map-trifold')+'<span></span>';
      $('span',location).textContent=$('.location',story)?.textContent.trim()||'Stillwater Bayou';
      content.append(location);
      const p=document.createElement('p');p.className='hnt-pd-description';
      p.textContent=$('p',story)?.textContent.trim()||'Community-Entdeckung';
      content.append(p);
      // Decorative illustration only: not an attachment and not a viewer entry.
      const media=document.createElement('div');media.className='hnt-pd-media hnt-pd-cash';
      media.append(safeMedia('/build/next/hntv3-assets/cash-register-new.webp','Kassenfund in Hunt: Showdown'));
      const map=$('.discovery-map img',card);
      if(map){const thumb=safeMedia(map.getAttribute('src'),map.alt);thumb.className='hnt-pd-map-thumbnail';media.append(thumb);}
      content.append(media);
    }else if($('.media-art > img',card)){
      const source=$('.media-art > img',card);
      const media=document.createElement('button');media.type='button';media.className='hnt-pd-media';
      media.setAttribute('aria-label','Bild im Medienviewer öffnen');
      const preview=safeMedia(source.getAttribute('src'),source.alt);
      const adapt=()=>{if(preview.naturalHeight && preview.naturalWidth/preview.naturalHeight>2.25)media.classList.add('is-wide');};
      preview.addEventListener('load',adapt,{once:true});
      media.append(preview);
      if(preview.complete)adapt();
      media.insertAdjacentHTML('beforeend','<span class="hnt-pd-media-hint">'+glyph('magnifying-glass')+' Bild ansehen</span>');
      media.addEventListener('click',()=>openMedia(card,'.media-art'));
      content.append(media);
    }else if($('.hnt-share-embed',card)){
      const embed=$('.hnt-share-embed',card).cloneNode(true);
      const openOriginal=$('.hnt-share-visit',embed);
      const source=card.__hntSharedOriginalCard;
      if(openOriginal){
        if(source?.isConnected){
          openOriginal.addEventListener('click',()=>source.querySelector('[data-comments-toggle]')?.click());
        }else openOriginal.remove();
      }
      content.append(embed);
    }else if($('.loadout-grid',card)){
      const title=document.createElement('span');title.className='hnt-pd-small-label';
      title.textContent='HUNTER LOADOUT / AUSRÜSTUNG';
      content.append(title);
      const copy=$('.loadout-grid',card).cloneNode(true);
      copy.classList.add('hnt-pd-loadout');
      content.append(copy);
    }
    // Locally created extras retain their interaction and appearance in discussions.
    const feeling=$('.hnt-feed-feeling',card);
    if(feeling)content.append(feeling.cloneNode(true));
    const poll=$('.hnt-feed-poll',card);
    if(poll)content.append(poll.cloneNode(true));
    const clip=$('.hnt-feed-clip',card);
    if(clip){
      const copy=clip.cloneNode(true);
      const source=$('video',clip),video=$('video',copy);
      if(source&&video)video.src=source.currentSrc||source.src;
      content.append(copy);
    }
    if(!content.childNodes.length){
      const p=document.createElement('p');
      p.className='hnt-pd-caption';
      p.textContent='Ein Beitrag aus dem Bayou.';
      content.append(p);
    }
    setCount();
  }
  function openMedia(card,selector){
    const target=$(selector,card);
    hide();
    if(target)setTimeout(()=>target.click(),260);
  }
  const commentId=line=>{
    if(!line.dataset.hntId)line.dataset.hntId='pd-'+(++index);
    return line.dataset.hntId;
  };
  function loadComments(){
    if(!active)return [];
    const local=$$('.comment-feed .comment-line',active).map(line=>({
      id:commentId(line), parentId:line.dataset.hntParentId||null,
      mention:line.dataset.hntMention||'',name:$('strong',line)?.textContent||'KrispieArmy',
      initials:'KA',avatar:document.body.dataset.nextViewerAvatar||null,time:'gerade eben',message:$('span',line)?.textContent||'',mine:true
    })).filter(x=>x.message.trim());
    return [...local,...(bridge()?.getComments(active.id)||[])];
  }
  const activePrefs=()=>{
    let prefs=threadPrefs.get(active.id);
    if(!prefs){prefs={open:new Set()};threadPrefs.set(active.id,prefs);}
    return prefs;
  };
  function makeComment(c,depth){
    const node=document.createElement('article');
    node.className='hnt-pd-comment'+(c.mine?' is-mine':'');
    node.dataset.commentId=c.id;
    node.dataset.depth=String(depth);
    const iconWrap=document.createElement('span');
    iconWrap.className='hnt-pd-comment-avatar';iconWrap.textContent=c.initials||'HN';
    if(c.avatar && typeof c.avatar==="string"){
      const photo=document.createElement("img");
      photo.src=c.avatar;photo.alt="";photo.loading="lazy";photo.decoding="async";
      photo.addEventListener("error",()=>{photo.remove();iconWrap.textContent=c.initials||"HN";},{once:true});
      iconWrap.replaceChildren(photo);
    }
    const main=document.createElement('div');main.className='hnt-pd-comment-main';
    const top=document.createElement('div');top.className='hnt-pd-comment-head';
    const author=document.createElement('strong');author.textContent=c.name;
    const age=document.createElement('small');age.textContent=c.time||'gerade eben';
    top.append(author,age);
    const msg=document.createElement('p');
    if(c.mention){const mention=document.createElement('b');mention.textContent='@'+c.mention+' ';msg.append(mention);}
    msg.append(document.createTextNode(c.message));
    const actions=document.createElement('div');actions.className='hnt-pd-comment-actions';
    const reply=document.createElement('button');reply.type='button';reply.dataset.reply=c.id;reply.textContent='Antworten';
    const like=document.createElement('button');like.type='button';like.className='hnt-pd-comment-like';
    like.dataset.commentLike=c.id;
    const base=c.mine?0:(2+c.id.split('').reduce((sum,ch)=>sum+ch.charCodeAt(0),0)%8);
    const liked=bridge()?.isLiked(active.id,c.id)===true;
    like.dataset.baseLikes=String(base);
    like.classList.toggle('is-liked',liked);
    like.setAttribute('aria-pressed',String(liked));
    like.setAttribute('aria-label',(liked?'Like entfernen: ':'Kommentar liken: ')+c.name);
    like.innerHTML=glyph('heart')+'<span>'+String(base+(liked?1:0))+'</span>';
    actions.append(reply,like);main.append(top,msg,actions);node.append(iconWrap,main);
    return node;
  }
  let models=new Map();
  function renderComments({keepScroll=false,focusId=null}={}){
    if(!active)return;
    const y=scroll.scrollTop;
    const entries=loadComments();
    models=new Map(entries.map(c=>[c.id,c]));
    const kids=new Map(),roots=[];
    for(const c of entries){
      if(c.parentId && models.has(c.parentId)){
        if(!kids.has(c.parentId))kids.set(c.parentId,[]);
        kids.get(c.parentId).push(c);
      }else roots.push(c);
    }
    const prefs=activePrefs();
    if(!prefs.seeded){
      if(roots[0]?.id)prefs.open.add(roots[0].id);
      prefs.seeded=true;
    }
    const fragment=document.createDocumentFragment();
    function branch(c,depth=0){
      const wrapper=document.createElement('div');wrapper.className='hnt-pd-thread';
      wrapper.append(makeComment(c,depth));
      const replies=kids.get(c.id)||[];
      if(replies.length){
        const toggle=document.createElement('button');
        toggle.type='button';toggle.className='hnt-pd-replies-toggle';toggle.dataset.threadToggle=c.id;
        const shown=prefs.open.has(c.id);
        toggle.setAttribute('aria-expanded',String(shown));
        toggle.textContent=replies.length+' '+(replies.length===1?'Antwort':'Antworten')+(shown?' ausblenden':' anzeigen');
        const group=document.createElement('div');group.className='hnt-pd-replies';group.hidden=!shown;
        replies.forEach(child=>group.append(branch(child,Math.min(depth+1,2))));
        wrapper.append(toggle,group);
      }
      return wrapper;
    }
    roots.forEach(c=>fragment.append(branch(c)));
    comments.replaceChildren(fragment);
    if(!entries.length){
      const empty=document.createElement('div');empty.className='hnt-pd-empty';
      empty.innerHTML=glyph('chat-circle-dots')+'<strong>Noch keine Kommentare</strong><span>Schreib den ersten Kommentar zu diesem Beitrag.</span>';
      comments.append(empty);
    }
    setCount();
    if(keepScroll)scroll.scrollTop=y;
    if(focusId){
      const found=$$('[data-comment-id]',comments).find(n=>n.dataset.commentId===focusId);
      if(found){found.classList.add('hnt-pd-fresh');found.scrollIntoView({behavior:'smooth',block:'nearest'});}
    }
  }
  function resetReply(){
    replyTo=null;
    $('#hntPdReply').hidden=true;
    textarea.placeholder='Schreib einen Kommentar ...';
  }
  function show(card,button){
    clearTimeout(closing);
    if(currentlyOpen())hide(true);
    active=card;opener=button;oldOverflow=document.body.style.overflow;
    buildPost(card);
    resetReply();
    textarea.value='';updateComposer();
    renderComments();
    modal.hidden=false;
    scroll.scrollTop=0;
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'post-comments'}}));
    document.body.style.overflow='hidden';
    button.setAttribute('aria-expanded','true');
    // Focus immediately: delayed focus would steal keystrokes from a quick composer click.
    $('#hntPdClose').focus({preventScroll:true});
    requestAnimationFrame(()=>modal.classList.add('is-open'));
  }
  function hide(immediate=false){
    if(modal.hidden)return;
    const previous=opener;
    modal.classList.remove('is-open');
    if(previous)previous.setAttribute('aria-expanded','false');
    document.body.style.overflow=oldOverflow;
    const finish=()=>{modal.hidden=true;active=null;opener=null;if(!immediate && previous?.isConnected)previous.focus({preventScroll:true});};
    if(immediate)finish();else closing=setTimeout(finish,260);
  }
  function updateComposer(){
    $('#hntPdChars').textContent=textarea.value.length+' / 280';
    send.disabled=!textarea.value.trim();
    textarea.style.height='';
    textarea.style.height=Math.min(104,Math.max(42,textarea.scrollHeight))+'px';
  }
  textarea.addEventListener('input',updateComposer);
  textarea.addEventListener('keydown',event=>{
    if(event.key==='Enter'&&!event.shiftKey&&!event.isComposing){event.preventDefault();if(!send.disabled)form.requestSubmit();}
  });
  $('#hntPdCancelReply').addEventListener('click',()=>{resetReply();textarea.focus({preventScroll:true});});
  $('#hntPdClose').addEventListener('click',()=>hide());
  modal.addEventListener('click',event=>{if(event.target===modal)hide();});
  feed.addEventListener('click',event=>{
    const button=event.target.closest('[data-comments-toggle]');
    if(!button)return;
    const card=button.closest('.feed-post');
    if(!card)return;
    event.preventDefault();
    event.stopPropagation(); // Prevent feed.js from opening the old inline comment section.
    show(card,button);
  },true);
  comments.addEventListener('click',event=>{
    const like=event.target.closest('[data-comment-like]');
    if(like && active){
      const liked=bridge()?.toggleLike(active.id,like.dataset.commentLike);
      like.classList.toggle('is-liked',liked===true);
      like.setAttribute('aria-pressed',String(liked===true));
      $('span',like).textContent=String((Number(like.dataset.baseLikes)||0)+(liked?1:0));
      return;
    }
    const reply=event.target.closest('[data-reply]');
    if(reply){
      replyTo=models.get(reply.dataset.reply);
      if(!replyTo)return;
      $('#hntPdReply').hidden=false;
      $('#hntPdReplyName').textContent='Antwort an '+replyTo.name;
      textarea.placeholder='Antworte '+replyTo.name+' ...';
      textarea.focus({preventScroll:true});
      return;
    }
    const toggle=event.target.closest('[data-thread-toggle]');
    if(toggle){
      const group=toggle.nextElementSibling;if(!group)return;
      group.hidden=!group.hidden;
      const id=toggle.dataset.threadToggle;
      if(group.hidden)activePrefs().open.delete(id);else activePrefs().open.add(id);
      toggle.setAttribute('aria-expanded',String(!group.hidden));
      toggle.textContent=group.children.length+' '+(group.children.length===1?'Antwort':'Antworten')+(group.hidden?' anzeigen':' ausblenden');
    }
  });
  form.addEventListener('submit',event=>{
    event.preventDefault();
    if(!active)return;
    const val=textarea.value.trim();if(!val)return;
    const feedForm=$('.post-comment-box .comment-form',active);
    const feedInput=$('input',feedForm);
    if(!feedForm||!feedInput)return;
    feedInput.value=val;
    feedForm.requestSubmit();
    const fresh=$('.comment-feed .comment-line',active);
    let freshId=null;
    if(fresh){
      freshId=commentId(fresh);
      if(replyTo){
        const chain=[];let ancestor=replyTo;
        while(ancestor?.parentId && chain.length<6){chain.push(ancestor.parentId);ancestor=models.get(ancestor.parentId);}
        const capped=chain.length>=2&&replyTo.parentId;
        fresh.dataset.hntParentId=capped?replyTo.parentId:replyTo.id;
        if(capped)fresh.dataset.hntMention=replyTo.name;
        const prefs=activePrefs();prefs.open.add(fresh.dataset.hntParentId);
        chain.forEach(id=>prefs.open.add(id));
      }
    }
    textarea.value='';resetReply();updateComposer();
    renderComments({keepScroll:true,focusId:freshId});
    textarea.focus({preventScroll:true});
  });
  $('#hntPdLike').addEventListener('click',()=>{
    if(!active)return;
    $('[data-like]',active)?.click();
    setCount();
  });
  $('#hntPdFocusComment').addEventListener('click',()=>textarea.focus({preventScroll:true}));
  $('#hntPdSave').addEventListener('click',()=>{
    if(!active)return;
    $('.post-save[data-save]',active)?.click();setCount();
  });
  $('#hntPdShare').addEventListener('click',async()=>{
    if(!active)return;
    const b=$('#hntPdShare span');
    const url='https://hnt.rocks/feed#'+encodeURIComponent(active.id);
    try{await navigator.clipboard.writeText(url);b.textContent='Link kopiert';}
    catch{b.textContent='Zum Teilen bereit';}
    setTimeout(()=>{if(b.isConnected)b.textContent='Teilen';},1800);
  });
  document.addEventListener('hnt:next-comment-reaction',event=>{
    if (active?.id === event.detail?.postId)renderComments({keepScroll:true});
  });
  document.addEventListener('hnt:next-comment-saved',event=>{
    if (!active || event.detail?.postId !== active.id) return;
    renderComments({keepScroll:true,focusId:event.detail.commentId || null});
    setCount();
    textarea.value='';
    resetReply();
    updateComposer();
  });
  // Viewer and inline comments share the same local data store.
  document.addEventListener('hnt:comment-like',event=>{
    if(!active||event.detail?.postId!==active.id)return;
    $$('[data-comment-like]',comments).filter(n=>n.dataset.commentLike===event.detail.commentId).forEach(button=>{
      const liked=event.detail.liked;
      button.classList.toggle('is-liked',liked);
      button.setAttribute('aria-pressed',String(liked));
      $('span',button).textContent=String((Number(button.dataset.baseLikes)||0)+(liked?1:0));
    });
  });
  document.addEventListener('keydown',event=>{
    if(!currentlyOpen())return;
    if(event.key==='Escape'){event.preventDefault();hide();return;}
    if(event.key!=='Tab')return;
    const tabbable=$$('button:not(:disabled):not([hidden]),textarea,a[href]',modal).filter(el=>el.getClientRects().length>0);
    if(!tabbable.length)return;
    const first=tabbable[0],last=tabbable.at(-1);
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  },true);
})();

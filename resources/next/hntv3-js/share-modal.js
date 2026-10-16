/* HNT.ROCKS — Share Dispatch modal (local theme prototype)
 * One consistent sharing flow for feed, post comments modal and Bayou Focus.
 * Shared posts reference the original visually; their preview is not a new media attachment.
 */
(() => {
 'use strict';
 const feed = document.getElementById('feedList');
 if (!feed) return;
 const $ = (s, root=document) => root.querySelector(s);
 const $$ = (s, root=document) => Array.from(root.querySelectorAll(s));
 const ico = n => '<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+n+'"></use></svg>';
 const currentSnapshots = new WeakMap();
 let active=null, trigger=null, lastBodyOverflow='', hideTimeout=0, submitted=0;
 let currentSource=null;
 const root=document.createElement('div');
 root.id='hntShareModal';
 root.className='hnt-share-overlay';
 root.hidden=true;
 root.setAttribute('role','dialog');
 root.setAttribute('aria-modal','true');
 root.setAttribute('aria-labelledby','hntShareTitle');
 root.innerHTML=[
   '<section class="hnt-share-panel">',
     '<header class="hnt-share-header">',
       '<div class="hnt-share-header-mark">'+ico('share-fat')+'</div>',
       '<div class="hnt-share-header-titles"><small>HNT.ROCKS / COMMUNITY</small><h2 id="hntShareTitle">Beitrag teilen</h2></div>',
       '<button type="button" class="hnt-share-close" id="hntShareClose" aria-label="Schließen" title="Schließen (Esc)">'+ico('x')+'</button>',
     '</header>',
     '<div class="hnt-share-body">',
       '<div class="hnt-share-target">',
         '<img src="/build/next/hntv3-assets/avatar-profile.webp" alt="" class="hnt-share-own-avatar">',
         '<div class="hnt-share-target-info"><strong>KrispieArmy</strong>',
           '<span>'+ico('share-fat')+' Im eigenen Feed teilen</span></div>',
         '<span class="hnt-share-target-badge">DEIN FEED</span>',
       '</div>',
       '<div class="hnt-share-editor">',
         '<textarea id="hntShareNote" maxlength="500" rows="3" placeholder="Sag etwas zu diesem Beitrag ..." aria-label="Begleittext (optional)"></textarea>',
         '<div class="hnt-share-editor-bottom"><span>'+ico('crosshair')+' DEIN KOMMENTAR</span><span id="hntShareLength">0 / 500</span></div>',
         '<div class="hnt-share-editor-meter"><i id="hntShareMeter"></i></div>',
       '</div>',
       '<div class="hnt-share-preview-title"><strong>ORIGINALBEITRAG</strong><span>Vorschau</span></div>',
       '<div id="hntShareOriginal" class="hnt-share-original"></div>',
     '</div>',
     '<footer class="hnt-share-footer">',
       '<button class="hnt-share-copy" type="button" id="hntShareCopy" title="Link zum Original kopieren">'+ico('share-fat')+' <span>Link kopieren</span></button>',
       '<div class="hnt-share-footer-actions">',
         '<button type="button" class="hnt-share-cancel" id="hntShareCancel">Abbrechen</button>',
         '<button type="button" class="hnt-share-submit" id="hntShareSubmit">'+ico('share-fat')+' <span class="hnt-share-submit-long">Im Feed teilen</span><span class="hnt-share-submit-short">Teilen</span></button>',
       '</div>',
     '</footer>',
   '</section>'
 ].join('');
 document.body.append(root);
 const note=$('#hntShareNote'), original=$('#hntShareOriginal'), copy=$('#hntShareCopy');
 const closeBtn=$('#hntShareClose'), submit=$('#hntShareSubmit');
 const previewImage=(src,alt='')=>{
   const img=document.createElement('img');img.src=src;img.alt=alt;img.loading='eager';return img;
 };
 const makeText=(tag,className,value)=>{
   const el=document.createElement(tag);el.className=className;el.textContent=value||'';return el;
 };
 const getSnap = card => {
   if(currentSnapshots.has(card))return currentSnapshots.get(card);
   const kind=card.dataset.postType||'text';
   const name=$('.post-author strong',card)?.textContent.trim()||'Hunter';
   const avatar=$('.post-avatar img',card)?.getAttribute('src')||'/build/next/hntv3-assets/avatar-hnt.webp';
   const when=$('.post-author>span',card)?.textContent.trim()||'Aus dem Bayou';
   const caption=$('.post-caption',card)?.textContent.trim()||'';
   const heading=kind==='discovery'?$('.discovery-story h2',card)?.textContent.trim()||'Neuer Kassenspot gefunden.':'';
   const location=kind==='discovery'?$('.discovery-story .location',card)?.textContent.trim()||'':'';
   const text=caption||$('.discovery-story p',card)?.textContent.trim()||'';
   const img=$('.media-art > img',card);
   const video=$('.media-art video',card);
   const image=kind==='discovery'?'/build/next/hntv3-assets/cash-register-new.webp':img?.getAttribute('src')||video?.getAttribute('poster')||'';
   const items=kind==='loadout'
     ? $$('.loadout-grid .loadout-slot',card).map(item=>({
         title:$('strong',item)?.textContent||'',
         img:$('img',item)?.getAttribute('src')||''
       }))
     : [];
   const snap={card,id:card.id,name,avatar,when,kind,heading,location,text,image,items,source:card};
   return snap;
 };
 function buildOriginal(snap,destination){
   destination.replaceChildren();
   const media=snap.image?document.createElement('div'):null;
   if(media){
     media.className='hnt-share-original-media'+(snap.kind==='discovery'?' is-discovery':'');
     media.append(previewImage(snap.image,'Vorschau des Originalbeitrags'));
     if(snap.kind==='discovery'){
       const stamp=makeText('span','hnt-share-media-stamp','KASSENSPOT');
       media.append(stamp);
     }
     destination.append(media);
   }
   if(snap.kind==='loadout' && snap.items.length){
     const items=document.createElement('div');items.className='hnt-share-original-loadout';
     snap.items.forEach(item=>{
       const slot=document.createElement('div');
       if(item.img)slot.append(previewImage(item.img,item.title));
       slot.append(makeText('span','',item.title));
       items.append(slot);
     });
     destination.append(items);
   }
   const body=document.createElement('div');body.className='hnt-share-original-body';
   const head=document.createElement('div');head.className='hnt-share-original-head';
   const av=previewImage(snap.avatar,'');av.className='hnt-share-author-avatar';head.append(av);
   const h=document.createElement('div');h.className='hnt-share-original-author';
   h.append(makeText('strong','',snap.name),makeText('small','',snap.when));
   head.append(h);
   const kind=makeText('span','hnt-share-kind',snap.kind==='discovery'?'FUNDORT':snap.kind==='loadout'?'LOADOUT':snap.kind==='media'?'CAPTURE':'BEITRAG');
   head.append(kind);body.append(head);
   if(snap.heading)body.append(makeText('strong','hnt-share-original-heading',snap.heading));
   if(snap.location)body.append(makeText('span','hnt-share-original-place',snap.location));
   if(snap.text)body.append(makeText('p','hnt-share-original-caption',snap.text));
   if(!snap.text&&!snap.heading)body.append(makeText('p','hnt-share-original-caption','Beitrag aus der HNT.ROCKS Community.'));
   const meta=document.createElement('div');meta.className='hnt-share-original-meta';
   const sourceFooter=makeText('span','','HNT.ROCKS · Community');
   const mark=makeText('span','','ORIGINAL');
   meta.append(sourceFooter,mark);body.append(meta);
   destination.append(body);
 }
 function updateNote(){
   const l=note.value.length;
   $('#hntShareLength').textContent=l+' / 500';
   $('#hntShareMeter').style.width=l/500*100+'%';
 }
 function show(card,launch){
   if(!card)return;
   clearTimeout(hideTimeout);
   active=card;trigger=launch;currentSource=getSnap(card);
   lastBodyOverflow=document.body.style.overflow;
   buildOriginal(currentSource,original);
   note.value='';updateNote();
   root.hidden=false;
   document.body.style.overflow='hidden';
   document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'share-composer'}}));
   note.focus({preventScroll:true});
   requestAnimationFrame(()=>root.classList.add('is-visible'));
 }
 function hide({skipFocus=false}={}){
   if(root.hidden)return;
   root.classList.remove('is-visible');
   document.body.style.overflow=lastBodyOverflow;
   const back=trigger;
   active=null;trigger=null;currentSource=null;
   hideTimeout=setTimeout(()=>{
     root.hidden=true;
     if(!skipFocus&&back?.isConnected)back.focus({preventScroll:true});
   },250);
 }
 function createShared(){
   if(!currentSource)return;
   const snap=currentSource,ownNote=note.value.trim();
   const card=document.createElement('article');
   card.className='feed-post hnt-shared-post';
   card.id='post-shared-'+Date.now()+'-'+(++submitted);
   card.dataset.following='1';card.dataset.time='0';card.dataset.likes='0';card.dataset.postType='shared';
   currentSnapshots.set(card,snap);
   card.__hntSharedOriginalCard=snap.source;
   const head=document.createElement('div');head.className='post-head';
   const av=document.createElement('span');av.className='post-avatar';av.append(previewImage('/build/next/hntv3-assets/avatar-profile.webp',''));
   const who=document.createElement('div');who.className='post-author';
   const title=document.createElement('div');title.className='author-line';title.append(makeText('strong','','KrispieArmy'));
   who.append(title,makeText('span','','Gerade eben · Beitrag geteilt'));
   head.append(av,who);
   card.append(head);
   if(ownNote)card.append(makeText('p','post-caption',ownNote));
   const embed=document.createElement('div');embed.className='hnt-share-embed';
   buildOriginal(snap,embed);
   const openButton=document.createElement('button');
   openButton.type='button';
   openButton.className='hnt-share-visit';
   openButton.innerHTML='Originalbeitrag ansehen '+ico('arrow-up-right');
   openButton.addEventListener('click',()=>{
     // Open the original post's existing comment modal, when it is still in the feed.
     const source=snap.source;
     if(source?.isConnected){
       source.querySelector('[data-comments-toggle]')?.click();
     }
   });
   embed.append(openButton);
   card.append(embed);
   const actions=document.createElement('div');actions.className='post-actions';
   actions.innerHTML='<div class="post-actions-primary">'+
     '<button class="post-action like-btn" type="button" data-like aria-pressed="false">'+ico('heart')+' <span class="like-count">0</span></button>'+
     '<button class="post-action" type="button" data-comments-toggle aria-expanded="false">'+ico('chat-circle-dots')+' <span class="comment-count">0</span></button>'+
     '<button class="post-action" type="button" data-share>'+ico('share-fat')+' <span>Teilen</span></button>'+
     '</div><button class="post-action post-save" type="button" data-save aria-pressed="false">'+ico('bookmark-simple')+' <span>Merken</span></button>';
   const commentBox=document.createElement('div');
   commentBox.className='post-comment-box';commentBox.hidden=true;
   commentBox.innerHTML='<div class="comment-preview">Kommentiere diesen geteilten Beitrag.</div>'+
     '<form class="comment-form"><input required maxlength="280" aria-label="Kommentar schreiben" placeholder="Dein Kommentar ...">'+
     '<button type="submit" aria-label="Kommentar senden">'+ico('paper-plane-tilt')+'</button></form><div class="comment-feed" aria-live="polite"></div>';
   card.append(actions,commentBox);
   feed.prepend(card);
   hide({skipFocus:true});
   card.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion:reduce)').matches?'instant':'smooth',block:'start'});
   const message=document.createElement('span');
   message.className='hnt-share-success';message.textContent='Beitrag im eigenen Feed geteilt';
   document.body.append(message);
   setTimeout(()=>message.remove(),2400);
 }
 function linkFor(snap){return 'https://hnt.rocks/feed#'+encodeURIComponent(snap.id);}
 async function copyLink(){
   if(!currentSource)return;
   try{
     await navigator.clipboard.writeText(linkFor(currentSource));
     const label=$('span',copy);label.textContent='Link kopiert';
     copy.classList.add('is-copied');
     setTimeout(()=>{if(label.isConnected)label.textContent='Link kopieren';copy.classList.remove('is-copied');},1600);
   }catch{
     const label=$('span',copy);label.textContent='Kopieren nicht möglich';
     setTimeout(()=>{if(label.isConnected)label.textContent='Link kopieren';},1700);
   }
 }
 function intercept(event){
   const clicked=event.target.closest('[data-share],#hntPdShare,#hntMvShare');
   if(!clicked || !clicked.isConnected)return;
   let card=null,fromModal='';
   if(clicked.id==='hntPdShare'){
     // The post modal marks its source comment button as expanded.
     const button=$$('.feed-post [data-comments-toggle]',feed).find(b=>b.getAttribute('aria-expanded')==='true');
     card=button?.closest('.feed-post')||null;
     fromModal='post';
   }else if(clicked.id==='hntMvShare'){
     const who=$('#hntMediaViewer')?.querySelector('#hntMvTitle');
     // Media viewer's current entry is exposed below through the last clicked feed card.
     card=window.__hntShareMediaCard||null;
     fromModal='media';
   }else card=clicked.closest('.feed-post');
   if(!card)return;
   event.preventDefault();event.stopImmediatePropagation();
   if(fromModal==='post'){
     $('#hntPdClose')?.click();
     setTimeout(()=>show(card,clicked),300);
   }else if(fromModal==='media'){
     $('#hntMvClose')?.click();
     setTimeout(()=>show(card,clicked),300);
   }else{
     const m=$('.post-more-menu',card);
     if(m)m.hidden=true;
     show(card,clicked);
   }
 }
 // Remember the media-click origin without changing the viewer's own implementation.
 feed.addEventListener('click',event=>{
   const art=event.target.closest('.media-art, [data-post-media]');
   if(art)window.__hntShareMediaCard=art.closest('.feed-post');
 },true);
 document.addEventListener('click',intercept,true);
 $('#hntShareClose').addEventListener('click',()=>hide());
 $('#hntShareCancel').addEventListener('click',()=>hide());
 $('#hntShareSubmit').addEventListener('click',createShared);
 $('#hntShareCopy').addEventListener('click',copyLink);
 note.addEventListener('input',updateNote);
 root.addEventListener('click',e=>{if(e.target===root)hide();});
 document.addEventListener('keydown',event=>{
   if(root.hidden)return;
   if(event.key==='Escape'){
     event.preventDefault();event.stopImmediatePropagation();hide();return;
   }
   if(event.key!=='Tab')return;
   const visible=$$('button:not(:disabled),textarea',root).filter(el=>el.getClientRects().length);
   if(!visible.length)return;
   if(event.shiftKey&&document.activeElement===visible[0]){event.preventDefault();visible.at(-1).focus();}
   else if(!event.shiftKey&&document.activeElement===visible.at(-1)){event.preventDefault();visible[0].focus();}
 },true);
 window.HNTShareModal={open:show,close:hide};
})();

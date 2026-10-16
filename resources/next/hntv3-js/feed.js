// Local-only HNT.ROCKS feed prototype. Never posts or changes remote account state.
(()=>{
 'use strict';
 const $$=(q,root=document)=>[...root.querySelectorAll(q)];
 const $=(q,root=document)=>root.querySelector(q);
 const sprite=name=>`<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-${name}"></use></svg>`;
 let toastTimer;
 function toast(text){const box=$('#feedToast');box.textContent=text;box.hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>box.hidden=true,2700)}
 const list=$('#feedList');
 function applyFilter(key){
  $$('.feed-filter').forEach(b=>{const on=b.dataset.filter===key;b.classList.toggle('is-selected',on);b.setAttribute('aria-pressed',String(on))});
  const posts=$$('.feed-post',list);
  if(key==='latest')posts.sort((a,b)=>Number(a.dataset.time)-Number(b.dataset.time));
  else if(key==='popular')posts.sort((a,b)=>Number(b.dataset.likes)-Number(a.dataset.likes));
  else posts.sort((a,b)=>Number(a.dataset.time)-Number(b.dataset.time));
  posts.forEach(p=>{list.append(p);p.hidden=key==='following'&&p.dataset.following!=='1'});
  $('#feedEmpty').hidden=posts.some(p=>!p.hidden);
 }
 $$('.feed-filter').forEach(button=>button.addEventListener('click',()=>applyFilter(button.dataset.filter)));
 $('#resetFeed').addEventListener('click',()=>applyFilter('all'));
 list.addEventListener('click',async event=>{
  const button=event.target.closest('button');if(!button)return;
  const card=button.closest('.feed-post');if(!card)return;
  if(button.hasAttribute('data-like')){
   const active=button.classList.toggle('is-liked');button.setAttribute('aria-pressed',String(active));
   $('.like-count',button).textContent=Number(card.dataset.likes)+(active?1:0);
  } else if(button.hasAttribute('data-comments-toggle')){
   const box=$('.post-comment-box',card);box.hidden=!box.hidden;button.setAttribute('aria-expanded',String(!box.hidden));if(!box.hidden)box.querySelector('input')?.focus();
  } else if(button.hasAttribute('data-save')){
   const saved=card.classList.toggle('user-saved');$$('[data-save]',card).forEach(b=>{b.classList.toggle('is-saved',saved);b.setAttribute('aria-pressed',String(saved));if(b.querySelector('span'))b.querySelector('span').textContent=saved?'Gemerkt':'Merken'});toast(saved?'Beitrag gemerkt':'Beitrag aus Merkliste entfernt');$('.post-more-menu',card).hidden=true;
  } else if(button.hasAttribute('data-share')){
   const url=location.href.split('#')[0]+'#'+card.id;
   try{await navigator.clipboard.writeText(url);toast('Beitragslink kopiert')}catch{toast('Beitrags-ID: '+card.id)}
   $('.post-more-menu',card).hidden=true;
  } else if(button.hasAttribute('data-more')){
   const options=$('.post-more-menu',card);$$('.post-more-menu').forEach(m=>{if(m!==options)m.hidden=true});options.hidden=!options.hidden;button.setAttribute('aria-expanded',String(!options.hidden));
  }
 });
 list.addEventListener('submit',e=>{
  if(!e.target.classList.contains('comment-form'))return;
  e.preventDefault();const card=e.target.closest('.feed-post');const input=$('input',e.target);const val=input.value.trim();if(!val)return;
  const line=document.createElement('div');line.className='comment-line';const name=document.createElement('strong');name.textContent='KrispieArmy';const msg=document.createElement('span');msg.textContent=val;line.append(name,msg);$('.comment-feed',card).prepend(line);input.value='';const count=$('.comment-count',card);count.textContent=Number(count.textContent)+1;toast('Kommentar hinzugefügt');
 });
 document.addEventListener('click',e=>{if(!e.target.closest('.post-more-btn,.post-more-menu'))$$('.post-more-menu').forEach(m=>m.hidden=true)});
 const overlay=$('#composerOverlay'),body=$('#postContent'),form=$('#newPostForm');let attachedImage=null;
 const close=()=>{overlay.hidden=true;document.body.style.removeProperty('overflow');};
 const open=()=>{document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'composer'}}));overlay.hidden=false;document.body.style.overflow='hidden';body.focus()};
 $('#composerStart').addEventListener('click',open);$('#headerPostBtn').addEventListener('click',open);
 $('#composerMediaBtn').addEventListener('click',open);$('#composerMomentBtn').addEventListener('click',()=>toast('Moments öffnen: https://hnt.rocks/moments'));
 $('#composerClose').addEventListener('click',close);
 overlay.addEventListener('click',e=>{if(e.target===overlay)close()});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&!overlay.hidden)close()});
 $('#composerImage').addEventListener('change',e=>{
  const file=e.target.files[0];if(!file)return;
  if(!file.type.startsWith('image/')){toast('Bitte eine Bilddatei auswählen');return}
  const reader=new FileReader();reader.onload=()=>{attachedImage=reader.result;$('#composerPreviewImage').src=attachedImage;$('#composerPreview').hidden=false};reader.readAsDataURL(file);
 });
 $('#removePreview').addEventListener('click',()=>{attachedImage=null;$('#composerPreview').hidden=true;$('#composerImage').value=''});
 form.addEventListener('submit',e=>{
  e.preventDefault();const text=body.value.trim();
  const extraCheck={hasContent:false,valid:true,message:''};
  document.dispatchEvent(new CustomEvent('hnt:composer:validate',{detail:extraCheck}));
  if(!extraCheck.valid){toast(extraCheck.message||'Bitte Eingaben prüfen');return}
  if(text.length<3&&!extraCheck.hasContent){toast('Bitte einen längeren Text eingeben');return}
  const card=document.createElement('article');card.className='feed-post';card.id='post-demo-'+Date.now();card.dataset.following='1';card.dataset.time='0';card.dataset.likes='0';
  const head=document.createElement('div');head.className='post-head';head.innerHTML='<span class="post-avatar"><img src="/build/next/hntv3-assets/avatar-profile.webp" alt=""></span><div class="post-author"><div class="author-line"><strong>KrispieArmy</strong></div><span>Gerade eben · Lokal erstellt</span></div>';
  const caption=document.createElement('p');caption.className='post-caption';caption.textContent=text;card.append(head,caption);
  if(attachedImage){const art=document.createElement('div');art.className='media-art';const img=document.createElement('img');img.alt='Dein beigefügtes Bild';img.src=attachedImage;art.append(img);card.append(art)}
  document.dispatchEvent(new CustomEvent('hnt:composer:render',{detail:{card,caption,text}}));
  const actions=document.createElement('div');actions.className='post-actions';actions.innerHTML=`<div class="post-actions-primary"><button class="post-action like-btn" type="button" data-like aria-pressed="false">${sprite('heart')} <span class="like-count">0</span></button><button class="post-action" type="button" data-comments-toggle aria-expanded="false">${sprite('chat-circle-dots')} <span class="comment-count">0</span></button><button class="post-action" type="button" data-share>${sprite('share-fat')} Teilen</button></div><button class="post-action post-save" type="button" data-save aria-pressed="false">${sprite('bookmark-simple')} <span>Merken</span></button>`;
  const comments=document.createElement('div');comments.className='post-comment-box';comments.hidden=true;comments.innerHTML=`<div class="comment-preview">Schreib einen Kommentar zum Beitrag.</div><form class="comment-form"><input placeholder="Dein Kommentar …" required maxlength="280" aria-label="Kommentar schreiben"><button type="submit" aria-label="Kommentar senden">${sprite('paper-plane-tilt')}</button></form><div class="comment-feed" aria-live="polite"></div>`;
  card.append(actions,comments);list.prepend(card);document.dispatchEvent(new CustomEvent('hnt:composer:posted',{detail:{card}}));form.reset();attachedImage=null;$('#composerPreview').hidden=true;close();applyFilter('all');window.scrollTo({top:0,behavior:'smooth'});toast('Beitrag lokal erstellt');
 });
})();

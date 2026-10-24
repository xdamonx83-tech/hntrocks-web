(()=>{
'use strict';
const $=(selector,root=document)=>root.querySelector(selector);
const page=$('.hnt-news-page');
if(page){
 const cards=[...page.querySelectorAll('[data-article]')];
 const filters=[...page.querySelectorAll('[data-filter]')];
 const tags=[...page.querySelectorAll('[data-tag]')];
 const search=$('#newsSearch',page);
 const label=$('#newsFilterLabel',page),active=$('#newsActiveFilters',page),empty=$('#newsEmpty',page);
 const count=$('#newsShownCount',page);
 const url=new URL(location.href);
 let category=url.searchParams.get('category')||'all';
 let tag=url.searchParams.get('tag')||'';
 let query=url.searchParams.get('q')||'';
 if(search)search.value=query;
 const update=(push)=>{
  let visible=0;
  for(const card of cards){
   const categoryOk=category==='all'||card.dataset.category===category;
   const tagOk=!tag||(card.dataset.tags||'').split('|').includes(tag);
   const content=(card.dataset.title||'')+' '+(card.querySelector('p')?.textContent||'');
   const textOk=!query||content.toLocaleLowerCase().includes(query.toLocaleLowerCase());
   const show=categoryOk&&tagOk&&textOk;
   card.hidden=!show;
   if(show)visible++;
  }
  filters.forEach(button=>{
   const selected=button.dataset.filter===category;
   button.classList.toggle('is-active',selected);
   button.setAttribute('aria-pressed',String(selected));
  });
  tags.forEach(button=>button.classList.toggle('is-active',button.dataset.tag===tag));
  if(count)count.textContent=visible;
  if(empty)empty.hidden=visible>0;
  const parts=[];
  if(category!=='all')parts.push(category);
  if(tag)parts.push('#'+tag);
  if(query)parts.push(query);
  if(active)active.hidden=!parts.length;
  if(label)label.textContent=parts.join(' · ');
  if(push){
   const next=new URL(location.href);
   for(const name of ['category','tag','q'])next.searchParams.delete(name);
   if(category!=='all')next.searchParams.set('category',category);
   if(tag)next.searchParams.set('tag',tag);
   if(query)next.searchParams.set('q',query);
   history.replaceState(null,'',next.pathname+next.search+next.hash);
  }
 };
 filters.forEach(button=>button.addEventListener('click',()=>{
  category=button.dataset.filter;tag='';update(true);
 }));
 tags.forEach(button=>button.addEventListener('click',()=>{tag=tag===button.dataset.tag?'':button.dataset.tag;update(true);document.getElementById('newsArchive')?.scrollIntoView({behavior:'smooth'});}));
 search?.addEventListener('input',()=>{query=search.value.trim();update(true)});
 const reset=()=>{category='all';tag='';query='';if(search)search.value='';update(true)};
 $('#newsClearFilters',page)?.addEventListener('click',reset);
 $('#newsEmptyReset',page)?.addEventListener('click',reset);
 update(false);
}
const detail=$('.nd-page');
if(detail){
 const q=sel=>detail.querySelector(sel);
 const lang=document.documentElement.lang||'de';
 const de=lang==='de';
 const dict={
  copied:{de:'Link kopiert',en:'Link copied',es:'Enlace copiado',ru:'Ссылка скопирована'},
  high:{de:'Hoher Kontrast',en:'High contrast',es:'Alto contraste',ru:'Высокая контрастность'},
  focus:{de:'Lesefokus',en:'Reading focus',es:'Modo enfoque',ru:'Режим фокуса'},
  on:{de:'aktiviert',en:'enabled',es:'activado',ru:'включён'},
  off:{de:'deaktiviert',en:'disabled',es:'desactivado',ru:'выключен'},
  noComments:{de:'Noch keine Kommentare. Beginne die Diskussion.',en:'No comments yet. Start the discussion.',es:'Todavía no hay comentarios.',ru:'Пока нет комментариев.'},
  error:{de:'Kommentare konnten nicht geladen werden.',en:'Comments could not be loaded.',es:'No se pudieron cargar comentarios.',ru:'Не удалось загрузить комментарии.'},
  publishError:{de:'Kommentar konnte nicht veröffentlicht werden.',en:'Could not publish comment.',es:'No se pudo publicar el comentario.',ru:'Не удалось опубликовать комментарий.'},
  liked:{de:'Gefällt dir',en:'Liked',es:'Te gusta',ru:'Нравится'},
  like:{de:'Gefällt mir',en:'Like',es:'Me gusta',ru:'Нравится'},
 };
 const t=name=>dict[name]?.[lang]||dict[name]?.en||name;
 const toast=message=>{
  let el=q('#hntNewsToast');
  if(!el){el=document.createElement('div');el.id='hntNewsToast';el.className='nd-toast';el.setAttribute('role','status');el.setAttribute('aria-live','polite');detail.append(el)}
  el.textContent=message;el.classList.add('is-visible');
  clearTimeout(toast.timer);toast.timer=setTimeout(()=>el.classList.remove('is-visible'),2500);
 };
 const readKey='hnt-news-reading-prefs-v1';
 let prefs={font:1,contrast:false};
 try{const old=JSON.parse(localStorage.getItem(readKey)||'null');if(old&&typeof old==='object')prefs={...prefs,...old}}catch{}
 let font=Math.min(1.5,Math.max(.9,Number(prefs.font)||1));
 let contrast=!!prefs.contrast,focus=false;
 const savePrefs=()=>{try{localStorage.setItem(readKey,JSON.stringify({font,contrast}))}catch{}};
 const setFont=(val,announce=false)=>{
  font=Math.round(Math.max(.9,Math.min(1.5,Number(val)||1))*10)/10;
  detail.style.setProperty('--nd-font-scale',String(font));
  for(const button of detail.querySelectorAll('.nd-font-controls button')){
   button.classList.toggle('is-active',button.id==='ndFontReset'&&font===1);
  }
  q('#ndFontDown')?.toggleAttribute('disabled',font<=.9);
  q('#ndFontUp')?.toggleAttribute('disabled',font>=1.5);
  savePrefs();
  if(announce)toast((de?'Schriftgröße: ':'Text size: ')+Math.round(font*100)+' %');
  requestAnimationFrame(updateProgress);
 };
 const setContrast=(value,announce=false)=>{
  contrast=!!value;
  detail.classList.toggle('is-high-contrast',contrast);
  q('#ndContrast')?.setAttribute('aria-pressed',String(contrast));
  savePrefs();
  if(announce)toast(t('high')+' '+t(contrast?'on':'off'));
 };
 const setFocus=(value)=>{
  focus=!!value;
  detail.classList.toggle('is-focus',focus);
  q('#ndFocus')?.setAttribute('aria-pressed',String(focus));
  if(q('#ndFocusExit'))q('#ndFocusExit').hidden=!focus;
  toast(t('focus')+' '+t(focus?'on':'off'));
  requestAnimationFrame(updateProgress);
 };
 q('#ndFontDown')?.addEventListener('click',()=>setFont(font-.1,true));
 q('#ndFontReset')?.addEventListener('click',()=>setFont(1,true));
 q('#ndFontUp')?.addEventListener('click',()=>setFont(font+.1,true));
 q('#ndContrast')?.addEventListener('click',()=>setContrast(!contrast,true));
 q('#ndFocus')?.addEventListener('click',()=>setFocus(!focus));
 q('#ndFocusExit')?.addEventListener('click',()=>setFocus(false));
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&focus)setFocus(false)});
 const bar=q('#ndProgressFill'),percent=q('#ndPercent'),sideProgress=q('#ndSidebarProgress'),ring=q('#ndReadingRing'),story=q('#ndStory');
 const headings=[...detail.querySelectorAll('#ndArticleBody h2[id],#ndArticleBody h3[id]')];
 function updateProgress(){
  const body=q('#ndArticleBody');
  const end=(story||body)?.getBoundingClientRect().bottom+scrollY||document.documentElement.scrollHeight;
  const start=(story||body)?.getBoundingClientRect().top+scrollY||0;
  const passed=scrollY+innerHeight*.52-start;
  const value=Math.max(0,Math.min(100,Math.round(100*passed/Math.max(1,end-start))));
  if(bar)bar.style.width=value+'%';
  if(percent)percent.textContent=value+'%';
  if(sideProgress)sideProgress.style.width=value+'%';
  if(ring)ring.style.setProperty('--nd-progress',String(value));
  let current=-1;headings.forEach((heading,index)=>{if(heading.getBoundingClientRect().top<innerHeight*.38)current=index});
  detail.querySelectorAll('.nd-toc-link').forEach((link,index)=>{const active=index===current;link.classList.toggle('is-current',active);if(active)link.setAttribute('aria-current','location');else link.removeAttribute('aria-current')});
 }
 addEventListener('scroll',updateProgress,{passive:true});
 addEventListener('resize',updateProgress,{passive:true});
 setFont(font);setContrast(contrast);
 updateProgress();
 const share=async()=>{
  const uri=document.querySelector('link[rel=canonical]')?.href||location.href;
  try{
   if(navigator.share){await navigator.share({title:document.title,url:uri});return}
   await navigator.clipboard.writeText(uri);toast(t('copied'));
  }catch(e){if(e.name!=='AbortError')toast(uri)}
 };
 q('#ndShare')?.addEventListener('click',share);
 q('#ndShareBottom')?.addEventListener('click',share);
 const id=detail.dataset.newsArticleId;
 const list=q('#newsCommentList'),sort=q('#ndCommentSort');
 const commentCount=q('#ndCommentCount'),discussionCount=q('#ndCommentListCount');
 let comments=[];
 const countDisplay=n=>{if(commentCount)commentCount.textContent='('+n+')';if(discussionCount)discussionCount.textContent=n};
 const renderComments=()=>{
  if(!list)return;
  list.replaceChildren();
  const visible=[...comments].sort((a,b)=>{
   const timeA=Date.parse(a.created_at||'')||0,timeB=Date.parse(b.created_at||'')||0;
   return sort?.value==='oldest' ? timeA-timeB : timeB-timeA;
  });
  if(!visible.length){
   const blank=document.createElement('div');blank.className='nd-comments-empty';
   blank.textContent=t('noComments');list.append(blank);return;
  }
  for(const comment of visible){
   const article=document.createElement('article');article.className='nd-user-comment hnt-news-reader-comment';
   if(comment.parent_id)article.classList.add('is-reply');
   const avatar=document.createElement('img');avatar.className='nd-user-avatar';
   avatar.alt='';avatar.loading='lazy';
   avatar.src=comment.user?.avatar_url||'/build/next/hntv3-assets/avatar-hnt.webp';
   const body=document.createElement('div');body.className='nd-user-comment-body';
   const heading=document.createElement('div');heading.className='nd-user-comment-meta';
   const name=document.createElement('strong');name.textContent=comment.user?.name||'Hunter';
   const time=document.createElement('time');const date=new Date(comment.created_at||'');
   if(!Number.isNaN(date.getTime())){time.dateTime=comment.created_at;time.textContent=new Intl.DateTimeFormat(lang,{dateStyle:'medium'}).format(date)}
   heading.append(name,time);
   const paragraph=document.createElement('p');paragraph.textContent=comment.body||'';
   body.append(heading,paragraph);article.append(avatar,body);list.append(article);
  }
 };
 sort?.addEventListener('change',renderComments);
 if(id&&list){
  fetch('/api/v1/news/articles/'+encodeURIComponent(id)+'/engagement',{headers:{'Accept':'application/json'},credentials:'same-origin'})
  .then(async response=>{if(!response.ok)throw Error('HTTP '+response.status);return response.json()})
  .then(json=>{
   comments=Array.isArray(json?.data?.comments)?json.data.comments:[];
   countDisplay(Number(json?.data?.comments_count)||comments.length);
   const likes=Number(json?.data?.likes_count)||0;
   const count=q('#newsBottomLikeCount');
   if(count&&likes>0){count.textContent=String(likes);count.hidden=false}
   renderComments();
  }).catch(()=>{if(list)list.textContent=t('error')});
 }
 const authenticated=detail.dataset.newsAuth==='1';
 if(!authenticated||!id)return;
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 if(!csrf)return;
 const likeButtons=[q('#newsLike'),q('#newsLikeBottom')].filter(Boolean),save=q('#newsSave');
 const bottomLabel=q('#newsBottomLikeLabel'),likeCount=q('#newsBottomLikeCount');
 let currentLikes=0;
 const request=async(path,method='GET',body)=>{
  const response=await fetch('/news/'+encodeURIComponent(id)+path,{
   method,credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
   ...(body?{body:JSON.stringify(body)}:{})
  });
  if(!response.ok)throw Error('HTTP '+response.status);
  return response.json();
 };
 const mark=(nodes,enabled)=>nodes.filter(Boolean).forEach(button=>{button.classList.toggle('is-active',!!enabled);button.setAttribute('aria-pressed',String(!!enabled))});
 const updateLike=(enabled,count)=>{
  mark(likeButtons,enabled);if(bottomLabel)bottomLabel.textContent=t(enabled?'liked':'like');
  if(Number.isFinite(Number(count))){
   currentLikes=Number(count);if(likeCount){likeCount.hidden=currentLikes<=0;likeCount.textContent=String(currentLikes)}
  }
 };
 request('/engagement/viewer').then(r=>{updateLike(r?.data?.liked);mark([save],r?.data?.saved)}).catch(()=>{});
 for(const button of likeButtons)button.addEventListener('click',async()=>{
  likeButtons.forEach(b=>b.disabled=true);
  try{const res=await request('/like','POST');updateLike(res?.data?.liked,res?.data?.likes_count)}
  catch{toast('Like konnte nicht gespeichert werden.')}finally{likeButtons.forEach(b=>b.disabled=false)}
 });
 save?.addEventListener('click',async()=>{
  save.disabled=true;
  try{const res=await request('/save','POST');mark([save],res?.data?.saved)}
  catch{toast('Merken fehlgeschlagen.')}finally{save.disabled=false}
 });
 const form=q('#newsCommentForm'),input=q('#newsCommentText'),count=q('#ndCharCount'),error=q('#newsCommentError');
 input?.addEventListener('input',()=>{if(count)count.textContent=input.value.length});
 form?.addEventListener('submit',async event=>{
  event.preventDefault();const value=input?.value.trim();
  if(!value||value.length<2)return;
  const button=form.querySelector('button[type=submit]');if(button)button.disabled=true;
  if(error){error.hidden=true;error.textContent=''}
  try{
   const res=await request('/comments','POST',{body:value});
   if(res?.data?.comment)comments.unshift(res.data.comment);
   countDisplay(Number(res?.data?.comments_count)||comments.length);
   input.value='';if(count)count.textContent='0';renderComments();
  }catch{
   if(error){error.hidden=false;error.textContent=t('publishError')}
  }finally{if(button)button.disabled=false}
 });
}
})();

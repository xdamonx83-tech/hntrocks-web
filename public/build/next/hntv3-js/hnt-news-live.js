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
 const bar=$('#ndProgressFill',detail);
 const percent=$('#ndPercent',detail);
 const sidebar=$('#ndSidebarProgress',detail);
 const ring=$('#ndReadingRing',detail);
 const sync=()=>{
  const scrollable=Math.max(1,document.documentElement.scrollHeight-innerHeight);
  const value=Math.max(0,Math.min(100,Math.round((scrollY/scrollable)*100)));
  if(bar)bar.style.width=value+'%';
  if(percent)percent.textContent=value+'%';
  if(sidebar)sidebar.style.width=value+'%';
  if(ring)ring.style.setProperty('--nd-progress',String(value));
 };
 addEventListener('scroll',sync,{passive:true});
 addEventListener('resize',sync);
 sync();
 $('#ndShare',detail)?.addEventListener('click',async()=>{
  const uri=document.querySelector('link[rel=canonical]')?.href||location.href;
  try{
   if(navigator.share){await navigator.share({url:uri});return;}
   await navigator.clipboard.writeText(uri);
   const button=$('#ndShare',detail);if(button)button.title='Link kopiert';
  }catch{}
 });
 const id=detail.dataset.newsArticleId;
 const list=$('#newsCommentList',detail);
 if(id&&list){
  fetch('/api/v1/news/articles/'+encodeURIComponent(id)+'/engagement',{credentials:'same-origin',headers:{'Accept':'application/json'}})
  .then(async response=>{if(!response.ok)throw Error('Comments unavailable');return response.json()})
  .then(json=>{
   list.replaceChildren();
   const comments=json?.data?.comments||[];
   if(!Array.isArray(comments)||!comments.length){const el=document.createElement('p');el.textContent='Noch keine Kommentare.';list.append(el);return;}
   for(const c of comments.slice(0,30)){
    const el=document.createElement('article');el.className='hnt-news-reader-comment';
    const name=document.createElement('strong');name.textContent=c.user?.name||'Hunter';
    const time=document.createElement('time');time.textContent=c.created_at?new Date(c.created_at).toLocaleDateString(document.documentElement.lang||'de'):'';
    const text=document.createElement('p');text.textContent=c.body||'';
    el.append(name,time,text);list.append(el);
   }
  }).catch(()=>{list.textContent='Kommentare konnten nicht geladen werden.';});
 }
}
})();

/* Use the existing Laravel NewsEngagementController through authenticated web routes. */
(()=>{
'use strict';
const root=document.querySelector('.nd-page[data-news-auth="1"]');if(!root)return;
const id=root.dataset.newsArticleId;
const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
if(!id||!csrf)return;
const like=root.querySelector('#newsLike'),save=root.querySelector('#newsSave');
const form=root.querySelector('#newsCommentForm'),error=root.querySelector('#newsCommentError');
const request=async(path,method='GET',body)=>{
 const response=await fetch('/news/'+encodeURIComponent(id)+path,{
  method,credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf},
  ...(body?{body:JSON.stringify(body)}:{})
 });
 if(!response.ok)throw Error('HTTP '+response.status);
 return response.json();
};
const mark=(button,active)=>{
 if(!button)return;
 button.setAttribute('aria-pressed',String(!!active));
 button.classList.toggle('is-active',!!active);
};
request('/engagement/viewer').then(v=>{mark(like,v?.data?.liked);mark(save,v?.data?.saved)}).catch(()=>{});
like?.addEventListener('click',async()=>{
 like.disabled=true;
 try{const response=await request('/like','POST');mark(like,response?.data?.liked)}catch{like.title='Aktion konnte nicht gespeichert werden'}finally{like.disabled=false}
});
save?.addEventListener('click',async()=>{
 save.disabled=true;
 try{const response=await request('/save','POST');mark(save,response?.data?.saved)}catch{save.title='Aktion konnte nicht gespeichert werden'}finally{save.disabled=false}
});
form?.addEventListener('submit',async event=>{
 event.preventDefault();
 const value=form.querySelector('textarea')?.value.trim();
 if(!value)return;
 const button=form.querySelector('button[type=submit]');if(button)button.disabled=true;
 if(error)error.textContent='';
 try{await request('/comments','POST',{body:value});location.reload()}
 catch(err){if(error)error.textContent='Kommentar konnte nicht veröffentlicht werden.';if(button)button.disabled=false}
});
})();

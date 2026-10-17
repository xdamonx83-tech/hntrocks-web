/* HNTv3: server-confirmed poll votes, never client-side fabricated totals. */
(() => {
 'use strict';
 if(!document.body.hasAttribute('data-hnt-next-live-posts'))return;
 const feed=document.getElementById('feedList');
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
 if(!feed||!csrf)return;
 const pending=new Set();
 function notice(message,error){
  if(window.HNTNextFeedWrites?.notice)window.HNTNextFeedWrites.notice(message,error);
  else console.warn(message);
 }
 document.addEventListener('click',async event=>{
  const button=event.target.closest('#feedList .hnt-live-poll [data-live-poll-option]');
  if(!button)return;
  event.preventDefault();event.stopPropagation();event.stopImmediatePropagation();
  if(button.disabled)return;
  const card=button.closest('.feed-post[data-next-feed-post]');
  const id=card?.dataset.nextFeedPost,option=button.dataset.livePollOption;
  if(!id||!/^\d+$/.test(id)||!option||pending.has(id))return;
  pending.add(id);
  const poll=button.closest('.hnt-live-poll');
  poll?.classList.add('is-voting');
  let saved=false;
  try {
   const response=await fetch('/feed/'+encodeURIComponent(id)+'/poll/vote',{
    method:'POST',credentials:'same-origin',
    headers:{Accept:'application/json','Content-Type':'application/json','X-CSRF-TOKEN':csrf,'X-Requested-With':'XMLHttpRequest'},
    body:JSON.stringify({poll_option_id:Number(option)})
   });
   let result={};
   try{result=await response.json()}catch{}
   if(!response.ok||!result?.ok)throw new Error(result?.message||'Abstimmung fehlgeschlagen.');
   saved=true;
   const updated=window.HNTNextFeedWrites?.loadCard?
    await window.HNTNextFeedWrites.loadCard(id):
    await (async()=>{
      const get=await fetch('/next/feed/posts/'+encodeURIComponent(id)+'/card',{credentials:'same-origin',headers:{Accept:'text/html'}});
      if(!get.ok)throw Error('Ergebnisse nicht abrufbar.');
      const div=document.createElement('template');div.innerHTML=await get.text();
      return div.content.querySelector('.feed-post[data-next-feed-post]');
    })();
   if(!updated)throw Error('Ergebnisse nicht abrufbar.');
   card.replaceWith(updated);
   notice('Deine Stimme wurde gezählt.');
  }catch(error){
   notice(saved?'Stimme gespeichert. Bitte neu laden, um das Ergebnis zu sehen.':(error?.message||'Abstimmung fehlgeschlagen.'),true);
  }finally{
   pending.delete(id);poll?.classList.remove('is-voting');
  }
 },true);
})();


/* Read-only vote breakdown: authorized names grouped by selected option. */
(()=>{
 'use strict';
 const feed=document.getElementById('feedList');
 if(!feed||!document.body.hasAttribute('data-hnt-next-live-posts'))return;
 const modal=document.createElement('div');
 modal.className='hnt-poll-voters-overlay';modal.id='hntPollVotersModal';modal.hidden=true;
 modal.innerHTML=[
  '<section class="hnt-poll-voters-dialog" role="dialog" aria-modal="true" aria-labelledby="hntVotersTitle" aria-describedby="hntVotersMeta">',
   '<header class="hnt-poll-voters-head"><div><div class="hnt-poll-voters-kicker">HNT.ROCKS / COMMUNITY VOTES</div>',
   '<h2 class="hnt-poll-voters-title" id="hntVotersTitle">Abstimmungsdetails</h2>',
   '<div class="hnt-poll-voters-meta" id="hntVotersMeta">Stimmen werden geladen …</div></div>',
   '<button type="button" class="hnt-poll-voters-close" aria-label="Schließen">×</button></header>',
   '<div class="hnt-poll-voters-body" id="hntVotersBody"><p class="hnt-poll-voters-status">Stimmen werden geladen …</p></div>',
  '</section>'
 ].join('');
 document.body.append(modal);
 const title=modal.querySelector('#hntVotersTitle'),meta=modal.querySelector('#hntVotersMeta');
 const body=modal.querySelector('#hntVotersBody'),x=modal.querySelector('.hnt-poll-voters-close');
 let active=null,focus=null,oldOverflow='',loading=false;
 const make=(tag,cls,txt)=>{const el=document.createElement(tag);if(cls)el.className=cls;if(txt!==undefined)el.textContent=String(txt);return el;};
 function close(){
  modal.hidden=true;active=null;document.body.style.overflow=oldOverflow;
  const target=focus;focus=null;if(target?.isConnected)target.focus({preventScroll:true});
 }
 async function get(post,option=null,offset=0){
  const url='/next/feed/posts/'+encodeURIComponent(post)+'/poll/voters'+(option===null?'':'?option_id='+encodeURIComponent(option)+'&offset='+encodeURIComponent(offset));
  const r=await fetch(url,{credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
  let j=null;try{j=await r.json();}catch{}
  if(!r.ok||j?.ok!==true)throw Error(j?.message||(r.status===403?'Bitte zuerst abstimmen, um die gewählten Antworten der Mitglieder zu sehen.':'Abstimmungsdetails konnten nicht geladen werden.'));
  return j;
 }
 function addUsers(group,users){
  const list=group.querySelector('.hnt-poll-voters-users');
  for(const user of users){
   const row=make('div','hnt-poll-voters-user'),avatar=make('img'),name=make('span','',user.name||'Hunter');
   avatar.alt='';avatar.loading='lazy';avatar.src=user.avatar||'/build/next/hntv3-assets/avatar-profile.webp';
   row.append(avatar,name);list.append(row);
  }
 }
 function optionNode(option){
  const group=make('section','hnt-poll-voters-option');group.dataset.voterOption=String(option.id);
  const top=make('div','hnt-poll-voters-option-top');
  top.append(make('span','',option.body),make('strong','',option.count+' '+(option.count===1?'Stimme':'Stimmen')+' · '+option.percent+'%'));
  const track=make('div','hnt-poll-voters-progress'),fill=make('span');
  fill.style.width=Math.min(100,Math.max(0,Number(option.percent)||0))+'%';track.append(fill);
  group.append(top,track,make('div','hnt-poll-voters-users'));
  addUsers(group,option.voters||[]);
  if(!option.count)group.querySelector('.hnt-poll-voters-users').append(make('span','hnt-poll-voters-empty','Noch keine Stimmen für diese Antwort'));
  if(option.next_offset!==null){
   const more=make('button','hnt-poll-voters-more','Weitere Mitglieder anzeigen');
   more.type='button';more.dataset.moreVoters=String(option.id);more.dataset.nextOffset=String(option.next_offset);
   group.append(more);
  }
  return group;
 }
 async function open(post,button){
  if(loading)return;
  focus=button;active=post;loading=true;oldOverflow=document.body.style.overflow;
  modal.hidden=false;document.body.style.overflow='hidden';title.textContent='Abstimmungsdetails';
  meta.textContent='Echte Stimmen aus der Community';
  body.replaceChildren(make('p','hnt-poll-voters-status','Stimmen werden geladen …'));
  x.focus({preventScroll:true});
  try{
   const result=await get(post);
   if(active!==post)return;
   title.textContent=result.question||'Community-Umfrage';
   meta.textContent=result.total+' '+(result.total===1?'Stimme':'Stimmen')+' insgesamt · '+(result.closed?'Abgeschlossen':'Aktive Umfrage');
   body.replaceChildren(...result.options.map(optionNode));
   if(!result.options.length)body.append(make('p','hnt-poll-voters-status','Keine Antworten vorhanden.'));
  }catch(error){if(active===post)body.replaceChildren(make('p','hnt-poll-voters-status',error.message||'Fehler beim Laden.'));}
  finally{loading=false;}
 }
 feed.addEventListener('click',e=>{
  const button=e.target.closest('.hnt-live-poll [data-poll-voters]');
  if(!button)return;
  e.preventDefault();e.stopImmediatePropagation();
  if(/^\d+$/.test(button.dataset.pollVoters||''))void open(button.dataset.pollVoters,button);
 },true);
 body.addEventListener('click',async e=>{
  const more=e.target.closest('[data-more-voters]');
  if(!more||!active||more.disabled)return;
  const option=more.dataset.moreVoters,offset=Number(more.dataset.nextOffset),post=active;
  if(!/^\d+$/.test(option)||!Number.isInteger(offset)||offset<0)return;
  more.disabled=true;more.textContent='Lädt …';
  try{
   const page=await get(post,option,offset);
   if(active!==post)return;
   const group=more.closest('.hnt-poll-voters-option');
   if(!page.options[0]||!group)return;
   addUsers(group,page.options[0].voters||[]);
   if(page.options[0].next_offset===null)more.remove();
   else{more.dataset.nextOffset=String(page.options[0].next_offset);more.disabled=false;more.textContent='Weitere Mitglieder anzeigen';}
  }catch{more.disabled=false;more.textContent='Erneut versuchen';}
 });
 x.addEventListener('click',close);
 modal.addEventListener('click',e=>{if(e.target===modal)close();});
 modal.addEventListener('keydown',e=>{
  if(e.key==='Escape'){e.preventDefault();close();return;}
  if(e.key!=='Tab')return;
  const all=[...modal.querySelectorAll('button:not(:disabled),a[href]')].filter(el=>el.getClientRects().length);
  if(!all.length)return;
  if(e.shiftKey&&document.activeElement===all[0]){e.preventDefault();all.at(-1).focus();}
  else if(!e.shiftKey&&document.activeElement===all.at(-1)){e.preventDefault();all[0].focus();}
 });
})();

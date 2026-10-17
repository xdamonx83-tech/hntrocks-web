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

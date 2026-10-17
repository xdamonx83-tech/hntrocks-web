/* HNTv3: authenticated, server-backed topbar; no fabricated counters. */
(()=>{
 'use strict';
 const types=['notifications','messages','friends'];
 const triggers=Object.fromEntries(types.map(t=>[t,document.getElementById('socialToggle-'+t)]));
 const panels=Object.fromEntries(types.map(t=>[t,document.getElementById('socialPanel-'+t)]));
 if(types.some(t=>!triggers[t]||!panels[t]))return;
 const csrf=document.querySelector('meta[name="csrf-token"]')?.content||'';
 const text=(tag,content,cls)=>{const e=document.createElement(tag);if(cls)e.className=cls;e.textContent=String(content??'');return e;};
 const badge=(type,n)=>{
  const el=document.getElementById('socialBadge-'+type);
  if(!el)return;
  el.textContent=String(n>99?'99+':n);el.hidden=!n;
 };
 const avatar=(url,letters)=>{
  const wrap=text('span',letters||'H','social-avatar');
  if(url){const img=document.createElement('img');img.src=url;img.alt='';img.loading='lazy';img.style.cssText='width:100%;height:100%;object-fit:cover;border-radius:50%;';
   img.addEventListener('error',()=>img.remove(),{once:true});wrap.append(img);}
  return wrap;
 };
 const status=(id,msg)=>{const e=document.getElementById(id);if(e)e.textContent=msg;};
 const counts={notifications:0,messages:0,friends:0};
 let active=null,data=null,loading=null,messageFilter='all';
 const fetchState=async()=>{
  const response=await fetch('/next/topbar',{credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
  if(!response.ok)throw Error('Topbar konnte nicht geladen werden');
  const json=await response.json();if(!json.ok)throw Error('Ungültige Topbar-Antwort');
  return json;
 };
 function setEmpty(target,message){
  target.replaceChildren(text('div',message,'social-empty'));
 }
 function notifications(){
  const n=data.notifications;counts.notifications=n.unread;
  badge('notifications',n.unread);
  status('socialCount-notifications',n.unread?n.unread+' ungelesen':'Alles gelesen');
  const target=document.getElementById('socialNotices');target.replaceChildren();
  for(const item of n.items){
   const row=document.createElement('a');row.className='social-entry'+(item.unread?' is-unread':'');
   row.href=item.url||'/notifications';row.dataset.notificationId=String(item.id);
   const info=text('span','','social-entry-copy');
   info.append(text('strong',item.title),text('span',item.body||''),text('small',item.time||''));
   row.append(avatar(item.avatar,'H'),info);
   if(item.unread)row.append(text('i','','social-unread-dot'));
   target.append(row);
  }
  if(!n.items.length)setEmpty(target,'Keine Benachrichtigungen');
  document.getElementById('socialMarkRead').disabled=!n.unread;
 }
 function messages(){
  const m=data.messages;counts.messages=m.unread;badge('messages',m.unread);
  status('socialCount-messages',m.unread?m.unread+' ungelesene Nachrichten':'Keine ungelesenen Nachrichten');
  status('socialUnreadMsgCount',m.unread);
  const target=document.getElementById('socialMessagesList');target.replaceChildren();
  const items=m.items.filter(item=>messageFilter!=='unread'||item.unread>0);
  for(const item of items){
   const row=document.createElement('a');row.href=item.url;row.className='social-entry social-msg-entry'+(item.unread?' is-unread':'');
   const copy=text('span','','social-entry-copy');
   copy.append(text('strong',item.name),text('span',item.body),text('small',item.time||''));
   row.append(avatar(item.avatar,(item.name||'H').substring(0,2).toUpperCase()),copy);
   if(item.unread)row.append(text('i','','social-unread-dot'));
   target.append(row);
  }
  if(!items.length)setEmpty(target,messageFilter==='unread'?'Keine ungelesenen Chats':'Noch keine Nachrichten');
  document.getElementById('socialMessageEmpty').hidden=true;
 }
 function friends(){
  const f=data.friends;counts.friends=f.pending;badge('friends',f.pending);
  status('socialCount-friends',f.pending?f.pending+' offene Anfragen':'Keine offenen Anfragen');
  const target=document.getElementById('socialRequests');target.replaceChildren();
  for(const item of f.items){
   const row=text('div','','social-request');row.dataset.friendshipId=String(item.id);
   const details=text('div','','social-request-details'),info=text('div','','social-request-info');
   info.append(text('strong',item.name),text('small',item.time||''));
   details.append(info,text('p','Möchte mit dir befreundet sein'));
   const buttons=text('div','','social-request-actions');
   for(const action of ['accept','decline']){
    const btn=text('button',action==='accept'?'Annehmen':'Ablehnen',action==='accept'?'social-accept':'social-decline');
    btn.type='button';btn.dataset.action=action;buttons.append(btn);
   }
   details.append(buttons);row.append(avatar(item.avatar,(item.name||'H').slice(0,2).toUpperCase()),details);target.append(row);
  }
  document.getElementById('socialFriendEmpty').hidden=!!f.items.length;
 }
 function paint(){if(!data)return;notifications();messages();friends();}
 async function refresh(){if(loading)return loading;
  loading=fetchState().then(json=>{data=json;paint();}).catch(error=>{
   for(const t of types){badge(t,0);const node=document.getElementById(t==='notifications'?'socialNotices':t==='messages'?'socialMessagesList':'socialRequests');setEmpty(node,'Daten momentan nicht verfügbar. Bitte erneut öffnen.');}
   console.warn(error.message);
  }).finally(()=>{loading=null;});return loading;
 }
 function close(focus=false){if(!active)return;const t=active;active=null;panels[t].hidden=true;triggers[t].setAttribute('aria-expanded','false');if(focus)triggers[t].focus();}
 function place(type){
  const trigger=triggers[type].getBoundingClientRect(),width=panels[type].offsetWidth;
  const left=Math.max(9,Math.min(innerWidth-width-9,trigger.left+trigger.width/2-width/2));
  panels[type].style.setProperty('--social-left',left+'px');
  panels[type].style.setProperty('--social-top',(trigger.bottom+13)+'px');
  panels[type].style.setProperty('--social-origin',Math.max(16,Math.min(width-16,trigger.left+trigger.width/2-left))+'px');
 }
 function open(t){
  if(active)close();document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'social'}}));
  const menu=document.getElementById('accountMenu');if(menu)menu.hidden=true;
  const other=document.getElementById('moreMenu');if(other)other.hidden=true;
  panels[t].hidden=false;triggers[t].setAttribute('aria-expanded','true');active=t;place(t);void refresh();
 }
 for(const t of types){
  triggers[t].addEventListener('click',()=>active===t?close():open(t));
  triggers[t].addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();if(active!==t)open(t);panels[t].querySelector('button,a')?.focus();}});
  badge(t,0);
 }
 document.addEventListener('pointerdown',e=>{if(active&&!panels[active].contains(e.target)&&!triggers[active].contains(e.target))close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape'&&active){e.stopImmediatePropagation();close(true);}},true);
 document.addEventListener('hnt:dropdown-open',e=>{if(e.detail?.origin!=='social')close();});
 window.addEventListener('resize',()=>{if(active)place(active);});
 document.getElementById('socialMarkRead').addEventListener('click',async e=>{
  const btn=e.currentTarget;btn.disabled=true;
  try{const r=await fetch('/notifications/read-all',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf}});if(!r.ok)throw Error('Could not mark read');await refresh();}
  catch{btn.disabled=false;status('socialCount-notifications','Markieren fehlgeschlagen');}
 });
 document.getElementById('socialNotices').addEventListener('click',async e=>{
  const row=e.target.closest('[data-notification-id]');if(!row||!row.classList.contains('is-unread'))return;
  e.preventDefault();const href=row.href;
  try{const r=await fetch('/notifications/'+encodeURIComponent(row.dataset.notificationId)+'/read',{method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf}});if(!r.ok)throw Error('Mark read failed');location.assign(href);}
  catch{location.assign(href);}
 });
 document.querySelectorAll('[data-message-filter]').forEach(btn=>btn.addEventListener('click',()=>{
  messageFilter=btn.dataset.messageFilter;
  document.querySelectorAll('[data-message-filter]').forEach(other=>{const sel=other===btn;other.classList.toggle('is-selected',sel);other.setAttribute('aria-pressed',String(sel));});
  if(data)messages();
 }));
 document.getElementById('socialRequests').addEventListener('click',async e=>{
  const btn=e.target.closest('[data-action]');if(!btn)return;
  const row=btn.closest('[data-friendship-id]');if(!row)return;
  const action=btn.dataset.action;const buttons=row.querySelectorAll('button');buttons.forEach(b=>b.disabled=true);
  try{
   const r=await fetch('/friends/'+encodeURIComponent(row.dataset.friendshipId)+'/'+(action==='accept'?'accept':'decline'),{
     method:'POST',credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':csrf}});
   if(!r.ok)throw Error('Request failed');
   await refresh();
  }catch{buttons.forEach(b=>b.disabled=false);row.querySelector('.social-request-details').append(text('small','Aktion fehlgeschlagen – bitte erneut versuchen.'));}
 });
 for(const id of ['socialNotices','socialMessagesList','socialRequests']){const node=document.getElementById(id);node.replaceChildren(text('div','Wird geladen …','social-empty'));}
 document.getElementById("socialFriendEmpty").hidden=true;
 void refresh();
 document.addEventListener('visibilitychange',()=>{if(!document.hidden)void refresh();});
})();

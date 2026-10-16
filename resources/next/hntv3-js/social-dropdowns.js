// HNT.ROCKS Social Signals — local UI demo data only. Real counters/messages need backend integration.
(()=>{
  'use strict';
  const types=['notifications','messages','friends'];
  const triggers=Object.fromEntries(types.map(t=>[t,document.querySelector(`#socialToggle-${t}`)]));
  const panels=Object.fromEntries(types.map(t=>[t,document.querySelector(`#socialPanel-${t}`)]));
  let active=null;
  const tooltip=document.querySelector('#navTooltip');
  const visible=t=>!panels[t].hidden;
  function close(restoreFocus=false){
    if(!active)return;
    const prev=active;
    panels[prev].hidden=true;
    triggers[prev].setAttribute('aria-expanded','false');
    active=null;
    if(restoreFocus)triggers[prev].focus();
  }
  function position(t){
    const btn=triggers[t].getBoundingClientRect();
    const width=panels[t].offsetWidth;
    const viewport=innerWidth;
    const left=Math.max(9,Math.min(viewport-width-9,btn.left+btn.width/2-width/2));
    const top=btn.bottom+13;
    panels[t].style.setProperty('--social-top',`${top}px`);
    panels[t].style.setProperty('--social-left',`${left}px`);
    panels[t].style.setProperty('--social-origin',`${Math.max(16,Math.min(width-16,btn.left+btn.width/2-left))}px`);
  }
  function open(t){
    if(active && active!==t)close();
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'social'}}));
    if(tooltip)tooltip.hidden=true;
    const account=document.getElementById('accountMenu');
    const accountToggle=document.getElementById('accountMenuToggle');
    if(account&&!account.hidden){account.hidden=true;accountToggle?.setAttribute('aria-expanded','false')}
    const more=document.getElementById('moreMenu');
    if(more&&!more.hidden){more.hidden=true;document.getElementById('moreMenuToggle')?.setAttribute('aria-expanded','false')}
    panels[t].hidden=false;
    triggers[t].setAttribute('aria-expanded','true');
    active=t;
    position(t);
  }
  types.forEach(t=>{
    const btn=triggers[t];
    btn.addEventListener('click',()=>visible(t)?close():open(t));
    btn.addEventListener('keydown',e=>{if(e.key==='ArrowDown'){e.preventDefault();if(!visible(t))open(t);panels[t].querySelector('button:not(:disabled),a')?.focus()}});
  });
  document.addEventListener('pointerdown',e=>{if(!active)return;if(!panels[active].contains(e.target)&&!triggers[active].contains(e.target))close()});
  document.addEventListener('keydown',e=>{if(e.key==='Escape'&&active){e.preventDefault();e.stopImmediatePropagation();close(true)}},true);
  document.addEventListener('focusin',e=>{if(active&&!panels[active].contains(e.target)&&!triggers[active].contains(e.target))close()});
  window.addEventListener('resize',()=>{if(active)position(active)});
  window.addEventListener('scroll',()=>{if(active)position(active)},{passive:true});
  document.addEventListener('hnt:dropdown-open',e=>{if(e.detail?.origin!=='social')close()});
  // Badge counts reflect demo interactions only; no live server state is modified.
  function setBadge(t,count){const badge=document.getElementById(`socialBadge-${t}`);badge.textContent=String(count);badge.hidden=count===0;}
  const notifications=[...document.querySelectorAll('[data-notice="1"]')];
  function updateNotifications(){const n=notifications.filter(x=>x.classList.contains('is-unread')).length;setBadge('notifications',n);document.getElementById('socialCount-notifications').textContent=n?`${n} neue Aktivitäten`:'Alles auf dem neuesten Stand';document.getElementById('socialMarkRead').disabled=n===0;}
  document.getElementById('socialMarkRead').addEventListener('click',()=>{notifications.forEach(x=>x.classList.remove('is-unread'));updateNotifications()});
  // Filter actually toggles the visible conversation rows.
  let messageFilter='all';
  const messageRows=[...document.querySelectorAll('.social-msg-entry')];
  function updateMessages(){const n=messageRows.filter(x=>x.dataset.unread==='1').length;setBadge('messages',n);document.getElementById('socialCount-messages').textContent=n?`${n} ungelesene Chats`:'Alle Nachrichten gelesen';document.getElementById('socialUnreadMsgCount').textContent=n;messageRows.forEach(x=>x.hidden=messageFilter==='unread'&&x.dataset.unread!=='1');document.getElementById('socialMessageEmpty').hidden=messageFilter!=='unread'||n!==0;}
  document.querySelectorAll('[data-message-filter]').forEach(btn=>btn.addEventListener('click',()=>{messageFilter=btn.dataset.messageFilter;document.querySelectorAll('[data-message-filter]').forEach(b=>{const sel=b===btn;b.classList.toggle('is-selected',sel);b.setAttribute('aria-pressed',sel)});updateMessages()}));
  // The friend buttons deliberately do not call the backend. They demonstrate feedback and visual states.
  const requests=[...document.querySelectorAll('.social-request[data-request="1"]')];
  function updateFriends(){const n=requests.filter(r=>!r.classList.contains('is-resolved')).length;setBadge('friends',n);document.getElementById('socialCount-friends').textContent=n?`${n} offene ${n===1?'Anfrage':'Anfragen'}`:'Keine offenen Anfragen';document.getElementById('socialFriendEmpty').hidden=n!==0;}
  document.querySelectorAll('[data-respond]').forEach(button=>button.addEventListener('click',()=>{
    const row=button.closest('.social-request');if(row.classList.contains('is-resolved'))return;
    const accepted=button.dataset.respond==='accept';
    row.classList.add('is-resolved');
    row.querySelector('.social-request-actions').hidden=true;
    row.querySelector('.social-request-status').hidden=false;
    row.querySelector('.social-request-status').textContent=accepted?'✓ Anfrage lokal angenommen':'Anfrage lokal abgelehnt';
    updateFriends();
  }));
  // An account-menu click is outside the social panel: handled by pointerdown.
  updateNotifications();updateMessages();updateFriends();
})();

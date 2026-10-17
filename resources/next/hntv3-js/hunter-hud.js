/* HNT.ROCKS — Hunter Network / Hunter Journal (local design test) */
(()=>{
'use strict';
const onlineToggle=document.getElementById('hunterOnlineToggle'),xpToggle=document.getElementById('hunterXpToggle');
if(!onlineToggle||!xpToggle)return;
const prestige=window.HNTPrestigeData||{career_xp:0,level:1,prestige:0,cycle_xp:0,cycle_cap:12250,xp_boost_percent:0};
const prestigeWords=window.HNTPrestigeWords||{};
const prestigeProgress=Number(window.HNTPrestigeProgress||0);
const pw=k=>prestigeWords[k]||k;
const icon=name=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+name+'"></use></svg>';
const onlineTexts={
 de:{friends:'Freunde',all:'Alle',team:'Team',online:'Online',intro:'Wer ist gerade in der Community unterwegs?',search:'Hunter suchen ...',teamMember:'Teammitglied',friend:'Freund',you:'Du',nothing:'Keine Online-Hunter gefunden.',loading:'Hunter werden geladen ...',error:'Online-Hunter konnten nicht geladen werden.',members:'Hunter finden',people:'Hunter online',more:'Weitere Hunter in der Mitgliederübersicht'},
 en:{friends:'Friends',all:'All',team:'Team',online:'Online',intro:'Who is exploring the community right now?',search:'Search hunters ...',teamMember:'Team member',friend:'Friend',you:'You',nothing:'No hunters online for this filter.',loading:'Loading hunters ...',error:'Unable to load online hunters.',members:'Find hunters',people:'Hunters online',more:'More hunters in the members directory'},
 es:{friends:'Amigos',all:'Todos',team:'Equipo',online:'En línea',intro:'¿Quién está en la comunidad ahora?',search:'Buscar cazadores ...',teamMember:'Miembro del equipo',friend:'Amigo',you:'Tú',nothing:'No se encontraron cazadores en línea.',loading:'Cargando cazadores ...',error:'No se pudo cargar la lista.',members:'Buscar cazadores',people:'Cazadores conectados',more:'Más cazadores en el directorio'},
 ru:{friends:'Друзья',all:'Все',team:'Команда',online:'В сети',intro:'Кто сейчас в сообществе?',search:'Поиск охотников ...',teamMember:'Участник команды',friend:'Друг',you:'Вы',nothing:'Охотников в сети не найдено.',loading:'Загрузка охотников ...',error:'Не удалось загрузить список.',members:'Найти охотников',people:'Охотники в сети',more:'Другие охотники в каталоге'}
};
const onlineLang=(document.documentElement.lang||'de').slice(0,2).toLowerCase();
const ow=onlineTexts[onlineLang]||onlineTexts.de;
const tasks=[
 ['story','Eine Geschichte teilen','Berichte von deiner letzten Jagd',50,'book-open'],
 ['help','Einem Hunter helfen','Eine hilfreiche Antwort hinterlassen',75,'users-three'],
 ['vote','Deine Stimme abgeben','Bei einer Community-Umfrage abstimmen',25,'chat-circle-dots']
];
const storageKey='hnt-hunter-journal-demo-v1';
let done=new Set();
try{const arr=JSON.parse(sessionStorage.getItem(storageKey)||'[]');if(Array.isArray(arr))done=new Set(arr.filter(x=>tasks.some(t=>t[0]===x)))}catch{}
let active=null,tab='friends',query='',detailFocus=null;

const online=document.createElement('section');online.id='hunterOnlinePanel';online.className='hnt-hud-panel hnt-online-panel';online.hidden=true;online.setAttribute('role','region');online.setAttribute('aria-label','Online-Hunter');
online.innerHTML=[
'<div class="hnt-hud-line"></div><div class="hnt-hud-head">',
'<div class="hnt-hud-kicker"><i class="hnt-hud-signal"></i> HUNTER NETWORK</div>',
'<div class="hnt-hud-heading"><div><h2>'+ow.people+'<span>.</span></h2><p>'+ow.intro+'</p></div><div class="hnt-hud-head-icon">',icon('users-three'),'</div></div>',
'<div class="hnt-online-metrics"><span><strong id="hntOnlineTotal">0</strong> '+ow.online+'</span><b></b><span><strong id="hntOnlineFriends">0</strong> '+ow.friends+'</span><b></b><span><strong id="hntOnlineTeam">0</strong> '+ow.team+'</span></div></div>',
'<div class="hnt-online-controls"><div class="hnt-online-tabs" role="group" aria-label="'+ow.people+'">',
'<button type="button" class="is-active" data-online-tab="friends" aria-pressed="true">'+ow.friends+' <em id="hntOnlineFriendsTab">0</em></button>',
'<button type="button" data-online-tab="all" aria-pressed="false">'+ow.all+' <em id="hntOnlineAllTab">0</em></button>',
'<button type="button" data-online-tab="team" aria-pressed="false">'+ow.team+' <em id="hntOnlineTeamTab">0</em></button>',
'</div><label class="hnt-online-search">',icon('magnifying-glass'),'<input id="hntOnlineSearch" type="search" autocomplete="off" placeholder="'+ow.search+'" aria-label="'+ow.search+'"></label></div>',
'<div id="hntOnlineList" class="hnt-online-list" tabindex="0" aria-live="polite"></div>',
'<a href="/members" class="hnt-hud-foot"><span>',icon('crosshair'),' HNT.ROCKS COMMUNITY</span><span>'+ow.members+' ',icon('arrow-up-right'),'</span></a>'
].join('');

const journal=document.createElement('section');journal.id='hunterJournalPanel';journal.className='hnt-hud-panel hnt-journal-panel';journal.hidden=true;journal.setAttribute('role','region');journal.setAttribute('aria-label','Hunter Journal');
journal.innerHTML=[
'<div class="hnt-hud-line is-gold"></div><div class="hnt-hud-head">',
'<div class="hnt-hud-kicker"><i class="hnt-hud-signal"></i> HNT.ROCKS / JOURNAL</div>',
'<div class="hnt-hud-heading"><div><h2>Deine Jagd. Dein Ruf.</h2><p>Community-Aufträge und Auszeichnungen</p></div><div class="hnt-hud-head-icon is-gold">',icon('crosshair'),'</div></div></div>',
'<div class="hnt-journal-content"><section class="hnt-journal-rank"><div class="hnt-journal-rank-head"><div><small>HUNTER-RANG</small><div class="hnt-journal-xp"><strong id="hntJournalXP">0</strong><span>XP</span></div></div>',
'<div class="hnt-journal-seal">',icon('crosshair'),'<span>HNT</span></div></div>',
'<div class="hnt-journal-track"><span id="hntJournalTrack"></span></div><div class="hnt-journal-rank-caption"><span>Level</span><span id="hntNextLevel">-- XP</span></div></section>',
'<div class="hnt-journal-section-title"><div><small>TÄGLICHE KOPFGELDER</small><h3>Deine Aufträge</h3></div><span id="hntTasksCount">0 / 3</span></div>',
'<div id="hntJournalTasks" class="hnt-journal-tasks"></div>',
'<button type="button" class="hnt-journal-feature" id="hntJournalFeature" aria-label="Badge Sumpfveteran ansehen">',
'<span class="hnt-journal-feature-icon">',icon('crosshair'),'</span><span class="hnt-journal-feature-text"><small>NÄCHSTE AUSZEICHNUNG</small><strong>Sumpfveteran</strong><span>Drei Tage täglich alle Kopfgelder erledigen</span>',
'<i class="hnt-mini-track"><b id="hntMiniBadgeProgress"></b></i><em id="hntMiniBadgeLabel">2 von 3 Tagen</em></span>',icon('arrow-right'),'</button>',
'<div class="hnt-journal-note">',icon('target'),' Tägliche Community-Kopfgelder</div></div>',
'<a href="https://hnt.rocks/profile" class="hnt-hud-foot"><span>',icon('trophy'),' HUNTER-PROFIL</span><span>Alle Auszeichnungen ',icon('arrow-up-right'),'</span></a>'
].join('');
const modal=document.createElement('div');modal.id='hntBadgeOverlay';modal.className='hnt-badge-overlay';modal.hidden=true;
modal.innerHTML=[
'<div class="hnt-badge-dialog" role="dialog" aria-modal="true" aria-labelledby="hntBadgeName" tabindex="-1">',
'<button type="button" class="hnt-badge-close" aria-label="Badge schließen">',icon('x'),'</button>',
'<div class="hnt-badge-eyebrow">',icon('crosshair'),' HNT.ROCKS / AUSZEICHNUNG</div>',
'<div id="hntBadgeState" class="hnt-badge-status">NOCH NICHT FREIGESCHALTET</div>',
'<h2 id="hntBadgeName">Sumpfveteran</h2>',
'<p class="hnt-badge-description">Schließe an drei aufeinanderfolgenden Tagen deine täglichen Community-Kopfgelder ab.</p>',
'<div class="hnt-badge-showcase"><div class="hnt-badge-emblem"><div class="hnt-badge-emblem-core">',icon('crosshair'),'<strong>HNT</strong><small>ROCKS</small></div></div><span class="hnt-badge-multiplier">x12</span></div>',
'<div class="hnt-badge-progress-title"><span>FORTSCHRITT</span><strong id="hntBadgeProgressText">2 / 3 TAGE</strong></div>',
'<div class="hnt-badge-progress"><i id="hntBadgeProgress"></i></div>',
'<p class="hnt-badge-progress-note" id="hntBadgeHint">Noch ein Tag bis zur Freischaltung.</p>',
'<button type="button" id="hntBadgeBack" class="hnt-badge-back">Zurück zu den Aufträgen ',icon('arrow-right'),'</button>',
'</div>'
].join('');
document.body.append(online,journal,modal);

let onlineSerial=0,onlineTimer=null,onlineCounts=null;
const roster=online.querySelector('#hntOnlineList');
function onlineAvatar(user){
 const box=document.createElement('span');box.className='hnt-online-avatar';
 const initials=(user.name||'H').trim().substring(0,2).toUpperCase();
 box.textContent=initials;
 if(user.avatar){const img=document.createElement('img');img.alt='';img.loading='lazy';
  img.addEventListener('error',()=>{box.classList.remove('is-photo');box.replaceChildren(document.createTextNode(initials));},{once:true});
  box.classList.add('is-photo');box.replaceChildren(img);img.src=user.avatar;}
 return box;
}
function renderOnline(json){
 const counts=json.counts||{all:0,friends:0,team:0};onlineCounts=counts;
 for(const [id,n] of [['hntOnlineTotal',counts.all],['hntOnlineFriends',counts.friends],['hntOnlineTeam',counts.team],
  ['hntOnlineAllTab',counts.all],['hntOnlineFriendsTab',counts.friends],['hntOnlineTeamTab',counts.team]]){
  const el=online.querySelector('#'+id);if(el)el.textContent=String(n??0);
 }
 const counter=document.getElementById('hunterOnlineCount');
 if(counter)counter.textContent=String(counts.all||0)+' '+ow.online;
 onlineToggle.setAttribute('aria-label',String(counts.all||0)+' '+ow.people);
 roster.replaceChildren();
 const items=Array.isArray(json.items)?json.items:[];
 for(const h of items){
  const row=document.createElement('a');row.className='hnt-online-person';
  row.href=h.url||'/members';
  const copy=document.createElement('span');copy.className='hnt-online-person-copy';
  const name=document.createElement('strong');name.textContent=h.name||'Hunter';
  const desc=document.createElement('small');desc.textContent=h.self?ow.you:h.team?ow.teamMember:h.friend?ow.friend:ow.online;
  const status=document.createElement('span');status.className='hnt-online-pill'+(h.team?' seeking':'');
  status.textContent=h.team?ow.team.toUpperCase():ow.online.toUpperCase();
  copy.append(name,desc);row.append(onlineAvatar(h),copy,status);roster.append(row);
 }
 if(!items.length){const empty=document.createElement('p');empty.className='hnt-online-empty';empty.textContent=ow.nothing;roster.append(empty);}
 if(json.more){const notice=document.createElement('p');notice.className='hnt-online-empty';notice.textContent=ow.more;roster.append(notice);}
}
async function loadOnline(){
 const serial=++onlineSerial;
 if(!onlineCounts){roster.replaceChildren();const state=document.createElement('p');state.className='hnt-online-empty';state.textContent=ow.loading;roster.append(state);}
 const params=new URLSearchParams({tab,q:query});
 try{const r=await fetch('/next/online?'+params.toString(),{credentials:'same-origin',headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'}});
  if(!r.ok)throw Error('Online request failed');
  const json=await r.json();if(serial!==onlineSerial)return;
  if(!json.ok)throw Error('Online response invalid');renderOnline(json);
 }catch(e){if(serial!==onlineSerial)return;roster.replaceChildren();const error=document.createElement('p');error.className='hnt-online-empty';error.textContent=ow.error;roster.append(error);}
}
function renderBadge(){
 const unlocked=done.size===3;
 const badge=modal.querySelector('.hnt-badge-dialog');
 badge.classList.toggle('is-earned',unlocked);
 modal.querySelector('#hntBadgeState').textContent=unlocked?'AUSZEICHNUNG FREIGESCHALTET':'NOCH NICHT FREIGESCHALTET';
 modal.querySelector('#hntBadgeProgressText').textContent=unlocked?'3 / 3 TAGE':'2 / 3 TAGE';
 modal.querySelector('#hntBadgeProgress').style.width=unlocked?'100%':'66.67%';
 modal.querySelector('#hntBadgeHint').textContent=unlocked?'Du hast die Auszeichnung Sumpfveteran freigeschaltet.':'Noch ein Tag bis zur Freischaltung. Schließe die heutigen Kopfgelder ab.';
}
function renderJournal(){
 const xp=Number(prestige.career_xp||0);
 const xpFormat=new Intl.NumberFormat(document.documentElement.lang||'de-DE');
 journal.querySelector('.hnt-journal-rank-head small').textContent=pw('prestige_count')+' '+prestige.prestige+' · '+pw('level')+' '+prestige.level;
 journal.querySelector('.hnt-journal-rank-caption>span').textContent=pw('level')+' '+prestige.level+' / 50';
 journal.querySelector('.hnt-hud-heading h2').textContent=pw('journal_title');
 journal.querySelector('.hnt-hud-heading p').textContent=pw('journal_note');
 journal.querySelector('#hntJournalXP').textContent=xpFormat.format(xp);
 document.getElementById('hunterHudXpValue').textContent=xpFormat.format(xp)+' XP';
 journal.querySelector('#hntJournalTrack').style.width=Math.max(0,Math.min(100,prestigeProgress))+'%';
 journal.querySelector('#hntNextLevel').textContent=Number(prestige.level)>=50?pw('journal_ready'):xpFormat.format(Math.max(0,Number(prestige.level)*250-Number(prestige.cycle_xp||0)))+' '+pw('journal_to_next');
 journal.querySelector('#hntTasksCount').textContent=done.size+' / 3';
 const holder=journal.querySelector('#hntJournalTasks');holder.replaceChildren();
 tasks.forEach(t=>{
  const checked=done.has(t[0]);const button=document.createElement('button');
  button.type='button';button.dataset.task=t[0];button.className='hnt-journal-task'+(checked?' is-complete':'');button.setAttribute('aria-pressed',String(checked));
  button.innerHTML='<span class="hnt-journal-task-icon">'+icon(checked?'check':t[4])+'</span><span class="hnt-journal-task-copy"><strong>'+t[1]+'</strong><small>'+t[2]+'</small></span><span class="hnt-journal-reward">'+(checked?'ERLEDIGT':'+'+t[3]+' XP')+'</span>';
  holder.append(button);
 });
 const earned=done.size===3;
 journal.querySelector('#hntJournalFeature').classList.toggle('is-earned',earned);
 journal.querySelector('#hntMiniBadgeProgress').style.width=earned?'100%':'66.67%';
 journal.querySelector('#hntMiniBadgeLabel').textContent=earned?'3 / 3 Tage · Freigeschaltet':'2 von 3 Tagen';
 renderBadge();
}
function place(panel,trigger){
 panel.hidden=false;panel.style.visibility='hidden';
 const rect=trigger.getBoundingClientRect();
 const panelWidth=panel.getBoundingClientRect().width;
 const x=Math.min(Math.max(10,rect.left-6),window.innerWidth-panelWidth-10);
 const y=Math.max(8,Math.min(rect.bottom+9,window.innerHeight-170));
 panel.style.setProperty('--hnt-hud-origin',Math.max(28,Math.min(panelWidth-28,rect.left+rect.width/2-Math.max(10,x)))+'px');
 panel.style.left=Math.max(10,x)+'px';panel.style.top=Math.max(8,y)+'px';
 panel.style.maxHeight=Math.max(160,window.innerHeight-y-9)+'px';
 panel.style.visibility='';
}
function close(restore=false){
 if(!active)return;
 const el=active;active=null;
 el.hidden=true;el.classList.remove('is-open');
 const btn=el===online?onlineToggle:xpToggle;
 btn.setAttribute('aria-expanded','false');
 if(restore)btn.focus();
}
function open(panel,trigger){
 if(active===panel){close();return}
 close();
 document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'hunter-hud'}}));
 if(panel===journal)renderJournal();else void loadOnline();
 active=panel;place(panel,trigger);
 trigger.setAttribute('aria-expanded','true');
 panel.classList.add('is-open');
}
onlineToggle.addEventListener('click',()=>open(online,onlineToggle));
xpToggle.addEventListener('click',()=>open(journal,xpToggle));
online.querySelectorAll('[data-online-tab]').forEach(b=>b.addEventListener('click',()=>{
 tab=b.dataset.onlineTab;
 online.querySelectorAll('[data-online-tab]').forEach(t=>{t.classList.toggle('is-active',t===b);t.setAttribute('aria-pressed',String(t===b))});
 void loadOnline();
}));
online.querySelector('#hntOnlineSearch').addEventListener('input',e=>{query=e.target.value.trim().toLowerCase();clearTimeout(onlineTimer);onlineTimer=setTimeout(()=>void loadOnline(),220)});
journal.querySelector('#hntJournalTasks').addEventListener('click',e=>{
 const btn=e.target.closest('[data-task]');if(!btn)return;
 if(done.has(btn.dataset.task))done.delete(btn.dataset.task);else done.add(btn.dataset.task);
 try{sessionStorage.setItem(storageKey,JSON.stringify([...done]))}catch{}
 renderJournal();
});
function openBadge(){
 detailFocus=document.activeElement;renderBadge();modal.hidden=false;
 requestAnimationFrame(()=>modal.classList.add('is-open'));
 modal.querySelector('.hnt-badge-close').focus();
}
function closeBadge(){
 modal.classList.remove('is-open');modal.hidden=true;
 if(detailFocus?.isConnected)detailFocus.focus();
}
journal.querySelector('#hntJournalFeature').addEventListener('click',openBadge);
modal.querySelector('.hnt-badge-close').addEventListener('click',closeBadge);
modal.querySelector('#hntBadgeBack').addEventListener('click',()=>{
 closeBadge();if(active!==journal)open(journal,xpToggle);
 journal.querySelector('#hntJournalTasks [data-task]:not(.is-complete)')?.focus();
});
modal.addEventListener('pointerdown',e=>{if(e.target===modal)closeBadge()});
document.addEventListener('pointerdown',e=>{if(active&&!active.contains(e.target)&&!onlineToggle.contains(e.target)&&!xpToggle.contains(e.target))close()});
document.addEventListener('keydown',e=>{
 if(e.key==='Escape'&&!modal.hidden){e.preventDefault();e.stopImmediatePropagation();closeBadge()}
 else if(e.key==='Escape'&&active){close(true)}
 if(e.key==='Tab'&&!modal.hidden){
  const controls=[...modal.querySelectorAll('button:not([disabled])')];
  if(e.shiftKey&&document.activeElement===controls[0]){e.preventDefault();controls[controls.length-1].focus()}
  else if(!e.shiftKey&&document.activeElement===controls[controls.length-1]){e.preventDefault();controls[0].focus()}
 }
},true);
document.addEventListener('hnt:dropdown-open',e=>{if(e.detail?.origin!=='hunter-hud')close()});
function reposition(){if(active)place(active,active===online?onlineToggle:xpToggle)}
window.addEventListener('resize',reposition);
window.addEventListener('scroll',reposition,{passive:true});
renderJournal();
void loadOnline();
window.setInterval(()=>{if(!document.hidden)void loadOnline()},45000);
})();

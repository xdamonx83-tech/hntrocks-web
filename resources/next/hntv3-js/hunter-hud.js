/* HNT.ROCKS — Hunter Network / Hunter Journal (local design test) */
(()=>{
'use strict';
const onlineToggle=document.getElementById('hunterOnlineToggle'),xpToggle=document.getElementById('hunterXpToggle');
if(!onlineToggle||!xpToggle)return;
const icon=name=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+name+'"></use></svg>';
const hunters=[
 ['NightWraith','NW','violet','Community-Feed',true,false],['SwampCrow','SC','gold','Maps',true,true],
 ['MapScout','MS','slate','Guides',true,false],['CaldwellEnjoyer','CE','bronze','Loadouts',true,true],
 ['BayouNomad','BN','green','Moments',true,false],['FogWalker','FW','slate','Feed',true,false],
 ['MireFox','MF','violet','Cups',false,true],['OldCrow','OC','gold','Guides',false,false],
 ['RotjawRookie','RR','bronze','Teams',false,true],['SparksAndCoffee','SA','green','Feed',false,false],
 ['QuietFootsteps','QF','slate','Maps',false,false],['CrookedLantern','CL','bronze','Feed',false,false]
];
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
'<div class="hnt-hud-heading"><div><h2>Hunter online<span>.</span></h2><p>Wer streift gerade durch die Community?</p></div><div class="hnt-hud-head-icon">',icon('users-three'),'</div></div>',
'<div class="hnt-online-metrics"><span><strong>12</strong> online</span><b></b><span><strong>6</strong> Freunde</span><b></b><span><strong>4</strong> Squad offen</span></div></div>',
'<div class="hnt-online-controls"><div class="hnt-online-tabs" role="group" aria-label="Online-Hunter filtern">',
'<button type="button" class="is-active" data-online-tab="friends" aria-pressed="true">Freunde <em>6</em></button>',
'<button type="button" data-online-tab="all" aria-pressed="false">Alle <em>12</em></button>',
'<button type="button" data-online-tab="squad" aria-pressed="false">Squad offen <em>4</em></button>',
'</div><label class="hnt-online-search">',icon('magnifying-glass'),'<input id="hntOnlineSearch" type="search" autocomplete="off" placeholder="Hunter suchen ..." aria-label="Hunter suchen"></label></div>',
'<div id="hntOnlineList" class="hnt-online-list"></div>',
'<a href="https://hnt.rocks/members" class="hnt-hud-foot"><span>',icon('crosshair'),' HNT.ROCKS COMMUNITY</span><span>Hunter finden ',icon('arrow-up-right'),'</span></a>'
].join('');

const journal=document.createElement('section');journal.id='hunterJournalPanel';journal.className='hnt-hud-panel hnt-journal-panel';journal.hidden=true;journal.setAttribute('role','region');journal.setAttribute('aria-label','Hunter Journal');
journal.innerHTML=[
'<div class="hnt-hud-line is-gold"></div><div class="hnt-hud-head">',
'<div class="hnt-hud-kicker"><i class="hnt-hud-signal"></i> HNT.ROCKS / JOURNAL</div>',
'<div class="hnt-hud-heading"><div><h2>Deine Jagd. Dein Ruf.</h2><p>Community-Aufträge und Auszeichnungen</p></div><div class="hnt-hud-head-icon is-gold">',icon('crosshair'),'</div></div></div>',
'<div class="hnt-journal-content"><section class="hnt-journal-rank"><div class="hnt-journal-rank-head"><div><small>HUNTER-RANG 18</small><div class="hnt-journal-xp"><strong id="hntJournalXP">23.245</strong><span>XP</span></div></div>',
'<div class="hnt-journal-seal">',icon('crosshair'),'<span>HNT</span></div></div>',
'<div class="hnt-journal-track"><span id="hntJournalTrack"></span></div><div class="hnt-journal-rank-caption"><span>Rang 18</span><span id="hntNextLevel">Noch 1.755 XP bis Rang 19</span></div></section>',
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

function renderOnline(){
 const holder=online.querySelector('#hntOnlineList');
 holder.replaceChildren();
 const data=hunters.filter(h=>(tab==='friends'?h[4]:tab==='squad'?h[5]:true)&&h[0].toLowerCase().includes(query));
 if(!data.length){holder.innerHTML='<p class="hnt-online-empty">Keine Hunter gefunden. Probiere einen anderen Namen.</p>';return}
 data.forEach(h=>{
  const row=document.createElement('div');row.className='hnt-online-person';
  const avatar=document.createElement('span');avatar.className='hnt-online-avatar tone-'+h[2];avatar.textContent=h[1];
  const copy=document.createElement('span');copy.className='hnt-online-person-copy';
  const title=document.createElement('strong');title.textContent=h[0];
  const where=document.createElement('small');where.textContent=(h[5]?'Squad offen · ':'')+h[3];
  copy.append(title,where);
  const status=document.createElement('span');status.className='hnt-online-pill'+(h[5]?' seeking':'');status.textContent=h[5]?'SQUAD':'ONLINE';
  row.append(avatar,copy,status);holder.append(row);
 });
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
 const xp=23245+tasks.filter(t=>done.has(t[0])).reduce((s,t)=>s+t[3],0);
 const xpFormat=new Intl.NumberFormat('de-DE');
 journal.querySelector('#hntJournalXP').textContent=xpFormat.format(xp);
 document.getElementById('hunterHudXpValue').textContent=xpFormat.format(xp)+' XP';
 journal.querySelector('#hntJournalTrack').style.width=Math.min(100,(xp-20000)/5000*100)+'%';
 journal.querySelector('#hntNextLevel').textContent='Noch '+xpFormat.format(25000-xp)+' XP bis Rang 19';
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
 if(panel===journal)renderJournal();else renderOnline();
 active=panel;place(panel,trigger);
 trigger.setAttribute('aria-expanded','true');
 panel.classList.add('is-open');
}
onlineToggle.addEventListener('click',()=>open(online,onlineToggle));
xpToggle.addEventListener('click',()=>open(journal,xpToggle));
online.querySelectorAll('[data-online-tab]').forEach(b=>b.addEventListener('click',()=>{
 tab=b.dataset.onlineTab;
 online.querySelectorAll('[data-online-tab]').forEach(t=>{t.classList.toggle('is-active',t===b);t.setAttribute('aria-pressed',String(t===b))});
 renderOnline();
}));
online.querySelector('#hntOnlineSearch').addEventListener('input',e=>{query=e.target.value.trim().toLowerCase();renderOnline()});
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
})();

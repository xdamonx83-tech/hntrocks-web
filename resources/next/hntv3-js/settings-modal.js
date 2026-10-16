/* HNT.ROCKS / Settings V1. A local design-preview overlay, not a Laravel settings API.
   Live /settings links are only intercepted in local file:// HNTv3 previews. */
(()=>{
  'use strict';
  const localPreview=location.protocol==='file:' || location.hostname==='localhost' || location.hostname==='127.0.0.1';
  if(!localPreview)return;
  const icon=n=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+n+'"></use></svg>';
  const key='hnt-v3-settings-preview-v1';
  const defaults={
    language:'de',region:'eu',animations:true,compact:false,avatarY:'50',coverY:'50',favoriteMap:'stillwater',playStyle:'team',
    displayName:'KrispieArmy',about:'Jäger aus Leidenschaft. Taktische Gefechte, Atmosphäre im Bayou und starke Runden im Team.',
    voice:true,mode:'trio',experience:'experienced',temperament:'chill',mentor:true,
    goals:['pvp','bounty','extract'],commentNotifs:true,messageNotifs:true,friendNotifs:true,cupNotifs:true,
    visibility:'public',onlineStatus:true
  };
  const allowed=new Set(Object.keys(defaults));
  const store=()=>{try{const o=JSON.parse(localStorage.getItem(key)||'{}');return {...defaults,...Object.fromEntries(Object.entries(o).filter(([k])=>allowed.has(k)))}}catch{return {...defaults}}};
  let saved=store(),draft={...saved,goals:[...saved.goals]},active='appearance';
  const entries=[
    {id:'appearance',name:'Darstellung',icon:'gear-six',group:'ALLGEMEIN',caption:'Sprache, Layout und Animationen'},
    {id:'language',name:'Sprache & Region',icon:'compass',group:'ALLGEMEIN',caption:'Deine Sprache auf HNT.ROCKS'},
    {id:'profile',name:'Profil',icon:'person-simple-run',group:'HUNTER',caption:'Profilinformationen'},
    {id:'dna',name:'Hunter DNA',icon:'target',group:'HUNTER',caption:'Spielstil und Teampräferenzen'},
    {id:'connections',name:'Verknüpfungen',icon:'game-controller',group:'HUNTER',caption:'Gaming-Plattformen'},
    {id:'notifications',name:'Benachrichtigungen',icon:'bell',group:'KONTO',caption:'Aktivitäten und Hinweise'},
    {id:'privacy',name:'Datenschutz',icon:'shield-check',group:'KONTO',caption:'Sichtbarkeit und Aktivität'},
    {id:'account',name:'Konto & Sicherheit',icon:'gear-six',group:'KONTO',caption:'Dein Konto verwalten'}
  ];
  const escapeHtml=str=>String(str).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const select=(field,opts)=>'<select id="hntSetting-'+field+'" data-hnt-field="'+field+'" class="hnt-settings-select">'+opts.map(([v,s])=>'<option value="'+escapeHtml(v)+'"'+(draft[field]===v?' selected':'')+'>'+escapeHtml(s)+'</option>').join('')+'</select>';
  const input=(field,max)=>'<input class="hnt-settings-input" id="hntSetting-'+field+'" data-hnt-field="'+field+'" maxlength="'+max+'" value="'+escapeHtml(draft[field])+'"/>';
  const textArea=(field,max)=>'<textarea class="hnt-settings-textarea" id="hntSetting-'+field+'" rows="3" data-hnt-field="'+field+'" maxlength="'+max+'">'+escapeHtml(draft[field])+'</textarea>';
  const toggle=(field,name,detail)=>'<div class="hnt-settings-option"><div><strong>'+escapeHtml(name)+'</strong><p>'+escapeHtml(detail)+'</p></div><button type="button" role="switch" class="hnt-settings-toggle" data-hnt-toggle="'+field+'" aria-label="'+escapeHtml(name)+'" aria-checked="'+String(!!draft[field])+'"><span></span></button></div>';
  const card=(title,content,sub)=>'<section class="hnt-settings-card"><div class="hnt-settings-card-head"><h3>'+title+'</h3>'+(sub?'<p>'+sub+'</p>':'')+'</div>'+content+'</section>';
  const field=(name,body,note)=>'<div class="hnt-settings-field"><label for="hntSetting-'+name+'">'+({'displayName':'Spielername','about':'Über mich','favoriteMap':'Lieblingskarte','playStyle':'Spielstil','language':'Sprache','region':'Region','mode':'Bevorzugter Modus','experience':'Erfahrung','temperament':'Temperament','visibility':'Profilsichtbarkeit'}[name]||name)+'</label>'+body+(note?'<p>'+escapeHtml(note)+'</p>':'')+'</div>';
  const pill=txt=>'<span class="hnt-settings-tag">'+escapeHtml(txt)+'</span>';
  const previewInfo='<p class="hnt-settings-info">'+icon('shield-check')+' Diese Seite ist eine lokale Designvorschau. Speichern ändert keine echten HNT.ROCKS-Kontodaten.</p>';
  const goalOpts=[['pvp','PvP'],['bounty','Bounty'],['extract','Extraktion'],['boss','Boss'],['events','Events']];
  function bodyFor(id){
    switch(id){
      case 'appearance':return card('Designstandard', '<div class="hnt-settings-theme">'+
          '<div class="hnt-settings-theme-picture"><span></span><span></span><span></span><i></i></div>'+
          '<div><strong>HNTv3 · Bayou Dark</strong><p>Unser verbindlicher dunkler HNT.ROCKS-Stil mit Exo 2 und grünen Akzenten.</p>'+pill('Aktiv')+'</div></div>',
          'Die Gestaltung bleibt einheitlich mit Feed und Profil.')
        +card('Oberfläche',toggle('animations','Animationen anzeigen','Übergänge und Modal-Effekte in der lokalen Vorschau.')+
          toggle('compact','Kompakte Anzeige','Reduzierte Abstände innerhalb dieses Einstellungsmodals.'))
        +previewInfo;
      case 'language':return card('Sprache & Region',
          '<div class="hnt-settings-fields">'+
          field('language',select('language',[['de','Deutsch'],['en','English'],['es','Español'],['ru','Русский']]),'Die Oberfläche wird hier nicht vollständig übersetzt; das ist eine Vorschau der Auswahl.')+
          field('region',select('region',[['eu','Europa'],['na','Nordamerika'],['sa','Südamerika'],['asia','Asien']]),'Region für dein Community-Profil.')+'</div>')+previewInfo;
      case 'profile':return window.HNTProfileEditorPreview?.render(draft) || card('Hunter-Profil','<p>Profilbearbeitung wird geladen.</p>');
      case 'dna':return card('Spielweise','<div class="hnt-settings-fields hnt-settings-fields-3">'+
          field('mode',select('mode',[['solo','Solo'],['duo','Duo'],['trio','Trio']]))+
          field('experience',select('experience',[['new','Einsteiger'],['experienced','Erfahren'],['veteran','Veteran']]))+
          field('temperament',select('temperament',[['chill','Chill'],['tactical','Taktisch'],['aggressive','Offensiv']]))+'</div>'+
          toggle('voice','Voice aktiv','Du möchtest im Team per Voice kommunizieren.')+
          toggle('mentor','Anfänger willkommen','Du bist offen für Runden mit neuen Huntern.'))+
          card('Deine Ziele','<div class="hnt-settings-goals">'+goalOpts.map(([id,title])=>'<label class="hnt-settings-goal"><input type="checkbox" data-hnt-goal="'+id+'"'+(draft.goals.includes(id)?' checked':'')+'/><span>'+escapeHtml(title)+'</span></label>').join('')+'</div>',
               'Was suchst du bei deinen Hunt-Runden?')+previewInfo;
      case 'connections':return card('Gaming-Konten','<div class="hnt-settings-platforms">'+
          ['Steam','Xbox'].map(name=>'<div class="hnt-settings-platform"><span class="hnt-settings-platform-icon">'+icon('game-controller')+'</span><div><strong>'+name+'</strong><p>Statistik-Verknüpfung über dein Profil verwalten.</p></div><a href="https://hnt.rocks/profile/edit" class="hnt-settings-link">Verwalten '+icon('arrow-up-right')+'</a></div>').join('')+'</div>',
         'Bestehende Integrationen, keine neuen Demo-Anbieter.')+previewInfo;
      case 'notifications':return card('Community',
          toggle('commentNotifs','Kommentare','Hinweise zu Reaktionen und Antworten auf deine Beiträge.')+
          toggle('messageNotifs','Nachrichten','Neue Nachrichten von anderen Huntern.')+
          toggle('friendNotifs','Freundschaftsanfragen','Anfragen und neue Freundschaften.')+
          toggle('cupNotifs','Cups & Events','Neuigkeiten zu Community-Wettbewerben.'))+previewInfo;
      case 'privacy':return card('Sichtbarkeit',field('visibility',select('visibility',[['public','Öffentlich'],['friends','Nur Freunde'],['private','Privat']]),'Diese Auswahl speichert nur den Entwurf dieser Vorschau.')+
          toggle('onlineStatus','Online-Status anzeigen','Andere Hunter können deinen Online-Status sehen.'))+
          card('Deine Daten','<p class="hnt-settings-paragraph">Rechtliche Informationen und Datenschutz findest du in den bestehenden HNT.ROCKS-Seiten.</p><a class="hnt-settings-link" href="https://hnt.rocks/datenschutz">Datenschutz öffnen '+icon('arrow-up-right')+'</a>')+previewInfo;
      case 'account':return card('Account',
          '<div class="hnt-settings-option"><div><strong>Kontodaten und Sicherheit</strong><p>Passwort, Anmeldedaten und Sicherheitsfunktionen bleiben im bestehenden Laravel-Kontosystem.</p></div><a class="hnt-settings-link" data-hnt-settings-external href="https://hnt.rocks/settings">Im CMS öffnen '+icon('arrow-up-right')+'</a></div>')+
          card('Kein Demo-Login','<p class="hnt-settings-paragraph">Dieses Design simuliert weder eine Kontoverknüpfung noch eine Passwortänderung. Für diese Aktionen bleibt die bestehende Live-Anwendung zuständig.</p>')+previewInfo;
      default:return previewInfo;
    }
  }

  const overlay=document.createElement('div');
  overlay.id='hntSettingsOverlay';overlay.className='hnt-post-dialog hnt-settings-overlay';
  overlay.setAttribute('role','dialog');overlay.setAttribute('aria-modal','true');overlay.setAttribute('aria-labelledby','hntSettingsTitle');overlay.hidden=true;
  const grouped=['ALLGEMEIN','HUNTER','KONTO'].map(group=>
    '<div class="hnt-settings-nav-group"><span class="hnt-settings-nav-heading">'+group+'</span>'+
    entries.filter(e=>e.group===group).map(e=>
      '<button type="button" data-hnt-settings-tab="'+e.id+'" class="hnt-settings-nav-item" aria-current="'+(e.id===active?'page':'false')+'">'+icon(e.icon)+'<span>'+e.name+'</span>'+icon('arrow-right')+'</button>').join('')+'</div>').join('');
  overlay.innerHTML='<div class="hnt-pd-panel hnt-settings-panel" role="document">'+
    '<header class="hnt-pd-top hnt-settings-top"><div class="hnt-pd-brand"><span class="hnt-pd-brand-icon">'+icon('gear-six')+'</span><span><small>HNT.ROCKS / ACCOUNT</small><strong id="hntSettingsTitle">Einstellungen</strong></span></div>'+
      '<div class="hnt-settings-top-right"><span class="hnt-settings-preview-label">DESIGNVORSCHAU</span><button type="button" class="hnt-pd-close" id="hntSettingsClose" aria-label="Einstellungen schließen" title="Schließen (Esc)">'+icon('x')+'</button></div></header>'+
    '<div class="hnt-settings-main"><nav class="hnt-settings-nav" aria-label="Einstellungsbereiche">'+grouped+'</nav>'+
      '<section class="hnt-settings-workspace"><header class="hnt-settings-page-head"><span class="hnt-settings-eyebrow">DEIN HUNTER-CENTER</span><h2 id="hntSettingsSectionTitle"></h2><p id="hntSettingsSectionCaption"></p></header>'+
       '<div class="hnt-settings-content" id="hntSettingsContent"></div>'+
       '<footer class="hnt-settings-footer"><span id="hntSettingsFeedback" aria-live="polite" role="status">Änderungen gelten zunächst nur in dieser Vorschau.</span><div><button type="button" class="hnt-settings-secondary" id="hntSettingsDiscard">Verwerfen</button><button type="button" class="btn-compose hnt-settings-save" id="hntSettingsSave">'+icon('check')+' Vorschau speichern</button></div></footer>'+
      '</section></div></div>';
  document.body.append(overlay);
  const closeButton=overlay.querySelector('#hntSettingsClose');
  const content=overlay.querySelector('#hntSettingsContent');
  const workspace=overlay.querySelector('.hnt-settings-workspace');
  const feedback=overlay.querySelector('#hntSettingsFeedback');
  const panel=overlay.querySelector('.hnt-settings-panel');
  let opener=null,oldOverflow='',closing=false,timeout=0,previousHash='';

  const render=()=>{
    const e=entries.find(x=>x.id===active)||entries[0];
    overlay.querySelector('#hntSettingsSectionTitle').textContent=e.name;
    overlay.querySelector('#hntSettingsSectionCaption').textContent=e.caption;
    overlay.querySelectorAll('[data-hnt-settings-tab]').forEach(n=>{
      const on=n.dataset.hntSettingsTab===active;
      n.classList.toggle('is-selected',on);
      n.setAttribute('aria-current',on?'page':'false');
    });
    content.innerHTML=bodyFor(active);
    if(active==='profile')window.HNTProfileEditorPreview?.mount(content,draft);
    overlay.classList.toggle('hnt-settings-compact',draft.compact);
    overlay.classList.toggle('hnt-settings-motion-off',!draft.animations);
    workspace.scrollTop=0;
  };
  function open(tab,source){
    if(!overlay.hidden)return;
    saved=store();draft={...saved,goals:[...saved.goals]};
    opener=source||document.activeElement;
    active=entries.some(x=>x.id===tab)?tab:'appearance';
    closing=false;oldOverflow=document.body.style.overflow;
    document.body.style.overflow='hidden';
    feedback.textContent='Änderungen gelten zunächst nur in dieser Vorschau.';
    overlay.hidden=false;render();
    requestAnimationFrame(()=>{overlay.classList.add('is-open');setTimeout(()=>{if(!overlay.hidden&&!closing)closeButton.focus({preventScroll:true});},90);});
  }
  function close(){
    if(overlay.hidden||closing)return;
    closing=true;overlay.classList.remove('is-open');clearTimeout(timeout);
    timeout=setTimeout(()=>{
      overlay.hidden=true;closing=false;document.body.style.overflow=oldOverflow;
      if(location.hash.startsWith('#settings'))history.replaceState(null,'',location.pathname+location.search+(previousHash||''));
      opener?.focus?.({preventScroll:true});opener=null;
    },matchMedia('(prefers-reduced-motion:reduce)').matches?0:260);
  }
  overlay.querySelectorAll('[data-hnt-settings-tab]').forEach(btn=>btn.addEventListener('click',()=>{
    active=btn.dataset.hntSettingsTab;feedback.textContent='Änderungen gelten zunächst nur in dieser Vorschau.';render();
  }));
  content.addEventListener('input',event=>{
    const el=event.target;
    if(el.dataset.hntField && allowed.has(el.dataset.hntField)){
      draft[el.dataset.hntField]=el.value;feedback.textContent='Nicht gespeicherte Vorschau-Änderungen';
    }
  });
  content.addEventListener('change',event=>{
    const el=event.target;
    if(el.dataset.hntField && allowed.has(el.dataset.hntField))draft[el.dataset.hntField]=el.value;
    if(el.dataset.hntGoal){
      const goals=new Set(draft.goals);
      if(el.checked)goals.add(el.dataset.hntGoal);else goals.delete(el.dataset.hntGoal);
      draft.goals=[...goals];
    }
    feedback.textContent='Nicht gespeicherte Vorschau-Änderungen';
  });
  content.addEventListener('click',event=>{
    const b=event.target.closest('[data-hnt-toggle]');
    if(!b)return;
    const id=b.dataset.hntToggle;
    if(!allowed.has(id))return;
    draft[id]=!draft[id];b.setAttribute('aria-checked',String(draft[id]));
    overlay.classList.toggle('hnt-settings-compact',draft.compact);
    overlay.classList.toggle('hnt-settings-motion-off',!draft.animations);
    feedback.textContent='Nicht gespeicherte Vorschau-Änderungen';
  });
  overlay.querySelector('#hntSettingsSave').addEventListener('click',()=>{
    try{
      localStorage.setItem(key,JSON.stringify(draft));
      saved={...draft,goals:[...draft.goals]};
      window.HNTProfileEditorPreview?.afterSave(saved);
      feedback.textContent=window.HNTProfileEditorPreview?.hasImages()?'Textdaten lokal gespeichert. Bilder bleiben nur bis zum Neuladen als Vorschau sichtbar.':'Lokal gespeichert – dein echtes HNT.ROCKS-Konto bleibt unverändert.';
    }catch{feedback.textContent='Lokales Speichern war nicht möglich. Dein Konto bleibt unverändert.';}
  });
  overlay.querySelector('#hntSettingsDiscard').addEventListener('click',()=>{
    draft={...saved,goals:[...saved.goals]};
    window.HNTProfileEditorPreview?.discard();
    render();feedback.textContent='Nicht gespeicherte Vorschau-Änderungen verworfen.';
  });
  closeButton.addEventListener('click',close);
  overlay.addEventListener('click',event=>{if(event.target===overlay)close();});
  document.addEventListener('keydown',event=>{
    if(overlay.hidden)return;
    if(event.key==='Escape'){event.preventDefault();event.stopImmediatePropagation();close();return;}
    if(event.key!=='Tab')return;
    const focusables=[...overlay.querySelectorAll('button:not([disabled]),a[href],input:not([disabled]),select:not([disabled]),textarea:not([disabled])')].filter(el=>el.getClientRects().length);
    const first=focusables[0],last=focusables[focusables.length-1];
    if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
    else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  },true);
  document.addEventListener('click',event=>{
    const a=event.target.closest('a[href]');
    if(!a||a.hasAttribute('data-hnt-settings-external'))return;
    const href=a.getAttribute('href')||'';
    const section=a.dataset.hntSettingsSection;
    if(section){event.preventDefault();open(section,a);return;}
    if(href==='https://hnt.rocks/profile/edit'||href==='/profile/edit'||href==='profile-edit.html'){
      event.preventDefault();open(a.closest('.hnt-v5-games,.hnt-v5-platform-grid,.hnt-settings-platform')?'connections':'profile',a);return;
    }
    if(href==='https://hnt.rocks/settings'||href==='/settings'||href==='settings.html'){
      event.preventDefault();open(a.closest('#hp-panel-dna,.hnt-dna-dashboard')?'dna':'appearance',a);
    }
  });
  window.HNTSettingsPreview={open:(section='appearance')=>open(section,null),close};
  if(location.hash==='#settings'||location.hash.startsWith('#settings/')){
    const tab=location.hash.split('/')[1]||'appearance';
    previousHash='';
    // Deferred editor module initializes immediately after this file.
    // Open deep links once all deferred scripts have registered their content.
    setTimeout(()=>open(tab,null),0);
  }
  window.addEventListener('hashchange',()=>{
    if(location.hash==='#settings'||location.hash.startsWith('#settings/')){
      if(overlay.hidden)open(location.hash.split('/')[1]||'appearance',null);
    }
  });
})();

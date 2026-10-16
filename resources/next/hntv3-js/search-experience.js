/* HNT.ROCKS / Hunter Search – enriched local navigation command palette.
   Works with the existing searchTrigger, searchInput, searchResults and
   established destination links. No mock web search or invented hits. */
(()=>{
  'use strict';
  const overlay=document.getElementById('searchOverlay');
  const dialog=overlay?.querySelector('.search-dialog');
  const input=document.getElementById('searchInput');
  const results=document.getElementById('searchResults');
  if(!overlay||!dialog||!input||!results)return;
  const ph=name=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+name+'"></use></svg>';
  const descriptors={
    Feed:{category:'Community',group:'Community',icon:'house',sub:'Beiträge & Neuigkeiten aus dem Bayou',keys:'beiträge posts'},
    Moments:{category:'Community',group:'Community',icon:'film-strip',sub:'Clips und Highlights',keys:'video clips'},
    Maps:{category:'Entdecken',group:'Entdecken',icon:'map-trifold',sub:'Interaktive Hunt: Showdown Karten',keys:'karte karten stillwater lawson desalle mammons'},
    Guides:{category:'Entdecken',group:'Entdecken',icon:'book-open',sub:'Kartenwissen, Strategien & Tipps',keys:'anleitungen hilfe'},
    Cups:{category:'Wettbewerbe',group:'Wettbewerbe',icon:'trophy',sub:'Turniere und Community-Cups',keys:'last blood tournament'},
    'Ready Lobbies':{category:'Community',group:'Community',icon:'users-three',sub:'Mitstreiter für deine nächste Jagd',keys:'mitspieler squad lobby'},
    Teams:{category:'Community',group:'Community',icon:'users-three',sub:'Dein Squad und die Community',keys:'squad partner'},
    Mitglieder:{category:'Community',group:'Community',icon:'user-circle',sub:'Hunter und Spielerprofile',keys:'user spieler freunde friends hunter'},
    Rocks:{category:'Wettbewerbe',group:'Wettbewerbe',icon:'star',sub:'Rocks, Rewards und Belohnungen',keys:'punkte belohnung gamification xp'},
    'Hall of Fame':{category:'Wettbewerbe',group:'Wettbewerbe',icon:'medal',sub:'Die besten Hunter der Community',keys:'rank leaderboard'}
  };
  const defaults=[
    ['Feed','https://hnt.rocks/feed'],['Moments','https://hnt.rocks/moments'],
    ['Maps','https://hnt.rocks/maps'],['Guides','https://hnt.rocks/guides'],
    ['Cups','https://hnt.rocks/cups'],['Ready Lobbies','https://hnt.rocks/ready-lobbies'],
    ['Teams','https://hnt.rocks/teams'],['Mitglieder','https://hnt.rocks/members'],
    ['Rocks','https://hnt.rocks/rocks'],['Hall of Fame','https://hnt.rocks/hall-of-fame']
  ];
  const sources=(typeof destinations!=='undefined'&&Array.isArray(destinations))
    ? destinations : defaults.map(([name,href])=>({name,href}));
  const entries=sources
    .filter(x=>x&&descriptors[x.name]&&/^https:\/\/hnt\.rocks\//.test(x.href))
    .map(x=>({...x,...descriptors[x.name]}));
  const tabs=[
    {key:'all',text:'Alle'},
    {key:'Community',text:'Community'},
    {key:'Entdecken',text:'Entdecken'},
    {key:'Wettbewerbe',text:'Wettbewerbe'}
  ];
  let filter='all',activeIndex=-1,lastTrigger=null;
  dialog.classList.add('hnt-search-dialog');
  dialog.setAttribute('aria-label','HNT.ROCKS durchsuchen');
  dialog.setAttribute('aria-labelledby','hntSearchHeading');

  const searchField=dialog.querySelector('.search-field');
  const originalEsc=searchField?.querySelector('.esc-hint');
  const head=document.createElement('div');
  head.className='hnt-search-head';
  head.innerHTML=
    '<div class="hnt-search-heading">'+
      '<span class="hnt-search-brand-icon">'+ph('crosshair')+'</span>'+
      '<span class="hnt-search-head-copy"><small>HNT.ROCKS / COMMUNITY</small><strong id="hntSearchHeading">Suche</strong></span>'+
      '<button type="button" class="hnt-search-close" id="hntSearchClose" aria-label="Suche schließen">'+ph('x')+'</button>'+
    '</div>';
  const filters=document.createElement('div');
  filters.className='hnt-search-filter-row';
  const filterWrap=document.createElement('div');
  filterWrap.className='hnt-search-filters';
  filterWrap.setAttribute('role','group');filterWrap.setAttribute('aria-label','Suchbereich filtern');
  for(const t of tabs){
    const button=document.createElement('button');
    button.type='button';button.dataset.searchFilter=t.key;
    button.textContent=t.text;button.setAttribute('aria-pressed',String(t.key===filter));
    if(t.key===filter)button.classList.add('is-active');
    filterWrap.append(button);
  }
  filters.append(filterWrap);
  const resultsHeading=document.createElement('div');
  resultsHeading.className='hnt-search-results-heading';
  resultsHeading.innerHTML='<span id="hntSearchResultTitle">SCHNELLZUGRIFF</span><small id="hntSearchCount">10 ZIELE</small>';
  const foot=document.createElement('div');
  foot.className='hnt-search-footer';
  foot.innerHTML='<span>'+ph('crosshair')+' HNT.ROCKS / COMMUNITY</span>'+
    '<span class="hnt-search-shortcuts"><kbd>↑</kbd><kbd>↓</kbd> Wählen <kbd>↵</kbd> Öffnen <kbd>Esc</kbd> Schließen</span>';
  const accent=document.createElement('div');accent.className='hnt-search-accent';
  dialog.replaceChildren(accent,head,searchField,filters,resultsHeading,results,foot);
  searchField.classList.add('hnt-search-field');
  input.placeholder='Seiten, Karten, Guides oder Hunter ...';
  input.setAttribute('aria-label','HNT.ROCKS Suche');
  input.setAttribute('aria-autocomplete','list');
  input.setAttribute('aria-controls','searchResults');
  results.setAttribute('role','listbox');
  originalEsc?.remove();
  const shortcut=document.createElement('span');
  shortcut.className='hnt-search-input-shortcut';
  shortcut.innerHTML='<kbd>/</kbd><span>SUCHEN</span>';
  searchField.append(shortcut);

  const closeButton=document.getElementById('hntSearchClose');
  const normalize=str=>String(str??'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
  const toUrl=entry=>entry.href;
  const getLinks=()=>[...results.querySelectorAll('.hnt-search-result')];
  function setActive(next){
    const links=getLinks();
    if(!links.length){activeIndex=-1;return}
    activeIndex=Math.max(0,Math.min(links.length-1,next));
    links.forEach((a,i)=>{
      a.classList.toggle('is-highlighted',i===activeIndex);
      a.setAttribute('aria-selected',String(i===activeIndex));
    });
    links[activeIndex].scrollIntoView({block:'nearest'});
    input.setAttribute('aria-activedescendant',links[activeIndex].id);
  }
  function render(){
    const q=normalize(input.value.trim());
    const filtered=entries.filter(e=>(filter==='all'||e.group===filter)
      && (!q||normalize(e.name+' '+e.sub+' '+e.keys+' '+e.category).includes(q)));
    results.replaceChildren();
    resultsHeading.querySelector('#hntSearchResultTitle').textContent=q?'SUCHERGEBNISSE':'SCHNELLZUGRIFF';
    resultsHeading.querySelector('#hntSearchCount').textContent=filtered.length+' '+(filtered.length===1?'ZIEL':'ZIELE');
    activeIndex=-1;input.removeAttribute('aria-activedescendant');
    if(!filtered.length){
      const empty=document.createElement('div');
      empty.className='hnt-search-empty';
      empty.innerHTML='<span>'+ph('magnifying-glass')+'</span><strong>Nichts gefunden</strong><p>Versuche einen anderen Begriff oder wechsle den Suchbereich.</p>';
      results.append(empty);
      return;
    }
    // Show the most familiar navigation destinations first; no fabricated user or post results.
    filtered.forEach((e,i)=>{
      const a=document.createElement('a');a.className='search-result hnt-search-result';a.href=toUrl(e);
      a.id='hntSearchHit'+i;
      a.setAttribute('role','option');a.setAttribute('aria-selected','false');
      const mark=document.createElement('span');mark.className='hnt-search-result-icon';mark.innerHTML=ph(e.icon);
      const copy=document.createElement('span');copy.className='hnt-search-result-copy';
      const title=document.createElement('strong');title.textContent=e.name;
      const sub=document.createElement('small');sub.textContent=e.sub;
      copy.append(title,sub);
      const side=document.createElement('span');side.className='hnt-search-result-side';
      const cat=document.createElement('em');cat.textContent=e.category;
      side.append(cat);
      const arrow=document.createElement('span');arrow.className='hnt-search-result-arrow';arrow.innerHTML=ph('arrow-up-right');
      a.append(mark,copy,side,arrow);
      a.addEventListener('pointerenter',()=>{activeIndex=i;highlight(i)});
      results.append(a);
    });
  }
  function highlight(n){
    const links=getLinks();
    links.forEach((a,i)=>{a.classList.toggle('is-highlighted',i===n);a.setAttribute('aria-selected',String(i===n))});
    const target=links[n];if(target)input.setAttribute('aria-activedescendant',target.id);
  }
  function setFilter(next){
    filter=next;
    filterWrap.querySelectorAll('button').forEach(btn=>{
      const selected=btn.dataset.searchFilter===next;
      btn.classList.toggle('is-active',selected);btn.setAttribute('aria-pressed',String(selected));
    });
    render();
  }
  filterWrap.addEventListener('click',event=>{
    const btn=event.target.closest('[data-search-filter]');
    if(btn)setFilter(btn.dataset.searchFilter);
  });
  function close(){
    overlay.hidden=true;
    lastTrigger?.focus?.({preventScroll:true});
    lastTrigger=null;
  }
  closeButton.addEventListener('click',close);
  // Legacy input uses bubbling listeners; capture keeps the new categorized renderer.
  input.addEventListener('input',e=>{e.stopImmediatePropagation();render()},{capture:true});
  input.addEventListener('keydown',e=>{
    if(e.key==='ArrowDown'||e.key==='ArrowUp'){
      e.preventDefault();
      const links=getLinks();
      if(links.length)setActive(activeIndex<0?(e.key==='ArrowDown'?0:links.length-1):(activeIndex+(e.key==='ArrowDown'?1:-1)+links.length)%links.length);
    }else if(e.key==='Enter'){
      const links=getLinks();
      if(!links.length)return;
      e.preventDefault();
      const link=links[activeIndex>=0?activeIndex:0];
      if(link)window.location.assign(link.href);
    }
  });
  // Rebuild the layout when the original showSearch() removes [hidden].
  const observer=new MutationObserver(()=>{
    if(overlay.hidden){
      overlay.classList.remove('hnt-search-visible');
      input.removeAttribute('aria-activedescendant');
      return;
    }
    lastTrigger=lastTrigger||document.getElementById('searchTrigger');
    filter='all';
    filterWrap.querySelectorAll('button').forEach(btn=>{
      const active=btn.dataset.searchFilter==='all';
      btn.classList.toggle('is-active',active);btn.setAttribute('aria-pressed',String(active));
    });
    render();
    overlay.classList.remove('hnt-search-visible');
    void dialog.offsetWidth;
    overlay.classList.add('hnt-search-visible');
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'search'}}));
  });
  observer.observe(overlay,{attributes:true,attributeFilter:['hidden']});
  document.addEventListener('keydown',e=>{
    if(overlay.hidden||e.key!=='Tab')return;
    const focusables=[...dialog.querySelectorAll('input,button,a[href]')]
      .filter(el=>el.getClientRects().length&&!el.disabled);
    if(!focusables.length)return;
    const first=focusables[0],last=focusables.at(-1);
    if(e.shiftKey&&document.activeElement===first){e.preventDefault();last.focus()}
    else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first.focus()}
  },true);
  render();
})();
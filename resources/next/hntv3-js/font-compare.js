/* HNT.ROCKS — Schriftlabor v2 / 32 live font choices.
 * Loads newly selected Google Fonts on demand instead of downloading all families.
 * Font selection is stored locally. No production settings are changed.
 */
(() => {
  'use strict';

  const fonts = [
    // Balanced modern sans-serif
    {id:'ibm', name:'IBM Plex Sans', category:'klar', note:'Ruhig, präzise, sehr gut lesbar'},
    {id:'inter', name:'Inter', category:'klar', note:'Modern und neutral'},
    {id:'manrope', name:'Manrope', category:'klar', note:'Klar mit weichen Formen'},
    {id:'dm-sans', name:'DM Sans', category:'klar', note:'Unaufgeregt, sauber'},
    {id:'jakarta', name:'Plus Jakarta Sans', category:'klar', note:'Modern, etwas charaktervoller'},
    {id:'outfit', name:'Outfit', category:'klar', note:'Geometrisch, gut lesbar'},
    {id:'rubik', name:'Rubik', category:'klar', note:'Rund und griffig'},
    {id:'source', name:'Source Sans 3', category:'klar', note:'Sehr gut für längere Texte'},
    {id:'work', name:'Work Sans', category:'klar', note:'Solide und vielseitig'},
    {id:'public', name:'Public Sans', category:'klar', note:'Schlicht und zuverlässig'},
    {id:'geist', name:'Geist', category:'klar', note:'Geradliniger UI-Stil'},
    {id:'archivo', name:'Archivo', category:'klar', note:'Klar, sportlich, ausgewogen'},

    // Compact families
    {id:'roboto', name:'Roboto Condensed', category:'kompakt', note:'Dein aktueller Favorit'},
    {id:'ibm-condensed', name:'IBM Plex Sans Condensed', category:'kompakt', note:'Kompakt, angenehm lesbar'},
    {id:'barlow', name:'Barlow Semi Condensed', category:'kompakt', note:'Frühere Variante'},
    {id:'archivo-narrow', name:'Archivo Narrow', category:'kompakt', note:'Schlank und funktional'},
    {id:'fira-condensed', name:'Fira Sans Condensed', category:'kompakt', note:'Kompakt mit Charakter'},
    {id:'titillium', name:'Titillium Web', category:'kompakt', note:'Leicht industrieller Look'},
    {id:'oswald', name:'Oswald', category:'kompakt', note:'Prägnant, schmal, markant'},
    {id:'barlow-condensed', name:'Barlow Condensed', category:'kompakt', note:'Deutlich schmaler'},
    {id:'asap-condensed', name:'Asap Condensed', category:'kompakt', note:'Zurückhaltend und robust'},
    {id:'encode-condensed', name:'Encode Sans Condensed', category:'kompakt', note:'Kompakt und technisch'},

    // Character and gaming
    {id:'space', name:'Space Grotesk', category:'markant', note:'Eigenständig und geometrisch'},
    {id:'exo', name:'Exo 2', category:'markant', note:'Modern, dezent futuristisch'},
    {id:'chakra', name:'Chakra Petch', category:'markant', note:'Mechanisch, Gaming-Stil'},
    {id:'oxanium', name:'Oxanium', category:'markant', note:'Technisch und kantig'},
    {id:'sora', name:'Sora', category:'markant', note:'Kräftig, modern, geometrisch'},
    {id:'kanit', name:'Kanit', category:'markant', note:'Charakterstark und stabil'},
    {id:'urbanist', name:'Urbanist', category:'markant', note:'Elegant und geometrisch'},
    {id:'jost', name:'Jost', category:'markant', note:'Offen, angenehm breit'},
    {id:'lexend', name:'Lexend', category:'markant', note:'Sehr offen und lesefreundlich'},
    {id:'rajdhani', name:'Rajdhani', category:'markant', note:'Kantig und schmal'}
  ];

  const groups = [
    {id:'alle', name:'Alle'},
    {id:'klar', name:'Klar'},
    {id:'kompakt', name:'Kompakt'},
    {id:'markant', name:'Markant'}
  ];
  const groupNames = {klar:'KLAR & AUSGEWOGEN',kompakt:'KOMPAKT & CONDENSED',markant:'MARKANT & GAMING'};
  const keys = new Map(fonts.map(x => [x.id,x]));
  // Fresh preference key: visitors start with Exo 2, not an old experiment.
  const storageKey='hnt-theme-font-lab-exo2';
  const html=document.documentElement;
  const knownLinks=new Set();
  const fontLabEnabled=new URLSearchParams(location.search).get('fontlab')==='1';
  html.dataset.hntFontlab=fontLabEnabled?'1':'0';
  let initial='exo';

  // Preview choices never change the normal Exo 2 default.
  // Remember a comparison choice only while the explicitly opened lab is active.
  if(fontLabEnabled){
    try{
      const params=new URLSearchParams(location.search);
      const fromUrl=params.get('font');
      const saved=localStorage.getItem(storageKey);
      if(keys.has(fromUrl))initial=fromUrl;
      else if(keys.has(saved))initial=saved;
    }catch{}
  }

  const fontStack = profile => '"' + profile.name.replace(/"/g,'') + '", Arial, sans-serif';

  function loadFont(profile) {
    if(!profile || knownLinks.has(profile.id))return;
    knownLinks.add(profile.id);
    // Exo 2 is already requested in the document head as the default face.
    if(profile.id==='exo' && document.querySelector('link[data-hnt-standard-font="exo"]'))return;
    // CSS2 syntax is supported by all choices in this catalog.
    const link=document.createElement('link');
    link.rel='stylesheet';
    link.dataset.hntFontRequest=profile.id;
    link.href='https://fonts.googleapis.com/css2?family='
      +encodeURIComponent(profile.name).replace(/%20/g,'+')
      +':wght@400;500;600;700&display=swap';
    link.addEventListener('load',()=>{
      if(html.dataset.hntFont===profile.id && document.fonts) {
        document.fonts.load('600 16px "'+profile.name+'"').catch(()=>{});
      }
    },{once:true});
    document.head.append(link);
  }

  function applyFont(id,remember=true) {
    const profile=keys.get(id);
    if(!profile)return;
    loadFont(profile);
    html.dataset.hntFont=id;
    html.style.setProperty('--hnt-preview-font',fontStack(profile));
    if(remember){
      try{localStorage.setItem(storageKey,id)}catch{}
    }
  }
  applyFont(initial,false);

  function mount() {
    if(document.getElementById('hntFontLab'))return;
    const host=document.createElement('aside');
    host.id='hntFontLab';
    host.setAttribute('aria-label','Schriftarten vergleichen');

    const trigger=document.createElement('button');
    trigger.className='hnt-font-lab-trigger';
    trigger.type='button';
    trigger.title='Schriftarten testen';
    trigger.setAttribute('aria-expanded','false');
    trigger.setAttribute('aria-controls','hntFontLabPanel');
    trigger.innerHTML='<span class="font-lab-aa" aria-hidden="true">Aa</span><span class="font-lab-text">Schrift testen</span><span class="font-lab-count">'+fonts.length+' Fonts</span>';

    const panel=document.createElement('div');
    panel.className='hnt-font-lab-panel';
    panel.id='hntFontLabPanel';
    panel.hidden=true;

    const head=document.createElement('header');
    head.className='hnt-font-lab-head';
    head.innerHTML=
      '<div class="hnt-font-lab-eyebrow">HNT.ROCKS / SCHRIFTLABOR</div>'+
      '<div class="hnt-font-lab-title-row"><h3 class="hnt-font-lab-title">Schriften vergleichen</h3>'+
      '<span class="hnt-font-lab-current" aria-live="polite"></span></div>'+
      '<p class="hnt-font-lab-desc">Wähle eine Schrift. Du siehst die Änderung sofort im ganzen Theme.</p>';

    const sample=document.createElement('div');
    sample.className='hnt-font-lab-sample';
    sample.innerHTML=
      '<div class="hnt-font-lab-sample-head"><strong>LIVE-VORSCHAU</strong><small>Überschrift + Fließtext</small></div>'+
      '<p class="hnt-font-lab-sample-title">Der Bayou wartet.</p>'+
      '<p class="hnt-font-lab-sample-copy">Neuer Kassenspot bei Seven Sisters Estate – Ä, Ö, Ü und ß.</p>';

    const tools=document.createElement('div');
    tools.className='hnt-font-lab-tools';
    const searchBox=document.createElement('label');
    searchBox.className='hnt-font-lab-search';
    searchBox.innerHTML='<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="10.8" cy="10.8" r="6.6"/><path d="m16 16 5 5"/></svg>';
    const search=document.createElement('input');
    search.type='search';search.autocomplete='off';search.spellcheck=false;
    search.placeholder='Schrift suchen ...';
    search.setAttribute('aria-label','Schriftarten durchsuchen');
    searchBox.append(search);
    const filters=document.createElement('div');
    filters.className='hnt-font-lab-filters';
    filters.setAttribute('aria-label','Stilgruppen');
    for(const group of groups){
      const b=document.createElement('button');
      b.type='button';b.className='hnt-font-lab-filter';
      b.dataset.category=group.id;
      b.setAttribute('aria-pressed',String(group.id==='alle'));
      b.textContent=group.name+' ('+(group.id==='alle'?fonts.length:fonts.filter(f=>f.category===group.id).length)+')';
      filters.append(b);
    }
    tools.append(searchBox,filters);

    const total=document.createElement('div');
    total.className='hnt-font-lab-total';
    const totalText=document.createElement('span');
    const selectedText=document.createElement('span');
    total.append(totalText,selectedText);

    const list=document.createElement('div');
    list.className='hnt-font-lab-list';
    list.setAttribute('role','group');
    list.setAttribute('aria-label','Schriftarten auswählen');

    const foot=document.createElement('footer');
    foot.className='hnt-font-lab-footer';
    foot.textContent='Auswahl wird im Browser gespeichert · Nur lokales Theme';

    panel.append(head,sample,tools,total,list,foot);
    host.append(trigger,panel);
    document.body.append(host);

    let currentCategory='alle',searchTerm='';
    function setOpen(open){
      panel.hidden=!open;
      trigger.setAttribute('aria-expanded',String(open));
      if(open)draw();
    }
    function normalize(s){
      return s.toLocaleLowerCase('de').normalize('NFKD').replace(/[\u0300-\u036f]/g,'');
    }
    function draw(){
      list.replaceChildren();
      const shown=fonts.filter(font=>(currentCategory==='alle'||font.category===currentCategory)
         && (!searchTerm||normalize(font.name+' '+font.note+' '+font.category).includes(normalize(searchTerm))));
      totalText.textContent=shown.length+' von '+fonts.length+' Schriftarten';
      selectedText.textContent='Aktiv: '+(keys.get(html.dataset.hntFont)?.name||'');
      head.querySelector('.hnt-font-lab-current').textContent=keys.get(html.dataset.hntFont)?.name||'';
      if(!shown.length){
        const none=document.createElement('div');none.className='hnt-font-lab-empty';
        none.textContent='Keine passenden Schriften gefunden.';
        list.append(none);
        return;
      }
      let lastGroup='';
      for(const profile of shown){
        if(currentCategory==='alle' && !searchTerm && lastGroup!==profile.category){
          lastGroup=profile.category;
          const label=document.createElement('div');
          label.className='hnt-font-lab-group';
          label.textContent=groupNames[profile.category];
          list.append(label);
        }
        const b=document.createElement('button');
        b.className='hnt-font-lab-option';b.type='button';
        b.dataset.font=profile.id;
        const selected=html.dataset.hntFont===profile.id;
        b.setAttribute('aria-pressed',String(selected));
        const aa=document.createElement('span');
        aa.className='hnt-font-lab-glyph';aa.textContent='Aa';
        const copy=document.createElement('span');
        copy.className='hnt-font-lab-copy';
        const title=document.createElement('strong');title.textContent=profile.name;
        const note=document.createElement('small');note.textContent=profile.note;
        copy.append(title,note);
        const mark=document.createElement('span');mark.className='hnt-font-lab-check';
        mark.textContent=selected?'✓':'';mark.setAttribute('aria-hidden','true');
        b.append(aa,copy,mark);
        b.addEventListener('click',()=>{
          applyFont(profile.id);
          draw();
        });
        list.append(b);
      }
    }

    trigger.addEventListener('click',()=>setOpen(panel.hidden));
    search.addEventListener('input',()=>{
      searchTerm=search.value.trim();
      draw();
    });
    filters.addEventListener('click',e=>{
      const filter=e.target.closest('[data-category]');
      if(!filter)return;
      currentCategory=filter.dataset.category;
      filters.querySelectorAll('[data-category]').forEach(item=>item.setAttribute('aria-pressed',String(item===filter)));
      draw();
    });
    document.addEventListener('pointerdown',e=>{
      if(!host.contains(e.target))setOpen(false);
    });
    document.addEventListener('keydown',e=>{
      if(e.key==='Escape'&&!panel.hidden){
        e.stopPropagation();
        setOpen(false);
        trigger.focus({preventScroll:true});
      }
    });
    // Only the selected font is requested. The catalog labels use Inter.
  }

  if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',mount,{once:true});
  else mount();
})();

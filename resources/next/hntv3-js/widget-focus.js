/* HNT.ROCKS / Feed-Widgets
   Desktop: einklappbare Sidebar und Fokus-Modus.
   Mobile/Tablet: echtes, separat scrollbarers Hunter-Desk-Panel, kein Widget-Ende im Feed. */
(()=>{
  'use strict';
  const main=document.querySelector('.feed-main');
  const layout=main?.querySelector('.feed-layout');
  const aside=layout?.querySelector('.feed-widgets');
  const actions=main?.querySelector('.feed-heading-actions');
  if(!main||!layout||!aside||!actions)return;

  const storageKey='hnt.feed.widgets.collapsed.v1';
  const mq=window.matchMedia('(max-width:990px)');
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const icon=(id)=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+id+'"></use></svg>';
  const widgetCount=aside.querySelectorAll('.widget').length;
  aside.id='hntFeedWidgets';

  /* Desktop controls: keep the existing look. */
  const hideButton=document.createElement('button');
  hideButton.type='button';
  hideButton.id='hntHideWidgets';
  hideButton.className='hnt-widget-hide';
  hideButton.setAttribute('aria-label','Rechte Widgets ausblenden und Feed vergrößern');
  hideButton.setAttribute('aria-controls',aside.id);
  hideButton.title='Widgets ausblenden · Mehr Platz für Beiträge';
  hideButton.innerHTML=icon('arrow-right')+'<span>Widgets ausblenden</span>';
  const newPostBtn=actions.querySelector('#headerPostBtn');
  if(newPostBtn)actions.insertBefore(hideButton,newPostBtn);
  else actions.append(hideButton);

  const restore=document.createElement('button');
  restore.type='button';
  restore.id='hntShowWidgets';
  restore.className='hnt-widget-restore';
  restore.setAttribute('aria-label','Rechte Widgets wieder anzeigen');
  restore.setAttribute('aria-controls',aside.id);
  restore.title='Widgets anzeigen';
  restore.innerHTML='<span class="hnt-widget-restore-icon">'+icon('arrow-left')+'</span><span class="hnt-widget-restore-copy">Widgets</span><span class="hnt-widget-restore-count">'+widgetCount+'</span>';
  document.body.append(restore);

  let desktopCollapsed=false;
  try{desktopCollapsed=localStorage.getItem(storageKey)==='1'}catch{}
  let desktopRevealTimer=0;
  function setDesktopCollapsed(collapsed,{save=true,animate=true}={}){
    desktopCollapsed=collapsed;
    clearTimeout(desktopRevealTimer);
    main.classList.toggle('hnt-widgets-collapsed',collapsed);
    layout.classList.toggle('hnt-widgets-collapsed',collapsed);
    main.classList.toggle('hnt-widget-no-motion',!animate||reduced);
    aside.inert=collapsed;
    aside.setAttribute('aria-hidden',String(collapsed));
    hideButton.hidden=collapsed;
    hideButton.setAttribute('aria-expanded',String(!collapsed));
    restore.hidden=!collapsed;
    restore.setAttribute('aria-expanded',String(!collapsed));
    main.classList.toggle('hnt-widgets-revealing',!collapsed&&animate&&!reduced);
    if(!collapsed&&animate&&!reduced){
      desktopRevealTimer=window.setTimeout(()=>main.classList.remove('hnt-widgets-revealing'),650);
    }
    if(save){try{localStorage.setItem(storageKey,collapsed?'1':'0')}catch{}}
    if(!animate&&!reduced){
      requestAnimationFrame(()=>requestAnimationFrame(()=>main.classList.remove('hnt-widget-no-motion')));
    }
  }
  hideButton.addEventListener('click',()=>{
    if(mq.matches)return;
    setDesktopCollapsed(true);
    restore.focus({preventScroll:true});
  });
  restore.addEventListener('click',()=>{
    if(mq.matches)return;
    setDesktopCollapsed(false);
    hideButton.focus({preventScroll:true});
  });

  /* Mobile: reuse the actual widget nodes, do not duplicate DOM, listeners or IDs. */
  const placeholder=document.createComment('HNT.ROCKS Widget position');
  layout.insertBefore(placeholder,aside);

  const scrim=document.createElement('div');
  scrim.id='hntMobileWidgetsScrim';
  scrim.className='hnt-mobile-widget-scrim';
  scrim.setAttribute('aria-hidden','true');

  const sheet=document.createElement('section');
  sheet.id='hntMobileWidgetsSheet';
  sheet.className='hnt-mobile-widget-sheet';
  sheet.setAttribute('role','dialog');
  sheet.setAttribute('aria-modal','true');
  sheet.setAttribute('aria-labelledby','hntMobileWidgetsHeading');
  sheet.setAttribute('aria-hidden','true');
  sheet.inert=true;

  const header=document.createElement('div');
  header.className='hnt-mobile-widget-head';
  header.innerHTML=
    '<div class="hnt-mobile-widget-eyebrow"><i></i> HNT.ROCKS / COMMUNITY DESK <small>'+widgetCount+' MODULE</small></div>'+
    '<div class="hnt-mobile-widget-heading"><span class="hnt-mobile-widget-heading-icon">'+icon('crosshair')+'</span>'+
      '<div class="hnt-mobile-widget-titles"><h2 id="hntMobileWidgetsHeading">Dein Hunter Desk<span>.</span></h2>'+
      '<p>Alles aus deiner Community auf einen Blick.</p></div>'+
      '<button type="button" class="hnt-mobile-widget-close" id="hntCloseMobileWidgets" aria-label="Widgets schließen">'+icon('x')+'</button>'+
    '</div>';
  sheet.append(header);
  document.body.append(scrim,sheet);
  const closeButton=header.querySelector('#hntCloseMobileWidgets');

  const mobileFab=document.createElement('button');
  mobileFab.id='hntMobileWidgetsFab';
  mobileFab.type='button';
  mobileFab.className='hnt-mobile-widget-fab';
  mobileFab.hidden=true;
  mobileFab.setAttribute('aria-label','Hunter Desk öffnen: '+widgetCount+' Widgets');
  mobileFab.setAttribute('aria-controls',sheet.id);
  mobileFab.setAttribute('aria-expanded','false');
  mobileFab.innerHTML=
    '<span class="hnt-mobile-widget-fab-symbol">'+icon('crosshair')+'</span>'+
    '<span class="hnt-mobile-widget-fab-label">Widgets</span>'+
    '<span class="hnt-mobile-widget-fab-count">'+widgetCount+'</span>';
  document.body.append(mobileFab);

  let mobileMode=false;
  let mobileOpen=false;
  let scrollY=0;
  let previousBodyStyles=null;
  function lockPage(){
    scrollY=window.scrollY;
    const s=document.body.style;
    previousBodyStyles={position:s.position,top:s.top,left:s.left,right:s.right,width:s.width,overflow:s.overflow};
    s.position='fixed';s.top=-scrollY+'px';s.left='0';s.right='0';s.width='100%';s.overflow='hidden';
  }
  function unlockPage(){
    if(!previousBodyStyles)return;
    const s=document.body.style;
    Object.assign(s,previousBodyStyles);
    previousBodyStyles=null;
    window.scrollTo({top:scrollY,left:0,behavior:'instant'});
  }
  function openMobile(){
    if(!mobileMode||mobileOpen)return;
    mobileOpen=true;
    mobileFab.setAttribute('aria-expanded','true');
    mobileFab.setAttribute('aria-label','Hunter Desk geöffnet');
    lockPage();
    sheet.inert=false;
    aside.inert=false;
    sheet.setAttribute('aria-hidden','false');
    aside.setAttribute('aria-hidden','false');
    scrim.classList.add('is-open');
    sheet.classList.add('is-open');
    document.documentElement.classList.add('hnt-mobile-widgets-open');
    aside.scrollTop=0;
    closeButton.focus({preventScroll:true});
  }
  function closeMobile({restoreFocus=true}={}){
    if(!mobileOpen)return;
    mobileOpen=false;
    mobileFab.setAttribute('aria-expanded','false');
    mobileFab.setAttribute('aria-label','Hunter Desk öffnen: '+widgetCount+' Widgets');
    if(restoreFocus&&mobileMode)mobileFab.focus({preventScroll:true});
    sheet.classList.remove('is-open');
    scrim.classList.remove('is-open');
    document.documentElement.classList.remove('hnt-mobile-widgets-open');
    sheet.inert=true;
    aside.inert=true;
    sheet.setAttribute('aria-hidden','true');
    aside.setAttribute('aria-hidden','true');
    unlockPage();
  }
  function syncViewport(){
    const onMobile=mq.matches;
    if(mobileMode===onMobile)return;
    if(onMobile){
      mobileMode=true;
      clearTimeout(desktopRevealTimer);
      main.classList.remove('hnt-widgets-collapsed','hnt-widgets-revealing','hnt-widget-no-motion');
      layout.classList.remove('hnt-widgets-collapsed');
      sheet.append(aside);
      aside.inert=true;
      aside.setAttribute('aria-hidden','true');
      mobileFab.hidden=false;
      restore.hidden=true;
      hideButton.hidden=true;
    }else{
      closeMobile({restoreFocus:false});
      mobileMode=false;
      mobileFab.hidden=true;
      layout.insertBefore(aside,placeholder);
      setDesktopCollapsed(desktopCollapsed,{save:false,animate:false});
    }
  }
  mobileFab.addEventListener('click',openMobile);
  closeButton.addEventListener('click',()=>closeMobile());
  scrim.addEventListener('click',()=>closeMobile());
  document.addEventListener('keydown',event=>{
    if(!mobileOpen)return;
    // A dialog above the Hunter Desk owns Escape and Tab while it is visible.
    if(document.querySelector('#hntPrestigeOverlay:not([hidden]), #hntPostDialog.is-open, #composerOverlay:not([hidden])'))return;
    if(event.key==='Escape'){
      event.preventDefault();
      event.stopImmediatePropagation();
      closeMobile();
    }else if(event.key==='Tab'){
      const focusables=Array.from(sheet.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),textarea:not([disabled]),select:not([disabled]),[tabindex]:not([tabindex="-1"])'))
        .filter(el=>!el.inert&&el.getClientRects().length);
      if(!focusables.length)return;
      const first=focusables[0],last=focusables[focusables.length-1];
      if(!sheet.contains(document.activeElement)){
        event.preventDefault();
        (event.shiftKey?last:first).focus();
        return;
      }
      if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus()}
      else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus()}
    }
  },true);
  /* A short rightward swipe on the stationary header closes the drawer;
     vertical scrolling inside the widget body remains untouched. */
  let startTouch=null;
  header.addEventListener('touchstart',e=>{
    if(e.touches.length===1)startTouch={x:e.touches[0].clientX,y:e.touches[0].clientY};
  },{passive:true});
  header.addEventListener('touchend',e=>{
    if(!startTouch||!mobileOpen)return;
    const dx=e.changedTouches[0].clientX-startTouch.x;
    const dy=e.changedTouches[0].clientY-startTouch.y;
    startTouch=null;
    if(dx>85&&Math.abs(dy)<50)closeMobile();
  },{passive:true});

  mq.addEventListener('change',syncViewport);
  setDesktopCollapsed(desktopCollapsed,{save:false,animate:false});
  syncViewport();
})();
/* HNTv3 design preview: locale selection is persisted locally.
   Laravel integration must use the real account locale + translated SSR routes.
   Do not label untranslated preview content with an incorrect HTML lang. */
(()=>{
  'use strict';
  const container=document.getElementById('hntLanguage');
  const trigger=document.getElementById('hntLanguageToggle');
  const menu=document.getElementById('hntLanguageMenu');
  const current=document.getElementById('hntLanguageCurrent');
  if(!container||!trigger||!menu||!current)return;

  const names={de:'Deutsch',en:'English',es:'Español',ru:'Русский'};
  const options=[...menu.querySelectorAll('[data-lang]')];
  const storageKey='hnt-preview-language';
  let selected='de';
  try{
    const previous=localStorage.getItem(storageKey);
    if(Object.hasOwn(names,previous))selected=previous;
  }catch(_error){/* local files/private mode may disallow storage */}

  const isOpen=()=>!menu.hidden;
  const position=()=>{
    if(!isOpen())return;
    const rect=trigger.getBoundingClientRect();
    const menuHeight=menu.offsetHeight;
    const top=rect.bottom+9;
    const y=(top+menuHeight>innerHeight-8 && rect.top>menuHeight+8)?rect.top-menuHeight-9:top;
    menu.style.setProperty('--hnt-lang-top',Math.max(8,y)+'px');
    const desiredRight=Math.max(10,innerWidth-rect.right);
    const safeRight=Math.min(desiredRight,Math.max(10,innerWidth-menu.offsetWidth-10));
    menu.style.setProperty('--hnt-lang-right',safeRight+'px');
  };
  function close(restoreFocus=false){
    if(!isOpen())return;
    menu.hidden=true;
    trigger.setAttribute('aria-expanded','false');
    if(restoreFocus)trigger.focus();
  }
  function open(){
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'language'}}));
    menu.hidden=false;
    trigger.setAttribute('aria-expanded','true');
    position();
  }
  function setLocale(code,save=false){
    if(!Object.hasOwn(names,code))return;
    selected=code;
    current.textContent=code.toUpperCase();
    trigger.setAttribute('aria-label','Sprache wählen, aktuell '+names[code]);
    container.dataset.locale=code;
    options.forEach(option=>{
      const active=option.dataset.lang===code;
      option.classList.toggle('is-current',active);
      option.setAttribute('aria-checked',String(active));
    });
    if(save){
      try{localStorage.setItem(storageKey,code)}catch(_error){}
      // An integration hook for the eventual Laravel language preference.
      document.dispatchEvent(new CustomEvent('hnt:locale-changed',{detail:{locale:code,previewOnly:true}}));
    }
  }
  trigger.addEventListener('click',()=>isOpen()?close():open());
  trigger.addEventListener('keydown',event=>{
    if(event.key==='ArrowDown' || event.key==='Enter' && !isOpen()){
      event.preventDefault();
      if(!isOpen())open();
      options.find(option=>option.dataset.lang===selected)?.focus();
    }
  });
  menu.addEventListener('click',event=>{
    const option=event.target.closest('[data-lang]');
    if(!option)return;
    setLocale(option.dataset.lang,true);
    close(true);
  });
  menu.addEventListener('keydown',event=>{
    const index=options.indexOf(document.activeElement);
    if(['ArrowDown','ArrowUp','Home','End'].includes(event.key)){
      event.preventDefault();
      const next=event.key==='Home'?0:event.key==='End'?options.length-1:
        (index+(event.key==='ArrowDown'?1:-1)+options.length)%options.length;
      options[next].focus();
    }
  });
  document.addEventListener('pointerdown',event=>{
    if(isOpen()&&!container.contains(event.target))close();
  });
  document.addEventListener('keydown',event=>{
    if(event.key==='Escape'&&isOpen()){
      event.preventDefault();
      event.stopImmediatePropagation();
      close(true);
    }
  },true);
  document.addEventListener('focusin',event=>{
    if(isOpen()&&!container.contains(event.target))close();
  });
  document.addEventListener('hnt:dropdown-open',event=>{
    if(event.detail?.origin!=='language')close();
  });
  window.addEventListener('resize',position);
  window.addEventListener('scroll',position,{passive:true});
  setLocale(selected);
})();

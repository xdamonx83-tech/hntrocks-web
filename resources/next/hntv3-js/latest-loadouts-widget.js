/* HNT.ROCKS — Latest Loadouts sidebar concept, local demo interactions */
(() => {
  'use strict';
  const widget=document.querySelector('.latest-loadouts-widget');
  if(!widget)return;
  const allButton=widget.querySelector('[data-loadout-all]');
  const entries=Array.from(widget.querySelectorAll('[data-loadout-entry]'));
  const iconLabel=allButton?.querySelector('span');

  function setExpanded(entry,expanded){
    const button=entry.querySelector('[data-loadout-toggle]');
    const panel=document.getElementById(button.getAttribute('aria-controls'));
    if(!panel)return;
    button.setAttribute('aria-expanded',String(expanded));
    panel.hidden=!expanded;
  }
  function syncAllButton(){
    const allOpen=entries.every(entry=>entry.querySelector('[data-loadout-toggle]').getAttribute('aria-expanded')==='true');
    allButton?.setAttribute('aria-expanded',String(allOpen));
    if(iconLabel)iconLabel.textContent=allOpen?'Einklappen':'Alle anzeigen';
  }
  entries.forEach(entry=>{
    const button=entry.querySelector('[data-loadout-toggle]');
    button?.addEventListener('click',()=>{
      const expanded=button.getAttribute('aria-expanded')==='true';
      setExpanded(entry,!expanded);
      syncAllButton();
    });
  });
  allButton?.addEventListener('click',()=>{
    const expand=allButton.getAttribute('aria-expanded')!=='true';
    entries.forEach(entry=>setExpanded(entry,expand));
    syncAllButton();
  });
  widget.querySelectorAll('[data-loadout-source]').forEach(button=>{
    button.addEventListener('click',()=>{
      const sourceId=button.dataset.loadoutSource;
      const post=document.getElementById(sourceId);
      if(!post)return;
      // Restore feed visibility even if a different feed filter was selected.
      if(post.hidden){
        document.querySelector('.feed-filter[data-filter="all"]')?.click();
      }
      post.scrollIntoView({behavior:window.matchMedia('(prefers-reduced-motion:reduce)').matches?'instant':'smooth',block:'center'});
      post.classList.remove('latest-loadout-post-focus');
      // Replay subtle focus pulse only on a direct user click.
      void post.offsetWidth;
      post.classList.add('latest-loadout-post-focus');
      setTimeout(()=>post.classList.remove('latest-loadout-post-focus'),1750);
    });
  });
})();

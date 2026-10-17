// HNT.ROCKS account dropdown, independently scoped to avoid affecting navigation or the demo content.
(()=>{
  'use strict';
  const trigger=document.getElementById('accountMenuToggle');
  const panel=document.getElementById('accountMenu');
  if(!trigger||!panel)return;
  let closeOnReturn=false;
  const isOpen=()=>!panel.hidden;
  const closedLabel=trigger.getAttribute('aria-label')||'Account';
  const openLabel=trigger.dataset.accountOpenLabel||closedLabel;
  const close=(focusTrigger=false)=>{
    if(!isOpen())return;
    panel.hidden=true;
    trigger.setAttribute('aria-expanded','false');
    trigger.setAttribute('aria-label',closedLabel);
    if(focusTrigger) trigger.focus();
  };
  const positionMenu=()=>{
    if(panel.hidden)return;
    const rect=trigger.getBoundingClientRect();
    const top=Math.max(8,Math.min(window.innerHeight-155,rect.bottom+9));
    const right=Math.max(9,window.innerWidth-rect.right);
    panel.style.setProperty('--hnt-account-top',top+'px');
    panel.style.setProperty('--hnt-account-right',right+'px');
  };
  window.addEventListener('resize',positionMenu);
  window.addEventListener('scroll',positionMenu,{passive:true});
  const open=()=>{
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'account'}}));
    const others=document.getElementById('moreMenu');
    const moreBtn=document.getElementById('moreMenuToggle');
    if(others && !others.hidden){others.hidden=true;moreBtn?.setAttribute('aria-expanded','false')}
    panel.hidden=false;
    positionMenu();
    trigger.setAttribute('aria-expanded','true');
    trigger.setAttribute('aria-label',openLabel);
  };
  trigger.addEventListener('click',()=>isOpen()?close():open());
  document.addEventListener('pointerdown',(e)=>{if(isOpen()&&!panel.contains(e.target)&&!trigger.contains(e.target))close();});
  document.addEventListener('keydown',(e)=>{
    if(e.key==='Escape'&&isOpen()){
      e.preventDefault();e.stopImmediatePropagation();close(true);
    }
    if(e.key==='ArrowDown'&&document.activeElement===trigger){
      e.preventDefault();if(!isOpen())open();panel.querySelector('a')?.focus();
    }
  },true);
  document.addEventListener('focusin',(e)=>{if(isOpen()&&!panel.contains(e.target)&&!trigger.contains(e.target))close()});
  panel.addEventListener('click',e=>{
    if(e.target.closest('a'))close();
  });
})();

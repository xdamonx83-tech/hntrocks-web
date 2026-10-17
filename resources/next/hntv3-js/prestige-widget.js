/* HNT.ROCKS Prestige: real status and reward confirmation. */
(()=>{
 'use strict';
 const widget=document.getElementById('hntPrestigeWidget');
 const start=document.getElementById('hntPrestigeStart');
 const state=window.HNTPrestigeData;
 const words=window.HNTPrestigeWords||{};
 if(!widget||!start||!state)return;
 const t=k=>words[k]||k;
 const icon=name=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+name+'"></use></svg>';
 const box=document.createElement('div');
 box.className='hnt-prestige-overlay';
 box.id='hntPrestigeOverlay';
 box.hidden=true;
 box.innerHTML='<div class="hnt-prestige-dialog" id="hntPrestigeDialog" role="dialog" aria-modal="true" aria-labelledby="hntPrestigeTitle" aria-describedby="hntPrestigeExplanation" tabindex="-1">'
  +'<button type="button" class="hnt-prestige-close hnt-pd-close">'+icon('x')+'</button>'
  +'<div class="hnt-prestige-eyebrow">HNT.ROCKS / HUNTER PRESTIGE</div>'
  +'<div class="hnt-prestige-dialog-title"><img src="/build/next/hntv3-assets/prestige-skull.png" alt=""/><div><h2 id="hntPrestigeTitle"></h2><p id="hntPrestigeSubhead"></p></div></div>'
  +'<div class="hnt-prestige-reset-note" id="hntPrestigeExplanation"></div>'
  +'<fieldset><legend id="hntPrestigeRewardLegend"></legend><div class="hnt-prestige-choices">'
  +'<label class="hnt-prestige-choice"><input type="radio" name="hntPrestigeReward" value="xp"/><span class="hnt-prestige-choice-icon">'+icon('lightning')+'</span><strong id="hntPrestigeXpTitle"></strong><small id="hntPrestigeXpNote"></small></label>'
  +'<label class="hnt-prestige-choice"><input type="radio" name="hntPrestigeReward" value="cosmetic"/><span class="hnt-prestige-choice-icon">'+icon('sparkle')+'</span><strong id="hntPrestigeCosmeticTitle"></strong><small id="hntPrestigeCosmeticNote"></small></label>'
  +'</div></fieldset>'
  +'<div class="hnt-prestige-dialog-actions"><button type="button" class="hnt-prestige-cancel"></button><button type="button" class="hnt-prestige-confirm" disabled></button></div>'
  +'<p class="hnt-prestige-dialog-foot" id="hntPrestigeFoot" role="status" aria-live="polite"></p></div>';
 document.body.append(box);
 const dialog=box.querySelector('.hnt-prestige-dialog');
 const $=selector=>box.querySelector(selector);
 const set=(id,value)=>{const el=$(id);if(el)el.textContent=value;};
 const radios=[...box.querySelectorAll('[name="hntPrestigeReward"]')];
 const closeBtn=$('.hnt-prestige-close'),cancelBtn=$('.hnt-prestige-cancel'),confirm=$('.hnt-prestige-confirm');
 closeBtn.setAttribute('aria-label',t('close'));closeBtn.title=t('close')+' (Esc)';
 set('#hntPrestigeSubhead',t('dialog_intro'));
 set('#hntPrestigeExplanation',t('dialog_note'));
 set('#hntPrestigeRewardLegend',t('choose'));
 set('#hntPrestigeXpTitle',t('xp_choice'));
 set('#hntPrestigeXpNote',t('xp_explain'));
 set('#hntPrestigeCosmeticTitle',t('cosmetic_choice'));
 cancelBtn.textContent=t('cancel');
 confirm.textContent=t('confirm');
 let current=state, busy=false, closing=false, oldOverflow='', returnFocus=null;
 const usable=()=>Boolean(window.HNTPrestigeEnabled);
 const isOpen=()=>!box.hidden;
 const refreshConfirm=()=>{
  const selected=radios.find(r=>r.checked);
  confirm.disabled=busy||!selected||selected.disabled||!current.can_prestige||!usable();
 };
 const renderStatus=data=>{
  current=data;
  $('#hntPrestigeTitle').textContent=t('start')+' '+(Number(data.prestige)+1);
  const cosmetics=Number(data.cosmetics_available||0);
  const cosmetic=radios.find(r=>r.value==='cosmetic');
  cosmetic.disabled=cosmetics<=0;
  if(cosmetic.disabled)cosmetic.checked=false;
  set('#hntPrestigeCosmeticNote',cosmetics<=0?t('all_owned'):t('cosmetic_explain')+' '+t('available_items').replace(':count',String(cosmetics)));
  cosmetic.closest('label').classList.toggle('is-unavailable',cosmetics<=0);
  set('#hntPrestigeFoot',t('modal_footer'));
  refreshConfirm();
 };
 const close=()=>{
  if(!isOpen()||closing||busy)return;
  closing=true;box.classList.remove('is-open');document.removeEventListener('keydown',onKey,true);
  document.body.style.overflow=oldOverflow;
  window.setTimeout(()=>{
   box.hidden=true;closing=false;
   const target=returnFocus?.isConnected&&!returnFocus.disabled?returnFocus:widget.querySelector('.widget-top a');
   target?.focus({preventScroll:true});
  },260);
 };
 const onKey=event=>{
  if(event.key==='Escape'){event.preventDefault();event.stopImmediatePropagation();close();return;}
  if(event.key==='Tab'){
   const all=[...dialog.querySelectorAll('button:not(:disabled),input:not(:disabled)')];
   if(!all.length)return;
   const first=all[0],last=all[all.length-1];
   if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
   else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
  }
 };
 const open=async()=>{
  if(!usable()||!current.can_prestige||!box.hidden||closing)return;
  busy=true;
  try{
   const response=await fetch('/next/prestige',{credentials:'same-origin',headers:{Accept:'application/json'},cache:'no-store'});
   if(!response.ok)throw Error('status');
   const latest=await response.json();
   if(!latest.can_prestige)return;
   renderStatus(latest);
   returnFocus=document.activeElement;
   oldOverflow=document.body.style.overflow;
   radios.forEach(r=>r.checked=false);
   busy=false;refreshConfirm();box.hidden=false;
   document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'prestige'}}));
   document.body.style.overflow='hidden';
   void box.offsetWidth;
   window.requestAnimationFrame(()=>box.classList.add('is-open'));
   document.addEventListener('keydown',onKey,true);
   (radios.find(r=>!r.disabled)||closeBtn).focus({preventScroll:true});
  }catch(err){
   widget.querySelector('#hntPrestigeStatus span').textContent=t('error');
  }finally{busy=false;}
 };
 start.addEventListener('click',open);
 radios.forEach(r=>r.addEventListener('change',refreshConfirm));
 closeBtn.addEventListener('click',close);cancelBtn.addEventListener('click',close);
 box.addEventListener('click',e=>{if(e.target===box)close();});
 confirm.addEventListener('click',async()=>{
  if(busy||!usable()||!current.can_prestige)return;
  const selected=radios.find(r=>r.checked&&!r.disabled);
  if(!selected)return;
  busy=true;refreshConfirm();closeBtn.disabled=true;cancelBtn.disabled=true;
  confirm.textContent=t('processing');
  try{
   const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
   if(!csrf)throw Error('csrf');
   const response=await fetch('/next/prestige',{
    method:'POST',credentials:'same-origin',
    headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},
    body:JSON.stringify({reward:selected.value})
   });
   const result=await response.json().catch(()=>({}));
   if(!response.ok||!result.success){
    const message=result.errors?.reward?.[0]||result.errors?.prestige?.[0]||result.message||t('error');
    throw Error(message);
   }
   set('#hntPrestigeFoot',t('success')+(result.reward?.cosmetic?' '+result.reward.cosmetic:''));
   confirm.textContent=t('success');
   radios.forEach(r=>r.disabled=true);
   confirm.disabled=true;
   window.setTimeout(()=>window.location.reload(),1400);
  }catch(error){
   set('#hntPrestigeFoot',error.message||t('error'));
   confirm.textContent=t('confirm');
   closeBtn.disabled=false;cancelBtn.disabled=false;
   busy=false;refreshConfirm();
  }
 });
})();

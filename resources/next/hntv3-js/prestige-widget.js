/* HNT.ROCKS — Prestige 50 design preview.
   Only UI simulation. No account, XP, badge or shop mutation. */
(()=>{
  'use strict';
  const widget=document.getElementById('hntPrestigeWidget');
  const start=document.getElementById('hntPrestigeStart');
  if(!widget||!start)return;
  const $=selector=>widget.querySelector(selector);
  const icon=name=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+name+'"></use></svg>';

  const overlay=document.createElement('div');
  overlay.className='hnt-prestige-overlay';
  overlay.id='hntPrestigeOverlay';
  overlay.hidden=true;
  overlay.innerHTML=`
    <div class="hnt-prestige-dialog" id="hntPrestigeDialog" role="dialog" aria-modal="true" aria-labelledby="hntPrestigeTitle" aria-describedby="hntPrestigeExplanation" tabindex="-1">
      <button class="hnt-prestige-close hnt-pd-close" type="button" aria-label="Schließen" title="Schließen (Esc)">${icon('x')}</button>
      <div class="hnt-prestige-eyebrow">HNT.ROCKS / HUNTER PRESTIGE</div>
      <div class="hnt-prestige-dialog-title">
        <img src="/build/next/hntv3-assets/prestige-skull.png" alt="" />
        <div><h2 id="hntPrestigeTitle">Prestige 16</h2><p>Ein neuer Durchlauf. Eine neue Auszeichnung.</p></div>
      </div>
      <div class="hnt-prestige-reset-note" id="hntPrestigeExplanation">Du startest freiwillig wieder bei <strong>Level 1</strong>. Deine bisherigen Cosmetics und Badges bleiben erhalten. Du bekommst den Badge für Prestige 16.</div>
      <fieldset>
        <legend>WÄHLE DEINE BELOHNUNG</legend>
        <div class="hnt-prestige-choices">
          <label class="hnt-prestige-choice">
            <input type="radio" name="hntPrestigeReward" value="xp" />
            <span class="hnt-prestige-choice-icon">${icon('lightning')}</span>
            <strong>+10 % XP</strong><small>Bonus für den nächsten Prestige-Durchlauf. Nicht stapelbar.</small>
          </label>
          <label class="hnt-prestige-choice">
            <input type="radio" name="hntPrestigeReward" value="cosmetic" />
            <span class="hnt-prestige-choice-icon">${icon('sparkle')}</span>
            <strong>Mystery Cosmetic</strong><small>Ein zufälliges kostenloses Cosmetic aus dem HNT.ROCKS Shop.</small>
          </label>
        </div>
      </fieldset>
      <div class="hnt-prestige-dialog-actions">
        <button class="hnt-prestige-cancel" type="button">Abbrechen</button>
        <button class="hnt-prestige-confirm" type="button" disabled>Prestige bestätigen</button>
      </div>
      <p class="hnt-prestige-dialog-foot">Designvorschau: Noch keine echte XP-, Badge- oder Shopbuchung.</p>
    </div>`;
  document.body.append(overlay);
  const dialog=overlay.querySelector('.hnt-prestige-dialog');
  const radios=[...overlay.querySelectorAll('input[name="hntPrestigeReward"]')];
  const confirm=overlay.querySelector('.hnt-prestige-confirm');
  const closeButtons=overlay.querySelectorAll('.hnt-prestige-close,.hnt-prestige-cancel');
  let restoreFocus=null;
  let closingTimer=0;
  let oldOverflow='';
  let state={level:50,prestige:15,reward:null};
  const isOpen=()=>!overlay.hidden;
  function close(){
    if(!isOpen()||closingTimer)return;
    overlay.classList.remove('is-open');
    document.removeEventListener('keydown',handleKeys,true);
    document.body.style.overflow=oldOverflow;
    closingTimer=window.setTimeout(()=>{
      overlay.hidden=true;
      closingTimer=0;
      const target=restoreFocus?.isConnected&&!restoreFocus.disabled
        ?restoreFocus:widget.querySelector('.widget-top a');
      target?.focus({preventScroll:true});
    },260);
  }
  function handleKeys(event){
    if(event.key==='Escape'){
      event.preventDefault();event.stopImmediatePropagation();close();
    }
    if(event.key==='Tab'){
      const focusables=[...dialog.querySelectorAll('button:not(:disabled),input:not(:disabled)')];
      const first=focusables[0],last=focusables[focusables.length-1];
      if(event.shiftKey&&document.activeElement===first){event.preventDefault();last.focus();}
      else if(!event.shiftKey&&document.activeElement===last){event.preventDefault();first.focus();}
    }
  }
  function open(){
    if(state.level!==50||!overlay.hidden)return;
    restoreFocus=document.activeElement;
    oldOverflow=document.body.style.overflow;
    radios.forEach(r=>r.checked=false);
    confirm.disabled=true;
    overlay.hidden=false;
    document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'prestige'}}));
    document.body.style.overflow='hidden';
    // Same two-stage backdrop/panel reveal as the post discussion modal.
    void overlay.offsetWidth;
    requestAnimationFrame(()=>overlay.classList.add('is-open'));
    document.addEventListener('keydown',handleKeys,true);
    radios[0].focus({preventScroll:true});
  }
  function render(){
    $('#hntPrestigeRank').textContent='PRESTIGE '+state.prestige+' · LEVEL '+state.level;
    $('#hntPrestigeEmblemNumber').textContent=state.prestige;
    $('.hnt-prestige-emblem').setAttribute('aria-label','Prestige-Badge '+state.prestige);
    $('#hntPrestigeProgressLabel').textContent='LEVEL '+state.level+' / 50';
    const capped=state.level===50;
    $('#hntPrestigeProgressPercent').textContent=capped?'MAX. LEVEL':'0 % ZUM NÄCHSTEN LEVEL';
    $('#hntPrestigeProgressFill').style.width=capped?'100%':'0%';
    $('#hntPrestigeProgressBar').setAttribute('aria-valuenow',capped?'100':'0');
    const message=capped?'Level 50 erreicht. Prestige ist verfügbar.':
      state.reward==='xp'?'+10 % XP-Boost für Prestige '+state.prestige+' aktiv.':
      'Prestige '+state.prestige+' aktiv · Cosmetic-Auswahl in der Vorschau.';
    $('#hntPrestigeStatus span').textContent=message;
    start.disabled=!capped;
    start.setAttribute('aria-disabled',String(!capped));
    $('#hntPrestigeStartText').textContent=capped?'Prestige '+(state.prestige+1)+' starten':'Prestige '+state.prestige+' gestartet';
    $('#hntPrestigeHint').textContent=capped?'Freiwillig · Level wird auf 1 zurückgesetzt':
      'Badge erhalten · Nächster Aufstieg bei Level 50';
  }
  start.addEventListener('click',open);
  closeButtons.forEach(button=>button.addEventListener('click',close));
  overlay.addEventListener('click',event=>{if(event.target===overlay)close()});
  radios.forEach(input=>input.addEventListener('change',()=>{confirm.disabled=!radios.some(r=>r.checked)}));
  confirm.addEventListener('click',()=>{
    const picked=radios.find(r=>r.checked)?.value;
    if(state.level!==50||!['xp','cosmetic'].includes(picked))return;
    state={level:1,prestige:state.prestige+1,reward:picked};
    render();
    close();
    document.dispatchEvent(new CustomEvent('hnt:prestige-preview-confirmed',{
      detail:{prestige:state.prestige,reward:picked,previewOnly:true}
    }));
  });
  render();
})();

/* HNT.ROCKS - genuine Laravel post management for the Next feed.
 * No demo mutations. Laravel retains final ownership and report validation.
 */
(() => {
  'use strict';
  const feed=document.getElementById('feedList');
  const csrf=document.querySelector('meta[name="csrf-token"]')?.content;
  if (!feed || !csrf || !document.body.hasAttribute('data-hnt-next-live-posts')) return;

  const reasons=[
    ['spam','Spam oder unerwünschte Werbung'],
    ['abuse','Belästigung oder Beleidigung'],
    ['hate','Hassrede'],
    ['nsfw','Unangemessener Inhalt'],
    ['fraud','Betrug oder Täuschung'],
    ['cheating','Cheating oder Manipulation'],
    ['privacy','Verletzung der Privatsphäre'],
    ['other','Anderer Grund']
  ];
  let active=null;
  let previousFocus=null;
  let busy=false;
  const overlay=document.createElement('div');
  overlay.className='hnt-post-manage-overlay';
  overlay.id='hntPostManageOverlay';
  overlay.hidden=true;
  overlay.setAttribute('role','presentation');
  overlay.innerHTML=[
    '<section class="hnt-post-manage-panel" role="dialog" aria-modal="true" aria-labelledby="hntPostManageHeading" aria-describedby="hntPostManageSubheading">',
    '<header class="hnt-post-manage-head"><div>',
    '<div class="hnt-post-manage-eyebrow">HNT.ROCKS / POST OPTIONS</div>',
    '<h2 class="hnt-post-manage-heading" id="hntPostManageHeading"></h2>',
    '<p class="hnt-post-manage-subtitle" id="hntPostManageSubheading"></p>',
    '</div><button class="hnt-post-manage-x" type="button" aria-label="Schließen" title="Schließen (Esc)"><svg aria-hidden="true" class="ph" viewBox="0 0 256 256"><use href="#ph-x"></use></svg></button></header>',
    '<form class="hnt-post-manage-form" id="hntPostManageForm">',
    '<label class="hnt-post-manage-label" data-manage-edit>Beitragstext',
    '<textarea id="hntManageBody" maxlength="5000" placeholder="Deinen Beitrag bearbeiten …"></textarea>',
    '<small class="hnt-post-manage-counter" id="hntManageCounter">0 / 5000</small></label>',
    '<label class="hnt-post-manage-label" data-manage-report>Meldegrund',
    '<select id="hntManageReason" required></select></label>',
    '<label class="hnt-post-manage-label" data-manage-report>Weitere Informationen (optional)',
    '<textarea id="hntManageReportText" maxlength="2000" placeholder="Was ist dir aufgefallen?"></textarea></label>',
    '<p class="hnt-post-manage-message" id="hntManageMessage" role="status" aria-live="polite"></p>',
    '<footer class="hnt-post-manage-controls">',
    '<button class="hnt-post-manage-cancel" type="button">Abbrechen</button>',
    '<button class="hnt-post-manage-primary" id="hntManageConfirm" type="submit">Bestätigen</button>',
    '</footer></form></section>'
  ].join('');
  document.body.append(overlay);
  const form=overlay.querySelector('#hntPostManageForm');
  const title=overlay.querySelector('#hntPostManageHeading');
  const description=overlay.querySelector('#hntPostManageSubheading');
  const body=overlay.querySelector('#hntManageBody');
  const counter=overlay.querySelector('#hntManageCounter');
  const reason=overlay.querySelector('#hntManageReason');
  const reportText=overlay.querySelector('#hntManageReportText');
  const message=overlay.querySelector('#hntManageMessage');
  const confirm=overlay.querySelector('#hntManageConfirm');
  const dismiss=overlay.querySelector('.hnt-post-manage-cancel');
  const closeX=overlay.querySelector('.hnt-post-manage-x');

  for(const [key,label] of reasons){
    const option=document.createElement('option');
    option.value=key;option.textContent=label;
    reason.append(option);
  }
  function notice(text,error=false){
    const helper=window.HNTNextFeedWrites?.notice;
    if(helper){helper(text,error);return;}
    let toast=document.getElementById('hntManagementToast');
    if(!toast){toast=document.createElement('div');toast.id='hntManagementToast';toast.className='hnt-live-action-toast';toast.setAttribute('role','status');document.body.append(toast);}
    toast.textContent=text;toast.hidden=false;
    clearTimeout(toast._timeout);
    toast._timeout=setTimeout(()=>{toast.hidden=true;},4300);
  }
  function close(){
    if(busy)return;
    overlay.hidden=true;
    document.body.style.removeProperty('overflow');
    const focus=previousFocus;previousFocus=null;active=null;
    if(focus?.isConnected)focus.focus({preventScroll:true});
  }
  function show(mode,card,trigger){
    if(!card?.dataset.nextFeedPost) return;
    if(mode==='report' && card.dataset.postOwner==='1')return;
    if((mode==='edit'||mode==='delete') && card.dataset.postOwner!=='1')return;
    const id=card.dataset.nextFeedPost;
    if(!/^\d+$/.test(id))return;
    previousFocus=trigger;
    active={mode,id,card};
    body.value=card.dataset.postBody||'';
    counter.textContent=String(body.value.length)+' / 5000';
    reason.value='spam';reportText.value='';message.textContent='';
    overlay.querySelectorAll('[data-manage-edit]').forEach(el=>{el.hidden=mode!=='edit';});
    overlay.querySelectorAll('[data-manage-report]').forEach(el=>{el.hidden=mode!=='report';});
    reason.disabled=mode!=='report';body.disabled=mode!=='edit';reportText.disabled=mode!=='report';
    confirm.classList.toggle('is-danger',mode==='delete');
    const details={
      edit:['Beitrag bearbeiten','Du kannst den Text ändern. Bilder, Clips und andere Anhänge bleiben erhalten.','Änderungen speichern'],
      delete:['Beitrag löschen','Diesen Beitrag wirklich dauerhaft löschen? Er kann nicht wiederhergestellt werden.','Dauerhaft löschen'],
      report:['Beitrag melden','Deine Meldung wird vertraulich an die HNT.ROCKS Moderation übermittelt.','Meldung absenden']
    }[mode];
    [title.textContent,description.textContent,confirm.textContent]=details;
    card.querySelector('.post-more-menu')?.setAttribute('hidden','');
    const more=card.querySelector('.post-more-btn');
    if(more)more.setAttribute('aria-expanded','false');
    overlay.hidden=false;document.body.style.overflow='hidden';
    const focus=mode==='edit'?body:mode==='report'?reason:confirm;
    focus.focus({preventScroll:true});
  }
  async function request(path,method,payload){
    const response=await fetch(path,{
      method,credentials:'same-origin',
      headers:{'X-CSRF-TOKEN':csrf,'Accept':'application/json','Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
      body:JSON.stringify(payload||{})
    });
    let data={};
    try{data=await response.json()}catch{}
    if(!response.ok){
      const detail=data?.errors?Object.values(data.errors).flat()[0]:null;
      throw new Error(detail||data?.message||(
        response.status===419?'Die Sitzung ist abgelaufen. Bitte erneut anmelden.':
        response.status===403?'Für diese Aktion fehlen dir die Berechtigungen.':
        'Speichern fehlgeschlagen ('+response.status+').'
      ));
    }
    return data;
  }
  function refreshEmptyState(){
    const empty=document.getElementById('feedEmpty');
    if(empty)empty.hidden=!!feed.querySelector('.feed-post[data-next-feed-post]');
  }
  async function handleSubmit(event){
    event.preventDefault();
    if(!active||busy)return;
    const {mode,id,card}=active;
    if(!card?.isConnected){message.textContent='Der Beitrag ist nicht mehr sichtbar.';return;}
    const text=body.value.trim();
    if(mode==='edit' && text.length>5000){message.textContent='Der Text darf höchstens 5000 Zeichen enthalten.';return;}
    if(mode==='report' && !reason.value){message.textContent='Bitte einen Meldegrund auswählen.';return;}
    busy=true;confirm.disabled=true;dismiss.disabled=true;closeX.disabled=true;
    message.textContent='Wird verarbeitet …';
    let persisted=false;
    try {
      let data;
      if(mode==='edit'){
        data=await request('/feed/'+encodeURIComponent(id),'PUT',
          {body:text,visibility:card.dataset.postVisibility||'public'});
        if(!data?.ok)throw new Error('Keine Bestätigung vom Server.');
        persisted=true;
        let replacement;
        if(window.HNTNextFeedWrites?.loadCard)replacement=await window.HNTNextFeedWrites.loadCard(id);
        else {
          const fetched=await fetch('/next/feed/posts/'+encodeURIComponent(id)+'/card',{credentials:'same-origin',headers:{Accept:'text/html'}});
          if(!fetched.ok)throw new Error('Die aktualisierte Beitragsansicht konnte nicht geladen werden.');
          const template=document.createElement('template');template.innerHTML=await fetched.text();
          replacement=template.content.querySelector('.feed-post[data-next-feed-post]');
        }
        if(!replacement)throw new Error('Die Beitragsansicht konnte nicht aktualisiert werden.');
        card.replaceWith(replacement);
        notice('Beitrag aktualisiert.');
      }else if(mode==='delete'){
        data=await request('/feed/'+encodeURIComponent(id),'DELETE',{});
        if(!data?.ok)throw new Error('Keine Löschbestätigung vom Server.');
        persisted=true;card.remove();refreshEmptyState();
        notice('Beitrag gelöscht.');
      }else {
        data=await request('/reports','POST',{type:'feed_post',id:Number(id),reason:reason.value,body:reportText.value.trim()});
        if(!data?.reported)throw new Error('Meldung nicht bestätigt.');
        persisted=true;notice(data.message||'Meldung eingegangen. Vielen Dank.');
      }
      busy=false;confirm.disabled=false;dismiss.disabled=false;closeX.disabled=false;
      close();
    }catch(error){
      message.textContent=persisted?'Aktion gespeichert, aber die Anzeige konnte nicht aktualisiert werden. Bitte Seite neu laden.':(error.message||'Aktion fehlgeschlagen.');
      busy=false;confirm.disabled=false;dismiss.disabled=false;closeX.disabled=false;
    }
  }
  feed.addEventListener('click',event=>{
    const control=event.target.closest('[data-post-edit],[data-post-delete],[data-post-report]');
    if(!control)return;
    event.preventDefault();event.stopPropagation();
    const card=control.closest('.feed-post');
    const mode=control.hasAttribute('data-post-edit')?'edit':control.hasAttribute('data-post-delete')?'delete':'report';
    show(mode,card,control);
  },true);
  form.addEventListener('submit',handleSubmit);
  body.addEventListener('input',()=>counter.textContent=String(body.value.length)+' / 5000');
  closeX.addEventListener('click',close);dismiss.addEventListener('click',close);
  overlay.addEventListener('click',event=>{if(event.target===overlay)close();});
  overlay.addEventListener('keydown',event=>{
    if(event.key==='Escape'){event.preventDefault();close();return;}
    if(event.key!=='Tab')return;
    const controls=[...overlay.querySelectorAll('button:not(:disabled),input:not(:disabled),textarea:not(:disabled),select:not(:disabled)')].filter(el=>!el.closest('[hidden]'));
    if(!controls.length)return;
    if(event.shiftKey&&document.activeElement===controls[0]){event.preventDefault();controls.at(-1).focus();}
    else if(!event.shiftKey&&document.activeElement===controls.at(-1)){event.preventDefault();controls[0].focus();}
  });
})();

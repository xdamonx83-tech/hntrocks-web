/* HNT.ROCKS: poll, feeling, clip – client-side composer preview */
(() => {
 'use strict';
 const dialog=document.querySelector('#composerOverlay .hnt-dispatch-dialog');
 const form=document.getElementById('newPostForm');
 const body=document.getElementById('postContent');
 const image=document.getElementById('composerImage');
 const chips=dialog?.querySelector('.hnt-dispatch-chips');
 if(!dialog||!form||!body||!chips)return;
 const icon=name=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+name+'"></use></svg>';
 const moods=[["😊","Glücklich","happy"],["🔥","Motiviert","excited"],["🎯","Fokussiert","focused"],["🌙","Entspannt","chill"],["😴","Müde","tired"],["🧂","Salzig","salty"]]
 const translatedMoods=window.HNTNextLocaleMoodLabels;
 if(translatedMoods)for(const item of moods){if(translatedMoods[item[2]])item[1]=translatedMoods[item[2]];}
 let pollOn=false,feeling=null,clipURL=null,clipName='',panel='',seq=0;
 const polls=new Map();
 const toolbar=document.createElement('div');
 toolbar.className='hnt-extra-toolbar';
 toolbar.innerHTML=[
  '<span class="hnt-extra-eyebrow">ZUM BEITRAG HINZUFÜGEN</span>',
  '<div class="hnt-extra-tools">',
  '<button type="button" data-extra="poll" aria-pressed="false" aria-expanded="false" aria-controls="hntExtraPoll"><svg viewBox="0 0 24 24" class="hnt-extra-svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M5 19v-8M11 19V5M17 19v-5M3 21h17"/></svg>Umfrage</button>',
  '<button type="button" data-extra="feeling" aria-pressed="false" aria-expanded="false" aria-controls="hntExtraFeeling"><svg viewBox="0 0 24 24" class="hnt-extra-svg" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M8 14.5q4 4 8 0M8.5 10h.1M15.5 10h.1"/></svg><span id="hntFeelingLabel">Gefühl</span></button>',
  '<button type="button" data-extra="clip" aria-pressed="false">'+icon('film-strip')+'Clip</button>',
  '</div>'
 ].join('');
 const holder=document.createElement('div');holder.className='hnt-extra-panels';
 const pollPanel=document.createElement('section');pollPanel.className='hnt-extra-panel';pollPanel.id='hntExtraPoll';pollPanel.hidden=true;
 pollPanel.innerHTML=[
  '<div class="hnt-extra-top"><strong>UMFRAGE ERSTELLEN</strong><button type="button" data-close-extra="poll" aria-label="Umfrage entfernen">'+icon('x')+'</button></div>',
  '<label class="hnt-extra-field"><span>Deine Frage</span><input id="hntPollQuestion" maxlength="180" placeholder="Was interessiert dich im Bayou?"></label>',
  '<div id="hntPollOptions" class="hnt-poll-inputs">',
  '<label class="hnt-extra-field"><span>Antwort 1</span><input maxlength="80" placeholder="Erste Antwort"></label>',
  '<label class="hnt-extra-field"><span>Antwort 2</span><input maxlength="80" placeholder="Zweite Antwort"></label>',
  '</div>',
  '<div class="hnt-poll-controls"><button type="button" id="hntPollAdd">'+icon('plus')+' Antwort hinzufügen</button>',
  '<label>Umfrage läuft <select id="hntPollDuration"><option value="24">24 Stunden</option><option value="72">3 Tage</option><option value="168">7 Tage</option></select></label></div>'
 ].join('');
 const feelingPanel=document.createElement('section');feelingPanel.className='hnt-extra-panel';feelingPanel.id='hntExtraFeeling';feelingPanel.hidden=true;
 feelingPanel.innerHTML='<div class="hnt-extra-top"><strong>WIE FÜHLST DU DICH?</strong><button type="button" data-close-extra="feeling" aria-label="Gefühl schließen">'+icon('x')+'</button></div><div class="hnt-mood-grid"></div>';
 const moodGrid=feelingPanel.querySelector('.hnt-mood-grid');
 moods.forEach(([emoji,name])=>{
  const button=document.createElement('button');button.type='button';button.dataset.mood=name;button.className='hnt-mood-choice';button.setAttribute('aria-pressed','false');
  const a=document.createElement('span');a.textContent=emoji;a.className='hnt-mood-icon';
  const b=document.createElement('span');b.textContent=name;button.append(a,b);moodGrid.append(button);
 });
 const clipPanel=document.createElement('section');clipPanel.id='hntExtraClip';clipPanel.className='hnt-extra-panel hnt-extra-clip-panel';clipPanel.hidden=true;
 clipPanel.innerHTML='<div class="hnt-extra-top"><strong>'+icon('film-strip')+' CLIP ANHÄNGEN</strong><button type="button" data-close-extra="clip" aria-label="Clip entfernen">'+icon('x')+'</button></div><video id="hntClipPreview" controls playsinline preload="metadata"></video><div class="hnt-clip-note"><span id="hntClipName"></span><span>Lokale Vorschau · Max. 30 MB</span></div>';
 const clipFile=document.createElement('input');clipFile.type='file';clipFile.id='hntClipFile';clipFile.accept='video/mp4,video/webm';clipFile.hidden=true;clipFile.setAttribute('aria-label','Videoclip auswählen');
 holder.append(pollPanel,feelingPanel,clipPanel,clipFile);
 chips.after(toolbar);toolbar.after(holder);
 const button=id=>toolbar.querySelector('[data-extra="'+id+'"]');
 function syncButtons(){
  button('poll').classList.toggle('is-active',pollOn);button('poll').setAttribute('aria-pressed',String(pollOn));button('poll').setAttribute('aria-expanded',String(panel==='poll'));
  button('feeling').classList.toggle('is-active',!!feeling);button('feeling').setAttribute('aria-pressed',String(!!feeling));button('feeling').setAttribute('aria-expanded',String(panel==='feeling'));
  button('clip').classList.toggle('is-active',!!clipURL);button('clip').setAttribute('aria-pressed',String(!!clipURL));button('clip').disabled=pollOn;
  document.getElementById('hntFeelingLabel').textContent=feeling?feeling[0]+' '+feeling[1]:'Gefühl';
  image.disabled=pollOn;
  dialog.querySelector('.composer-upload')?.classList.toggle('is-extra-disabled',pollOn);
  body.required=!(pollOn||Boolean(clipURL)||Boolean(image.files?.length));
 }
 image.addEventListener('change',syncButtons);
 document.getElementById('removePreview')?.addEventListener('click',()=>requestAnimationFrame(syncButtons));
 function show(type){panel=type||'';pollPanel.hidden=panel!=='poll';feelingPanel.hidden=panel!=='feeling';clipPanel.hidden=panel!=='clip';syncButtons();}
 function clearClip(){
  const video=document.getElementById('hntClipPreview');video.pause();video.removeAttribute('src');video.load();
  if(clipURL)URL.revokeObjectURL(clipURL);
  clipURL=null;clipName='';clipFile.value='';
  if(panel==='clip')show('');else syncButtons();
 }
 function setPoll(active){
  pollOn=active;
  if(active){
   if(image.value)document.getElementById('removePreview')?.click();
   if(clipURL)clearClip();
   show('poll');
   document.getElementById('hntPollQuestion').focus({preventScroll:true});
  }else show('');
  syncButtons();
 }
 toolbar.addEventListener('click',e=>{
  const target=e.target.closest('[data-extra]');if(!target)return;
  if(target.dataset.extra==='poll')setPoll(!pollOn);
  if(target.dataset.extra==='feeling')show(panel==='feeling'?'':'feeling');
  if(target.dataset.extra==='clip'&&!pollOn)clipFile.click();
 });
 holder.addEventListener('click',e=>{
  const close=e.target.closest('[data-close-extra]');
  if(close){const kind=close.dataset.closeExtra;if(kind==='poll')setPoll(false);else if(kind==='clip')clearClip();else show('');return;}
  const mood=e.target.closest('[data-mood]');
  if(mood){
   feeling=feeling?.[1]===mood.dataset.mood?null:(moods.find(m=>m[1]===mood.dataset.mood)||null);
   moodGrid.querySelectorAll('button').forEach(b=>b.setAttribute('aria-pressed',String(!!feeling&&b===mood)));
   show('');
  }
 });
 const optionRows=document.getElementById('hntPollOptions'),add=document.getElementById('hntPollAdd');
 function syncAdd(){add.disabled=optionRows.querySelectorAll('input').length>=4;}
 add.addEventListener('click',()=>{
  const n=optionRows.querySelectorAll('input').length;if(n>=4)return;
  const row=document.createElement('div');row.className='hnt-extra-removable';
  const label=document.createElement('label');label.className='hnt-extra-field';
  const title=document.createElement('span');title.textContent='Antwort '+(n+1);
  const inp=document.createElement('input');inp.maxLength=80;inp.placeholder='Weitere Antwort';
  label.append(title,inp);
  const del=document.createElement('button');del.type='button';del.className='hnt-extra-delete';del.title='Antwort entfernen';del.setAttribute('aria-label','Antwort entfernen');del.innerHTML=icon('x');
  del.addEventListener('click',()=>{
   row.remove();[...optionRows.querySelectorAll('input')].forEach((a,i)=>a.closest('label').querySelector('span').textContent='Antwort '+(i+1));syncAdd();
  });
  row.append(label,del);optionRows.append(row);syncAdd();inp.focus();
 });
 clipFile.addEventListener('change',()=>{
  const file=clipFile.files?.[0];if(!file)return;
  if(!/^video\/(mp4|webm)$/i.test(file.type)&&!/\.(mp4|webm)$/i.test(file.name)){
   alert('Bitte MP4 oder WebM auswählen.');clipFile.value='';return;
  }
  if(file.size>30*1024*1024){alert('Maximal 30 MB für die lokale Clip-Vorschau.');clipFile.value='';return;}
  if(image.value)document.getElementById('removePreview')?.click();
  if(clipURL)clearClip();
  clipName=file.name;clipURL=URL.createObjectURL(file);
  document.getElementById('hntClipPreview').src=clipURL;
  document.getElementById('hntClipName').textContent=clipName;
  show('clip');
 });
 const pollData=()=>({question:document.getElementById('hntPollQuestion').value.trim(),options:[...optionRows.querySelectorAll('input')].map(i=>i.value.trim()).filter(Boolean),hours:Number(document.getElementById('hntPollDuration').value)});
 window.HNTNextComposerExtras={read(){return {poll:pollOn?pollData():null,feelingKey:feeling?.[2]||null}},validate(){const data=pollOn?pollData():null;if(!data)return null;if(data.question.length<5)return 'Bitte eine Umfragefrage mit mindestens 5 Zeichen eingeben.';if(data.options.length<2)return 'Bitte mindestens zwei Antworten eingeben.';if(new Set(data.options.map(x=>x.toLowerCase())).size!==data.options.length)return 'Antworten dürfen sich nicht wiederholen.';return null}};
 document.addEventListener('hnt:composer:validate',e=>{
  if(clipURL)e.detail.hasContent=true;
  if(!pollOn)return;
  e.detail.hasContent=true;
  const data=pollData();
  if(data.question.length<5){e.detail.valid=false;e.detail.message='Bitte eine Umfragefrage eingeben (mindestens 5 Zeichen).';}
  else if(data.options.length<2){e.detail.valid=false;e.detail.message='Bitte mindestens zwei Antworten eingeben.';}
  else if(new Set(data.options.map(x=>x.toLowerCase())).size!==data.options.length){e.detail.valid=false;e.detail.message='Antworten dürfen sich nicht wiederholen.';}
 });
 const el=(name,cls,text)=>{const node=document.createElement(name);node.className=cls;if(text!==undefined)node.textContent=text;return node;};
 function createPoll(state){
  const root=el('section','hnt-feed-poll');root.dataset.hntPollId=state.id;
  const head=el('div','hnt-feed-poll-top');head.append(el('span','hnt-feed-poll-label','COMMUNITY-UMFRAGE'),el('span','hnt-feed-poll-duration',state.duration));
  const question=el('h3','hnt-feed-poll-question',state.question);
  const options=el('div','hnt-feed-poll-options');
  state.options.forEach((label,i)=>{
   const b=el('button','hnt-feed-poll-option');b.type='button';b.dataset.pollOption=String(i);
   b.setAttribute('aria-label','Abstimmen für '+label);
   const bar=el('span','hnt-feed-poll-bar');const content=el('span','hnt-feed-poll-option-line');
   content.append(el('span','hnt-feed-poll-name',label),el('span','hnt-feed-poll-percent',''));
   b.append(bar,content);options.append(b);
  });
  root.append(head,question,options,el('div','hnt-feed-poll-foot','0 Stimmen · Einmal abstimmen'));
  return root;
 }
 function syncPoll(state){
  const count=state.votes.reduce((a,b)=>a+b,0);
  const finished=state.selected!==null||Date.now()>=state.expiry;
  document.querySelectorAll('[data-hnt-poll-id="'+state.id+'"]').forEach(root=>{
   root.querySelectorAll('[data-poll-option]').forEach(b=>{
    const i=Number(b.dataset.pollOption),pct=count?Math.round(state.votes[i]/count*100):0;
    b.disabled=finished;b.classList.toggle('show-results',finished);b.classList.toggle('is-selected',state.selected===i);
    b.querySelector('.hnt-feed-poll-bar').style.width=(finished?pct:0)+'%';
    b.querySelector('.hnt-feed-poll-percent').textContent=finished?pct+'%':'';
   });
   root.querySelector('.hnt-feed-poll-foot').textContent=count+' '+(count===1?'Stimme':'Stimmen')+' · '+(Date.now()>=state.expiry?'Abgeschlossen':state.selected!==null?'Deine Stimme wurde gezählt':'Einmal abstimmen');
  });
 }
 document.addEventListener('click',e=>{
  const b=e.target.closest('.hnt-feed-poll-option');if(!b)return;
  const parent=b.closest('[data-hnt-poll-id]'),state=polls.get(parent?.dataset.hntPollId);
  if(!state||state.selected!==null||Date.now()>=state.expiry)return;
  state.selected=Number(b.dataset.pollOption);state.votes[state.selected]++;syncPoll(state);
 });
 document.addEventListener('hnt:composer:render',e=>{
  const {card,caption,text}=e.detail;
  if(feeling){const mood=el('div','hnt-feed-feeling');mood.append(el('span','hnt-feed-feeling-emoji',feeling[0]),document.createTextNode('Fühlt sich '+feeling[1].toLowerCase()));card.insertBefore(mood,caption);}
  if(pollOn){
   const data=pollData(),duration=data.hours===24?'24 Std.':data.hours===72?'3 Tage':'7 Tage';
   const state={id:'hnt-poll-'+(++seq)+'-'+Date.now(),question:data.question,options:data.options,duration,expiry:Date.now()+data.hours*3600000,selected:null,votes:data.options.map(()=>0)};
   polls.set(state.id,state);card.dataset.postType='poll';card.append(createPoll(state));
  }
  if(!text)caption.remove();
  if(clipURL){
   card.dataset.postType='clip';const wrap=el('div','hnt-feed-clip');
   const video=document.createElement('video');video.controls=true;video.playsInline=true;video.preload='metadata';video.src=clipURL;video.setAttribute('aria-label','Clip: '+clipName);
   const meta=el('div','hnt-feed-clip-meta');meta.append(el('span','hnt-feed-clip-label','HUNTER CLIP'),el('span','hnt-feed-clip-name',clipName));
   wrap.append(video,meta);card.append(wrap);
   clipURL=null;clipName='';clipFile.value='';
  }
 });
 document.addEventListener('hnt:composer:posted',()=>{
  pollOn=false;feeling=null;panel='';
  pollPanel.hidden=true;feelingPanel.hidden=true;clipPanel.hidden=true;
  document.getElementById('hntPollQuestion').value='';document.getElementById('hntPollDuration').value='24';
  optionRows.querySelectorAll('.hnt-extra-removable').forEach(x=>x.remove());
  optionRows.querySelectorAll('input').forEach(x=>x.value='');
  moodGrid.querySelectorAll('button').forEach(x=>x.setAttribute('aria-pressed','false'));
  if(clipURL)clearClip();
  syncAdd();syncButtons();
 });
 syncButtons();
})();

/* HNT.ROCKS — Hunter Dispatch composer polish (visual/local, keeps feed.js authoritative). */
(() => {
  'use strict';
  const overlay=document.getElementById('composerOverlay');
  const dialog=overlay?.querySelector('.composer-dialog');
  const form=document.getElementById('newPostForm');
  const body=document.getElementById('postContent');
  const file=document.getElementById('composerImage');
  const preview=document.getElementById('composerPreview');
  if(!dialog||!form||!body||!file||!preview)return;
  const icon=n=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+n+'"></use></svg>';
  dialog.classList.add('hnt-dispatch-dialog');
  const head=dialog.querySelector('.composer-dialog-head');
  head?.querySelector('span')?.replaceChildren(document.createTextNode('HNT.ROCKS / HUNTER DISPATCH'));
  const title=document.getElementById('composerTitle');
  if(title)title.textContent='Neuer Beitrag';
  const headerNote=document.createElement('p');
  headerNote.className='hnt-dispatch-subtitle';
  headerNote.textContent='Geschichten direkt aus dem Bayou.';
  title?.after(headerNote);
  const textPanel=document.createElement('div');
  textPanel.className='hnt-dispatch-editor';
  body.before(textPanel);
  textPanel.append(body);
  const controls=document.createElement('div');
  controls.className='hnt-dispatch-editor-meta';
  controls.innerHTML='<span>'+icon('crosshair')+' DEIN EINSATZBERICHT</span><span id="hntDispatchCount">0 / 1000</span>';
  textPanel.append(controls);
  const meter=document.createElement('span');
  meter.className='hnt-dispatch-meter';
  meter.innerHTML='<i></i>';
  textPanel.append(meter);

  const chips=document.createElement('div');
  chips.className='hnt-dispatch-chips';
  chips.setAttribute('aria-label','Art des Beitrags');
  const modes=[['story','Erlebnis','Was ist dir heute im Bayou passiert?'],['question','Frage','Welche Frage hast du an die Community?'],['tip','Tipp','Teile deinen besten Hunt-Tipp ...']];
  modes.forEach(([key,title,placeholder],i)=>{
    const b=document.createElement('button');
    b.type='button';b.className='hnt-dispatch-chip'+(i===0?' is-active':'');
    b.dataset.dispatchMode=key;b.textContent=title;
    b.dataset.placeholder=placeholder;
    b.setAttribute('aria-pressed',String(i===0));
    chips.append(b);
  });
  textPanel.after(chips);
  const hint=document.createElement('div');
  hint.className='hnt-dispatch-hint';
  hint.innerHTML=icon('shield-check')+'<span>Dein Beitrag erscheint im Community-Feed.</span>';
  chips.after(hint);

  const previewCaption=document.createElement('div');
  previewCaption.className='hnt-dispatch-media-caption';
  previewCaption.innerHTML='<span>'+icon('image')+' ANHANG</span><span id="hntDispatchFilename">Bild ausgewählt</span>';
  preview.prepend(previewCaption);
  const fileInput=()=>file.files?.[0];
  function updatePreview(){
    document.getElementById('hntDispatchFilename').textContent=fileInput()?.name||'Bild ausgewählt';
  }
  file.addEventListener('change',()=>requestAnimationFrame(updatePreview));
  document.getElementById('removePreview')?.addEventListener('click',()=>{document.getElementById('hntDispatchFilename').textContent='Bild ausgewählt';});
  const foot=form.querySelector('.composer-dialog-foot');
  const footText=document.createElement('div');
  footText.className='hnt-dispatch-foot-label';
  footText.innerHTML='<span class="hnt-dispatch-live-dot"></span> Bereit zum Teilen';
  foot?.prepend(footText);

  function updateCount(){
    const n=body.value.length;
    const out=document.getElementById('hntDispatchCount');
    if(out)out.textContent=n+' / '+body.maxLength;
    const fill=meter.querySelector('i');
    fill.style.width=Math.min(100,n/body.maxLength*100)+'%';
    const warn=n>body.maxLength*.9;
    textPanel.classList.toggle('near-limit',warn);
    dialog.classList.toggle('has-content',n>0);
  }
  body.addEventListener('input',updateCount);updateCount();
  chips.addEventListener('click',e=>{
    const b=e.target.closest('[data-dispatch-mode]');if(!b)return;
    chips.querySelectorAll('button').forEach(item=>{const selected=item===b;item.classList.toggle('is-active',selected);item.setAttribute('aria-pressed',String(selected));});
    body.placeholder=b.dataset.placeholder;
    body.focus({preventScroll:true});
  });
  // Optional dropping images onto the editor. The existing upload handler handles validation and preview.
  for(const name of ['dragenter','dragover']){
    dialog.addEventListener(name,e=>{if(!e.dataTransfer?.types.includes('Files'))return;e.preventDefault();dialog.classList.add('is-dropping');});
  }
  dialog.addEventListener('dragleave',e=>{if(!dialog.contains(e.relatedTarget))dialog.classList.remove('is-dropping');});
  dialog.addEventListener('drop',e=>{
    e.preventDefault();dialog.classList.remove('is-dropping');
    const chosen=Array.from(e.dataTransfer?.files||[]).find(item=>item.type.startsWith('image/'));
    if(!chosen)return;
    try{const dt=new DataTransfer();dt.items.add(chosen);file.files=dt.files;file.dispatchEvent(new Event('change',{bubbles:true}));}catch{}
  });
  // Keep the new visuals consistent after local post creation.
  form.addEventListener('submit',()=>setTimeout(()=>{
    updateCount();
    previewCaption.querySelector('#hntDispatchFilename').textContent='Bild ausgewählt';
  },0));
})();

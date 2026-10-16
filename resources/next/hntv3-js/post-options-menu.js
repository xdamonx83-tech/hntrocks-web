/* HNT.ROCKS | Post Options — compact contextual menu, shared by every feed post. */
(() => {
  'use strict';
  const feed=document.getElementById('feedList');
  if(!feed)return;
  const icon=name=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+name+'"></use></svg>';
  const query=(sel,scope=document)=>scope.querySelector(sel);
  const menus=()=>Array.from(feed.querySelectorAll('.post-more-menu'));
  let lastOpen=null;

  function sync() {
    menus().forEach(menu=>{
      const card=menu.closest('.feed-post');
      const trigger=query('.post-more-btn',card);
      const open=!menu.hidden;
      trigger?.setAttribute('aria-expanded',String(open));
      trigger?.classList.toggle('is-menu-open',open);
      card?.classList.toggle('has-post-menu',open);
      const saved=card?.classList.contains('user-saved');
      const label=query('[data-save] .post-option-title',menu);
      const hint=query('[data-save] .post-option-description',menu);
      if(label)label.textContent=saved?'Beitrag gemerkt':'Beitrag speichern';
      if(hint)hint.textContent=saved?'Aus Merkliste entfernen':'Für später in Merkliste sichern';
      const badge=query('[data-save] .post-option-check',menu);
      if(badge)badge.hidden=!saved;
    });
  }

  function enhance(card){
    if(!card || card.dataset.postMenuReady==='true')return;
    const head=query('.post-head',card);
    if(!head)return;
    let trigger=query('.post-more-btn',head);
    let menu=query('.post-more-menu',head);
    if(!trigger){
      trigger=document.createElement('button');
      trigger.type='button';
      trigger.className='post-more-btn';
      trigger.setAttribute('data-more','');
      trigger.setAttribute('aria-label','Beitragsoptionen');
      trigger.setAttribute('aria-expanded','false');
      trigger.innerHTML=icon('dots-three');
      head.append(trigger);
    }
    if(!menu){
      menu=document.createElement('div');
      menu.className='post-more-menu';
      menu.hidden=true;
      menu.innerHTML='<button type="button" data-save>Beitrag speichern</button><button type="button" data-share>Link teilen</button>';
      head.append(menu);
    }
    if(!menu.id)menu.id='post-options-'+(card.id||Math.random().toString(36).slice(2));
    trigger.setAttribute('aria-controls',menu.id);
    trigger.title='Beitragsoptionen';
    menu.setAttribute('role','group');
    menu.setAttribute('aria-label','Beitragsoptionen');

    const oldSave=query('[data-save]',menu);
    const oldShare=query('[data-share]',menu);
    menu.replaceChildren();

    const eyebrow=document.createElement('div');
    eyebrow.className='post-options-eyebrow';
    eyebrow.innerHTML='<span class="post-options-signal"></span><strong>BEITRAGSOPTIONEN</strong><small>HNT.ROCKS</small>';
    menu.append(eyebrow);

    function decorate(button,name,label,description,extra=''){
      if(!button)return;
      button.classList.add('post-option-item');
      button.innerHTML='<span class="post-option-icon">'+icon(name)+'</span><span class="post-option-copy"><strong class="post-option-title"></strong><small class="post-option-description"></small></span>'+extra;
      query('.post-option-title',button).textContent=label;
      query('.post-option-description',button).textContent=description;
      menu.append(button);
    }
    decorate(oldSave,'bookmark-simple','Beitrag speichern','Für später in Merkliste sichern','<span class="post-option-check" hidden>'+icon('check')+'</span>');
    decorate(oldShare,'share-fat','Beitrag teilen','Im Feed teilen oder Link kopieren','<span class="post-option-arrow">'+icon('arrow-right')+'</span>');
    if(card.dataset.nextFeedPost){
      const own=card.dataset.postOwner==='1';
      const separator=document.createElement('div');separator.className='hnt-post-option-separator';menu.append(separator);
      const addAction=(name,label,description,iconName)=>{
        const action=document.createElement('button');action.type='button';action.setAttribute('data-post-'+name,'');
        decorate(action,iconName,label,description);
      };
      if(own){
        addAction('edit','Beitrag bearbeiten','Text des Beitrags aktualisieren','pencil-simple');
        addAction('delete','Beitrag löschen','Dauerhaft aus deinem Feed entfernen','x');
      } else {
        addAction('report','Beitrag melden','An die HNT.ROCKS Moderation','shield-check');
      }
    }
    const end=document.createElement('div');
    end.className='post-options-foot';
    end.textContent='AUS DER COMMUNITY';
    menu.append(end);
    card.dataset.postMenuReady='true';
  }

  function enhanceAll(){
    Array.from(feed.querySelectorAll('.feed-post')).forEach(enhance);
    sync();
  }

  feed.addEventListener('click',event=>{
    if(event.target.closest('.post-more-btn,.post-more-menu')){
      // Preserve feed.js's original action handlers, then sync menu affordances.
      queueMicrotask(sync);
    }
  });
  document.addEventListener('click',()=>{
    // The existing feed controller handles click-away; refresh visual state.
    queueMicrotask(sync);
  });
  document.addEventListener('keydown',event=>{
    if(event.key!=='Escape')return;
    const open=menus().filter(menu=>!menu.hidden);
    if(!open.length)return;
    open.forEach(menu=>menu.hidden=true);
    sync();
    query('.post-more-btn',open[0].closest('.feed-post'))?.focus({preventScroll:true});
    event.stopPropagation();
  });
  new MutationObserver(()=>enhanceAll()).observe(feed,{childList:true});
  enhanceAll();
})();

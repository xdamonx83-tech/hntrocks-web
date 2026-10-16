/* HNT.ROCKS — inline conversations, shared with Bayou Focus comments. */
(() => {
  'use strict';
  const list=document.getElementById('feedList');
  if(!list)return;
  const $=(s,root=document)=>root.querySelector(s);
  const $$=(s,root=document)=>[...root.querySelectorAll(s)];
  const bridge=()=>window.HNTCommentBridge;
  const icon=n=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+n+'"></use></svg>';
  const postStates=new WeakMap();
  let seq=0;
  const initials=name=>name?.split(/[^a-zA-Z0-9]+/).filter(Boolean).map(x=>x[0]).join('').slice(0,2).toUpperCase()||'HN';

  function init(card){
    const box=$('.post-comment-box',card);
    if(!box||postStates.has(card))return;
    box.classList.add('hnt-inline-discussion');
    const state={expanded:false,closed:new Set(),opened:new Set(),reply:null};
    postStates.set(card,state);
    const head=document.createElement('header');
    head.className='hnt-inline-head';
    head.innerHTML='<strong>'+icon('chat-circle-dots')+' Diskussion</strong><span class="hnt-inline-count" aria-live="polite"></span>';
    box.prepend(head);
    const body=document.createElement('div');
    body.className='hnt-inline-threads';
    body.setAttribute('aria-label','Kommentare zum Beitrag');
    head.after(body);
    const toggle=document.createElement('button');
    toggle.type='button';toggle.className='hnt-inline-show-more';
    body.after(toggle);
    const reply=document.createElement('div');
    reply.className='hnt-inline-reply-target';reply.hidden=true;
    reply.innerHTML='<span></span><button type="button" aria-label="Antwort abbrechen">'+icon('x')+'</button>';
    const form=$('.comment-form',box);
    form.before(reply);
    const avatar=document.createElement('img');
    avatar.src='/build/next/hntv3-assets/avatar-profile.webp';avatar.alt='';avatar.className='hnt-inline-composer-avatar';
    form.prepend(avatar);
    const input=$('input',form);
    if(input)input.placeholder='Deinen Kommentar schreiben ...';
    reply.querySelector('button').addEventListener('click',()=>{state.reply=null;reply.hidden=true;input.placeholder='Deinen Kommentar schreiben ...';});
    toggle.addEventListener('click',()=>{state.expanded=!state.expanded;render(card)});
    body.addEventListener('click',e=>{
      const like=e.target.closest('[data-comment-like]');
      if(like){
        const id=like.dataset.commentLike;
        bridge()?.toggleLike(card.id,id);
        return;
      }
      const respond=e.target.closest('[data-inline-reply]');
      if(respond){
        const id=respond.dataset.inlineReply;
        const comment=collect(card).find(c=>c.id===id);
        if(!comment)return;
        state.reply=comment;
        reply.hidden=false;
        reply.querySelector('span').textContent='Antwort an '+comment.name;
        input.placeholder='Antwort an '+comment.name+' ...';
        input.focus({preventScroll:true});
        return;
      }
      const expand=e.target.closest('[data-inline-thread-toggle]');
      if(expand){
        const id=expand.dataset.inlineThreadToggle;
        // Only toggle this thread, never redraw the full list.
        const panel=expand.nextElementSibling;
        if(panel){
          panel.hidden=!panel.hidden;
          if(panel.hidden){state.closed.add(id);state.opened.delete(id);}
          else{state.closed.delete(id);state.opened.add(id);}
          expand.setAttribute('aria-expanded',String(!panel.hidden));
          expand.textContent=panel.children.length+' '+(panel.children.length===1?'Antwort':'Antworten')+(panel.hidden?' anzeigen':' ausblenden');
        }
      }
    });
    render(card);
  }
  function collect(card){
    const result=[];
    // Locally added comments use the same hidden storage used by feed.js and the viewer.
    $$('.comment-feed .comment-line',card).forEach(line=>{
      if(!line.dataset.hntId)line.dataset.hntId='inline-local-'+(++seq);
      const message=$('span',line)?.textContent||'';
      if(message.trim())result.push({
        id:line.dataset.hntId,
        parentId:line.dataset.hntParentId||null,
        mention:line.dataset.hntMention||'',
        name:$('strong',line)?.textContent||'KrispieArmy',
        initials:'KA',time:'gerade eben',message,mine:true
      });
    });
    result.push(...(bridge()?.getComments(card.id)||[]));
    return result;
  }
  function commentNode(card,c,depth){
    const node=document.createElement('article');
    node.className='hnt-inline-comment'+(c.mine?' is-own':'');
    node.dataset.cid=c.id;
    node.dataset.depth=String(depth);
    const avatar=document.createElement('span');
    avatar.className='hnt-inline-avatar';avatar.textContent=c.initials||initials(c.name);
    const content=document.createElement('div');
    content.className='hnt-inline-copy';
    const top=document.createElement('div');top.className='hnt-inline-meta';
    const name=document.createElement('strong');name.textContent=c.name;
    const time=document.createElement('small');time.textContent=c.time;
    top.append(name,time);
    const message=document.createElement('p');
    if(c.mention){
      const tag=document.createElement('b');
      tag.textContent='@'+c.mention+' ';
      message.append(tag);
    }
    message.append(document.createTextNode(c.message));
    const tools=document.createElement('div');tools.className='hnt-inline-comment-tools';
    const reply=document.createElement('button');reply.type='button';
    reply.dataset.inlineReply=c.id;reply.textContent='Antworten';
    const count=c.mine?0:(2+c.id.split('').reduce((n,ch)=>n+ch.charCodeAt(0),0)%8);
    const liked=bridge()?.isLiked(card.id,c.id)===true;
    const like=document.createElement('button');like.type='button';
    like.className='hnt-inline-like'+(liked?' is-liked':'');
    like.dataset.commentLike=c.id;
    like.setAttribute('aria-pressed',String(liked));
    like.setAttribute('aria-label',(liked?'Like entfernen: ':'Kommentar liken: ')+c.name);
    like.innerHTML=icon('heart')+'<span>'+String(count+(liked?1:0))+'</span>';
    tools.append(reply,like);content.append(top,message,tools);node.append(avatar,content);
    return node;
  }
  function render(card){
    const box=$('.post-comment-box',card),state=postStates.get(card);
    if(!box||!state)return;
    const body=$('.hnt-inline-threads',box),toggle=$('.hnt-inline-show-more',box);
    const all=collect(card);
    const byId=new Map(all.map(c=>[c.id,c]));
    const roots=[],children=new Map();
    all.forEach(c=>{
      if(c.parentId&&byId.has(c.parentId)){
        if(!children.has(c.parentId))children.set(c.parentId,[]);
        children.get(c.parentId).push(c);
      }else roots.push(c);
    });
    const visible=state.expanded?roots:roots.slice(0,2);
    const fragment=document.createDocumentFragment();
    function thread(c,depth=0){
      const w=document.createElement('div');w.className='hnt-inline-thread';
      w.append(commentNode(card,c,depth));
      const nested=children.get(c.id)||[];
      if(nested.length){
        const t=document.createElement('button');t.type='button';t.className='hnt-inline-thread-toggle';
        t.dataset.inlineThreadToggle=c.id;
        const hidden=state.closed.has(c.id)||(!state.expanded&&!state.opened.has(c.id));
        t.setAttribute('aria-expanded',String(!hidden));
        t.textContent=nested.length+' '+(nested.length===1?'Antwort':'Antworten')+(hidden?' anzeigen':' ausblenden');
        const sub=document.createElement('div');sub.className='hnt-inline-children';sub.hidden=hidden;
        nested.forEach(child=>sub.append(thread(child,depth+1)));
        w.append(t,sub);
      }
      return w;
    }
    visible.forEach(c=>fragment.append(thread(c)));
    body.replaceChildren(fragment);
    if(!visible.length){
      const empty=document.createElement('p');
      empty.className='hnt-inline-empty';empty.textContent='Noch keine Kommentare. Starte die Diskussion.';
      body.append(empty);
    }
    const realCount=$('.comment-count',card)?.textContent||'0';
    $('.hnt-inline-count',box).textContent=realCount+' Kommentare';
    box.classList.toggle('is-expanded',state.expanded);
    toggle.hidden=roots.length<=2;
    toggle.textContent=state.expanded?'Weniger Kommentare anzeigen':'Weitere '+(roots.length-2)+' Diskussionen ansehen';
    toggle.setAttribute('aria-expanded',String(state.expanded));
  }
  function redraw(postId){
    const card=document.getElementById(postId);
    if(card&&postStates.has(card)&&!$('.post-comment-box',card).hidden)render(card);
  }
  document.addEventListener('hnt:comment-like',event=>{
    const {postId,commentId,liked}=event.detail||{};
    const card=document.getElementById(postId);
    if(!card||!postStates.has(card))return;
    $$('.hnt-inline-like',card).filter(e=>e.dataset.commentLike===commentId).forEach(btn=>{
      btn.classList.toggle('is-liked',liked);
      btn.setAttribute('aria-pressed',String(liked));
      const span=$('span',btn);
      if(span)span.textContent=String((collect(card).find(c=>c.id===commentId)?.mine?0:(2+commentId.split('').reduce((n,ch)=>n+ch.charCodeAt(0),0)%8))+(liked?1:0));
    });
  });
  list.addEventListener('click',event=>{
    const toggle=event.target.closest('[data-comments-toggle]');
    if(!toggle)return;
    const card=toggle.closest('.feed-post');
    if(card&&!$('.post-comment-box',card).hidden)render(card);
  });
  list.addEventListener('submit',event=>{
    if(!event.target.matches('.comment-form'))return;
    const card=event.target.closest('.feed-post'),state=postStates.get(card);
    if(!card||!state)return;
    // feed.js appended a .comment-line synchronously before this list listener.
    const newLine=$('.comment-feed .comment-line',card);
    if(newLine){
      newLine.dataset.hntId='inline-local-'+(++seq);
      if(state.reply){
        const selected=state.reply;
        const existing=collect(card);
        const byId=new Map(existing.map(item=>[item.id,item]));
        let level=0, ancestor=selected;
        while(ancestor?.parentId && level<5){level++;ancestor=byId.get(ancestor.parentId);}
        newLine.dataset.hntParentId=(level>=2 && selected.parentId)?selected.parentId:selected.id;
        if(level>=2)newLine.dataset.hntMention=selected.name;
        state.closed.delete(selected.id);
        state.opened.add(selected.id);
        if(selected.parentId){state.closed.delete(selected.parentId);state.opened.add(selected.parentId);}
        state.expanded=true;
      }
    }
    state.reply=null;
    const replyBox=$('.hnt-inline-reply-target',card);
    if(replyBox)replyBox.hidden=true;
    const input=$('.comment-form input',card);
    if(input)input.placeholder='Deinen Kommentar schreiben ...';
    render(card);
  });
  new MutationObserver(()=>$$('.feed-post',list).forEach(init)).observe(list,{childList:true});
  $$('.feed-post',list).forEach(init);
})();

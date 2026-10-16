/* HNT.ROCKS Bayou Focus — compact social controls and threaded comments.
   Example comments are local; user submissions use the existing feed composer handler. */
(() => {
  'use strict';
  const viewer=document.getElementById('hntMediaViewer');
  if(!viewer || document.getElementById('hntMvConversation')) return;
  const $=(s,r=document)=>r.querySelector(s);
  const $$=(s,r=document)=>[...r.querySelectorAll(s)];
  const icon=n=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+n+'"></use></svg>';
  const C=(id,name,initials,time,message,parentId=null)=>({id,name,initials,time,message,parentId});
  let activeCard=null;
  let replyTarget=null;
  let nextLocalId=0;
  let commentsById=new Map();
  const closedThreads=new Map();
  const commentLikes=new Map(); // Local likes persist while switching photos or folding threads.

  // Dense sample conversations to demonstrate deep scrolling and nested replies.
  const discussions={
    'post-loadout':[
      C('l1','MapScout','MS','vor 2 Min.','Winfield und Pax ist schon eine stabile Kombi. Spielst du High Velocity?'),
      C('l1a','CaldwellEnjoyer','CE','vor 1 Min.','Meist normale Munition. Bei dem Budget reicht mir das völlig.','l1'),
      C('l1a1','OldCrow','OC','gerade eben','Same. Mit den Eisenvisieren komm ich besser klar.','l1a'),
      C('l2','SwampCrow','SC','vor 8 Min.','Endlich mal kein Loadout für 800 Hunt Dollar.'),
      C('l2a','RavenHunter','RH','vor 6 Min.','Wenn du dreimal hintereinander stirbst, lernst du Budget-Builds zu lieben.','l2'),
      C('l3','NightWraith','NW','vor 14 Min.','Würde nur die Fire Bomb gegen Chokes tauschen, wenn ich im Trio spiele.'),
      C('l3a','CaldwellEnjoyer','CE','vor 11 Min.','Die Chokes hat bei uns meistens der Mate dabei.','l3'),
      C('l4','BayouNomad','BN','vor 22 Min.','Pax ist underrated. Gerade auf mittlere Distanz.'),
      C('l5','FogWalker','FW','vor 31 Min.','Mit der Winfield und Levering geht einiges.'),
      C('l5a','MapScout','MS','vor 29 Min.','Würde ich bei wenig Trait-Punkten sogar ohne spielen.','l5'),
      C('l6','DustyPax','DP','vor 49 Min.','Schönes Build. Ich nehm nur lieber die kleinen Vitality Shots.'),
      C('l7','GraveKeeper','GK','vor 1 Std.','Budget gewinnt manchmal gegen Meta. Genau mein Ding.')
    ],
    'post-bayou':[
      C('b1','CaldwellEnjoyer','CE','vor 3 Min.','Dieses Licht im Bayou ist einfach jedes Mal irre.'),
      C('b1a','NightWraith','NW','vor 2 Min.','Und dann knallt irgendwo eine Sparks. Natürlich.', 'b1'),
      C('b1a1','MapScout','MS','vor 1 Min.','Das ist der Moment, wo alle wieder in Deckung gehen.','b1a'),
      C('b1b','BayouNomad','BN','vor 2 Min.','Wir standen danach tatsächlich noch zwei Minuten nur rum.','b1'),
      C('b2','SwampCrow','SC','vor 8 Min.','War das Stillwater oder DeSalle? Sieht richtig gut aus.'),
      C('b2a','BayouNomad','BN','vor 7 Min.','Stillwater, Nähe Blanchett Graves.','b2'),
      C('b2b','RavenHunter','RH','vor 6 Min.','Hab genau da gestern noch einen Solo rausgefischt.','b2'),
      C('b2b1','SwampCrow','SC','vor 5 Min.','Dann weiß ich, wo ich heute Abend hinmuss.','b2b'),
      C('b3','RavenHunter','RH','vor 15 Min.','Mein Lieblingsmoment: nach dem Fight nur noch Regen und Krähen hören.'),
      C('b3a','CaldwellEnjoyer','CE','vor 13 Min.','Bis der nächste Hunter mit der Dolch um die Ecke kommt.','b3'),
      C('b4','MapScout','MS','vor 20 Min.','Drei raus, Ruhe im Compound. Genau für solche Runden spielt man Hunt.'),
      C('b4a','BayouNomad','BN','vor 17 Min.','Das Timing war wirklich perfekt.','b4'),
      C('b4a1','NightWraith','NW','vor 16 Min.','Den letzten Schuss würde ich gern als Clip sehen.','b4a'),
      C('b4b','SwampCrow','SC','vor 12 Min.','Und danach das Bounty noch sauber rausgebracht?','b4'),
      C('b5','IronHound','IH','vor 25 Min.','Die Kirche im Gegenlicht macht die Szene echt stark.'),
      C('b5a','MapScout','MS','vor 23 Min.','An der Stelle gibt es oft guten Blick auf die Rotation.','b5'),
      C('b6','OldCrow','OC','vor 33 Min.','Auf dem Screenshot sieht alles friedlich aus. Im Match hört man nur Panik.'),
      C('b6a','CaldwellEnjoyer','CE','vor 31 Min.','Hunt in einem Satz beschrieben.','b6'),
      C('b7','FogWalker','FW','vor 38 Min.','So einen Sonnenuntergang hatte ich seit dem Update kaum noch.'),
      C('b7a','RavenHunter','RH','vor 35 Min.','Morgens sieht die Map aber auch richtig stark aus.','b7'),
      C('b8','BayouRat','BR','vor 42 Min.','Würdet ihr hier den Highground halten oder direkt zum Extract gehen?'),
      C('b8a','MapScout','MS','vor 40 Min.','Wenn du Bounty hast: nicht gierig werden, raus da.','b8'),
      C('b8a1','OldCrow','OC','vor 39 Min.','Die Gier hat schon manchen Hunter in die Lobby gebracht.','b8a'),
      C('b9','DustyPax','DP','vor 54 Min.','Mit einem Romero wäre das eine ziemlich enge Nummer geworden.'),
      C('b10','GraveKeeper','GK','vor 1 Std.','Wunderbarer Screenshot. Das hat sofort Bayou-Stimmung.')
    ],
    'post-cash':[
      C('c1','MapScout','MS','vor 4 Min.','Ist das oben in der Scheune, direkt am Holzsteg?'),
      C('c1a','BayouNomad','BN','vor 3 Min.','Genau da! Wir hatten gestern auch eine Kasse an der Stelle.','c1'),
      C('c1a1','OldCrow','OC','vor 2 Min.','Bei uns stand stattdessen eine Munikiste.','c1a'),
      C('c2','SwampCrow','SC','vor 10 Min.','Nice, direkt auf der Karte markiert. Spart beim nächsten Run Zeit.'),
      C('c2a','NightWraith','NW','vor 9 Min.','Die roten Marker sind echt hilfreich.','c2'),
      C('c3','RavenHunter','RH','vor 12 Min.','Wie oft spawnt dort ungefähr eine Kasse?'),
      C('c3a','MapScout','MS','vor 11 Min.','Ist zufällig, der Spot selbst ist aber bestätigt.','c3'),
      C('c3a1','RavenHunter','RH','vor 10 Min.','Alles klar. Danke fürs Checken!','c3a'),
      C('c4','BayouRat','BR','vor 18 Min.','Oben im Scheunenbereich kommt man doch über die Leiter rein, oder?'),
      C('c4a','CaldwellEnjoyer','CE','vor 16 Min.','Ja, aber pass auf die Fensterseite auf.','c4'),
      C('c5','FogWalker','FW','vor 25 Min.','Ich lauf Seven Sisters meistens komplett außen herum. Das sollte ich ändern.'),
      C('c5a','OldCrow','OC','vor 24 Min.','Wenn die Runde ruhig ist, lohnt sich ein kurzer Check.','c5'),
      C('c6','IronHound','IH','vor 33 Min.','Cool, dass auch solche Funde im Feed auftauchen.'),
      C('c6a','SwampCrow','SC','vor 30 Min.','Mehr davon! Ich übersehe die Dinger ständig.','c6'),
      C('c7','DustyPax','DP','vor 47 Min.','Hab an dem Spot schon zweimal was gefunden.'),
      C('c8','GraveKeeper','GK','vor 1 Std.','Die Kartenansicht dazu ist praktisch.'),
      C('c8a','MapScout','MS','vor 56 Min.','Vor allem mit dem genauen Marker.','c8'),
      C('c9','NightWraith','NW','vor 1 Std.','Danke fürs Melden an die Community.')
    ]
  };
  // Shared local sample discussions and like states for the inline feed threads.
  window.HNTCommentBridge={
    getComments:postId=>(discussions[postId]||[]).map(item=>({...item})),
    isLiked:(postId,commentId)=>commentLikes.get(postId+':'+commentId)===true,
    toggleLike:(postId,commentId)=>{
      const key=postId+':'+commentId;
      const liked=!commentLikes.get(key);
      commentLikes.set(key,liked);
      document.dispatchEvent(new CustomEvent('hnt:comment-like',{detail:{postId,commentId,liked}}));
      return liked;
    }
  };
  document.addEventListener('hnt:comment-like',event=>{
    if(activeCard?.id!==event.detail?.postId)return;
    const button=$$('.hnt-mv-comment-like',viewer).find(node=>node.dataset.commentLike===event.detail.commentId);
    if(!button)return;
    const liked=event.detail.liked;
    button.classList.toggle('is-liked',liked);
    button.setAttribute('aria-pressed',String(liked));
    const count=$('span',button);
    if(count)count.textContent=String((Number(button.dataset.initialLikes)||0)+(liked?1:0));
  });
  const aside=$('.hnt-viewer-aside',viewer);
  const info=$('.hnt-viewer-aside-inner',aside);
  const actions=$('.hnt-viewer-actions',info);
  const commentAction=$('#hntMvComment',info);
  if(!aside||!info||!actions||!commentAction)return;

  // The media viewer already registered a "go to feed comments" handler.
  // Clone this hidden control to avoid accidentally triggering that navigation.
  const focusAction=commentAction.cloneNode(true);
  commentAction.replaceWith(focusAction);
  focusAction.title='Hier kommentieren';
  const focusLabel=$('span',focusAction);
  if(focusLabel)focusLabel.textContent='Schreiben';

  const conversation=document.createElement('section');
  conversation.className='hnt-mv-conversation';
  conversation.id='hntMvConversation';
  conversation.setAttribute('aria-label','Kommentare zu diesem Beitrag');
  conversation.innerHTML=[
    '<header class="hnt-mv-comments-head">',
      '<div class="hnt-mv-comments-heading">'+icon('chat-circle-dots')+'<h3>Kommentare</h3><span class="hnt-mv-count" id="hntMvCommentTotal">0</span></div>',
      '<span class="hnt-mv-comments-sort">Neueste zuerst</span>',
    '</header>',
    '<div class="hnt-mv-comments-scroll" id="hntMvCommentList" role="log" aria-live="polite" aria-relevant="additions"></div>'
  ].join('');
  const composer=document.createElement('form');
  composer.className='hnt-mv-composer';
  composer.id='hntMvCommentForm';
  composer.setAttribute('aria-label','Kommentar schreiben');
  composer.innerHTML=[
    '<div class="hnt-mv-compose-identity"><img src="/build/next/hntv3-assets/avatar-profile.webp" alt=""><span>Kommentieren als <strong>KrispieArmy</strong></span><span class="hnt-mv-compose-online" aria-hidden="true"></span></div>',
    '<div class="hnt-mv-reply-target" id="hntMvReplyTarget" hidden><span id="hntMvReplyLabel"></span><button type="button" id="hntMvReplyCancel" aria-label="Antwort abbrechen">'+icon('x')+'</button></div>',
    '<div class="hnt-mv-compose-field"><textarea id="hntMvCommentText" rows="2" maxlength="280" required placeholder="Schreib etwas zum Bild ..." aria-label="Dein Kommentar"></textarea></div>',
    '<div class="hnt-mv-compose-foot"><span class="hnt-mv-compose-hint"><span id="hntMvCharCount">0</span>/280 <span class="hnt-mv-compose-shortcut">· Enter senden</span></span>',
      '<button id="hntMvSendComment" type="submit" disabled>'+icon('paper-plane-tilt')+' Senden</button></div>'
  ].join('');
  aside.append(conversation,composer);
  const liveViewer=document.body.dataset.nextIntegration || document.body.hasAttribute('data-hnt-next-live-posts');
  if(liveViewer){
    const identity=$('.hnt-mv-compose-identity strong',composer);
    const avatar=$('.hnt-mv-compose-identity img',composer);
    if(identity && document.body.dataset.nextViewerName)identity.textContent=document.body.dataset.nextViewerName;
    if(avatar && document.body.dataset.nextViewerAvatar)avatar.src=document.body.dataset.nextViewerAvatar;
  }

  const list=$('#hntMvCommentList');
  const textarea=$('#hntMvCommentText');
  const submit=$('#hntMvSendComment');
  const charCount=$('#hntMvCharCount');
  const total=$('#hntMvCommentTotal');
  const replyBox=$('#hntMvReplyTarget');
  const replyLabel=$('#hntMvReplyLabel');
  const branchState=postId=>{
    if(!closedThreads.has(postId))closedThreads.set(postId,new Set());
    return closedThreads.get(postId);
  };
  function resetReply(){
    replyTarget=null;
    replyBox.hidden=true;
    textarea.placeholder='Schreib etwas zum Bild ...';
  }
  function selectReply(comment){
    replyTarget=comment;
    replyBox.hidden=false;
    replyLabel.textContent='Antwort an '+comment.name;
    textarea.placeholder='Deine Antwort an '+comment.name+' ...';
    textarea.focus({preventScroll:true});
  }
  $('#hntMvReplyCancel').addEventListener('click',()=>{
    resetReply();textarea.focus({preventScroll:true});
  });

  function collectComments(){
    if(activeCard?.dataset.nextFeedPost){
      return window.HNTCommentBridge?.getComments(activeCard.id) || [];
    }
    const allComments=[];
    const stored=$$('.comment-line',activeCard);
    stored.forEach((line,i)=>{
      if(!line.dataset.hntId)line.dataset.hntId='local-'+(++nextLocalId);
      const message=$('span',line)?.textContent||'';
      if(!message.trim())return;
      allComments.push({
        id:line.dataset.hntId,parentId:line.dataset.hntParentId||null,
        name:$('strong',line)?.textContent||'KrispieArmy',
        initials:'KA',time:'gerade eben',message,
        mention:line.dataset.hntMention||'',mine:true
      });
    });
    allComments.push(...(discussions[activeCard.id]||[]));
    return allComments;
  }
  function syncCount(){
    const count=$('.comment-count',activeCard)?.textContent?.trim()||'0';
    total.textContent=count;
    $('#hntMvComments').textContent=count;
  }
  function makeNode(comment,depth){
    const node=document.createElement('article');
    node.className='hnt-mv-comment'+(comment.mine?' is-mine':'');
    node.dataset.commentId=comment.id;
    node.dataset.depth=depth;
    const avatar=document.createElement('span');
    avatar.className='hnt-mv-comment-avatar';
    avatar.textContent=comment.initials;
    const content=document.createElement('div');
    content.className='hnt-mv-comment-content';
    const head=document.createElement('div');
    head.className='hnt-mv-comment-meta';
    const name=document.createElement('strong');
    name.textContent=comment.name;
    const time=document.createElement('time');
    time.textContent=comment.time;
    head.append(name,time);
    const message=document.createElement('p');
    if(comment.mention){
      const mention=document.createElement('b');
      mention.className='hnt-mv-mention';
      mention.textContent='@'+comment.mention+' ';
      message.append(mention);
    }
    message.append(document.createTextNode(comment.message));
    const tools=document.createElement('div');
    tools.className='hnt-mv-comment-tools';
    const reply=document.createElement('button');
    reply.type='button';reply.className='hnt-mv-reply-btn';
    reply.dataset.replyId=comment.id;
    reply.textContent='Antworten';
    reply.setAttribute('aria-label','Auf '+comment.name+' antworten');
    const key=(activeCard?.id||'post')+':'+comment.id;
    const isReal=Boolean(activeCard?.dataset.nextFeedPost);
    const base=isReal ? Number(comment.reactionsCount||0) : (comment.mine?0:(2+comment.id.split('').reduce((sum,ch)=>sum+ch.charCodeAt(0),0)%8));
    const liked=isReal ? Boolean(comment.viewerLiked) : commentLikes.get(key)===true;
    const like=document.createElement('button');
    like.type='button';
    like.className='hnt-mv-comment-like'+(liked?' is-liked':'');
    like.dataset.commentLike=comment.id;
    like.dataset.initialLikes=String(base);
    like.setAttribute('aria-label',(liked?'Like entfernen: ':'Kommentar liken: ')+comment.name);
    like.setAttribute('aria-pressed',String(liked));
    like.innerHTML=icon('heart')+'<span>'+String(isReal?base:base+(liked?1:0))+'</span>';
    tools.append(reply,like);
    content.append(head,message,tools);
    node.append(avatar,content);
    return node;
  }
  function drawComments({freshId=null,preserveScroll=false}={}){
    if(!activeCard)return;
    const previousScroll=preserveScroll?list.scrollTop:0;
    const data=collectComments();
    commentsById=new Map(data.map(c=>[c.id,c]));
    const children=new Map();
    const roots=[];
    for(const item of data){
      if(item.parentId && commentsById.has(item.parentId)){
        if(!children.has(item.parentId))children.set(item.parentId,[]);
        children.get(item.parentId).push(item);
      } else roots.push(item);
    }
    const closed=branchState(activeCard.id);
    const fragment=document.createDocumentFragment();
    function createThread(comment,depth=0){
      const thread=document.createElement('div');
      thread.className='hnt-mv-thread';
      thread.dataset.threadId=comment.id;
      const node=makeNode(comment,depth);
      thread.append(node);
      const replies=children.get(comment.id)||[];
      if(replies.length){
        const toggle=document.createElement('button');
        toggle.type='button';
        toggle.className='hnt-mv-thread-toggle';
        toggle.dataset.toggleId=comment.id;
        const isClosed=closed.has(comment.id);
        toggle.textContent=isClosed?(replies.length+' '+(replies.length===1?'Antwort anzeigen':'Antworten anzeigen')):(replies.length+' '+(replies.length===1?'Antwort':'Antworten')+' ausblenden');
        toggle.setAttribute('aria-expanded',String(!isClosed));
        const container=document.createElement('div');
        container.className='hnt-mv-replies';
        container.hidden=isClosed;
        replies.forEach(child=>container.append(createThread(child,depth+1)));
        thread.append(toggle,container);
      }
      return thread;
    }
    roots.forEach(comment=>fragment.append(createThread(comment)));
    list.replaceChildren(fragment);
    if(!roots.length){
      const empty=document.createElement('div');
      empty.className='hnt-mv-comments-empty';
      empty.innerHTML=icon('chat-circle-dots')+'<strong>Noch keine Kommentare</strong><span>Sei der Erste, der etwas dazu schreibt.</span>';
      list.append(empty);
    }
    syncCount();
    list.scrollTop=previousScroll;
    if(freshId){
      const posted=$$('.hnt-mv-comment',list).find(n=>n.dataset.commentId===freshId);
      if(posted){
        posted.classList.add('is-fresh');
        posted.scrollIntoView({block:'nearest',behavior:'smooth'});
      }
    }
  }
  list.addEventListener('click',event=>{
    const like=event.target.closest('[data-comment-like]');
    if(like && activeCard){
      const key=activeCard.id+':'+like.dataset.commentLike;
      const next=!commentLikes.get(key);
      commentLikes.set(key,next);
      like.classList.toggle('is-liked',next);
      like.setAttribute('aria-pressed',String(next));
      const base=Number(like.dataset.initialLikes)||0;
      const counter=like.querySelector('span');
      if(counter)counter.textContent=String(base+(next?1:0));
      const comment=commentsById.get(like.dataset.commentLike);
      like.setAttribute('aria-label',(next?'Like entfernen: ':'Kommentar liken: ')+(comment?.name||'Kommentar'));
      return;
    }
    const reply=event.target.closest('[data-reply-id]');
    if(reply){
      const item=commentsById.get(reply.dataset.replyId);
      if(item)selectReply(item);
      return;
    }
    const toggle=event.target.closest('[data-toggle-id]');
    if(toggle && activeCard){
      // Fold only the selected thread. Rebuilding the whole comment tree
      // used to replay the entrance animations and made the list jump.
      const replies=toggle.nextElementSibling;
      if(!replies?.classList.contains('hnt-mv-replies'))return;
      const isClosed=!replies.hidden;
      replies.hidden=isClosed;
      const closed=branchState(activeCard.id);
      if(isClosed)closed.add(toggle.dataset.toggleId);
      else closed.delete(toggle.dataset.toggleId);
      toggle.setAttribute('aria-expanded',String(!isClosed));
      const count=replies.children.length;
      const noun=count===1?'Antwort':'Antworten';
      toggle.textContent=isClosed
        ?count+' '+noun+' anzeigen'
        :count+' '+noun+' ausblenden';
    }
  });
  function updateEditor(){
    charCount.textContent=textarea.value.length;
    submit.disabled=!textarea.value.trim();
    textarea.style.height='';
    textarea.style.height=Math.min(textarea.scrollHeight,110)+'px';
  }
  textarea.addEventListener('input',updateEditor);
  textarea.addEventListener('keydown',e=>{
    if(e.key==='Enter'&&!e.shiftKey&&!e.isComposing){
      e.preventDefault();
      if(!submit.disabled)composer.requestSubmit();
    }
  });
  function focusComposer(){
    textarea.focus({preventScroll:true});
    if(window.matchMedia('(max-width:800px)').matches)composer.scrollIntoView({behavior:'smooth',block:'nearest'});
  }
  focusAction.addEventListener('click',focusComposer);

  composer.addEventListener('submit',event=>{
    event.preventDefault();
    if(!activeCard)return;
    const value=textarea.value.trim();
    if(!value)return;
    const feedForm=$('.post-comment-box .comment-form',activeCard);
    const feedInput=$('input',feedForm);
    if(!feedForm||!feedInput)return;
    const target=replyTarget;
    // Reuse feed.js for count updates and storing the exact comment text.
    feedInput.value=value;
    if(typeof feedForm.requestSubmit==='function')feedForm.requestSubmit();
    else feedForm.dispatchEvent(new Event('submit',{bubbles:true,cancelable:true}));
    const newLine=$('.comment-feed .comment-line',activeCard);
    let newId=null;
    if(newLine){
      newId='local-'+(++nextLocalId);
      newLine.dataset.hntId=newId;
      if(target){
        // Cap visual nesting at 2 levels, with @mention for deeper replies.
        const source=$$('.hnt-mv-comment',list).find(x=>x.dataset.commentId===target.id);
        const depth=Number(source?.dataset.depth||0);
        newLine.dataset.hntParentId=(depth>=2&&target.parentId)?target.parentId:target.id;
        if(depth>=2)newLine.dataset.hntMention=target.name;
        // The replied-to thread must be visible after sending.
        const closed=branchState(activeCard.id);
        closed.delete(target.id);
        if(target.parentId)closed.delete(target.parentId);
      }
    }
    textarea.value='';
    resetReply();
    updateEditor();
    drawComments({freshId:newId});
    textarea.focus({preventScroll:true});
  });

  document.addEventListener('hnt:next-comment-saved',event=>{
    if(!activeCard || event.detail?.postId !== activeCard.id)return;
    drawComments({freshId:event.detail?.commentId || null,preserveScroll:true});
  });
  document.addEventListener('hnt:next-comment-reaction',event=>{
    if(activeCard?.id === event.detail?.postId)drawComments({preserveScroll:true});
  });
  viewer.addEventListener('hnt:viewer-media',event=>{
    activeCard=event.detail?.card||null;
    textarea.value='';
    resetReply();
    updateEditor();
    drawComments();
    list.scrollTop=0;
  });
  document.getElementById('feedList')?.addEventListener('submit',event=>{
    if(!event.target.matches('.comment-form')||!activeCard)return;
    if(event.target.closest('.feed-post')!==activeCard)return;
    // submit updates the feed synchronously; defer redraw for external comments.
    setTimeout(()=>drawComments({preserveScroll:true}),0);
  });
})();

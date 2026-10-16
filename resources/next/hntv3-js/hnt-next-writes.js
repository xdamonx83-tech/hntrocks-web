/* HNT.ROCKS Next – real writes, original HNTv3 UI.
 * All saves use the existing Laravel endpoints. The card endpoint is an
 * authorized read-only HTML fragment; no alternate feed models or writes.
 */
(() => {
  'use strict';
  const root = document.querySelector('[data-hnt-next-live-posts="true"]');
  const list = document.getElementById('feedList');
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
  if (!root || !list || !csrf) return;

  let dialogCard = null;
  let viewerCard = null;
  const replyTo = {dialog: null, viewer: null};
  const pending = new WeakSet();
  let toastTimer = 0;

  function notice(message, isError=false) {
    let box = document.getElementById('hntLiveWriteNotice');
    if (!box) {
      box = document.createElement('div');
      box.id = 'hntLiveWriteNotice';
      box.className = 'hnt-live-action-toast';
      box.setAttribute('role','status');
      document.body.appendChild(box);
    }
    box.textContent = message;
    box.style.borderColor = isError ? '#c77979' : '';
    box.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => {box.hidden = true}, 4500);
  }

  function messageFor(status, payload) {
    if (status === 401 || status === 419) return 'Sitzung abgelaufen. Bitte neu anmelden.';
    if (status === 403) return 'Dafür fehlen dir die Berechtigungen.';
    if (status === 413) return 'Die Datei ist zu groß.';
    const validation = payload?.errors && Object.values(payload.errors).flat()?.[0];
    return validation || payload?.message || 'Speichern fehlgeschlagen ('+status+').';
  }

  async function post(path, body) {
    const isFormData = body instanceof FormData;
    const headers = {'X-CSRF-TOKEN':csrf,'Accept':'application/json','X-Requested-With':'XMLHttpRequest'};
    if (!isFormData) headers['Content-Type'] = 'application/json';
    const response = await fetch(path,{
      method:'POST',credentials:'same-origin',headers,
      body: isFormData ? body : JSON.stringify(body)
    });
    let payload;
    try { payload = await response.json(); } catch { payload = null; }
    if (!response.ok || !payload?.ok && path === '/feed') {
      throw new Error(messageFor(response.status, payload));
    }
    return payload;
  }

  async function loadCard(postId) {
    const response = await fetch('/next/feed/posts/'+encodeURIComponent(postId)+'/card',{
      method:'GET',credentials:'same-origin',
      headers:{Accept:'text/html','X-Requested-With':'XMLHttpRequest'}
    });
    if (!response.ok) throw new Error(messageFor(response.status));
    const template = document.createElement('template');
    template.innerHTML = (await response.text()).trim();
    const card = template.content.querySelector('.feed-post[data-next-feed-post]');
    if (!card || card.dataset.nextFeedPost !== String(postId))
      throw new Error('Der neue Beitrag konnte nicht dargestellt werden.');
    return document.importNode(card,true);
  }

  document.addEventListener('click', e => {
    const discussionTrigger = e.target.closest('#feedList .feed-post[data-next-feed-post] [data-comments-toggle]');
    if (discussionTrigger) {
      dialogCard = discussionTrigger.closest('.feed-post');
      replyTo.dialog = null;
    }
    const d = e.target.closest('#hntPdComments [data-reply]');
    if (d) replyTo.dialog = d.dataset.reply || null;
    const v = e.target.closest('#hntMvCommentList [data-reply]');
    if (v) replyTo.viewer = v.dataset.reply || null;
    if (e.target.closest('#hntPdCancelReply')) replyTo.dialog = null;
    if (e.target.closest('#hntMvReplyCancel')) replyTo.viewer = null;
  },true);
  document.getElementById('hntMediaViewer')?.addEventListener('hnt:viewer-media',e=>{
    viewerCard = e.detail?.card || null;
    replyTo.viewer = null;
  });

  async function saveComment(form) {
    const viewer = form.id==='hntMvCommentForm';
    const discussion = form.id==='hntPdForm';
    const card = viewer ? viewerCard : discussion ? dialogCard : form.closest('.feed-post');
    const id = card?.dataset.nextFeedPost;
    const input = viewer ? document.getElementById('hntMvCommentText') :
       discussion ? document.getElementById('hntPdText') : form.querySelector('input,textarea');
    const body = input?.value?.trim() || '';
    if (!id || !body) {notice('Bitte einen Kommentar eingeben.',true);return;}
    const parent = viewer ? replyTo.viewer : discussion ? replyTo.dialog : null;
    const submit = form.querySelector('[type="submit"]');
    if (pending.has(form)) return;
    pending.add(form);if(submit) submit.disabled = true;
    let saved = false;
    try {
      const data = await post('/feed/'+encodeURIComponent(id)+'/comments',
        {body,...(parent ? {parent_id:parent} : {})});
      if (!data?.ok) throw new Error('Kommentar konnte nicht gespeichert werden.');
      saved = true;
      const fresh = await loadCard(id);
      const current = list.querySelector('.feed-post[data-next-feed-post="'+CSS.escape(id)+'"]');
      if (current) {
        const oldJson = current.querySelector('script.hnt-live-comments');
        const newJson = fresh.querySelector('script.hnt-live-comments');
        if (oldJson && newJson) oldJson.textContent = newJson.textContent;
        const oldCount = current.querySelector('.comment-count');
        const newCount = fresh.querySelector('.comment-count');
        if (oldCount && newCount) oldCount.textContent = newCount.textContent;
      }
      if (input) {input.value='';input.dispatchEvent(new Event('input',{bubbles:true}));}
      if (discussion) {
        replyTo.dialog=null;
        document.getElementById('hntPdCancelReply')?.click();
      }
      if (viewer) {
        replyTo.viewer=null;
        document.getElementById('hntMvReplyCancel')?.click();
      }
      document.dispatchEvent(new CustomEvent('hnt:next-comment-saved',{
        detail:{postId:card.id,commentId:String(data.id || '')}
      }));
      notice('Kommentar veröffentlicht.');
    } catch(e) {
      notice(saved?'Kommentar gespeichert. Die Ansicht konnte nicht aktualisiert werden.':e.message,true);
    } finally {
      pending.delete(form);
      if(submit) submit.disabled = !input?.value?.trim() && (viewer || discussion);
    }
  }

  async function savePost(form) {
    if (pending.has(form)) return;
    const editor = document.getElementById('postContent');
    const text = editor?.value?.trim() || '';
    const image = document.getElementById('composerImage')?.files?.[0] || null;
    const clip = document.getElementById('hntClipFile')?.files?.[0] || null;
    const pollOn = document.querySelector('[data-extra="poll"][aria-pressed="true"]');
    const question = pollOn ? document.getElementById('hntPollQuestion')?.value?.trim() : '';
    const options = pollOn ? [...document.querySelectorAll('#hntPollOptions input')]
      .map(e=>e.value.trim()).filter(Boolean) : [];

    const valid = {valid:true,hasContent:false,message:''};
    document.dispatchEvent(new CustomEvent('hnt:composer:validate',{detail:valid}));
    if(!valid.valid){notice(valid.message || 'Bitte Eingaben überprüfen.',true);return;}
    if (!text && !image && !clip && options.length<2){notice('Bitte Text oder Medien hinzufügen.',true);return;}
    if(image && clip){notice('Bitte nur ein Bild oder einen Clip anhängen.',true);return;}
    if(pollOn && options.length<2){notice('Eine Umfrage braucht mindestens zwei Antworten.',true);return;}
    if (text.length>5000){notice('Dein Beitrag ist zu lang.',true);return;}

    const payload = new FormData();
    payload.append('visibility','public'); // Composer currently shows public feed
    if(text) payload.append('body',text);
    if(image) payload.append('media[]',image,image.name);
    if(clip) payload.append('media[]',clip,clip.name);
    if(pollOn) {
      payload.append('poll_question',question);
      options.forEach(option=>payload.append('poll_options[]',option));
    }
    const submit=form.querySelector('[type="submit"]');
    pending.add(form);if(submit)submit.disabled=true;
    let published = false;
    try {
      const data = await post('/feed',payload);
      if (!data?.ok || !data?.post_id) throw new Error('Unvollständige Serverantwort.');
      published=true;
      const card = await loadCard(data.post_id);
      list.prepend(card);
      document.getElementById('feedEmpty')?.setAttribute('hidden','');
      document.dispatchEvent(new CustomEvent('hnt:composer:posted',{detail:{card}}));
      document.getElementById('removePreview')?.click();
      form.reset();
      document.getElementById('composerClose')?.click();
      window.scrollTo({top:0,behavior:'smooth'});
      notice('Beitrag veröffentlicht.');
    } catch(e) {
      notice(published?'Beitrag gespeichert, konnte aber nicht automatisch angezeigt werden.':e.message,true);
    } finally {
      pending.delete(form);
      if(submit)submit.disabled=false;
    }
  }

  // Capture phase is essential: the original prototype's bubble handlers
  // would otherwise add a fake local post/comment. Stop them completely.
  document.addEventListener('submit', e => {
    const form=e.target;
    if (!form.matches('#newPostForm,.comment-form,#hntPdForm,#hntMvCommentForm')) return;
    e.preventDefault();
    e.stopImmediatePropagation();
    if(form.id==='newPostForm') void savePost(form);
    else void saveComment(form);
  },true);

  root.dataset.nextIntegration='posts-and-writes';
})();

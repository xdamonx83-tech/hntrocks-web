/* HNTv3 workspace bridge: styles and sizes the existing same-origin TipTap editor.
   The editor's React states, uploads, API token, autosave, translations, workflow
   and revision handling are deliberately not replaced or proxied. */
(() => {
  'use strict';
  const frame=document.getElementById('hntNewsEditorFrame');
  if(!frame)return;
  const base=(frame.dataset.baseUrl||'/next/news/editor').replace(/\/$/,'');
  const stylesheet=frame.dataset.editorStyle;
  let watcher=null, heightObserver=null, timer=null;
  let frameDoc=null, lastArticleId=null;

  const syncEditorId=()=>{
    try{
      const path=frame.contentWindow.location.pathname;
      const match=path.match(/^\/news\/([1-9]\d*)\/edit\/?$/);
      if(match){
        const id=match[1];
        if(id!==lastArticleId){
          lastArticleId=id;
          if(location.pathname!==base+'/'+id){
            history.replaceState(history.state,'',base+'/'+id);
          }
        }
      }
    }catch{}
  };

  const resize=()=>{
    try{
      const doc=frame.contentDocument;
      if(!doc||!doc.querySelector('.news-editor-page'))return;
      const stage=doc.querySelector('.news-editor-page .stage');
      if(!stage)return;
      const bottom=Math.max(
        stage.getBoundingClientRect().bottom+doc.defaultView.scrollY,
        stage.scrollHeight,
        doc.querySelector('.news-editor-page')?.scrollHeight||0
      );
      const height=Math.min(18000,Math.max(950,Math.ceil(bottom+40)));
      if(Math.abs(frame.offsetHeight-height)>8)frame.style.height=height+'px';
    }catch{}
  };

  const hookInternalLinks=doc=>{
    // Management lives at its own existing route, outside the HNTv3 iframe.
    doc.addEventListener('click',event=>{
      const node=event.target;
      if(!(node instanceof frame.contentWindow.Element))return;
      const anchor=node.closest('a[href]');
      if(!anchor)return;
      const href=new URL(anchor.href,location.origin);
      if(href.origin!==location.origin)return;
      if(href.pathname==='/news/manage'){
        event.preventDefault();
        event.stopImmediatePropagation();
        location.assign('/news/manage');
      }
    },true);
  };

  const decorate=()=>{
    try{
      const doc=frame.contentDocument;
      if(!doc)return false;
      const page=doc.querySelector('.news-editor-page');
      if(!page)return false;
      if(doc!==frameDoc){
        frameDoc=doc;
        hookInternalLinks(doc);
      }
      if(!doc.getElementById('hntEditorSkin')){
        const sheet=doc.createElement('link');
        sheet.rel='stylesheet';sheet.id='hntEditorSkin';sheet.href=stylesheet;
        sheet.onload=resize;
        doc.head.append(sheet);
      }
      doc.documentElement.setAttribute('data-hnt-editor-embedded','1');
      frame.classList.remove('is-loading');
      if(!heightObserver && frame.contentWindow.ResizeObserver){
        heightObserver=new frame.contentWindow.ResizeObserver(resize);
        heightObserver.observe(page);
        const stage=page.querySelector('.stage');
        if(stage)heightObserver.observe(stage);
      }
      syncEditorId();
      resize();
      return true;
    }catch{return false}
  };

  const connected=()=>{
    if(watcher){clearInterval(watcher);watcher=null}
    if(heightObserver){heightObserver.disconnect();heightObserver=null}
    frameDoc=null;
    frame.classList.add('is-loading');
    let attempts=0;
    const poll=()=>{
      attempts++;
      if(decorate()||attempts>=180){
        clearInterval(watcher);watcher=null;
      }
    };
    watcher=setInterval(poll,150);
    poll();
  };

  frame.addEventListener('load',connected);
  timer=setInterval(()=>{syncEditorId();resize()},1600);
  window.addEventListener('resize',resize,{passive:true});
  connected();
})();

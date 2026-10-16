(function(){
  'use strict';
  const tooltip=document.getElementById('navTooltip');
  const allLinks=[...document.querySelectorAll('.nav-link[data-tooltip]')];
  let hoverTarget=null;
  function hideTooltip(){ tooltip.hidden=true;hoverTarget=null; }
  function showTooltip(el){
    if(!el || !el.isConnected)return;
    hoverTarget=el;
    const r=el.getBoundingClientRect();
    tooltip.querySelector('strong').textContent=el.dataset.tooltip||el.getAttribute('aria-label')||'';
    tooltip.querySelector('small').textContent=el.dataset.description||'';
    tooltip.hidden=false;
    const rail=Boolean(el.closest('.game-rail'));
    const t=tooltip.getBoundingClientRect();
    let x=rail?r.right+13:r.left+r.width/2-t.width/2;
    let y=rail?r.top+r.height/2-t.height/2:r.bottom+11;
    if(!rail && y+t.height>innerHeight-8)y=r.top-t.height-9;
    x=Math.max(8,Math.min(x,innerWidth-t.width-8));
    y=Math.max(8,Math.min(y,innerHeight-t.height-8));
    tooltip.style.left=x+'px';tooltip.style.top=y+'px';
  }
  allLinks.forEach(el=>{
    el.addEventListener('mouseenter',()=>showTooltip(el));
    el.addEventListener('mouseleave',hideTooltip);
    el.addEventListener('focus',()=>showTooltip(el));
    el.addEventListener('blur',hideTooltip);
  });
  window.addEventListener('scroll',hideTooltip,{passive:true});
  const moreBtn=document.getElementById('moreMenuToggle');
  const moreMenu=document.getElementById('moreMenu');
  let closingTimer;
  function clearClose(){clearTimeout(closingTimer)}
  function openMore(){document.dispatchEvent(new CustomEvent('hnt:dropdown-open',{detail:{origin:'more'}}));clearClose();hideTooltip();const r=moreBtn.getBoundingClientRect();moreMenu.hidden=false;const h=moreMenu.offsetHeight;moreMenu.style.left=Math.min(r.right+14,innerWidth-moreMenu.offsetWidth-10)+'px';moreMenu.style.top=Math.max(10,Math.min(r.top-150,innerHeight-h-10))+'px';moreBtn.setAttribute('aria-expanded','true')}
  function closeMore(){clearClose();moreMenu.hidden=true;moreBtn.setAttribute('aria-expanded','false')}
  function scheduleClose(){clearClose();closingTimer=setTimeout(()=>{if(!moreMenu.matches(':hover')&&!moreBtn.matches(':hover'))closeMore()},180)}
  moreBtn.addEventListener('mouseenter',openMore);
  moreBtn.addEventListener('mouseleave',scheduleClose);
  moreMenu.addEventListener('mouseenter',clearClose);
  moreMenu.addEventListener('mouseleave',scheduleClose);
  moreBtn.addEventListener('click',e=>{e.preventDefault();moreMenu.hidden?openMore():closeMore()});
  moreBtn.addEventListener('focus',openMore);
  document.addEventListener('pointerdown',event=>{if(!moreMenu.hidden&&!moreMenu.contains(event.target)&&event.target!==moreBtn)closeMore()});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!moreMenu.hidden){closeMore();moreBtn.focus()}});
  // The search modal also finds real HNT.ROCKS pages, not only sample course content.
  const searchInput=document.getElementById('searchInput');
  const searchResults=document.getElementById('searchResults');
  const destinations=[...new Map([...document.querySelectorAll('a.nav-link[href],.more-menu-item[href]')].map(a=>[a.href,{name:a.getAttribute('data-tooltip')||a.querySelector('strong')?.textContent||a.getAttribute('aria-label'),url:a.href}])).values()].filter(x=>x.name);
  function replaceResults(){
    const q=searchInput.value.trim().toLocaleLowerCase('de');
    const found=destinations.filter(x=>x.name.toLocaleLowerCase('de').includes(q));
    if(!q)return; // The existing course demo remains visible before typing.
    searchResults.replaceChildren();
    if(!found.length){const div=document.createElement('div');div.className='search-empty';div.textContent='Kein Menüpunkt gefunden.';searchResults.append(div);return}
    for(const item of found){const a=document.createElement('a');a.href=item.url;a.className='search-result';a.style.textDecoration='none';const span=document.createElement('span');span.textContent=item.name;const small=document.createElement('small');small.textContent='HNT.ROCKS öffnen ↗';a.append(span,small);searchResults.append(a)}
  }
  searchInput.addEventListener('input',()=>setTimeout(replaceResults,0));
})();


/* HNTv3 local-only Cups preview routing. Production URLs and Laravel remain untouched. */
(()=>{
 if(location.protocol!=='file:'&&!['localhost','127.0.0.1'].includes(location.hostname))return;
 document.addEventListener('click',event=>{
  const link=event.target.closest('a[href]');
  if(!link||event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey||link.target==='_blank')return;
  let url;try{url=new URL(link.href)}catch{return}
  if(url.hostname!=='hnt.rocks'||url.pathname.replace(/\/+$/,'')!=='/cups')return;
  event.preventDefault();
  if(!location.pathname.toLowerCase().endsWith('/cups.html'))location.href='/cups';
 });
})();

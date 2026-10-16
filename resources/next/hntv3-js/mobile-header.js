/* HNT.ROCKS mobile topbar — single source for search and routes.
   Move the real search button, never duplicate handlers or dropdown IDs. */
(()=>{
  'use strict';
  const topbar=document.querySelector('.topbar');
  const header=topbar?.querySelector('.header-left');
  const search=document.getElementById('searchTrigger');
  const rail= document.querySelector('.game-rail .rail-games');
  if(!topbar||!header||!search||!rail)return;
  const mq=window.matchMedia('(max-width:780px)');
  const searchPlaceholder=document.createComment('HNT search desktop position');
  search.parentNode.insertBefore(searchPlaceholder,search);

  const icon=name=>'<svg class="ph" aria-hidden="true" viewBox="0 0 256 256"><use href="#ph-'+name+'"></use></svg>';
  const routeData=[
    {href:'https://hnt.rocks/maps',title:'Maps',description:'Hunt-Karten',glyph:'map-trifold'},
    {href:'https://hnt.rocks/guides',title:'Guides',description:'Community-Guides',glyph:'graduation-cap'}
  ];
  const added=[];
  for (const item of routeData){
    const link=document.createElement('a');
    link.className='game-button icon-btn nav-link hnt-mobile-rail-route';
    link.href=item.href;
    link.setAttribute('aria-label',item.title);
    link.setAttribute('title',item.title);
    link.dataset.tooltip=item.title;
    link.dataset.description=item.description;
    link.innerHTML=icon(item.glyph);
    added.push(link);
  }
  // Place them after Feed, so Hunt's core pages are available without opening "More".
  const firstLink=rail.querySelector('.game-button');
  if(firstLink)firstLink.after(...added);
  else rail.append(...added);

  search.classList.add('hnt-compact-search');
  search.setAttribute('aria-label','HNT.ROCKS Suche öffnen');
  search.setAttribute('title','HNT.ROCKS durchsuchen');

  function sync(){
    const mobile=mq.matches;
    document.documentElement.classList.toggle('hnt-mobile-header-active',mobile);
    if(mobile){
      if(search.parentElement!==header)header.append(search);
    }else if(searchPlaceholder.parentNode){
      searchPlaceholder.after(search);
    }
  }
  mq.addEventListener('change',sync);
  sync();
})();
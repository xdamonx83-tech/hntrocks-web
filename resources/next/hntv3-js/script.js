'use strict';
const destinations = [
 {name:'Feed',type:'Community',href:'https://hnt.rocks/feed'},
 {name:'Moments',type:'Videos & Highlights',href:'https://hnt.rocks/moments'},
 {name:'Maps',type:'Interaktive Karten',href:'https://hnt.rocks/maps'},
 {name:'Guides',type:'Wissen & Tipps',href:'https://hnt.rocks/guides'},
 {name:'Cups',type:'Wettbewerbe',href:'https://hnt.rocks/cups'},
 {name:'Ready Lobbies',type:'Mitspieler finden',href:'https://hnt.rocks/ready-lobbies'},
 {name:'Teams',type:'Community',href:'https://hnt.rocks/teams'},
 {name:'Mitglieder',type:'Community',href:'https://hnt.rocks/members'},
 {name:'Rocks',type:'Belohnungen',href:'https://hnt.rocks/rocks'},
 {name:'Hall of Fame',type:'Wettbewerbe',href:'https://hnt.rocks/hall-of-fame'}
];
const modal = document.getElementById('modal');
const searchOverlay = document.getElementById('searchOverlay');
const searchInput = document.getElementById('searchInput');
let lastFocused = null;
function openModal(title,description,icon='arrow-up-right'){
  lastFocused=document.activeElement;
  document.getElementById('modalTitle').textContent=title;
  document.getElementById('modalDescription').textContent=description;
  document.querySelector('#modalSymbol use').setAttribute('href','#ph-'+icon);
  modal.hidden=false;
  document.getElementById('modalClose').focus();
}
function closeModal(){modal.hidden=true;lastFocused?.focus?.()}
function showSearch(){lastFocused=document.activeElement;searchOverlay.hidden=false;searchInput.value='';updateSearch();searchInput.focus()}
function hideSearch(){searchOverlay.hidden=true;lastFocused?.focus?.()}
function updateSearch(){
 const phrase=searchInput.value.trim().toLowerCase();
 const found=destinations.filter(item=>item.name.toLowerCase().includes(phrase));
 const node=document.getElementById('searchResults');
 node.replaceChildren();
 if(!found.length){const empty=document.createElement('div');empty.className='search-empty';empty.textContent='No matches found.';node.append(empty);return}
 found.forEach(item=>{
  const b=document.createElement('a');b.className='search-result';b.href=item.href;b.style.textDecoration='none';
  const label=document.createElement('span');label.textContent=item.name;
  const type=document.createElement('small');type.textContent=item.type;
  b.append(label,type);
  node.append(b)
 })
}
searchInput.addEventListener('input',updateSearch);
document.getElementById('searchTrigger').addEventListener('click',showSearch);
document.getElementById('modalClose').addEventListener('click',closeModal);
document.getElementById('modalOkay').addEventListener('click',closeModal);
modal.addEventListener('click',e=>{if(e.target===modal)closeModal()});
searchOverlay.addEventListener('click',e=>{if(e.target===searchOverlay)hideSearch()});
document.addEventListener('keydown',e=>{
 if(e.key==='Escape'){if(!modal.hidden)closeModal();if(!searchOverlay.hidden)hideSearch();return}
 if(e.key==='/'&&modal.hidden&&searchOverlay.hidden&& !['INPUT','TEXTAREA'].includes(document.activeElement.tagName)){e.preventDefault();showSearch()}
});
document.querySelectorAll('[data-course]').forEach(el=>el.addEventListener('click',()=>openModal(el.dataset.course,'Explore guides, gameplay mechanics and strategies for this game. This is a visual HTML template; connect the button to your actual course page.','arrow-up-right')));
document.querySelectorAll('[data-community]').forEach(el=>el.addEventListener('click',()=>openModal(el.dataset.community,'Discover updates, meet other players and participate in this community. Link this card to your community page.','users-three')));
document.querySelectorAll('[data-game]').forEach(el=>el.addEventListener('click',()=>openModal(el.dataset.game,`Switch to ${el.dataset.game} to explore its guides and community.`, 'game-controller')));
const actionNames={home:'Home',add:'Add a game',messages:'Messages',notifications:'Notifications','friend-requests':'Friend requests',library:'My library',activity:'Activity',friends:'Communities',learn:'Learning Hub',settings:'Settings',account:'Your account'};
document.querySelectorAll('[data-action]').forEach(el=>el.addEventListener('click',()=>{const title=actionNames[el.dataset.action]||'Menu';openModal(title,'This navigation item is ready to be connected to your website.')}));
// Native mouse-wheel support for the horizontally scrolling carousel, only on Shift+wheel.
document.querySelectorAll('.horizontal-track').forEach(track=>{
 track.addEventListener('wheel',event=>{if(event.shiftKey){event.preventDefault();track.scrollLeft+=event.deltaY}}, {passive:false});
});

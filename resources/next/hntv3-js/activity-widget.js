/* HNT.ROCKS — local Activity widget demo interaction */
(() => {
  const button=document.querySelector('[data-activity-toggle]');
  const more=document.getElementById('activityWidgetMore');
  if(!button||!more)return;
  const label=button.querySelector('span');
  button.addEventListener('click',()=>{
    const expanded=button.getAttribute('aria-expanded')==='true';
    button.setAttribute('aria-expanded',String(!expanded));
    more.hidden=expanded;
    if(label)label.textContent=expanded?(button.dataset.moreLabel||'Alle anzeigen'):(button.dataset.lessLabel||'Weniger anzeigen');
  });
})();

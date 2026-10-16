/* HNT.ROCKS — Dark Heart Embers. UI effect only; likes are handled by the existing feed/viewer scripts. */
(() => {
  'use strict';
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
  const palette=['#8fd3a3','#b2e3bf','#71c88e','#a7d7b1','#80b98f'];
  const brand='HR:\\'; // HNT.ROCKS brand logo, not hearts.
  let layer;
  function getLayer(){
    if(!layer || !layer.isConnected){
      layer=document.createElement('div');
      layer.className='hnt-heart-sparks';
      layer.setAttribute('aria-hidden','true');
      document.body.appendChild(layer);
    }
    return layer;
  }
  const viewerOpen=()=>document.getElementById('hntMediaViewer')?.classList.contains('is-open');
  const discussionOpen=()=>document.getElementById('hntPostDialog')?.classList.contains('is-open');
  function flicker(button){
    const target=button.querySelector('svg')||button;
    if(typeof target.animate!=='function')return;
    target.animate([
      {transform:'scale(1)',filter:'brightness(1)'},
      {transform:'scale(1.29) rotate(-10deg)',filter:'brightness(1.28)'},
      {transform:'scale(.91) rotate(4deg)',filter:'brightness(.93)'},
      {transform:'scale(1)',filter:'brightness(1)'}
    ],{duration:350,easing:'ease-out'});
  }
  function scatter(button){
    if(reduced.matches)return;
    const target=button.querySelector('svg')||button;
    const r=target.getBoundingClientRect();
    if(r.width<1||r.height<1)return;
    const x=r.left+r.width/2,y=r.top+r.height/2;
    const host=getLayer();
    flicker(button);
    // Five rising HNT.ROCKS logos, two subtle green cinders — deliberately restrained.
    for(let i=0;i<7;i++){
      const ash=i>=5;
      const el=document.createElement('span');
      el.className=ash?'hnt-heart-ember':'hnt-heart-spark';
      if(!ash){
        el.classList.add('hnt-brand-spark');
        el.style.color=palette[i%palette.length];
        el.textContent=brand;
        const size=[11,13,10,14,12][i];
        el.style.fontFamily='"Exo 2",Arial,sans-serif';
        el.style.fontWeight='800';
        el.style.letterSpacing='-0.8px';
        el.style.fontSize=size+'px';
        el.style.lineHeight='1';
        el.style.whiteSpace='nowrap';
        el.style.display='inline-flex';
        el.style.justifyContent='center';
        el.style.width='34px';
        el.style.height='18px';
        el.style.filter='drop-shadow(0 0 5px rgba(113,200,142,.55))';
      } else {
        el.style.background='#7bc894';
        el.style.boxShadow='0 0 6px rgba(113,200,142,.5)';
      }
      el.style.left=(x+(i-3)*1.8)+'px';
      el.style.top=(y-(ash?1:6))+'px';
      const dx=[-34,-16,4,24,37,-24,28][i],rise=[-79,-96,-65,-103,-80,-52,-70][i];
      const duration=ash?590:860+i*67;
      host.appendChild(el);
      const motion=el.animate([
        {transform:'translate(-50%,-20%) scale(.45) rotate(-12deg)',opacity:0,offset:0},
        {transform:'translate('+Math.round(dx*.15-50)+'%,-42%) scale(1) rotate(1deg)',opacity:ash?.45:.83,offset:.18},
        {transform:'translate('+Math.round(dx)+'px,'+rise+'px) scale('+(ash?.4:.48)+') rotate('+(-24+i*11)+'deg)',opacity:0,offset:1}
      ],{duration,delay:i*38,easing:'cubic-bezier(.2,.63,.34,1)',fill:'forwards'});
      motion.finished.then(()=>el.remove()).catch(()=>el.remove());
    }
  }
  // On the real Laravel feed, animate only after the server confirms a Like.
  document.addEventListener('hnt:like-confirmed',event=>{
    const button=event.detail?.button;
    if(event.detail?.reacted && button instanceof Element)scatter(button);
  });

  // A document-level listener runs after the original like handlers have updated aria-pressed.
  document.addEventListener('click',event=>{
    const button=event.target.closest('#hntMvLike,#hntPdLike,[data-comment-like],.feed-post [data-like]');
    if(!button)return;
    const newlyLiked=button.id==='hntMvLike'
      ? button.classList.contains('is-active')
      : button.getAttribute('aria-pressed')==='true';
    if(!newlyLiked)return;
    // The media-viewer button programmatically clicks its feed equivalent:
    // suppress the invisible duplicate burst behind the overlay.
    if(button.hasAttribute('data-like')&&(viewerOpen()||discussionOpen()))return;
    scatter(button);
  });
})();

/* HNT.ROCKS — Dark Heart Embers. UI effect only; likes are handled by the existing feed/viewer scripts. */
(() => {
  'use strict';
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
  const palette=['#a05a62','#bf7376','#8d464f','#c3887d','#74424a'];
  const heart='<svg aria-hidden="true" viewBox="0 0 256 256"><path d="M128 224 C114 211 30 153 30 94 C30 52 78 38 108 60 C117 67 124 76 128 84 C132 76 139 67 148 60 C178 38 226 52 226 94 C226 153 142 211 128 224 Z"/></svg>';
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
    // Five dark-red floating hearts, two dying cinders — deliberately restrained.
    for(let i=0;i<7;i++){
      const ash=i>=5;
      const el=document.createElement('span');
      el.className=ash?'hnt-heart-ember':'hnt-heart-spark';
      if(!ash){
        el.style.color=palette[i%palette.length];
        el.innerHTML=heart;
        const size=[10,13,9,15,11][i];
        el.style.width=size+'px';
        el.style.height=size+'px';
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

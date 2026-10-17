/* HNT.ROCKS: equipped Like cosmetics (visual only, never mutates Likes/XP). */
(()=>{
  'use strict';
  const reduced=window.matchMedia('(prefers-reduced-motion: reduce)');
  const effects={
    like_effect_soul_whisper:{tone:'#9ae7b0',slash:'#b4fad0',symbol:'✧',shape:'mark',glow:'rgba(104,233,150,.65)',rise:1.16},
    like_effect_blood_moon:{tone:'#f48b87',slash:'#e64f53',symbol:'✦',shape:'mark',glow:'rgba(237,72,68,.7)',rise:1},
    like_effect_golden_hunter:{tone:'#f9de9e',slash:'#e7ac43',symbol:'✧',shape:'mark',glow:'rgba(255,200,83,.7)',rise:1.04},
    like_effect_ashes:{tone:'#b7aaa2',slash:'#8f9691',symbol:'·',shape:'dust',glow:'rgba(195,190,174,.4)',rise:.83},
    like_effect_cursed:{tone:'#a4f38d',slash:'#7ed36a',symbol:'◆',shape:'mark',glow:'rgba(76,208,79,.85)',rise:1.15},
    like_effect_bayou_mist:{tone:'#afdec8',slash:'#7bbda7',symbol:'·',shape:'mist',glow:'rgba(96,189,152,.55)',rise:.7},
    like_effect_iron_sparks:{tone:'#ffbd77',slash:'#d68854',symbol:'╱',shape:'sparks',glow:'rgba(242,137,67,.75)',rise:1.22},
    like_effect_moonfire:{tone:'#bfecff',slash:'#69bee8',symbol:'✦',shape:'mark',glow:'rgba(77,172,238,.8)',rise:1.15}
  };
  const defaultStyle={tone:'#fff',slash:'#8fd3a3',symbol:'·',shape:'mark',glow:'rgba(113,200,142,.55)',rise:1};
  let layer=null;
  function getLayer(){
    if(layer?.isConnected)return layer;
    layer=document.createElement('div');
    layer.className='hnt-heart-sparks';
    layer.setAttribute('aria-hidden','true');
    // These fallbacks support the shop/inventory where the Next feed CSS is not loaded.
    Object.assign(layer.style,{position:'fixed',inset:'0',zIndex:'12010',overflow:'hidden',pointerEvents:'none'});
    document.body.appendChild(layer);
    return layer;
  }
  function sparkle(button,key,force=false){
    if(reduced.matches)return;
    const target=button.querySelector('svg,.ph')||button;
    const rect=target.getBoundingClientRect();
    if(rect.width<1||rect.height<1)return;
    const x=rect.left+rect.width/2,y=rect.top+rect.height/2;
    const effect=effects[key]||defaultStyle;
    const host=getLayer();
    if(!force&&typeof target.animate==='function'){
      target.animate([
        {transform:'scale(1)',filter:'brightness(1)'},
        {transform:'scale(1.24) rotate(-7deg)',filter:'brightness(1.3)'},
        {transform:'scale(.93) rotate(3deg)',filter:'brightness(.9)'},
        {transform:'scale(1)',filter:'brightness(1)'}
      ],{duration:350,easing:'ease-out'});
    }
    // Lightweight DOM particles; no SVG filters, video or third-party libraries.
    for(let i=0;i<7;i++){
      const mist=effect.shape==='mist',dust=effect.shape==='dust',iron=effect.shape==='sparks';
      const secondary=i>=5||((mist||dust||iron)&&i%2===1);
      const el=document.createElement('span');
      el.className=secondary?'hnt-heart-ember':'hnt-heart-spark';
      Object.assign(el.style,{position:'absolute',display:'inline-flex',alignItems:'center',justifyContent:'center',pointerEvents:'none',whiteSpace:'nowrap',transformOrigin:'center',willChange:'transform,opacity',left:(x+(i-3)*2)+'px',top:(y-(secondary?1:6))+'px',fontFamily:'"Exo 2",Arial,sans-serif',fontWeight:'800',fontSize:(secondary?12:[11,13,10,14,12][i%5])+'px',lineHeight:'1',filter:'drop-shadow(0 0 5px '+effect.glow+')'});
      if(secondary){
        if(mist||dust){
          el.style.width=(7+(i%3)*5)+'px';el.style.height='4px';
          el.style.borderRadius='50%';el.style.background=effect.tone;el.style.opacity='.55';
          el.style.filter='blur('+(mist?3:1)+'px)';
        }else if(iron){
          el.textContent='╱';el.style.color=effect.tone;
        }else{
          el.textContent=effect.symbol;el.style.color=effect.tone;
        }
      }else if(dust||iron){
        el.textContent=effect.symbol;el.style.color=effect.tone;
      }else{
        const name=document.createElement('span');name.textContent='HR:';name.style.color=effect.tone;
        const slash=document.createElement('span');slash.textContent='\\';slash.style.color=effect.slash;
        el.append(name,slash);
      }
      host.appendChild(el);
      const dx=[-34,-16,4,24,37,-24,28][i];
      const rise=Math.round([-79,-96,-65,-103,-80,-52,-70][i]*effect.rise);
      const duration=secondary?1250:1900+i*105;
      const motion=el.animate([
        {transform:'translate(-50%,-20%) scale(.6) rotate(-9deg)',opacity:0,offset:0},
        {transform:'translate('+Math.round(dx*.12)+'px,-14px) scale(1) rotate(0deg)',opacity:secondary?.45:.92,offset:.12},
        {transform:'translate('+Math.round(dx*.68)+'px,'+Math.round(rise*.7)+'px) scale(.88) rotate('+(-10+i*5)+'deg)',opacity:secondary?.3:.88,offset:.7},
        {transform:'translate('+dx+'px,'+rise+'px) scale('+(secondary?.4:.58)+') rotate('+(-17+i*7)+'deg)',opacity:0,offset:1}
      ],{duration,delay:i*95,easing:'cubic-bezier(.2,.63,.34,1)',fill:'forwards'});
      motion.finished.then(()=>el.remove()).catch(()=>el.remove());
    }
  }
  const keyForViewer=()=>document.body?.dataset.nextLikeEffect||'default';
  const viewerOpen=()=>document.getElementById('hntMediaViewer')?.classList.contains('is-open');
  const discussionOpen=()=>document.getElementById('hntPostDialog')?.classList.contains('is-open');
  document.addEventListener('hnt:like-confirmed',event=>{
    const button=event.detail?.button;
    if(event.detail?.reacted&&button instanceof Element)sparkle(button,keyForViewer());
  });
  document.addEventListener('click',event=>{
    if(!(event.target instanceof Element))return;
    const preview=event.target.closest('[data-hnt-like-preview]');
    if(preview){
      const key=preview.getAttribute('data-hnt-like-preview');
      if(key&&effects[key])sparkle(preview,key,true);
      return;
    }
    const button=event.target.closest('#hntMvLike,#hntPdLike,[data-comment-like],.feed-post [data-like]');
    if(!button)return;
    const newlyLiked=button.id==='hntMvLike'?button.classList.contains('is-active'):button.getAttribute('aria-pressed')==='true';
    if(!newlyLiked)return;
    if(button.hasAttribute('data-like')&&(viewerOpen()||discussionOpen()))return;
    sparkle(button,keyForViewer());
  });
  // Public preview hook is visual only. A visitor can preview without owning an item;
  // equip and purchase remain server-authorized.
  window.HNTLikeEffects={preview:(element,key)=>{if(element instanceof Element&&effects[key])sparkle(element,key,true);},keys:()=>Object.keys(effects)};
})();

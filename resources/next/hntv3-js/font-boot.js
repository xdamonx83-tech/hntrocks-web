/* HNT.ROCKS font boot: Exo 2 default; 32-font editor only when requested. */
(()=>{
  'use strict';
  const root=document.documentElement;
  let preview=false;
  try{preview=new URLSearchParams(window.location.search).get('fontlab')==='1'}catch{}
  root.dataset.hntFontlab=preview?'1':'0';
  root.dataset.hntFont='exo';
  root.style.setProperty('--hnt-preview-font','"Exo 2", Arial, sans-serif');
  if(!preview)return;
  const script=document.createElement('script');
  script.src='/build/next/hntv3-js/font-compare.js';
  script.async=true;
  script.dataset.hntFontLabLoader='1';
  document.head.append(script);
})();
/* HNT.ROCKS — Post type badges
 * Categories (story/question/tip) remain independent from attachments
 * (image/clip/poll); the attributes are ready for future backend mapping.
 * Every visible label is derived from structured data, never from text guessing.
 */
(() => {
  'use strict';
  const feed = document.getElementById('feedList');
  if (!feed) return;

  const typeCatalog = Object.freeze({
    story: { text:'Erlebnis', icon:'compass' },
    question: { text:'Frage', icon:'chat-circle-dots' },
    tip: { text:'Tipp', icon:'lightning' },
    media: { text:'Medium', icon:'image' },
    clip: { text:'Medium', icon:'film-strip' },
    poll: { text:'Umfrage', icon:'chart' },
    discovery: { text:'Kassenspot', icon:'crosshair' },
    loadout: { text:'Loadout', icon:'target' }
  });

  function symbol(name) {
    if (name === 'chart') {
      const icon = document.createElementNS('http://www.w3.org/2000/svg','svg');
      icon.setAttribute('viewBox','0 0 24 24');
      icon.setAttribute('aria-hidden','true');
      icon.classList.add('post-type-icon');
      const path=document.createElementNS('http://www.w3.org/2000/svg','path');
      path.setAttribute('d','M5 19v-7m6 7V5m6 14v-9M3 21h18');
      path.setAttribute('stroke','currentColor');
      path.setAttribute('stroke-width','1.9');
      path.setAttribute('stroke-linecap','round');
      path.setAttribute('fill','none');
      icon.append(path);
      return icon;
    }
    const svg=document.createElementNS('http://www.w3.org/2000/svg','svg');
    svg.classList.add('ph','post-type-icon');
    svg.setAttribute('viewBox','0 0 256 256');
    svg.setAttribute('aria-hidden','true');
    const use=document.createElementNS('http://www.w3.org/2000/svg','use');
    use.setAttribute('href','#ph-'+name);
    svg.append(use);
    return svg;
  }

  function metadata(card) {
    const old=card.dataset.postType || 'text';
    const category=card.dataset.postCategory || (old==='discovery'?'discovery':old==='loadout'?'loadout':'story');
    const format=card.dataset.postFormat ||
      (old==='poll'?'poll':old==='clip'?'clip':old==='media'?'image':card.querySelector('.media-art img')?'image':'text');
    card.dataset.postCategory=category;
    card.dataset.postFormat=format;
    return {category,format};
  }

  function visibleTypes(card) {
    const {category,format}=metadata(card);
    if(category==='discovery')return ['discovery'];
    if(category==='loadout')return ['loadout'];
    const hasPoll=card.dataset.postHasPoll==='1'||format==='poll';
    const categories=['story','question','tip'];
    const types=[];
    if(categories.includes(category))types.push(category);
    if(format==='clip'||format==='image')types.push(format==='clip'?'clip':'media');
    if(hasPoll)types.push('poll');
    return types.length?types:['story'];
  }

  function renderBadge(card) {
    if(!card?.classList.contains('feed-post'))return;
    const head=card.querySelector('.post-head');
    if(!head)return;

    let row=head.querySelector('.post-type-labels');
    if(!row) {
      row=document.createElement('div');
      row.className='post-type-labels';
      row.setAttribute('aria-label','Beitragskategorie und Medienart');
    }
    row.replaceChildren();
    const types=visibleTypes(card);
    types.forEach(type=>{
      const spec=typeCatalog[type];
      if(!spec)return;
      const pill=document.createElement('span');
      pill.className='post-type-pill post-type-'+type;
      pill.dataset.postBadge=type;
      pill.append(symbol(spec.icon),document.createTextNode(spec.text));
      pill.title=spec.text;
      row.append(pill);
    });
    const more=head.querySelector('.post-more-btn');
    if(more)head.insertBefore(row,more);
    else head.append(row);
  }

  // Static demo posts carry their explicit categories in data-post-category.
  feed.querySelectorAll('.feed-post').forEach(renderBadge);

  // The composer fires this after the native post body and extras are built,
  // before insertion, so category and format can be set consistently.
  document.addEventListener('hnt:composer:render',event=>{
    const card=event.detail?.card;
    if(!card)return;
    const selected=document.querySelector('.hnt-dispatch-chip[aria-pressed="true"]');
    const mode=selected?.dataset.dispatchMode || 'story';
    card.dataset.postCategory=['story','question','tip'].includes(mode)?mode:'story';
    const postType=card.dataset.postType;
    card.dataset.postFormat=postType==='poll'?'poll':
      postType==='clip'?'clip':
      card.querySelector('.media-art img')?'image':'text';
  });
  document.addEventListener('hnt:composer:posted',event=>renderBadge(event.detail?.card));
})();

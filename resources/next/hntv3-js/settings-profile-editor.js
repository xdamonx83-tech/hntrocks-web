/* HNT.ROCKS Settings / Hunter Profile Editor V2.
   Local preview only: no backend write or image upload. */
(()=>{
  'use strict';
  if(!['file:','http:','https:'].includes(location.protocol))return;
  const media={avatar:null,cover:null};
  let content=null,draft=null;
  const icon=n=>'<svg class="ph" viewBox="0 0 256 256" aria-hidden="true"><use href="#ph-'+n+'"></use></svg>';
  const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const options=(field,values,value)=>'<select id="hntSetting-'+field+'" data-hnt-field="'+field+'" class="hnt-settings-select">'+values.map(([k,label])=>'<option value="'+esc(k)+'"'+(value===k?' selected':'')+'>'+esc(label)+'</option>').join('')+'</select>';
  const field=(key,label,html,help='')=>'<div class="hnt-settings-field"><label for="hntSetting-'+key+'">'+label+'</label>'+html+(help?'<p>'+help+'</p>':'')+'</div>';
  const card=(title,subtitle,inner)=>'<section class="hnt-settings-card hnt-profile-edit-card"><div class="hnt-settings-card-head"><h3>'+title+'</h3><p>'+subtitle+'</p></div>'+inner+'</section>';
  const info='<p class="hnt-settings-info">'+icon('shield-check')+' Vorschau für das neue HNTv3-Profil. Änderungen werden derzeit nicht an dein echtes Konto übertragen.</p>';

  function render(s){
    return card('Avatar & Titelbild','Dein Profil direkt im gewohnten HNTv3-Layout gestalten.',
      '<div class="hnt-profile-edit-visual">'+
        '<div class="hnt-profile-edit-cover"><img id="hntProfileEditCover" src="/build/next/hntv3-assets/profile-hero-bayou.jpg" alt="Titelbild-Vorschau"><span class="hnt-profile-edit-cover-tag">'+icon('image')+' TITELBILD</span>'+
        '<button type="button" data-hnt-profile-image-pick="cover" class="hnt-profile-edit-cover-btn">'+icon('pencil-simple')+' Bild ändern</button></div>'+
        '<div class="hnt-profile-edit-idrow"><div class="hnt-profile-edit-avatar"><img src="/build/next/hntv3-assets/profile-avatar.jpg" id="hntProfileEditAvatar" alt="Avatar-Vorschau">'+
        '<button type="button" data-hnt-profile-image-pick="avatar" aria-label="Avatar ändern" title="Avatar ändern">'+icon('pencil-simple')+'</button></div>'+
        '<div class="hnt-profile-edit-name"><small>HR:&#92;PLAYER</small><strong id="hntProfileEditName">KrispieArmy</strong><p id="hntProfileEditBio">Profilbeschreibung</p></div>'+
        '<span class="hnt-profile-edit-indicator">LIVE-VORSCHAU</span></div>'+
      '</div>'+
      '<div class="hnt-profile-edit-image-controls">'+
        '<div class="hnt-profile-edit-image-row"><div><strong>'+icon('image')+' Profilbild</strong><small>JPG, PNG oder WebP · max. 8 MB</small></div><div><button type="button" class="hnt-settings-secondary" data-hnt-profile-image-pick="avatar">Bild wählen</button><button type="button" class="hnt-profile-edit-link" data-hnt-profile-image-reset="avatar">Zurücksetzen</button></div></div>'+
        '<div class="hnt-profile-edit-image-row"><div><strong>'+icon('image')+' Titelbild</strong><small>Breites Motiv empfohlen · max. 8 MB</small></div><div><button type="button" class="hnt-settings-secondary" data-hnt-profile-image-pick="cover">Bild wählen</button><button type="button" class="hnt-profile-edit-link" data-hnt-profile-image-reset="cover">Zurücksetzen</button></div></div>'+
        '<div class="hnt-profile-edit-sliders">'+
          '<label for="hntSetting-avatarY">Avatarausschnitt <span id="hntProfileEditAvatarPos"></span><input id="hntSetting-avatarY" data-hnt-field="avatarY" type="range" min="0" max="100" step="1" value="'+esc(s.avatarY)+'"/></label>'+
          '<label for="hntSetting-coverY">Titelbildausschnitt <span id="hntProfileEditCoverPos"></span><input id="hntSetting-coverY" data-hnt-field="coverY" type="range" min="0" max="100" step="1" value="'+esc(s.coverY)+'"/></label>'+
        '</div>'+
        '<input type="file" id="hntProfileEditAvatarFile" data-hnt-profile-image-input="avatar" accept="image/png,image/jpeg,image/webp" hidden tabindex="-1"/>'+
        '<input type="file" id="hntProfileEditCoverFile" data-hnt-profile-image-input="cover" accept="image/png,image/jpeg,image/webp" hidden tabindex="-1"/>'+
        '<p class="hnt-profile-edit-image-note">'+icon('shield-check')+' Bilder werden nur vorübergehend im Browser angezeigt – nicht hochgeladen oder dauerhaft gespeichert.</p>'+
      '</div>')+
      card('Profilinformationen','Deine Angaben für die Community.',
      '<div class="hnt-settings-fields hnt-profile-edit-fields">'+
        field('displayName','Hunter-Name','<input class="hnt-settings-input" id="hntSetting-displayName" data-hnt-field="displayName" maxlength="30" value="'+esc(s.displayName)+'"/>','Wird während der Eingabe direkt oben angezeigt.')+
        field('region','Region',options('region',[['eu','Europa'],['na','Nordamerika'],['sa','Südamerika'],['asia','Asien']],s.region))+
        '<div class="hnt-profile-edit-full">'+field('about','Über mich','<textarea class="hnt-settings-textarea" id="hntSetting-about" data-hnt-field="about" rows="3" maxlength="280">'+esc(s.about)+'</textarea>','Kurze Vorstellung, maximal 280 Zeichen.')+'</div>'+
        field('favoriteMap','Lieblingskarte',options('favoriteMap',[['stillwater','Stillwater Bayou'],['lawson','Lawson Delta'],['desalle','DeSalle'],['mammons','Mammon’s Gulch']],s.favoriteMap))+
        field('playStyle','Spielstil',options('playStyle',[['team','Teamorientiert'],['tactical','Taktisch'],['solo','Solo'],['aggressive','Offensiv']],s.playStyle))+
      '</div>')+
      card('Weitere Hunter-Einstellungen','Diese Bereiche sind bereits im Settings-Modal vorhanden.',
       '<div class="hnt-profile-edit-jumps">'+
       '<button type="button" data-hnt-profile-editor-go="dna">'+icon('target')+' <span><strong>Hunter DNA</strong><small>Voice, Erfahrung, Ziele und Mentor</small></span>'+icon('arrow-right')+'</button>'+
       '<button type="button" data-hnt-profile-editor-go="connections">'+icon('game-controller')+' <span><strong>Gaming-Verknüpfungen</strong><small>Steam und Xbox verwalten</small></span>'+icon('arrow-right')+'</button></div>')+info;
  }
  function clear(which){
    if(media[which]?.url)URL.revokeObjectURL(media[which].url);
    media[which]=null;
  }
  function discard(){
    clear('avatar');clear('cover');
  }
  function preview(){
    if(!content||!content.isConnected)return;
    const find=id=>content.querySelector('#'+id);
    if(find('hntProfileEditName'))find('hntProfileEditName').textContent=draft?.displayName||'Hunter';
    if(find('hntProfileEditBio'))find('hntProfileEditBio').textContent=draft?.about||'Noch keine Beschreibung hinterlegt.';
    for(const [name,id,source,key,label] of [
      ['avatar','hntProfileEditAvatar','/build/next/hntv3-assets/profile-avatar.jpg','avatarY','hntProfileEditAvatarPos'],
      ['cover','hntProfileEditCover','/build/next/hntv3-assets/profile-hero-bayou.jpg','coverY','hntProfileEditCoverPos']
    ]){
      const img=find(id);
      if(img){const chosen=media[name]?.url||source;if(img.getAttribute('src')!==chosen)img.src=chosen;
        img.style.objectPosition='50% '+Math.max(0,Math.min(100,Number(draft?.[key])||0))+'%';}
      if(find(label))find(label).textContent=String(draft?.[key]??50)+' %';
    }
  }
  function feedback(message){
    const el=document.querySelector('#hntSettingsFeedback');if(el)el.textContent=message;
  }
  function afterSave(saved){
    const profile=document.querySelector('.hnt-v5-page');if(!profile)return;
    const name=profile.querySelector('#hntProfileName'),bio=profile.querySelector('.hnt-v5-bio'),about=profile.querySelector('.hnt-v5-overview-about>p');
    if(name)name.textContent=saved.displayName||'Hunter';
    if(bio)bio.textContent=saved.about||'';
    if(about)about.textContent=saved.about||'';
    for(const [key,sel,pos] of [['avatar','.hnt-v5-portrait img','avatarY'],['cover','.hnt-v5-cover img','coverY']]){
      const img=profile.querySelector(sel);if(!img)continue;
      if(media[key]?.url)img.src=media[key].url;
      img.style.objectPosition='50% '+(saved[pos]??50)+'%';
    }
  }
  document.addEventListener('click',e=>{
    const overlay=e.target.closest('#hntSettingsOverlay');if(!overlay)return;
    const choose=e.target.closest('[data-hnt-profile-image-pick]');
    if(choose){const what=choose.dataset.hntProfileImagePick;content?.querySelector('[data-hnt-profile-image-input="'+what+'"]')?.click();return;}
    const reset=e.target.closest('[data-hnt-profile-image-reset]');
    if(reset){clear(reset.dataset.hntProfileImageReset);preview();feedback('Bildvorschau zurückgesetzt.');return;}
    const jump=e.target.closest('[data-hnt-profile-editor-go]');
    if(jump){overlay.querySelector('[data-hnt-settings-tab="'+jump.dataset.hntProfileEditorGo+'"]')?.click();return;}
  });
  document.addEventListener('change',e=>{
    const input=e.target.closest('[data-hnt-profile-image-input]');
    if(!input||!e.target.closest('#hntSettingsOverlay'))return;
    const file=input.files?.[0],key=input.dataset.hntProfileImageInput;if(!file)return;
    if(!['image/jpeg','image/png','image/webp'].includes(file.type)||file.size>8*1024*1024){
      feedback('Bitte JPG, PNG oder WebP mit maximal 8 MB auswählen.');input.value='';return;
    }
    clear(key);media[key]={url:URL.createObjectURL(file),name:file.name};
    preview();feedback('Bild für die lokale Vorschau geladen. Kein Upload an HNT.ROCKS.');
  });
  document.addEventListener('input',e=>{
    if(!e.target.matches('#hntSettingsOverlay [data-hnt-field]'))return;
    if(!['displayName','about','avatarY','coverY'].includes(e.target.dataset.hntField))return;
    preview();
  });
  window.addEventListener('beforeunload',discard);
  window.HNTProfileEditorPreview={
    render,mount(node,s){content=node;draft=s;preview();},afterSave,discard,
    hasImages(){return !!(media.avatar||media.cover)}
  };
  try{
    const raw=JSON.parse(localStorage.getItem('hnt-v3-settings-preview-v1')||'null');
    if(raw&&typeof raw==='object')afterSave({
      displayName:typeof raw.displayName==='string'?raw.displayName:'KrispieArmy',
      about:typeof raw.about==='string'?raw.about:'',
      avatarY:raw.avatarY||'50',coverY:raw.coverY||'50'
    });
  }catch{}
})();
#!/usr/bin/env bash
set -euo pipefail
LIVE=/home/users/hunthub/www/hnt.rocks
ASSET="$LIVE/public/app/assets/page-DzV1O2672.js"
BASE=984b72e52eef916cb9d785fffd54f4f234cfb00d
BR=origin/feature/landing-news-arsenal-seo-20261004
FILES="app/Http/Controllers/Api/V1/LandingSummaryController.php app/Http/Controllers/Marketing/LandingPageController.php app/Services/Marketing/LandingDiscoveryService.php public/assets/socialite/css/public-landing.css resources/lang/de/ui.php resources/lang/en/ui.php resources/lang/es/ui.php resources/lang/ru/ui.php resources/views/react/landing-seo.blade.php"
test -f "$ASSET" && test -f "$LIVE/public/app/assets/index-SX_Kx1mv.js" || { echo "STOPP: Live-Assets stimmen nicht"; exit 1; }
STAGE=$(mktemp -d)
trap 'rm -rf "$STAGE"' EXIT
BACKUP="/home/users/hunthub/backups/landing-$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP"
for file in $FILES; do
 if [ -f "$LIVE/$file" ]; then
  expected=$(git -C "$LIVE" rev-parse "$BASE:$file")
  current=$(git -C "$LIVE" hash-object "$LIVE/$file")
  if [ "$expected" != "$current" ]; then echo "STOPP: Lokal geändert: $file"; exit 1; fi
  mkdir -p "$BACKUP/$(dirname "$file")"
  cp -a "$LIVE/$file" "$BACKUP/$file"
 elif [ "$file" != app/Services/Marketing/LandingDiscoveryService.php ]; then
  echo "STOPP: Fehlende Datei: $file"; exit 1
 fi
 mkdir -p "$STAGE/$(dirname "$file")"
 git -C "$LIVE" show "$BR:$file" > "$STAGE/$file"
 if [[ "$file" == *.php && "$file" != *.blade.php ]]; then php -l "$STAGE/$file" >/dev/null; fi
done
cp -a "$ASSET" "$BACKUP/landing-original.js"
cp -a "$ASSET" "$STAGE/landing.js"
export HNT_ASSET="$STAGE/landing.js"
node <<'JS'
const fs=require("fs"),path=process.env.HNT_ASSET;
let code=fs.readFileSync(path,"utf8"),tick=String.fromCharCode(96);
function change(a,b){a=a.replaceAll("§",tick);b=b.replaceAll("§",tick);
const at=code.indexOf(a);if(at<0||code.indexOf(a,at+1)>=0)throw Error("STOPP: Ungültiger Bundle-Anker "+a.slice(0,70));
code=code.slice(0,at)+b+code.slice(at+a.length)}
function extension(){/*
function HntPreload(){try{const x=document.getElementById("hnt-landing-discovery");return x?.textContent?JSON.parse(x.textContent):null}catch{return null}}
function HntLabel(lang,key){const x=HntPreload();return x?.labels?.[lang]?.[key]||x?.labels?.en?.[key]||key}
function HntSections({locale}){
const E=d.createElement,[data,setData]=(0,d.useState)(()=>HntPreload());
(0,d.useEffect)(()=>{const ac=new AbortController();
fetch("/api/v1/landing?locale="+encodeURIComponent(locale),{signal:ac.signal,headers:{"Accept":"application/json"}})
.then(r=>{if(!r.ok)throw Error(String(r.status));return r.json()})
.then(r=>setData({locale,latest_news:r.latest_news||[],arsenal_preview:r.arsenal_preview||[]})).catch(()=>{});
return()=>ac.abort()},[locale]);
const l=k=>HntLabel(locale,k),news=data?.locale===locale?data.latest_news||[]:[],
weapons=data?.locale===locale?data.arsenal_preview||[]:[],
arrow=()=>E("i",{className:"ph ph-arrow-up-right","aria-hidden":true}),
title=(prefix,path,id,btn)=>E("div",{className:"public-discovery-heading"},
 E("div",null,E("span",{className:"public-eyebrow"},l(prefix+"_eyebrow")),
 E("h2",{id},l(prefix+"_title")),E("p",null,l(prefix+"_body"))),
 E("a",{className:"public-button "+btn,href:path},l(prefix+"_cta")," ",arrow())),
newsCard=item=>E("article",{className:"public-news-card",key:item.url},
 E("a",{className:"public-news-image",href:item.url,tabIndex:-1,"aria-hidden":true},
 item.image_url?E("img",{src:s(item.image_url),alt:"",loading:"lazy"}):E("i",{className:"ph ph-newspaper-clipping"})),
 E("div",{className:"public-news-card-copy"},
 E("div",{className:"public-discovery-meta"},item.category?E("span",null,item.category):null,
 item.published_at?E("time",{dateTime:item.published_at},new Date(item.published_at).toLocaleDateString(locale)):null),
 E("h3",null,E("a",{href:item.url},item.title)),item.excerpt?E("p",null,item.excerpt):null,
 E("a",{className:"public-card-link",href:item.url},l("news_cta")," ",arrow()))),
weaponCard=item=>E("article",{className:"public-arsenal-card",key:item.slug},
 E("a",{className:"public-arsenal-image",href:item.url,tabIndex:-1,"aria-hidden":true},
 item.image_url?E("img",{src:s(item.image_url),alt:"",loading:"lazy"}):E("i",{className:"ph ph-crosshair"})),
 E("div",{className:"public-arsenal-card-copy"},
 E("span",{className:"public-arsenal-category"},item.category),
 E("h3",null,E("a",{href:item.url},item.name)),
 item.ammo_type?E("span",{className:"public-arsenal-ammo"},item.ammo_type):null,
 E("a",{className:"public-card-link",href:item.url},l("arsenal_details")," ",arrow())));
return E(d.Fragment,null,
 E("section",{className:"public-section public-news-section","aria-labelledby":"public-news-title"},
 E("div",{className:"public-container"},title("news","/news","public-news-title","secondary"),
 E("div",{className:"public-news-grid"},news.length?news.map(newsCard):E("p",{className:"public-discovery-empty"},l("news_empty"))))),
 E("section",{className:"public-section public-arsenal-section","aria-labelledby":"public-arsenal-title"},
 E("div",{className:"public-container"},title("arsenal","/arsenal","public-arsenal-title","primary"),
 E("div",{className:"public-arsenal-grid"},weapons.length?weapons.map(weaponCard):E("p",{className:"public-discovery-empty"},l("arsenal_empty"))))));
}
*/}
let added=extension.toString().split("/*")[1]?.split("*/")[0];
if(!added)throw Error("STOPP: React-Erweiterung fehlt");
change("function y(){",added+"function y(){");
change("V=[{label:x(§navCommunity§),to:§/feed§},{label:x(§navMoments§),to:§/moments§},{label:x(§navMaps§),to:§/maps§},",
"V=[{label:x(§navCommunity§),to:§/feed§},{label:x(§navMoments§),to:§/moments§},{label:x(§navMaps§),to:§/maps§},{label:HntLabel(O,§nav_news§),to:§/news§},{label:HntLabel(O,§nav_arsenal§),to:§/arsenal§},");
change("(0,f.jsx)(i,{to:§/maps§,children:x(§navMaps§)}),",
"(0,f.jsx)(i,{to:§/maps§,children:x(§navMaps§)}),(0,f.jsx)(i,{to:§/news§,children:HntLabel(O,§nav_news§)}),(0,f.jsx)(i,{to:§/arsenal§,children:HntLabel(O,§nav_arsenal§)}),");
change("(0,f.jsx)(§section§,{className:§public-section public-modules-section§",
"(0,f.jsx)(HntSections,{locale:O}),(0,f.jsx)(§section§,{className:§public-section public-modules-section§");
fs.writeFileSync(path,code);
JS
node --input-type=module --check < "$STAGE/landing.js" >/dev/null
for file in $FILES; do
 if [ -f "$LIVE/$file" ]; then
  chown --reference="$LIVE/$file" "$STAGE/$file"
  chmod --reference="$LIVE/$file" "$STAGE/$file"
 fi
done
chown --reference="$ASSET" "$STAGE/landing.js"
chmod --reference="$ASSET" "$STAGE/landing.js"
for file in $FILES; do
 mkdir -p "$LIVE/$(dirname "$file")"
 mv -f "$STAGE/$file" "$LIVE/$file"
done
mv -f "$STAGE/landing.js" "$ASSET"
(cd "$LIVE" && php artisan view:clear >/dev/null)
echo "LANDING NEWS & ARSENAL LIVE"
echo "Backup: $BACKUP"

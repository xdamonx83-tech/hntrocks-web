"use strict";
const fs = require("fs");
const [indexFile,pageFile,indexOutput,pageOutput] = process.argv.slice(2);
if (![indexFile,pageFile,indexOutput,pageOutput].every(Boolean)) throw Error("Expected 4 file paths");
let index=fs.readFileSync(indexFile,"utf8"),page=fs.readFileSync(pageFile,"utf8");
const q=String.fromCharCode(96);
function replaceOne(src,find,replacement){
  const at=src.indexOf(find);
  if(at<0||src.indexOf(find,at+1)!==-1)throw Error("STOPP: Unbekannte Live-Dateiversion ("+find.slice(0,65)+")");
  return src.slice(0,at)+replacement+src.slice(at+find.length);
}
if(page.includes("hntOptimizeCashImage")||index.includes("hntCashSpotDescription"))
  throw Error("Already patched");

const at=index.indexOf("cash-spots");
if(at<0)throw Error("API route not found");
const near=index.slice(Math.max(0,at-1100),at);
const re=/([A-Za-z_$][\w$]*)\.append\(([`'"])image\2,\s*([A-Za-z_$][\w$]*)\.image\)/g;
const matches=[...near.matchAll(re)];
if(matches.length!==1)throw Error("Unexpected FormData shape: "+matches.length);
const im=matches[0];
const form=im[1],body=im[3];
const replacement="("+im[0]+","+body+".description&&"+form+".append("+q+"description"+q+","+body+".description))";
index=replaceOne(index,im[0],replacement);

const submitRE=/await\s+([A-Za-z_$][\w$]*)\(([^,(){}]+),\{x:([^,{}]+),y:([^,{}]+),image:([^,{}]+)\}\)/g;
const submits=[...page.matchAll(submitRE)].filter(m=>{
 const errorPos=page.indexOf("Error submitting cash spot.",m.index);
 return errorPos>=0&&errorPos-m.index<950;
});
if(submits.length!==1)throw Error("Unexpected submit handler: "+submits.length);
const sm=submits[0];
const desc='document.querySelector(".cash-dialog textarea[data-hnt-cash-description]")?.value?.trim()||""';
page=replaceOne(page,sm[0],"await "+sm[1]+"("+sm[2]+",{x:"+sm[3]+",y:"+sm[4]+",image:await hntOptimizeCashImage("+sm[5]+"),description:"+desc+"})");

const actionRE=/,\(0,([A-Za-z_$][\w$]*)\.(jsx|jsxs)\)\(([`'"])div\3,\{className:([`'"])cash-dialog-actions\4/;
const actionMatches=[...page.matchAll(new RegExp(actionRE.source,"g"))];
if(actionMatches.length!==1)throw Error("Cash Spot form actions changed: "+actionMatches.length);
const ac=actionMatches[0],rt=ac[1];
const textarea=", (0,"+rt+".jsxs)("+q+"label"+q+",{children:[hntCashLabel("+q+"description"+q+"),"+
 "(0,"+rt+".jsx)("+q+"textarea"+q+",{\"data-hnt-cash-description\":!0,maxLength:1000,rows:3,"+
 "placeholder:hntCashLabel("+q+"placeholder"+q+")})]}),"+
 "(0,"+rt+"."+ac[2]+")("+ac[3]+"div"+ac[3]+",{className:"+ac[4]+"cash-dialog-actions"+ac[4];
page=replaceOne(page,ac[0],textarea);

function hntCashLabel(key){
  const raw=(localStorage.getItem("i18nextLng")||document.documentElement.lang||navigator.language||"de").toLowerCase();
  const lang=raw.split(/[-_]/)[0];
  const values={
    de:{description:"Beschreibung (optional)",placeholder:"Fundort oder Besonderheiten beschreiben …"},
    en:{description:"Description (optional)",placeholder:"Describe the spot and surroundings …"},
    es:{description:"Descripción (opcional)",placeholder:"Describe el lugar y los alrededores …"},
    ru:{description:"Описание (необязательно)",placeholder:"Опишите место и ориентиры …"}
  };
  return (values[lang]||values.de)[key]||key;
}
async function hntOptimizeCashImage(file){
  if(!file||!["image/jpeg","image/png","image/webp"].includes(file.type))throw Error("Invalid image type");
  const img=await createImageBitmap(file);
  try{
    const c=document.createElement("canvas"),ctx=c.getContext("2d",{alpha:false});
    if(!ctx)throw Error("Image processing unavailable");
    for(const edge of [2560,2200,1800,1500,1200]){
      const scale=Math.min(1,edge/Math.max(img.width,img.height));
      c.width=Math.max(1,Math.round(img.width*scale));
      c.height=Math.max(1,Math.round(img.height*scale));
      ctx.fillStyle="#0f1619";ctx.fillRect(0,0,c.width,c.height);
      ctx.drawImage(img,0,0,c.width,c.height);
      for(const quality of [.85,.75,.63]){
        const blob=await new Promise(resolve=>c.toBlob(resolve,"image/webp",quality));
        if(!blob||blob.type!=="image/webp")throw Error("WebP unavailable");
        if(blob.size<=4*1024*1024)
          return new File([blob],file.name.replace(/\.[^.]+$/,"")+".webp",{type:"image/webp"});
      }
    }
    throw Error("Could not compress screenshot");
  }finally{img.close()}
}
page+="\n"+hntCashLabel.toString()+"\n"+hntOptimizeCashImage.toString();
page=page.replace("Error submitting cash spot.","Cash-Spot-Upload fehlgeschlagen.");
fs.writeFileSync(indexOutput,index);
fs.writeFileSync(pageOutput,page);
console.log("Only Cash Spot API helper and Maps detail module updated.");

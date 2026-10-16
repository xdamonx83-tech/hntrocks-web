const fs = require('node:fs');
const path = require('node:path');

// Preserve original HNTv3 asset and script URLs inside the isolated /next build.
for (const type of ['hntv3-assets', 'hntv3-js']) {
  const source = path.resolve(__dirname, '../resources/next/' + type);
  const dest = path.resolve(__dirname, '../public/build/next/' + type);
  if (!fs.existsSync(source)) throw new Error('HNTv3 directory missing: ' + source);
  fs.mkdirSync(dest, {recursive:true});
  fs.cpSync(source, dest, {recursive:true, force:true});
  console.log('HNTV3_COPIED', type, fs.readdirSync(dest).length);
}
// Local Video.js v10 neutral player. Single ESM bundle; no external CDN.
const esbuild = require('esbuild');
const videoEntry = path.resolve(__dirname, '../node_modules/@videojs/cdn/video-neutral.js');
if (!fs.existsSync(videoEntry)) throw new Error('Install @videojs/cdn first');
esbuild.buildSync({
  entryPoints:[videoEntry],bundle:true,platform:'browser',format:'esm',
  target:'es2022',minify:true,legalComments:'inline',
  outfile:path.resolve(__dirname,'../public/build/next/hntv3-js/videojs-neutral.bundle.js')
});
console.log('VIDEOJS_V10_BUNDLED');

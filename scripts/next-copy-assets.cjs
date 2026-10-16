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
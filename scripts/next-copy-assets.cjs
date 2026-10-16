const fs = require('node:fs');
const path = require('node:path');

// Preserve original HNTv3 raster/SVG asset filenames for the preview.
// Vite builds the CSS+JS. This copies the static image files referenced by
// the canonical HNTv3 layout into the matching public deployment location.
const source = path.resolve(__dirname, '../resources/next/hntv3-assets');
const dest = path.resolve(__dirname, '../public/build/next/hntv3-assets');
if (!fs.existsSync(source)) throw new Error('HNTv3 assets source missing: ' + source);
fs.mkdirSync(dest, { recursive: true });
fs.cpSync(source, dest, { recursive: true, force: true });
console.log('HNTV3_ASSETS_COPIED', fs.readdirSync(dest).length);
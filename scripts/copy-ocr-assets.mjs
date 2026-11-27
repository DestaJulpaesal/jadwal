// Menyalin aset OCR ke public/vendor/tesseract supaya di-host sendiri (CSP: hanya 'self').
// Pakai:  npm i tesseract.js  &&  node scripts/copy-ocr-assets.mjs
import { copyFile, mkdir, readFile, rename, stat, writeFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';
import path from 'node:path';

const OUT = path.resolve('public/vendor/tesseract');
const LANG_URL = 'https://raw.githubusercontent.com/tesseract-ocr/tessdata_fast/main/ind.traineddata';
const LANG_SHA256 = '69786901da87ab8766c1ea7fbb10b28f2110c14da3f6c8f2735df131fba95d88';

await mkdir(path.join(OUT, 'core'), { recursive: true });
await mkdir(path.join(OUT, 'lang'), { recursive: true });

await copyFile('node_modules/tesseract.js/dist/worker.min.js', path.join(OUT, 'worker.min.js'));
// OEM 1 (LSTM) hanya butuh varian *-lstm.wasm.js; browser memilih satu sesuai dukungan SIMD.
for (const name of ['tesseract-core-lstm', 'tesseract-core-simd-lstm', 'tesseract-core-relaxedsimd-lstm']) {
    await copyFile(`node_modules/tesseract.js-core/${name}.wasm.js`, path.join(OUT, 'core', `${name}.wasm.js`));
}

const target = path.join(OUT, 'lang', 'ind.traineddata');
let languageMatches = false;
try {
    const existing = await readFile(target);
    languageMatches = createHash('sha256').update(existing).digest('hex') === LANG_SHA256;
} catch (error) {
    if (error.code !== 'ENOENT') throw error;
}

if (!languageMatches) {
    const response = await fetch(LANG_URL);
    if (!response.ok) throw new Error(`Gagal mengunduh data bahasa: HTTP ${response.status}`);
    const languageData = Buffer.from(await response.arrayBuffer());
    if (createHash('sha256').update(languageData).digest('hex') !== LANG_SHA256) {
        throw new Error('Checksum ind.traineddata tidak cocok.');
    }

    const temporaryTarget = `${target}.tmp`;
    await writeFile(temporaryTarget, languageData);
    await rename(temporaryTarget, target);
}

if ((await stat(target)).size < 500_000) throw new Error('ind.traineddata tampak rusak (terlalu kecil).');

await writeFile(path.join(OUT, 'NOTICE.txt'),
    'tesseract.js, tesseract.js-core, dan tessdata_fast (ind) berlisensi Apache-2.0.\n' +
    'https://github.com/naptha/tesseract.js  https://github.com/tesseract-ocr/tessdata_fast\n');
console.log('Aset OCR siap di', OUT);

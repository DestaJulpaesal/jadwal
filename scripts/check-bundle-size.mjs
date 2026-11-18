import { readFile } from 'node:fs/promises';
import { gzipSync } from 'node:zlib';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const manifestPath = path.join(root, 'public', 'build', 'manifest.json');
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const jsBudget = 50_000;
const cssBudget = 20_000;

const collect = (key, visited = new Set(), assets = { js: new Set(), css: new Set() }) => {
    if (visited.has(key)) {
        return assets;
    }

    visited.add(key);
    const entry = manifest[key];
    if (!entry) {
        throw new Error(`Missing Vite manifest entry: ${key}`);
    }

    if (entry.file?.endsWith('.js')) {
        assets.js.add(entry.file);
    } else if (entry.file?.endsWith('.css')) {
        assets.css.add(entry.file);
    }

    (entry.css || []).forEach((file) => assets.css.add(file));
    (entry.imports || []).forEach((importKey) => collect(importKey, visited, assets));

    return assets;
};

const gzipSize = async (files) => {
    let total = 0;
    for (const file of files) {
        const content = await readFile(path.join(root, 'public', 'build', file));
        total += gzipSync(content).byteLength;
    }

    return total;
};

const appAssets = collect('resources/js/app.js');
const appCss = collect('resources/css/app.css').css;
const publicCss = collect('resources/css/public.css').css;
const appPageCss = new Set([...appAssets.css, ...appCss]);
const publicPageCss = new Set([...appAssets.css, ...publicCss]);
const pageCssEntries = Object.keys(manifest)
    .filter((key) => key.startsWith('resources/css/pages/') && manifest[key].isEntry);

const jsSize = await gzipSize(appAssets.js);
let largestCss = { entry: 'shared application styles', size: await gzipSize(appPageCss) };
const largestPublicCss = await gzipSize(publicPageCss);
if (largestPublicCss > largestCss.size) {
    largestCss = { entry: 'shared public styles', size: largestPublicCss };
}
for (const key of pageCssEntries) {
    const pageAssets = collect(key);
    const size = await gzipSize(new Set([...publicPageCss, ...pageAssets.css]));
    if (size > largestCss.size) {
        largestCss = { entry: key, size };
    }
}

const formatSize = (size) => `${(size / 1000).toFixed(2)} KB gzip`;
console.log(`JavaScript (${formatSize(jsSize)}): ${jsSize <= jsBudget ? 'PASS' : 'FAIL'} (budget ${formatSize(jsBudget)})`);
console.log(`CSS, ${largestCss.entry} (${formatSize(largestCss.size)}): ${largestCss.size <= cssBudget ? 'PASS' : 'FAIL'} (budget ${formatSize(cssBudget)})`);

if (jsSize > jsBudget || largestCss.size > cssBudget) {
    process.exitCode = 1;
}

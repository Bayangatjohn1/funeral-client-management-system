import { copyFile, mkdir, readFile, writeFile } from 'node:fs/promises';
import { resolve } from 'node:path';

const root = resolve(import.meta.dirname, '..');
const target = resolve(root, 'public/vendor/offline');
await mkdir(resolve(target, 'fonts'), { recursive: true });

for (const [source, destination] of [
    ['flatpickr/dist/flatpickr.min.js', 'flatpickr.min.js'],
    ['flatpickr/dist/flatpickr.min.css', 'flatpickr.min.css'],
    ['flatpickr/LICENSE.md', 'flatpickr-LICENSE.md'],
    ['chart.js/dist/chart.umd.js', 'chart.umd.js'],
    ['chart.js/LICENSE.md', 'chartjs-LICENSE.md'],
]) {
    await copyFile(resolve(root, 'node_modules', source), resolve(target, destination));
}

const fonts = [
    ['dm-sans', 'DM Sans', [400, 500, 600, 700]],
    ['syne', 'Syne', [700]],
    ['plus-jakarta-sans', 'Plus Jakarta Sans', [400, 500, 600, 700, 800]],
    ['jetbrains-mono', 'JetBrains Mono', [400, 500, 600]],
    ['inter', 'Inter', [400, 700]],
    ['libre-baskerville', 'Libre Baskerville', [400, 700]],
];
const rules = [];
for (const [id, name, weights] of fonts) {
    for (const weight of weights) {
        const file = `${id}-latin-${weight}-normal.woff2`;
        await copyFile(resolve(root, `node_modules/@fontsource/${id}/files/${file}`), resolve(target, 'fonts', file));
        rules.push(`@font-face{font-family:'${name}';font-style:normal;font-weight:${weight};font-display:swap;src:url('./fonts/${file}') format('woff2')}`);
    }
    const license = await readFile(resolve(root, `node_modules/@fontsource/${id}/LICENSE`), 'utf8');
    await writeFile(resolve(target, 'fonts', `${id}-LICENSE.txt`), license);
}
await writeFile(resolve(target, 'fonts.css'), rules.join('\n') + '\n');
console.log('Prepared local fonts, Flatpickr and Chart.js in public/vendor/offline.');

// Copies the public site's built stylesheet and fonts (hashed names, from the
// Vite manifest) to stable paths for the design-sync converter:
//   .design-sync/.cache/webis-site.css  -> cfg.cssEntry
//   .design-sync/.cache/fonts/fonts.css -> cfg.extraFonts (+ its font files)
// Run after `npm run build` (see cfg.buildCmd).
import { copyFileSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = join(dirname(fileURLToPath(import.meta.url)), '..');
const build = join(root, 'public', 'build');
const manifest = JSON.parse(readFileSync(join(build, 'manifest.json'), 'utf8'));
const cache = join(root, '.design-sync', '.cache');
const fontsDir = join(cache, 'fonts');

mkdirSync(fontsDir, { recursive: true });

const site = manifest['resources/css/app.css'];

if (!site) {
    throw new Error(
        'resources/css/app.css is not in the Vite manifest - run `npm run build` first.',
    );
}

copyFileSync(join(build, site.file), join(cache, 'webis-site.css'));

const fontsKey = Object.keys(manifest).find((key) =>
    /^_fonts-.*\.css$/.test(key),
);

if (!fontsKey) {
    throw new Error(
        'No fonts stylesheet in the Vite manifest - check the bunny() fonts in vite.config.ts.',
    );
}

const fontsCss = readFileSync(join(build, manifest[fontsKey].file), 'utf8');
const files = [...fontsCss.matchAll(/url\(["']?([^"')]+\.woff2?)["']?\)/g)].map(
    (match) => match[1],
);

// The font files sit beside the stylesheet in public/build/assets/.
for (const file of new Set(files)) {
    const name = file.split('/').pop();

    copyFileSync(join(build, 'assets', name), join(fontsDir, name));
}

writeFileSync(
    join(fontsDir, 'fonts.css'),
    fontsCss.replace(
        /url\(["']?(?:[^"')]*\/)?([^/"')]+\.woff2?)["']?\)/g,
        'url("./$1")',
    ),
);

console.log(`prepared webis-site.css and ${new Set(files).size} font files`);

// Copies the hand-made foundation cards (.design-sync/cards/*.html) into the
// built bundle at guidelines/cards/, after the converter has run: Webis has no
// components, so the converter makes no cards of its own, and it copies only
// Markdown guidelines. Checks what package-validate.mjs checks for component
// cards (the @dsCard first line, <link> targets that resolve) plus that every
// class a card uses exists in the shipped stylesheet. Exits 1 on any failure.
//   node .design-sync/add-cards.mjs ./ds-bundle
import {
    copyFileSync,
    existsSync,
    mkdirSync,
    readFileSync,
    readdirSync,
} from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const out = resolve(process.argv[2] ?? './ds-bundle');
const source = join(dirname(fileURLToPath(import.meta.url)), 'cards');
const dest = join(out, 'guidelines', 'cards');
const css = readFileSync(join(out, '_ds_bundle.css'), 'utf8');
const failures = [];

mkdirSync(dest, { recursive: true });

for (const name of readdirSync(source).filter((file) =>
    file.endsWith('.html'),
)) {
    const html = readFileSync(join(source, name), 'utf8');

    if (
        !/^<!--\s*@dsCard\s+group="[^"]*"[^>]*-->/.test(html.split('\n', 1)[0])
    ) {
        failures.push(
            `${name}: first line isn't a <!-- @dsCard group="…" --> comment`,
        );
    }

    for (const [, href] of html.matchAll(/<link\b[^>]*\bhref="([^"]+)"/g)) {
        if (!/^https?:|^data:/.test(href) && !existsSync(resolve(dest, href))) {
            failures.push(
                `${name}: <link href="${href}"> doesn't resolve from guidelines/cards/`,
            );
        }
    }

    const classes = new Set(
        [...html.matchAll(/class="([^"]+)"/g)].flatMap(([, list]) =>
            list.split(/\s+/),
        ),
    );
    // Layout helpers the card defines in its own <style> are not site classes.
    const local = new Set(
        [...html.matchAll(/<style>([\s\S]*?)<\/style>/g)].flatMap(([, block]) =>
            [...block.matchAll(/\.([A-Za-z][\w-]*)/g)].map(([, cls]) => cls),
        ),
    );

    for (const cls of [...classes].filter((c) => !local.has(c))) {
        const escaped = cls.replace(/[.*+?^${}()|[\]\\:/]/g, (c) => `\\${c}`);

        if (!new RegExp(`\\.${escaped}(?![\\w-])`).test(css)) {
            failures.push(`${name}: class "${cls}" isn't in _ds_bundle.css`);
        }
    }

    copyFileSync(join(source, name), join(dest, name));
}

if (failures.length > 0) {
    console.error(failures.map((line) => `✗ ${line}`).join('\n'));
    process.exit(1);
}

console.log(`✓ ${readdirSync(dest).length} cards in guidelines/cards/`);

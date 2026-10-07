import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';

import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

/**
 * Make sure the `php` that build-time plugins shell out to is new enough.
 *
 * The Wayfinder plugin runs `php artisan wayfinder:generate` during the build.
 * On this Windows dev box an old XAMPP PHP (8.0) sits earlier on PATH than
 * Herd's, and Laravel 13 requires PHP >= 8.3 — so the build dies with an opaque
 * "Command failed" from rolldown. Prepend the newest Herd PHP when we can find
 * one (same helper as in Mydentist).
 *
 * No-op off Windows and when no Herd install is present, so CI and the Linux
 * deploy server are unaffected.
 */
function preferHerdPhp(): void {
    if (process.platform !== 'win32') return;

    const phpIsNewEnough = (dir: string): boolean => {
        try {
            const version = execFileSync(
                path.join(dir, 'php.exe'),
                ['-r', 'echo PHP_VERSION_ID;'],
                {
                    encoding: 'utf8',
                },
            );
            return Number(version.trim()) >= 80300;
        } catch {
            return false;
        }
    };

    // Already fine? Leave PATH alone.
    try {
        const current = execFileSync('php', ['-r', 'echo PHP_VERSION_ID;'], {
            encoding: 'utf8',
        });
        if (Number(current.trim()) >= 80300) return;
    } catch {
        // No php on PATH at all — fall through and try to supply one.
    }

    const herdBin = path.join(os.homedir(), '.config', 'herd', 'bin');
    if (!fs.existsSync(herdBin)) return;

    const candidate = fs
        .readdirSync(herdBin)
        .filter((entry) => /^php\d+$/.test(entry))
        .map((entry) => path.join(herdBin, entry))
        .filter((dir) => fs.existsSync(path.join(dir, 'php.exe')))
        .sort()
        .reverse()
        .find(phpIsNewEnough);

    if (candidate) {
        process.env.PATH = `${candidate}${path.delimiter}${process.env.PATH ?? ''}`;
    }
}

preferHerdPhp();

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                // Inertia admin CMS.
                'resources/js/app.ts',
                // Vue islands mounted into the public Blade pages. Kept as a
                // separate entry so SEO pages never load the Inertia runtime.
                'resources/js/public.ts',
            ],
            refresh: true,
            fonts: [
                // latin-ext is NOT optional on a Romanian site. The plugin
                // defaults to ['latin'], which covers â and î but NOT ă, ș or
                // ț — the browser then falls back to a system font for exactly
                // those glyphs, mid-word.
                //
                // Onest is the one typeface, site and admin alike. 300 is the
                // light weight of the large menu links.
                bunny('Onest', {
                    weights: [300, 400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext'],
                }),
                // Anton: the display face for big uppercase titles (the hero).
                // One weight only — never set it bold, or the browser fakes it.
                bunny('Anton', {
                    weights: [400],
                    subsets: ['latin', 'latin-ext'],
                }),
                // JetBrains Mono: only numerals and micro-labels (`font-micro`),
                // never running text. Small, so not preloaded.
                bunny('JetBrains Mono', {
                    weights: [400],
                    subsets: ['latin', 'latin-ext'],
                    display: 'swap',
                    preload: false,
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ]),
    // GSAP is only ever imported lazily (the public menu). Without this, the
    // dev server discovers it on first use, re-optimises and reloads the page.
    optimizeDeps: {
        include: ['gsap'],
    },
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});

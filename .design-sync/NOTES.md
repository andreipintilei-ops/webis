# design-sync notes — Webis

## How this repo syncs

- **Styles-only design system.** The public site is Laravel Blade + one Vite-built stylesheet (`resources/css/app.css`); there are no React components (the admin is Vue). The converter runs in tokens-only mode: `.design-sync/entry.mjs` exports nothing, the stylesheet ships as `_ds_bundle.css`, and the design agent builds with the site's CSS classes, taught by `.design-sync/conventions.md` (the README header).
- **Build first, then prepare:** `cfg.buildCmd` = `npm run build && node .design-sync/prepare.mjs`. The built CSS and font files have hashed names; `prepare.mjs` copies the current ones (from `public/build/manifest.json`) to `.design-sync/.cache/webis-site.css` and `.design-sync/.cache/fonts/` so `cssEntry` / `extraFonts` stay stable.
- **Package root is `.design-sync/`.** The repo's `package.json` has no `name`, so the converter's walk-up would reach the drive root. `.design-sync/package.json` (`"name": "webis"`) stops it there; `cssEntry` / `extraFonts` are relative to `.design-sync/`.
- **React for the preview runtime comes from `.ds-sync/node_modules`** (`npm i react@19 react-dom@19` there, beside esbuild / ts-morph / @types/react). The repo itself only has a stray transitive `react` without `react-dom`. Pass `--node-modules ./.ds-sync/node_modules` and `--entry ./.design-sync/entry.mjs`.
- **Render check skipped** (`--no-render-check`) with the owner's OK (2026-10-07): no components means no preview cards to screenshot, and no Playwright browser is installed on this machine.
- **Foundation cards are hand-made** (`.design-sync/cards/*.html`: colours, typography, buttons, section head, title chip, cards, product panel), added 2026-10-07 at the owner's request so the DS pane isn't empty. The converter only copies Markdown guidelines and the validator only checks `components/`, so `add-cards.mjs` copies them to `ds-bundle/guidelines/cards/` AFTER the driver run (the driver wipes `ds-bundle/`) and runs the same checks (@dsCard first line, links resolve, every class exists in `_ds_bundle.css`). They're outside `_ds_sync.json`, so they always re-upload; a card deleted from the repo must also be deleted from the project by hand.
- **Verify cards visually** with the machine's Chrome (no Playwright): `node .ds-sync/storybook/http-serve.mjs ./ds-bundle`, then open `/guidelines/cards/<name>.html`.
- **Two context rules the cards exposed** (now in `conventions.md`): a light-ground `.pill.btn-light` is violet only inside `.chapter`; a dark-ground `.pill.btn-dark` is violet only when the page has `[data-intro-backdrop][data-palette=violet]`. `project-card` / `chapter-chip` / `chapter-metric` take `--ink`/`--muted`/`--accent` from `.chapter` and render nearly invisible outside it.

## Commands

```sh
npm run build && node .design-sync/prepare.mjs
node .ds-sync/resync.mjs --config .design-sync/config.json --node-modules ./.ds-sync/node_modules \
  --entry ./.design-sync/entry.mjs --out ./ds-bundle --no-render-check [--remote .design-sync/.cache/remote-sync.json]
node .design-sync/add-cards.mjs ./ds-bundle
```

Upload `guidelines/cards/*` with every sync (full writes).

## Known render warns

- `[TOKENS_MISSING]` — `--fill`, `--i`, `--share`, `--n`, `--bar-height` are set per element in inline styles (ratings, stagger index, bar widths, explorer row count, chart bars); `--reka-*` are set at runtime by reka-ui (admin). Expected.
- `[FONT_MISSING]` "Impact", "Arial Narrow Bold" — fallbacks in the Anton stack only; Anton itself ships. Expected.
- `[RENDER_SKIPPED]` — by choice (above).

## Re-sync risks

- `conventions.md` names classes and tokens by hand — re-validate them against `ds-bundle/_ds_bundle.css` whenever the site's CSS changes (rename a class there, and the guide silently teaches a dead name).
- The shipped stylesheet is the whole public site CSS (including menu, page-transition and admin-adjacent utilities); classes no design should use are still present.
- Forgetting `npm run build` before `prepare.mjs` ships the previous build's CSS.

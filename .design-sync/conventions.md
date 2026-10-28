# Webis — how to build with this design system

**Styles only, no components.** Webis's site is server-rendered HTML styled by one stylesheet; `window.Webis` exports nothing. Build with plain HTML (or JSX) using the class names and tokens below — never invent look-alike styles. The truth is the bound stylesheet: `styles.css` → `fonts/fonts.css` (Onest, JetBrains Mono, Anton) + `_ds_bundle.css` (all component classes, Tailwind utilities, `:root` tokens). Read `_ds_bundle.css` before styling anything new.

## Setup

Wrap every page in `<div class="site-body font-sans antialiased">` — without `font-sans` text falls back to system fonts. Put `<span data-intro-backdrop data-palette="violet" hidden></span>` once in every page: it switches on the violet palette (dark-ground pills, accents) the homepage uses; without it they stay the dark default. No provider, no script. Content width: `max-width: var(--content-max); margin-inline: auto`; side gutter `padding-inline: var(--site-gap)`.

## Tokens (`var(--…)`)

| Token                                                       | Value / use                      |
| ----------------------------------------------------------- | -------------------------------- |
| `--violet-500` `#775afc`                                    | brand violet, accents, focus     |
| `--purple-500` `#a44cee`                                    | gradients with violet, hover     |
| `--violet-cta` / `--violet-cta-hover`                       | primary button fill / its hover  |
| `--navy-950` `#010f1c`                                      | ink on light                     |
| `--ink-muted` `#5b5f7a`                                     | secondary text                   |
| `--indigo-800` `#1c1f5e`                                    | deep indigo                      |
| `--lavender-50` `#f5f3ff`                                   | light section ground (`--paper`) |
| `--lavender-300` `#a18eff`                                  | soft violet on dark              |
| `--site-paper` `#f9f9fb`                                    | near-white                       |
| `--ease`, `--fast` `.18s`, `--base` `.32s`, `--slow` `.52s` | motion                           |

Dark grounds are the hero indigo (`#1f2868` / `#2d2b78` with violet–purple glows), never near-black. Mono labels: class `font-micro`, uppercase, small, letter-spaced. Headings: Onest medium, tight tracking — not Anton (hero headline only).

## Sections

A section is `<section class="chapter chapter--light">` (lavender with grain), `chapter--lavender` (plain lavender) or `chapter--dark` (indigo with glows; light text). Inside: `chapter__inner`, then a head:

```html
<header class="chapter__head">
    <div class="chapter__head-main">
        <p class="chapter__label">01 · Software la comandă</p>
        <h2 class="chapter__title">
            Sisteme
            <span class="title-pill" aria-hidden="true"
                ><span class="title-pill__inner"
                    ><!-- 3 white 24px stroke icons --></span
                ></span
            >
            construite de la zero.
        </h2>
    </div>
    <p class="chapter__intro">…</p>
</header>
```

`title-pill` = the violet chip with three white Lucide-style stroke icons, set between two words of a title — no single-icon or white chips. Subsections: `<div class="chapter-sub__header"><h3 class="chapter-sub__label">Ce construim</h3></div>` (mono label over a hairline), then content. Grids of items: `<ul class="chapter-cells chapter-cells--3">` of `<li class="chapter-cell">` with `chapter-cell__index`, `chapter-cell__name`, `chapter-cell__text`. Small tags: `chapter-chip`; figures: `<span class="chapter-metric"><b>240</b><span>Proiecte gestionate</span></span>`. Close a section with `<div class="chapter-cta">` holding a pill and an arrow link side by side.

## Buttons — copy this markup exactly

Primary (pill): `btn-light` on light grounds, `btn-dark` on dark. A light-ground pill is violet only inside a `.chapter` section (outside it, it renders dark); a dark-ground pill is violet only with the violet marker above.

```html
<a href="/contact" class="pill btn-light"
    ><span class="pill__ripple" aria-hidden="true"><span></span></span
    ><span class="pill__title"><span>Discută un proiect</span></span></a
>
```

Secondary (arrow link — a dot that grows into an arrow circle on hover):

```html
<a href="/web" class="arrow-link btn-light"
    ><span class="arrow-link__circle" aria-hidden="true"
        ><span class="arrow-link__dot"></span
        ><span class="arrow-link__fill"></span
        ><svg class="arrow-link__icon" viewBox="0 0 11 10" fill="currentColor">
            <path
                d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"
            /></svg></span
    ><span class="arrow-link__label"
        >Află mai multe<span
            class="arrow-link__border"
            aria-hidden="true"
        ></span></span
></a>
```

Never add a ↗ glyph to links or buttons.

## Cards and other parts

`project-card` (screenshot frame + `chapter-chip` + title + `chapter-metric`), `product-panel` (dark-section product block with `product-panel__checks`), `post-card` (blog card with a notch and button), `svc-card` (illustrated service card), `google-rating` (stars + score; `google-rating--light` / `--dark`), `statement` (large statement text on dark). `project-card`, `chapter-chip` and `chapter-metric` take their colours from the section: always place them inside a `.chapter` section, or their text renders nearly invisible. Copy their full markup from a rendered Webis page or the cards in `guidelines/cards/` rather than approximating it.

## Copy

Romanian, with ș/ț (comma below). Short, plain sentences; no exclamation marks, no SEO talk.

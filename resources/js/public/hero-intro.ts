import type { Gsap } from '@/public/gsap';
import { EASE, loadGsap } from '@/public/gsap';

/**
 * The opening hero's backdrop as a card, and its intro — after
 * integratedbio.com, with the counter-motion of a mask and what is inside it
 * from andrei-pintilei.github.io/portfolio.
 *
 * The card: at rest the backdrop is a rounded rectangle a few pixels in from
 * the screen's edges (css/site/hero-intro.css). Scrolling opens it out to the
 * full screen over the first quarter-screen, following the smooth scroll;
 * back at the top it is a card again.
 *
 * The intro, on a full load (see the <head> script in
 * layouts/public.blade.php):
 * 1. A small rounded rectangle (~100px) appears in the middle of the blank
 *    page.
 * 2. It grows into the card — the backdrop seen through a clip-path inset
 *    with rounded corners, redrawn each frame — while the gradient inside
 *    settles from a slight zoom, against the growth.
 * 3. The title rises line by line out of its masks; the eyebrow, buttons,
 *    logo strip and header come in after it.
 *
 * Applies to the page's intro hero ([data-intro], blocks/hero.blade.php).
 * With reduced motion the card simply stays as it is.
 */

/** Seconds, on the site's expo family (gsap.ts). */
const TIMING = {
    appearAt: 0.1,
    appear: 0.5,
    // It barely settles before it opens up.
    growAt: 0.45,
    grow: 1.6,
    // The content follows as the backdrop reaches the card.
    textAt: 1.7,
    text: 1.3,
    lineStagger: 0.12,
    restStagger: 0.1,
};

/** The starting rectangle, px (integratedbio's pill proportions). */
const START = { width: 100, height: 62 };
/** Corner radius while small, px: a rounded rectangle, not a pill. */
const RADIUS = 28;
/**
 * The card at rest, px: margin from the screen's edges and corner radius.
 * Keep in step with css/site/hero-intro.css.
 */
const CARD = { inset: 14, radius: 20 };
const CARD_SMALL = { inset: 10, radius: 16 };
/** Scrolled this share of the screen's height, the card is full screen. */
const OPEN_AFTER = 0.25;
/**
 * How far below its mask a title line waits (% of its height). More than a
 * full line: the marks over Î and Ă rise above the line and would otherwise
 * show at the mask's bottom edge.
 */
const LINE_HIDDEN = 140;
/** How far the inside starts zoomed in; it settles to 1 as the shape grows. */
const ZOOM = 1.25;

export async function initHero(): Promise<void> {
    const root = document.documentElement;
    const hero = document.querySelector<HTMLElement>('[data-intro]');
    // Fixed behind the page, outside the hero (blocks/partials/hero-backdrop).
    const backdrop = document.querySelector<HTMLElement>(
        '[data-intro-backdrop]',
    );
    const motion = !window.matchMedia('(prefers-reduced-motion: reduce)')
        .matches;

    if (backdrop) {
        hideWhenCovered(backdrop);
    }

    if (!hero || !backdrop || !motion) {
        root.classList.remove('intro');

        return;
    }

    const gsap = await loadGsap().catch(() => null);

    if (!gsap) {
        root.classList.remove('intro');

        return;
    }

    const card = window.matchMedia('(max-width: 640px)').matches
        ? CARD_SMALL
        : CARD;
    const open = (): void => void openOnScroll(gsap, hero, backdrop, card);

    // Played only from the very top; the <head> safety timeout may also have
    // given up on us while GSAP loaded.
    if (root.classList.contains('intro') && window.scrollY === 0) {
        playIntro(gsap, hero, backdrop, card, open);
    } else {
        root.classList.remove('intro');
        open();
    }
}

/**
 * The fixed backdrop shows only through the hero and the blocks set on it
 * ([data-backdrop-area]); once none of them is on screen it is hidden, so it
 * can never show through a later gap, and the gradient stops drawing (it
 * watches the same areas, js/public/soffit.ts).
 */
function hideWhenCovered(backdrop: HTMLElement): void {
    const areas = document.querySelectorAll('[data-backdrop-area]');

    if (areas.length === 0) {
        return;
    }

    const showing = new Set<Element>();

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                showing.add(entry.target);
            } else {
                showing.delete(entry.target);
            }
        }

        backdrop.style.visibility = showing.size > 0 ? '' : 'hidden';
    });

    areas.forEach((area) => observer.observe(area));
}

/**
 * Scroll-linked: the card's margin and corners go to nothing as the page
 * scrolls the first quarter-screen, and come back on the way up. Scrubbed by
 * ScrollTrigger, so it follows the smoothed scroll, not the raw one.
 */
async function openOnScroll(
    gsap: Gsap,
    hero: HTMLElement,
    backdrop: HTMLElement,
    card: typeof CARD,
): Promise<void> {
    const { ScrollTrigger } = await import('gsap/ScrollTrigger');
    gsap.registerPlugin(ScrollTrigger);

    const state = { open: 0 };
    const draw = (): void => {
        const closed = 1 - state.open;

        backdrop.style.clipPath = `inset(${card.inset * closed}px round ${card.radius * closed}px)`;
    };

    gsap.to(state, {
        open: 1,
        ease: 'none',
        onUpdate: draw,
        scrollTrigger: {
            trigger: hero,
            start: 'top top',
            end: () => `+=${window.innerHeight * OPEN_AFTER}`,
            scrub: true,
        },
    });
}

function playIntro(
    gsap: Gsap,
    hero: HTMLElement,
    backdrop: HTMLElement,
    card: typeof CARD,
    then: () => void,
): void {
    const root = document.documentElement;

    root.classList.add('intro-running');

    const media = backdrop.querySelector<HTMLElement>('[data-intro-media]');
    const lines = Array.from(
        hero.querySelectorAll<HTMLElement>('.hero-line__inner'),
    );
    const fades = Array.from(
        hero.querySelectorAll<HTMLElement>('[data-intro-fade]'),
    );
    const header = document.querySelector<HTMLElement>('.site-nav');

    const width = backdrop.clientWidth;
    const height = backdrop.clientHeight;
    // Where it stops: the card.
    const endWidth = width - 2 * card.inset;
    const endHeight = height - 2 * card.inset;
    // appear: 0 → 1 as the rectangle comes in; grow: 0 → 1 to the card.
    const shape = { appear: 0, grow: 0 };

    // The visible shape: centred, `w` × `h`, corners easing to the card's.
    const draw = (): void => {
        const p = shape.grow;
        // Comes in from 70% of its starting size.
        const start = 0.7 + 0.3 * shape.appear;
        const w = START.width * start + (endWidth - START.width) * p;
        // Height lags width early on, so it stays wide while small.
        const h = START.height * start + (endHeight - START.height) * p ** 1.35;
        const r = Math.min(
            RADIUS + (card.radius - RADIUS) * p,
            Math.min(w, h) / 2,
        );

        backdrop.style.clipPath = `inset(${(height - h) / 2}px ${(width - w) / 2}px round ${r}px)`;
    };

    // Take over the CSS starting states with the same values inline, then
    // drop the class: from here on the script alone moves things.
    draw();
    gsap.set(backdrop, { autoAlpha: 0 });
    // y: 0 explicitly — GSAP would otherwise read the CSS starting offset as
    // a pixel shift of its own and stack the two.
    gsap.set(lines, { y: 0, yPercent: LINE_HIDDEN });
    gsap.set(fades, { autoAlpha: 0, y: 24 });

    if (header) {
        gsap.set(header, { autoAlpha: 0, y: -16 });
    }

    if (media) {
        gsap.set(media, { scale: ZOOM });
    }

    root.classList.remove('intro');

    const finish = (): void => {
        // The card is the CSS resting state too, so nothing jumps here.
        backdrop.style.clipPath = '';
        gsap.set(backdrop, { clearProps: 'opacity,visibility' });
        gsap.set(
            [
                ...lines,
                ...fades,
                ...(header ? [header] : []),
                ...(media ? [media] : []),
            ],
            { clearProps: 'transform,opacity,visibility' },
        );
        root.classList.remove('intro-running');
        then();
    };

    const tl = gsap
        .timeline({ onComplete: finish })
        .to(
            shape,
            {
                appear: 1,
                duration: TIMING.appear,
                ease: EASE.out,
                onUpdate: draw,
            },
            TIMING.appearAt,
        )
        .to(
            backdrop,
            {
                autoAlpha: 1,
                duration: TIMING.appear * 0.6,
                ease: 'power1.out',
            },
            TIMING.appearAt,
        )
        .to(
            shape,
            {
                grow: 1,
                duration: TIMING.grow,
                ease: EASE.inOut,
                onUpdate: draw,
            },
            TIMING.growAt,
        )
        .to(
            lines,
            {
                yPercent: 0,
                duration: TIMING.text,
                ease: EASE.out,
                stagger: TIMING.lineStagger,
            },
            TIMING.textAt,
        )
        .to(
            fades,
            {
                autoAlpha: 1,
                y: 0,
                duration: TIMING.text,
                ease: EASE.out,
                stagger: TIMING.restStagger,
            },
            TIMING.textAt + 0.15,
        );

    // Against the growth: the inside settles from its zoom.
    if (media) {
        tl.to(
            media,
            { scale: 1, duration: TIMING.grow + 0.5, ease: EASE.out },
            TIMING.growAt,
        );
    }

    if (header) {
        tl.to(
            header,
            { autoAlpha: 1, y: 0, duration: TIMING.text, ease: EASE.out },
            TIMING.textAt + 0.1,
        );
    }
}

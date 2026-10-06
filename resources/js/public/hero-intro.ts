import type { Gsap } from '@/public/gsap';
import { EASE, loadGsap } from '@/public/gsap';

/**
 * The opening hero's backdrop — fixed behind the page, full screen — its
 * intro, and how it leaves. After integratedbio.com, with the counter-motion
 * of a mask and what is inside it from andrei-pintilei.github.io/portfolio.
 *
 * The intro, on a full load (see the <head> script in
 * layouts/public.blade.php):
 * 1. A small rounded rectangle (~100px) appears in the middle of the blank
 *    page.
 * 2. It grows to the full screen — the backdrop seen through a clip-path
 *    inset with rounded corners, redrawn each frame — while the gradient
 *    inside settles from a slight zoom, against the growth.
 * 3. The title rises line by line out of its masks; the eyebrow, buttons,
 *    logo strip and header come in after it.
 *
 * Leaving: once the last section on the backdrop ([data-backdrop-area])
 * starts to scroll off, the page after it slides up and the backdrop shrinks
 * into a rounded card just above it, while the gradient inside drifts up at
 * half the scroll's speed — the parallax. Scrubbed, so scrolling back up
 * plays it in reverse.
 *
 * Applies to the page's intro hero ([data-intro], blocks/hero.blade.php).
 * With reduced motion the backdrop simply stays full screen until covered.
 */

/** Seconds, on the site's expo family (gsap.ts). */
const TIMING = {
    appearAt: 0.1,
    appear: 0.5,
    // It barely settles before it opens up.
    growAt: 0.45,
    grow: 1.6,
    // The content follows as the backdrop reaches the screen's edges.
    textAt: 1.7,
    text: 1.3,
    lineStagger: 0.12,
    restStagger: 0.1,
};

/**
 * The starting rectangle, px (2:1), and its corner radius. On narrow screens
 * it is scaled down to `maxShare` of the screen's width, keeping its shape.
 */
const START = { width: 400, height: 200, maxShare: 0.8 };
const RADIUS = 42;
/**
 * Leaving: the card's margin from the screen's edges (a share of the
 * screen's width, capped in px) and corner radius (px), both reached over
 * the first `settle` of the way and then held; the shortest the card gets
 * (a share of the screen's height) before it scrolls away upward with the
 * page instead of thinning into a strip; and how far the gradient inside has
 * drifted up when covered (a share of the screen's height — the page
 * covering it travels a whole height, so 0.5 is half speed).
 */
const LEAVE = {
    insetVw: 0.03,
    insetMax: 12,
    radius: 20,
    settle: 0.15,
    minHeight: 0.5,
    lag: 0.5,
};

/**
 * The card's visible rectangle on screen while it leaves, or null while the
 * backdrop fills the screen. The round menu button reads it (site-nav.ts):
 * over a dark section whose background has shrunk away, it is on white.
 */
let card: { left: number; top: number; right: number; bottom: number } | null =
    null;

/** Whether the backdrop is visible at this point of the screen. */
export function backdropCovers(x: number, y: number): boolean {
    return (
        card === null ||
        (x >= card.left && x <= card.right && y >= card.top && y <= card.bottom)
    );
}
/**
 * How far below its mask a title line waits (% of its height). More than a
 * full line: the marks over Î and Ă rise above the line and would otherwise
 * show at the mask's bottom edge.
 */
const LINE_HIDDEN = 140;
/** How far the inside starts zoomed in; it settles to 1 as the shape grows. */
const ZOOM = 1.25;

/**
 * @param ready settles once smooth scrolling is set up (or skipped), so the
 *   scroll-linked leaving measures the page as it will scroll
 */
export async function initHero(ready: Promise<unknown>): Promise<void> {
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

    const leave = (): void => void leaveOnScroll(gsap, backdrop, ready);

    // Played only from the very top; the <head> safety timeout may also have
    // given up on us while GSAP loaded.
    if (root.classList.contains('intro') && window.scrollY === 0) {
        playIntro(gsap, hero, backdrop, leave);
    } else {
        root.classList.remove('intro');
        leave();
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
 * Scroll-linked leaving: from when the last section on the backdrop starts to
 * scroll off (its bottom at the screen's bottom — the next section coming in)
 * until it has gone (its bottom at the top), the backdrop shrinks into a
 * rounded card riding above the section coming in, and the gradient inside
 * drifts up at half speed.
 *
 * Measured from where that section is drawn, every frame — not from the
 * scroll position: with smooth scrolling the content trails the scroll, and
 * a card driven by the scroll would run ahead of it and cut into it.
 */
async function leaveOnScroll(
    gsap: Gsap,
    backdrop: HTMLElement,
    ready: Promise<unknown>,
): Promise<void> {
    const areas = document.querySelectorAll<HTMLElement>(
        '[data-backdrop-area]',
    );
    const last = areas[areas.length - 1];

    if (!last) {
        return;
    }

    await ready.catch(() => undefined);

    const media = backdrop.querySelector<HTMLElement>('[data-intro-media]');
    const overlap = document.documentElement.dataset.heroLeave === 'overlap';
    const state = { p: 0 };

    const draw = (): void => {
        const p = state.p;

        if (p === 0) {
            card = null;
            backdrop.style.clipPath = '';

            if (media) {
                media.style.transform = '';
            }

            return;
        }

        const width = window.innerWidth;
        const height = window.innerHeight;

        // The overlap way (data-hero-leave="overlap" on <html>, set by
        // /clienti): no card — the next section slides up over the full-screen
        // backdrop with a rounded top. The backdrop shows down to that
        // section's top; only the drift remains.
        if (overlap) {
            const next = last.nextElementSibling;
            const edge = next ? next.getBoundingClientRect().top : height;

            card = { left: 0, top: 0, right: width, bottom: edge };

            if (media) {
                media.style.transform = `translate3d(0, ${-height * LEAVE.lag * p}px, 0)`;
            }

            return;
        }

        const settled = Math.min(1, p / LEAVE.settle);
        const margin =
            Math.min(width * LEAVE.insetVw, LEAVE.insetMax) * settled;
        const radius = LEAVE.radius * settled;
        // The section coming in has its top at height × (1 − p): the card's
        // bottom edge rides the margin above it, so its rounded corners show.
        const bottom = height * (1 - p) - margin;
        // Its top holds at the margin until the card is as short as it gets,
        // then moves up with the bottom: the card scrolls away with the page.
        const top = Math.min(margin, bottom - height * LEAVE.minHeight);

        card = { left: margin, top, right: width - margin, bottom };
        // A top far above the screen is clamped: the corners are out of view
        // either way.
        backdrop.style.clipPath = `inset(${Math.max(top, -2 * radius)}px ${margin}px ${height - bottom}px ${margin}px round ${radius}px)`;

        // The parallax: what is inside drifts up at half speed.
        if (media) {
            media.style.transform = `translate3d(0, ${-height * LEAVE.lag * p}px, 0)`;
        }
    };

    // p: 0 while the section's bottom is at or below the screen's bottom,
    // 1 once it has reached the top. Redrawn only when it changes.
    gsap.ticker.add(() => {
        const bottom = last.getBoundingClientRect().bottom;
        const height = window.innerHeight;
        const p = Math.min(1, Math.max(0, (height - bottom) / height));

        if (p !== state.p) {
            state.p = p;
            draw();
        }
    });
}

function playIntro(
    gsap: Gsap,
    hero: HTMLElement,
    backdrop: HTMLElement,
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
    const startScale = Math.min(1, (width * START.maxShare) / START.width);
    const startWidth = START.width * startScale;
    const startHeight = START.height * startScale;
    // appear: 0 → 1 as the rectangle comes in; grow: 0 → 1 to the full screen.
    const shape = { appear: 0, grow: 0 };

    // The visible shape: centred, `w` × `h`, corners flattening as it grows.
    const draw = (): void => {
        const p = shape.grow;
        // Comes in from 70% of its starting size.
        const start = 0.7 + 0.3 * shape.appear;
        const w = startWidth * start + (width - startWidth) * p;
        // Height lags width early on, so it stays wide while small.
        const h = startHeight * start + (height - startHeight) * p ** 1.35;
        const r = Math.min(RADIUS * (1 - p) ** 0.6, Math.min(w, h) / 2);

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
        // Full screen is the CSS resting state too, so nothing jumps here.
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

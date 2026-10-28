import type { Gsap } from '@/public/gsap';
import { loadGsap } from '@/public/gsap';

/**
 * The opening hero's backdrop — fixed behind the page, full screen — and
 * how it leaves. After integratedbio.com. There is no intro: the hero is
 * there from the first frame, as every other page's.
 *
 * Leaving: once the last section on the backdrop ([data-backdrop-area])
 * starts to scroll off, the page after it slides up over it on a rounded
 * top (the layout's "overlap", every page), the gradient holding still — no
 * parallax. ("card": the backdrop shrinks into a rounded card above it.)
 * Scrubbed, so scrolling back up plays it in reverse.
 *
 * Applies to the page's opening hero ([data-intro], blocks/hero.blade.php).
 * With reduced motion the backdrop simply stays full screen until covered.
 */

/**
 * Leaving: the card's margin from the screen's edges (a share of the
 * screen's width, capped in px) and corner radius (px), both reached over
 * the first `settle` of the way and then held; the shortest the card gets
 * (a share of the screen's height) before it scrolls away upward with the
 * page instead of thinning into a strip; and how far the gradient inside has
 * drifted up when covered (a share of the screen's height): 0 — no parallax
 * anywhere on the site, the gradient holds still as the page covers it.
 */
const LEAVE = {
    insetVw: 0.03,
    insetMax: 12,
    radius: 20,
    settle: 0.15,
    minHeight: 0.5,
    lag: 0,
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
 * @param ready settles once smooth scrolling is set up (or skipped), so the
 *   scroll-linked leaving measures the page as it will scroll
 */
export async function initHero(ready: Promise<unknown>): Promise<void> {
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
        return;
    }

    const gsap = await loadGsap().catch(() => null);

    if (gsap) {
        void leaveOnScroll(gsap, backdrop, ready);
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

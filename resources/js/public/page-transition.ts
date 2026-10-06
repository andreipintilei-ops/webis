/**
 * Page transitions: the Theodore curtain (Codrops, MIT) moving top to bottom,
 * with the page itself moving underneath it — after the overlay transition on
 * andrei-pintilei.github.io/portfolio. Split across two real page loads:
 *
 * 1. Leaving: an internal link is clicked → the curtain drops from the top,
 *    curved edge first, while the page content drifts down under it → once
 *    the screen is covered, the browser navigates.
 * 2. Arriving: the inline script in <head> sees the flag left in step 1 and
 *    paints the page covered (html.curtain-cover), its content already
 *    lifted; the curtain then carries on out through the bottom while the
 *    content drops into place from above.
 *
 * The moving content is whatever carries [data-transition-shift] (the top bar
 * and <main>) — never fixed elements, which a transformed ancestor would
 * break. Navigation stays ordinary page loads: full HTML for crawlers, nothing
 * to break without JavaScript. Skipped for anything that should not animate:
 * modified clicks, new tabs, downloads, same-page anchors, other origins, the
 * admin, [data-no-transition] links, and visitors preferring reduced motion.
 */

import {
    curtainMove,
    EASE,
    loadGsap,
    prefersReducedMotion,
    prefetchGsapWhenIdle,
} from '@/public/gsap';

/** Session flag: "the next page should arrive covered". Read in <head>. */
export const CURTAIN_FLAG = 'webis:curtain';

// Curtain shapes in the overlay's 0–100 viewBox.
const PATHS = {
    // Leaving: drop from the top edge, the middle leading, until covered.
    topFlat: 'M 0 0 V 0 Q 50 0 100 0 V 0 z',
    topCurve: 'M 0 0 V 50 Q 50 100 100 50 V 0 z',
    topFull: 'M 0 0 V 100 Q 50 100 100 100 V 0 z',
    // Arriving: the same downward motion, now leaving through the bottom.
    bottomFull: 'M 0 100 V 0 Q 50 0 100 0 V 100 z',
    bottomCurve: 'M 0 100 V 50 Q 50 100 100 50 V 100 z',
    bottomFlat: 'M 0 100 V 100 Q 50 100 100 100 V 100 z',
};

/** Seconds and pixels; tune the feel here. Easings: EASE (gsap.ts). */
const MOTION = {
    // Each curtain move is one continuous sweep (EASE.curtain) — the same as
    // the menu's (theodore-menu.ts).
    cover: 0.6,
    reveal: 0.6,
    // The next page is requested this far into covering: on the curtain's
    // sweep the screen is already ~99% covered, and the browser keeps
    // showing this page until the next one arrives, so it lands covered.
    navigateAt: 0.8,
    // How far the old page drifts down as it is covered…
    leaveShift: 100,
    // …and how far above its place the new page starts, settling (expo.out).
    enterShift: -150,
    enterDuration: 0.9,
};

/** If the reveal cannot run (GSAP failed to load…), uncover anyway. */
const FAILSAFE_MS = 4000;

export function initPageTransitions(): void {
    const root = document.documentElement;
    const path = document.querySelector<SVGPathElement>('[data-curtain-path]');

    if (!path) {
        return;
    }

    const shifted = (): HTMLElement[] =>
        Array.from(
            document.querySelectorAll<HTMLElement>('[data-transition-shift]'),
        );

    let leaving = false;

    const uncover = (): void => {
        root.classList.remove('curtain-cover');
        path.setAttribute('d', PATHS.bottomFlat);

        for (const element of shifted()) {
            element.style.transform = '';
        }
    };

    // ---- Arriving ------------------------------------------------------------

    if (root.classList.contains('curtain-cover')) {
        sessionStorage.removeItem(CURTAIN_FLAG);
        const failsafe = window.setTimeout(uncover, FAILSAFE_MS);

        void loadGsap()
            .then((gsap) => {
                window.clearTimeout(failsafe);

                // Hand over from the CSS cover to the animated curtain without
                // a visible change: same shape, same lifted content.
                gsap.set(shifted(), { y: MOTION.enterShift });
                path.setAttribute('d', PATHS.bottomFull);
                root.classList.remove('curtain-cover');

                gsap.timeline()
                    // The curve forms early as the curtain starts to go.
                    .to(
                        path,
                        curtainMove(
                            PATHS.bottomCurve,
                            PATHS.bottomFlat,
                            MOTION.reveal,
                            EASE.curtain,
                            0.4,
                        ),
                    )
                    // The content lands as the curtain clears it.
                    .to(
                        shifted(),
                        {
                            duration: MOTION.enterDuration,
                            ease: EASE.out,
                            y: 0,
                            clearProps: 'transform',
                        },
                        MOTION.reveal * 0.35,
                    );
            })
            .catch(uncover);
    } else if (!prefersReducedMotion()) {
        prefetchGsapWhenIdle();
    }

    // Back/forward can restore this page from memory exactly as it was left:
    // covered, mid-transition. Reset it.
    window.addEventListener('pageshow', (event) => {
        if (event.persisted) {
            leaving = false;
            sessionStorage.removeItem(CURTAIN_FLAG);
            uncover();
        }
    });

    // ---- Leaving -------------------------------------------------------------

    document.addEventListener('click', (event) => {
        const link = transitionLink(event);

        if (!link || leaving) {
            return;
        }

        event.preventDefault();
        leaving = true;
        const href = link.href;

        void loadGsap()
            .then((gsap) => {
                gsap.timeline()
                    .set(path, { attr: { d: PATHS.topFlat } })
                    .to(
                        path,
                        curtainMove(
                            PATHS.topCurve,
                            PATHS.topFull,
                            MOTION.cover,
                        ),
                    )
                    .to(
                        shifted(),
                        {
                            duration: MOTION.cover,
                            ease: EASE.curtain,
                            y: MOTION.leaveShift,
                        },
                        0,
                    )
                    // At an absolute time, after the tweens: placed before
                    // them it would push their start back.
                    .call(
                        () => navigateCovered(href),
                        [],
                        MOTION.cover * MOTION.navigateAt,
                    );
            })
            .catch(() => window.location.assign(href));
    });
}

/**
 * Go to `href` with the screen already covered, so the next page arrives under
 * the curtain and reveals itself. For whatever covered the screen — the
 * transition above, or the Theodore menu's closing curtain.
 */
export function navigateCovered(href: string): void {
    try {
        sessionStorage.setItem(CURTAIN_FLAG, '1');
    } catch {
        // Storage blocked: the next page simply arrives uncovered.
    }

    window.location.assign(href);
}

/**
 * The link a click should animate away to, or null to let the browser handle
 * the click as usual (also used by the Theodore menu's links).
 */
export function transitionLink(event: MouseEvent): HTMLAnchorElement | null {
    if (
        event.defaultPrevented ||
        event.button !== 0 ||
        event.metaKey ||
        event.ctrlKey ||
        event.shiftKey ||
        event.altKey ||
        prefersReducedMotion()
    ) {
        return null;
    }

    const link = (event.target as Element | null)?.closest<HTMLAnchorElement>(
        'a[href]',
    );

    if (
        !link ||
        link.hasAttribute('download') ||
        link.hasAttribute('data-no-transition') ||
        (link.target && link.target !== '_self')
    ) {
        return null;
    }

    const url = new URL(link.href, window.location.href);

    if (
        url.origin !== window.location.origin ||
        url.pathname.startsWith('/admin') ||
        // Only the fragment differs: an in-page jump, not a page change.
        (url.pathname === window.location.pathname &&
            url.search === window.location.search)
    ) {
        return null;
    }

    return link;
}

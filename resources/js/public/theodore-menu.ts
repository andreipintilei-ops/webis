/**
 * Full-screen menu with an SVG curtain transition, adapted from Codrops'
 * "Theodore" (https://github.com/codrops/Theodore, MIT).
 *
 * Opening: the curtain rises from the bottom with a curved edge, the page's
 * big title lifts away, then the curtain pulls off the top to reveal the menu
 * and its links rise in. Closing plays the mirror image.
 *
 * GSAP is not in the page bundle: it is fetched once the browser is idle after
 * load (or earlier, the moment the visitor reaches for the button), so the
 * first click never waits on a download. Visitors who prefer reduced motion
 * get an instant toggle. The markup works without JavaScript via CSS :target.
 */

import {
    curtainMove,
    EASE,
    loadGsap,
    prefetchGsapWhenIdle,
} from '@/public/gsap';
import { navigateCovered, transitionLink } from '@/public/page-transition';
import { lockScroll } from '@/public/site-nav';

// Curtain shapes in the overlay's 0–100 viewBox.
const PATHS = {
    // Open, phase 1: rise from the bottom edge.
    bottomFlat: 'M 0 100 V 100 Q 50 100 100 100 V 100 z',
    bottomCurve: 'M 0 100 V 50 Q 50 0 100 50 V 100 z',
    bottomFull: 'M 0 100 V 0 Q 50 0 100 0 V 100 z',
    // Open, phase 2: pull off through the top edge.
    topFull: 'M 0 0 V 100 Q 50 100 100 100 V 0 z',
    topCurve: 'M 0 0 V 50 Q 50 0 100 50 V 0 z',
    topFlat: 'M 0 0 V 0 Q 50 0 100 0 V 0 z',
    // Close: the same moves mirrored.
    closeCurve: 'M 0 0 V 50 Q 50 100 100 50 V 0 z',
    closeRevealCurve: 'M 0 100 V 50 Q 50 100 100 50 V 100 z',
};

/**
 * Seconds. Theodore's originals were 0.8 / 0.3 / 0.3 / 0.8 / 1.1 (≈2.2s per
 * direction); these keep the shape of the motion at ≈1.3s.
 */
const TIMING = {
    // Each curtain move (covering, then clearing) is one expo.inOut sweep.
    cover: 0.8,
    reveal: 0.8,
    // The links (or the page) settling into place as the curtain clears (expo.out)…
    rise: 1.2,
    // …starting this far into the reveal.
    riseAt: 0.3,
    // The links falling away as the menu closes (expo.in).
    fall: 0.5,
    stagger: 0.05,
    // How far the page lifts away under the opening curtain.
    lift: 200,
};

/**
 * Controls, all optional except at least one way in:
 * - [data-theo-open] / [data-theo-close]: Theodore's own button and ✕
 * - [data-menu-toggle="theodore"]: a toggle from elsewhere (the site's round
 *   button, which turns into the ✕ itself via `nav-open` on <html>)
 */
export function initTheodoreMenu(): void {
    const root = document.documentElement;
    const header = document.querySelector<HTMLElement>('[data-theo-header]');
    const menu = document.querySelector<HTMLElement>('[data-theo-menu]');
    const overlay = document.querySelector<SVGPathElement>(
        '[data-theo-overlay]',
    );
    const openers = Array.from(
        document.querySelectorAll<HTMLElement>('[data-theo-open]'),
    );
    const toggles = Array.from(
        document.querySelectorAll<HTMLElement>('[data-menu-toggle="theodore"]'),
    );
    const closeButton =
        document.querySelector<HTMLElement>('[data-theo-close]');

    if (!menu || !overlay || openers.length + toggles.length === 0) {
        return;
    }

    // The big links, which rise in; the rest of the menu (logo row, side
    // column, foot), which fades up after them.
    const items = Array.from(
        menu.querySelectorAll<HTMLAnchorElement>('[data-theo-item]'),
    );
    const reveals = Array.from(
        menu.querySelectorAll<HTMLElement>('[data-theo-reveal]'),
    );
    // Every link that leaves through the closing curtain.
    const links = Array.from(
        menu.querySelectorAll<HTMLAnchorElement>(
            'a[data-theo-item], a[data-theo-link]',
        ),
    );
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let isOpen = false;
    // True only while the curtain is moving. The links (or the page) settling
    // afterwards is decoration: a click then cuts it short instead of being
    // ignored — otherwise the menu looks done but needs a second click.
    let isAnimating = false;
    let timeline: gsap.core.Timeline | null = null;
    let returnFocusTo: HTMLElement | null = null;

    // Closed menus are out of the tab order and hidden from screen readers.
    menu.inert = true;

    // Arrow functions, not declarations: declarations are hoisted, so
    // TypeScript would forget the null checks above.
    const setOpenState = (open: boolean): void => {
        isOpen = open;
        header?.classList.toggle('theo-header--menu-open', open);
        root.classList.toggle('nav-open', open);
        menu.classList.toggle('theo-menu--open', open);
        menu.inert = !open;
        lockScroll(open);

        for (const control of [...openers, ...toggles]) {
            control.setAttribute('aria-expanded', String(open));
        }
    };

    /** Into the menu: its ✕ if it has one, else the first link. */
    const focusInside = (): void =>
        (closeButton ?? items[0])?.focus({ preventScroll: true });

    const focusBack = (): void => returnFocusTo?.focus({ preventScroll: true });

    /** Elements that lift away while the menu opens (e.g. the hero title). */
    const shifted = (): HTMLElement[] =>
        Array.from(document.querySelectorAll<HTMLElement>('[data-theo-shift]'));

    const open = async (): Promise<void> => {
        if (isOpen || isAnimating) {
            return;
        }

        if (reducedMotion.matches) {
            setOpenState(true);
            focusInside();

            return;
        }

        isAnimating = true;
        const gsap = await loadGsap();

        timeline?.kill();
        timeline = gsap
            .timeline()
            // Cover: up from the bottom while the page lifts away under it.
            .set(overlay, { attr: { d: PATHS.bottomFlat } })
            .to(overlay, {
                ...curtainMove(
                    PATHS.bottomCurve,
                    PATHS.bottomFull,
                    TIMING.cover,
                ),
                // Swap in the menu while the curtain fully covers the page.
                onComplete: () => setOpenState(true),
            })
            .to(
                shifted(),
                { duration: TIMING.cover, ease: EASE.inOut, y: -TIMING.lift },
                0,
            )
            // Reveal: off through the top, the links rising in behind it.
            .set(items, { opacity: 0, y: 150 })
            .set(reveals, { opacity: 0, y: 30 })
            .set(overlay, { attr: { d: PATHS.topFull } })
            .addLabel('reveal')
            .to(
                overlay,
                curtainMove(
                    PATHS.topCurve,
                    PATHS.topFlat,
                    TIMING.reveal,
                    EASE.inOut,
                    0.4,
                ),
                'reveal',
            )
            // The curtain is off: the menu is open and answers clicks again.
            .call(
                () => {
                    isAnimating = false;
                    focusInside();
                },
                [],
                `reveal+=${TIMING.reveal}`,
            )
            .to(
                items,
                {
                    duration: TIMING.rise,
                    ease: EASE.out,
                    y: 0,
                    opacity: 1,
                    stagger: TIMING.stagger,
                },
                `reveal+=${TIMING.reveal * TIMING.riseAt}`,
            )
            .to(
                reveals,
                {
                    duration: TIMING.rise,
                    ease: EASE.out,
                    y: 0,
                    opacity: 1,
                    stagger: TIMING.stagger,
                },
                `reveal+=${TIMING.reveal * TIMING.riseAt + 0.2}`,
            );
    };

    const close = async (): Promise<void> => {
        if (!isOpen || isAnimating) {
            return;
        }

        if (reducedMotion.matches) {
            setOpenState(false);
            focusBack();

            return;
        }

        isAnimating = true;
        const gsap = await loadGsap();

        timeline?.kill();
        timeline = gsap
            .timeline()
            // Cover: down from the top while the links fall away.
            .set(overlay, { attr: { d: PATHS.topFlat } })
            .to(overlay, {
                ...curtainMove(PATHS.closeCurve, PATHS.topFull, TIMING.cover),
                onComplete: () => setOpenState(false),
            })
            .to(
                [...items, ...reveals],
                {
                    duration: TIMING.fall,
                    ease: EASE.in,
                    y: 100,
                    opacity: 0,
                    stagger: -TIMING.stagger,
                },
                0,
            )
            // Reveal: on out through the bottom, the page settling back.
            .set(overlay, { attr: { d: PATHS.bottomFull } })
            .addLabel('reveal')
            .to(
                overlay,
                curtainMove(
                    PATHS.closeRevealCurve,
                    PATHS.bottomFlat,
                    TIMING.reveal,
                    EASE.inOut,
                    0.4,
                ),
                'reveal',
            )
            // The curtain is off: the menu is closed and the button answers again.
            .call(
                () => {
                    isAnimating = false;
                    focusBack();
                },
                [],
                `reveal+=${TIMING.reveal}`,
            )
            .to(
                shifted(),
                { duration: TIMING.rise, ease: EASE.out, y: 0 },
                `reveal+=${TIMING.reveal * TIMING.riseAt}`,
            );
    };

    /**
     * Leaving through a menu link: only the first half of closing — links fall
     * away, the curtain drops over everything — then the next page loads
     * still covered and reveals itself (page-transition.ts). One motion from
     * the menu to the new page.
     */
    const leave = async (href: string): Promise<void> => {
        isAnimating = true;
        const gsap = await loadGsap();

        timeline?.kill();
        timeline = gsap
            .timeline({ onComplete: () => navigateCovered(href) })
            .set(overlay, { attr: { d: PATHS.topFlat } })
            .to(
                overlay,
                curtainMove(PATHS.closeCurve, PATHS.topFull, TIMING.cover),
            )
            .to(
                [...items, ...reveals],
                {
                    duration: TIMING.fall,
                    ease: EASE.in,
                    y: 100,
                    opacity: 0,
                    stagger: -TIMING.stagger,
                },
                0,
            );
    };

    for (const item of links) {
        item.addEventListener('click', (event) => {
            if (!isOpen || isAnimating || reducedMotion.matches) {
                return;
            }

            // The page already shown: just close the menu.
            const url = new URL(item.href, window.location.href);

            if (
                url.pathname === window.location.pathname &&
                url.search === window.location.search
            ) {
                event.preventDefault();
                void close();

                return;
            }

            // Ordinary clicks only; new tabs, modified clicks… go their way.
            if (!transitionLink(event)) {
                return;
            }

            // Prevented here, so the page-wide transition leaves it alone.
            event.preventDefault();
            void leave(url.href).catch(() => window.location.assign(url.href));
        });
    }

    // Back/forward can restore the page as it was left: menu open, curtain
    // down. Put it back to closed.
    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) {
            return;
        }

        timeline?.kill();
        timeline = null;
        isAnimating = false;
        setOpenState(false);
        overlay.setAttribute('d', PATHS.bottomFlat);

        for (const element of [...items, ...reveals, ...shifted()]) {
            element.style.transform = '';
            element.style.opacity = '';
        }
    });

    for (const opener of openers) {
        opener.addEventListener('click', (event) => {
            event.preventDefault();
            returnFocusTo = opener;
            void open();
        });
    }

    for (const toggle of toggles) {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();

            if (isOpen) {
                void close();
            } else {
                returnFocusTo = toggle;
                void open();
            }
        });
    }

    closeButton?.addEventListener('click', (event) => {
        event.preventDefault();
        void close();
    });

    // Reaching for a button starts the download at once; otherwise it
    // happens quietly once the page has finished loading.
    for (const control of [...openers, ...toggles]) {
        for (const type of ['pointerenter', 'focus', 'touchstart'] as const) {
            control.addEventListener(type, () => void loadGsap(), {
                once: true,
                passive: true,
            });
        }
    }

    if (!reducedMotion.matches) {
        prefetchGsapWhenIdle();
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && isOpen) {
            void close();
        }
    });
}

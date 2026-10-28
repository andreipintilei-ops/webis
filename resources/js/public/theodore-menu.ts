/**
 * The menu panel on the right, with an SVG curtain transition adapted from
 * Codrops' "Theodore" (https://github.com/codrops/Theodore, MIT).
 *
 * Opening: the curtain sweeps across the panel from the right as a band with
 * curved edges, the page drifting away a little and dimming, and the panel is
 * uncovered right behind it, its links sliding in. Closing plays the mirror
 * image, the band sweeping back over it. A click on the dimmed page closes it.
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

// Curtain shapes in the overlay's 0–100 viewBox, for leaving through a
// link (the band of opening and closing is drawn by drawBand).
const PATHS = {
    // At rest: nothing, along the bottom edge.
    bottomFlat: 'M 0 100 V 100 Q 50 100 100 100 V 100 z',
    // Down from the top edge, bowing, to cover everything.
    topFlat: 'M 0 0 V 0 Q 50 0 100 0 V 0 z',
    closeCurve: 'M 0 0 V 50 Q 50 100 100 50 V 0 z',
    topFull: 'M 0 0 V 100 Q 50 100 100 100 V 0 z',
};

/**
 * Seconds. Theodore's originals were 0.8 / 0.3 / 0.3 / 0.8 / 1.1 (≈2.2s per
 * direction). Opening here: the band is off and the menu usable at 0.72s,
 * everything settled by ≈1.4s.
 */
const TIMING = {
    // The curtain's sweep across the screen (EASE.curtain)…
    cover: 0.6,
    // …and how far behind its leading edge the trailing one follows,
    // uncovering the menu (opening) or the page (closing).
    follow: 0.12,
    // The links (or the page) settling into place as the curtain clears (expo.out)…
    rise: 0.8,
    // …starting this far into the reveal.
    riseAt: 0.2,
    // The links falling away as the menu closes (expo.in).
    fall: 0.35,
    stagger: 0.04,
    // How far the page drifts away from the opening curtain.
    drift: 60,
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

    // The big links (and Servicii, which opens rather than leads), which
    // rise in; the rest of the menu (logo row, side column, foot), which
    // fades up after them.
    const items = Array.from(
        menu.querySelectorAll<HTMLElement>('[data-theo-item]'),
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
    // Servicii, which opens the services under it; closed each time the
    // menu opens, whatever it was left as.
    const groups = Array.from(
        menu.querySelectorAll<HTMLDetailsElement>('[data-theo-group]'),
    );
    const groupBodies = Array.from(
        menu.querySelectorAll<HTMLElement>('[data-theo-group-body]'),
    );
    const closeGroups = (): void => {
        for (const group of groups) {
            group.open = false;
            group.classList.remove('is-closing');
        }
    };
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    let isOpen = false;
    // True only while the curtain is moving. The links (or the page) settling
    // afterwards is decoration: a click then cuts it short instead of being
    // ignored — otherwise the menu looks done but needs a second click.
    let isAnimating = false;
    let timeline: gsap.core.Timeline | null = null;
    let returnFocusTo: HTMLElement | null = null;
    // Opened from the keyboard (a click with no pointer: detail 0). Only
    // then does focus move into the menu: on a phone a focused first link
    // would show as pointed at — rolled, its dot, the others dimmed.
    let byKeyboard = false;

    // Closed menus are out of the tab order and hidden from screen readers.
    menu.inert = true;

    // Arrow functions, not declarations: declarations are hoisted, so
    // TypeScript would forget the null checks above.
    const setOpenState = (open: boolean): void => {
        isOpen = open;
        header?.classList.toggle('theo-header--menu-open', open);
        root.classList.toggle('nav-open', open);
        root.classList.remove('nav-opening');
        menu.classList.toggle('theo-menu--open', open);
        menu.inert = !open;
        lockScroll(open);

        for (const control of [...openers, ...toggles]) {
            control.setAttribute('aria-expanded', String(open));
        }
    };

    /** Into the menu, from the keyboard: its ✕ if it has one, else the first link. */
    const focusInside = (): void => {
        if (byKeyboard) {
            (closeButton ?? items[0])?.focus({ preventScroll: true });
        }
    };

    /** Back to what opened it — when focus was in the menu, else it stays put. */
    const focusBack = (): void => {
        if (menu.contains(document.activeElement)) {
            returnFocusTo?.focus({ preventScroll: true });
        }
    };

    /** Elements that drift away while the menu opens (the page). */
    const shifted = (): HTMLElement[] =>
        Array.from(document.querySelectorAll<HTMLElement>('[data-theo-shift]'));

    /**
     * The curtain as a band: its leading edge sweeps across the screen and
     * its trailing edge follows TIMING.follow behind, both bowing as they
     * move (at most half the screen, mid-way, as Theodore's). Opening, it
     * rises (both edges bow upward); closing, it falls (both bow downward).
     * The menu is clipped to the edge on its side of the band, so it is
     * uncovered — or covered — right behind the curtain.
     */
    const bow = (p: number): number => 200 * p * (1 - p);

    const band = {
        lead: 0,
        trail: 0,
        up: true,
    };

    /*
      The band runs across the panel only, from the right: in the overlay's
      0–100 box (the screen), from `from` (the panel's left edge) to 100, its
      edges bowing in the panel's measure. Opening (`up`), it sweeps in from
      the right, the menu showing to the right of its trailing edge;
      closing, it sweeps back over the panel from its left edge, the menu
      showing to the right of its leading edge.
    */
    const drawBand = (): void => {
        const { lead, trail, up } = band;
        const from =
            (menu.getBoundingClientRect().left / window.innerWidth) * 100;
        const width = 100 - from;
        const curve = (p: number): number => (bow(p) * width) / 100;
        let d: string;
        let clip: number;

        if (up) {
            // In from the right: the left edge leads, the right edge trails.
            const left = 100 - width * lead;
            const right = 100 - width * trail;

            d = `M ${right} 0 H ${left} Q ${left - curve(lead)} 50 ${left} 100 H ${right} Q ${right - curve(trail)} 50 ${right} 0 z`;
            // The trailing edge reaches furthest left at its middle.
            clip = right - curve(trail) / 2;
        } else {
            // Back over it from the left: the right edge leads, the left trails.
            const right = from + width * lead;
            const left = from + width * trail;

            d = `M ${left} 0 Q ${left + curve(trail)} 50 ${left} 100 H ${right} Q ${right + curve(lead)} 50 ${right} 0 z`;
            // The leading edge reaches least far right at its ends.
            clip = right;
        }

        const inset = ((clip - from) / width) * 100;

        overlay.setAttribute('d', d);
        menu.style.clipPath = `inset(0 0 0 ${Math.min(100, Math.max(0, inset))}%)`;
    };

    /** Once the band is off: no clip on the menu, the curtain at rest. */
    const clearBand = (): void => {
        menu.style.removeProperty('clip-path');
        overlay.setAttribute('d', PATHS.bottomFlat);
        root.classList.remove('nav-moving');
    };

    const open = async (): Promise<void> => {
        if (isOpen || isAnimating) {
            return;
        }

        if (reducedMotion.matches) {
            closeGroups();
            setOpenState(true);
            focusInside();

            return;
        }

        isAnimating = true;
        // From the click: the round button takes its open look at once,
        // rather than when the curtain has covered the page (css/site/
        // menu-panel.css) — otherwise leaving it quickly refills it first.
        root.classList.add('nav-opening');

        const gsap = await loadGsap();
        const settled = TIMING.follow + TIMING.cover;

        timeline?.kill();
        // The round button keeps the page's tone while the band moves.
        root.classList.add('nav-moving');
        Object.assign(band, { lead: 0, trail: 0, up: true });
        drawBand();
        // Open from the start, hidden by its clip until the band uncovers it.
        setOpenState(true);
        gsap.killTweensOf(groupBodies);
        gsap.set(groupBodies, { clearProps: 'height' });
        closeGroups();
        gsap.set(items, { opacity: 0, x: 120 });
        // x: 0 — closing slid them out to the right.
        gsap.set(reveals, { opacity: 0, x: 0, y: 30 });

        timeline = gsap
            .timeline({ onUpdate: drawBand, onComplete: clearBand })
            .to(
                band,
                { lead: 1, duration: TIMING.cover, ease: EASE.curtain },
                0,
            )
            .to(
                band,
                { trail: 1, duration: TIMING.cover, ease: EASE.curtain },
                TIMING.follow,
            )
            // The page drifts away from the band, a little.
            .to(
                shifted(),
                {
                    duration: TIMING.cover,
                    ease: EASE.curtain,
                    x: -TIMING.drift,
                },
                0,
            )
            // The links slide in behind it.
            .to(
                items,
                {
                    duration: TIMING.rise,
                    ease: EASE.out,
                    x: 0,
                    opacity: 1,
                    stagger: TIMING.stagger,
                },
                TIMING.follow + TIMING.cover * TIMING.riseAt,
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
                TIMING.follow + TIMING.cover * TIMING.riseAt + 0.1,
            )
            // The band is off: the menu is open and answers clicks again.
            .call(
                () => {
                    // The menu is behind the button now: its open look.
                    root.classList.remove('nav-moving');
                    isAnimating = false;
                    focusInside();
                },
                [],
                settled,
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
        const settled = TIMING.follow + TIMING.cover;

        timeline?.kill();
        root.classList.add('nav-moving');
        Object.assign(band, { lead: 0, trail: 0, up: false });
        drawBand();

        timeline = gsap
            .timeline({ onUpdate: drawBand, onComplete: clearBand })
            // The links slide away as the band comes over them…
            .to(
                [...items, ...reveals],
                {
                    duration: TIMING.fall,
                    ease: EASE.in,
                    x: 80,
                    opacity: 0,
                    stagger: -TIMING.stagger,
                },
                0,
            )
            .to(
                band,
                { lead: 1, duration: TIMING.cover, ease: EASE.curtain },
                0,
            )
            // …and the page settles back right behind it.
            .to(
                band,
                { trail: 1, duration: TIMING.cover, ease: EASE.curtain },
                TIMING.follow,
            )
            .to(
                shifted(),
                {
                    duration: TIMING.rise,
                    ease: EASE.out,
                    x: 0,
                    // Not even a zero transform left on the page once it
                    // has settled: any transform makes it the box that
                    // position: fixed inside it keeps to — the home page's
                    // 3D object would stay at the page's top, off screen.
                    clearProps: 'transform',
                },
                TIMING.follow + TIMING.cover * TIMING.riseAt,
            )
            // The band is off: the menu is closed and the button answers again.
            .call(
                () => {
                    setOpenState(false);
                    root.classList.remove('nav-moving');
                    isAnimating = false;
                    focusBack();
                },
                [],
                settled,
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
            .timeline()
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
            )
            // Requested before the curtain is quite down, as on the page
            // transition (page-transition.ts): it lands covered either way.
            // At an absolute time, after the tweens, so it does not delay them.
            .call(() => navigateCovered(href), [], TIMING.cover * 0.8);
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

    // Servicii: the services ease open under it and shut again, rather than
    // snapping as <details> does; a click mid-way turns it back from where
    // it is. Its plus turns while it opens and back while it closes
    // (`is-closing`). With reduced motion, the plain toggle.
    for (const group of groups) {
        const summary = group.querySelector('summary');
        const body = group.querySelector<HTMLElement>('[data-theo-group-body]');

        if (!summary || !body) {
            continue;
        }

        summary.addEventListener('click', (event) => {
            if (reducedMotion.matches) {
                return;
            }

            event.preventDefault();

            const opening =
                !group.open || group.classList.contains('is-closing');
            const from = group.open ? body.getBoundingClientRect().height : 0;

            void loadGsap().then((gsap) => {
                gsap.killTweensOf(body);

                if (opening) {
                    group.classList.remove('is-closing');
                    group.open = true;
                    gsap.fromTo(
                        body,
                        { height: from },
                        {
                            height: 'auto',
                            duration: TIMING.rise,
                            ease: EASE.out,
                            clearProps: 'height',
                        },
                    );

                    return;
                }

                group.classList.add('is-closing');
                gsap.to(body, {
                    height: 0,
                    duration: TIMING.cover,
                    ease: EASE.curtain,
                    onComplete: () => {
                        group.open = false;
                        group.classList.remove('is-closing');
                        gsap.set(body, { clearProps: 'height' });
                    },
                });
            });
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
        clearBand();

        for (const element of [...items, ...reveals, ...shifted()]) {
            element.style.transform = '';
            element.style.opacity = '';
        }
    });

    for (const opener of openers) {
        opener.addEventListener('click', (event) => {
            event.preventDefault();
            returnFocusTo = opener;
            byKeyboard = event.detail === 0;
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
                byKeyboard = event.detail === 0;
                void open();
            }
        });
    }

    closeButton?.addEventListener('click', (event) => {
        event.preventDefault();
        void close();
    });

    // The dimmed page beside the panel closes it.
    document
        .querySelector<HTMLElement>('[data-theo-backdrop]')
        ?.addEventListener('click', (event) => {
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

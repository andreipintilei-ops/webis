/**
 * Behaviour of the top bar and round menu button (components/site/
 * top-bar.blade.php), whichever menu the button opens:
 *
 * - `nav-scrolled` on <html> once the top bar has scrolled away (~30% of the
 *   first screen), which scales the round button in
 * - `nav-on-dark` on <html> while the button is over a dark surface, which
 *   turns it white
 *
 * No animation library: CSS transitions on the motion tokens
 * (css/site/motion.css) are enough here.
 */

import { backdropCovers } from '@/public/hero-intro';
import { getSmoother } from '@/public/smooth-scroll';

const SCROLLED_AT = 0.3; // of the viewport height
// What is dark with the home page's dark lower tone (lower-tone.css): its
// sections, and the sections on the hero's background — where that
// background has shrunk away, the page behind them is dark too.
const DARK_TONE_SECTIONS =
    '.services, .selected-projects, .own-products, .how-we-work, [data-backdrop-area]';
// How long the page can keep gliding after the last scroll event (ScrollSmoother).
const SETTLE_MS = 1500;

export function initSiteNav(): void {
    if (!document.querySelector('[data-burger]')) {
        return;
    }

    initScrolledState();
    initBurgerTone();
}

/**
 * Stop the page scrolling under an open menu, without it jumping sideways as
 * the scrollbar disappears. Shared by every menu variant.
 */
export function lockScroll(locked: boolean): void {
    // With smooth scrolling the smoother holds the page itself (the scrollbar
    // stays, so nothing shifts).
    const smoother = getSmoother();

    if (smoother) {
        smoother.paused(locked);

        return;
    }

    const root = document.documentElement;
    const scrollbar = window.innerWidth - root.clientWidth;

    root.style.overflow = locked ? 'hidden' : '';
    root.style.paddingRight = locked && scrollbar > 0 ? `${scrollbar}px` : '';
}

function initScrolledState(): void {
    const root = document.documentElement;
    let ticking = false;

    const update = (): void => {
        ticking = false;
        root.classList.toggle(
            'nav-scrolled',
            window.scrollY > window.innerHeight * SCROLLED_AT,
        );
    };

    window.addEventListener(
        'scroll',
        () => {
            if (!ticking) {
                ticking = true;
                requestAnimationFrame(update);
            }
        },
        { passive: true },
    );
    update();
}

/**
 * `nav-on-dark` on <html> while the round button sits over a dark surface —
 * anything inside [data-nav-tone="dark"] — so it can turn white.
 *
 * It asks the browser what is actually drawn under the button's centre, so
 * overlaps come out right: the page sliding up over the parallax hero counts
 * as light the moment it reaches the button. Smooth scrolling keeps the page
 * moving after the last scroll event, so checks run each frame until it has
 * had time to settle.
 */
function initBurgerTone(): void {
    const root = document.documentElement;
    const burger = document.querySelector<HTMLElement>('.site-burger');

    if (!burger) {
        return;
    }

    let until = 0;
    let running = false;
    // Layers above the page that say nothing about what the page is: the
    // menu and the curtains.
    const overlays =
        '.site-burger, [data-theo-menu], .theo-overlay, .theo-overlay-grain, .page-curtain, .page-curtain-grain, .page-curtain-cover';
    const menuOpen = (): boolean =>
        root.classList.contains('nav-open') ||
        root.classList.contains('nav-opening');

    const check = (): void => {
        // With the menu open the button has its own look; checking now would
        // only see the menu.
        if (menuOpen()) {
            return;
        }

        // Scaled to nothing while hidden, but the centre is still the centre.
        const box = burger.getBoundingClientRect();
        const x = box.left + box.width / 2;
        const y = box.top + box.height / 2;
        const under = document
            .elementsFromPoint(x, y)
            .find((element) => !element.closest(overlays));
        // A section on the hero's fixed background is dark only where that
        // background still shows — not once it has shrunk away (hero-intro.ts).
        const onBackdrop = under?.closest('[data-backdrop-area]') != null;

        // The home page's sections below the hero, in their dark tone
        // (css/site/lower-tone.css).
        const darkTone =
            root.dataset.lower === 'dark' &&
            under?.closest(DARK_TONE_SECTIONS) != null;

        root.classList.toggle(
            'nav-on-dark',
            darkTone ||
                (under?.closest('[data-nav-tone="dark"]') != null &&
                    (!onBackdrop || backdropCovers(x, y))),
        );
    };

    const loop = (now: number): void => {
        check();

        if (now < until) {
            requestAnimationFrame(loop);
        } else {
            running = false;
        }
    };

    const wake = (): void => {
        until = performance.now() + SETTLE_MS;

        if (!running) {
            running = true;
            requestAnimationFrame(loop);
        }
    };

    window.addEventListener('scroll', wake, { passive: true });
    window.addEventListener('resize', wake, { passive: true });

    // When the menu closes, look again: the page may have moved under it,
    // and the content settles back over the next moment.
    let wasOpen = menuOpen();

    new MutationObserver(() => {
        const open = menuOpen();

        if (wasOpen && !open) {
            wake();
        }

        wasOpen = open;
    }).observe(root, { attributes: true, attributeFilter: ['class'] });

    wake();
}

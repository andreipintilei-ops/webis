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

import { getSmoother } from '@/public/smooth-scroll';

const SCROLLED_AT = 0.3; // of the viewport height
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

    const check = (): void => {
        // Scaled to nothing while hidden, but the centre is still the centre.
        const box = burger.getBoundingClientRect();
        const under = document
            .elementsFromPoint(
                box.left + box.width / 2,
                box.top + box.height / 2,
            )
            .find((element) => !burger.contains(element));

        root.classList.toggle(
            'nav-on-dark',
            under?.closest('[data-nav-tone="dark"]') != null,
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
    wake();
}

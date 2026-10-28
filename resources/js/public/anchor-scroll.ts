import { prefersReducedMotion } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * Links to a place further down the same page ("Cum lucrăm" on Software
 * la comandă → #cum-lucram). Under ScrollSmoother the content is moved by a
 * transform, so the browser's own jump to an anchor lands in the wrong
 * place; these go through the smoother instead (smoothly), or — without it,
 * as with reduced motion — through the browser, smoothly unless motion is
 * reduced. Only for targets marked `data-scroll-target`: every other hash
 * link (the menus' toggles…) is left alone.
 *
 * The address gets the hash, and focus moves to the target, so the next
 * Tab continues from there. Arriving with the hash in the address: the
 * smoother is moved there once it is set up.
 *
 * @param ready settles once smooth scrolling is set up (or skipped)
 */
export function initAnchorScroll(ready: Promise<unknown>): void {
    const targetOf = (hash: string): HTMLElement | null => {
        if (hash.length < 2) {
            return null;
        }

        const target = document.getElementById(
            decodeURIComponent(hash.slice(1)),
        );

        return target?.hasAttribute('data-scroll-target') ? target : null;
    };

    const go = (target: HTMLElement, smooth: boolean): void => {
        const smoother = getSmoother();

        if (smoother) {
            smoother.scrollTo(target, smooth, 'top top');
        } else {
            target.scrollIntoView({
                behavior: smooth && !prefersReducedMotion() ? 'smooth' : 'auto',
                block: 'start',
            });
        }

        if (!target.hasAttribute('tabindex')) {
            target.setAttribute('tabindex', '-1');
        }

        target.focus({ preventScroll: true });
    };

    document.addEventListener('click', (event) => {
        if (
            event.defaultPrevented ||
            event.button !== 0 ||
            event.metaKey ||
            event.ctrlKey ||
            event.shiftKey ||
            event.altKey
        ) {
            return;
        }

        const link = (
            event.target as Element | null
        )?.closest<HTMLAnchorElement>('a[href^="#"]');
        const target = link ? targetOf(link.getAttribute('href') ?? '') : null;

        if (!target) {
            return;
        }

        event.preventDefault();
        history.pushState(null, '', `#${target.id}`);
        go(target, true);
    });

    const arrived = targetOf(location.hash);

    if (arrived) {
        void ready.then(() => {
            if (getSmoother()) {
                go(arrived, false);
            }
        });
    }
}

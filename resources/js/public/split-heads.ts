import { loadGsap } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * A split section's head (css/site/web.css, .web-split: the title on the
 * left, the content on the right) holds its place while the content beside
 * it scrolls past, from 1024px, and lets go as the content's end comes up
 * level with its own. Only where the content is the taller of the two.
 *
 * Under ScrollSmoother the page is moved by a transform, so CSS sticky
 * cannot hold it: it is pinned by ScrollTrigger instead (as the project
 * showcase's lead, project-showcase.ts). Without smooth scrolling, sticky.
 */

/** How far below the screen's top it holds: clear of the top bar. */
const TOP = 128;

export async function initSplitHeads(): Promise<void> {
    const splits = Array.from(
        document.querySelectorAll<HTMLElement>('[data-split]'),
    );

    if (splits.length === 0) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    gsap.matchMedia().add('(min-width: 1024px)', () => {
        for (const split of splits) {
            const head = split.querySelector<HTMLElement>('[data-split-head]');
            const body = split.querySelector<HTMLElement>('[data-split-body]');

            if (!head || !body || body.offsetHeight <= head.offsetHeight) {
                continue;
            }

            if (getSmoother()) {
                ScrollTrigger.create({
                    trigger: head,
                    pin: true,
                    pinSpacing: false,
                    start: `top ${TOP}px`,
                    endTrigger: body,
                    end: () => `bottom ${TOP + head.offsetHeight}px`,
                    invalidateOnRefresh: true,
                });
            } else {
                head.style.position = 'sticky';
                head.style.top = `${TOP}px`;
            }
        }

        return () => {
            for (const split of splits) {
                const head =
                    split.querySelector<HTMLElement>('[data-split-head]');

                if (head) {
                    head.style.position = '';
                    head.style.top = '';
                }
            }
        };
    });
}

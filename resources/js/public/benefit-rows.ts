import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The benefit cards' movement with the scroll, from 1024px; reduced
 * motion: still.
 * - The rows (components/section/benefit-rows): as they scroll through,
 *   the two drift apart a little, the first to the left and the second to
 *   the right ([data-brows-row] is the direction).
 * - The columns (components/section/benefit-columns): the outer two rise a
 *   little faster than the middle one, so the stagger shifts as they pass
 *   ([data-bcols-col]: "fast" or "slow").
 */

/** How far each row travels across the whole scroll, in pixels… */
const DRIFT = 90;
/** …and how much further the outer columns rise than the middle one. */
const RISE = 40;

export async function initBenefitRows(): Promise<void> {
    const groups = Array.from(
        document.querySelectorAll<HTMLElement>('[data-brows]'),
    );
    const walls = Array.from(
        document.querySelectorAll<HTMLElement>('[data-bcols]'),
    );

    if (groups.length + walls.length === 0 || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    gsap.matchMedia().add('(min-width: 1024px)', () => {
        for (const group of groups) {
            group
                .querySelectorAll<HTMLElement>('[data-brows-row]')
                .forEach((row) => {
                    const way = Number(row.dataset.browsRow) || 1;

                    // From their resting offset outwards only, so the rows
                    // never line up on the way.
                    gsap.fromTo(
                        row,
                        { x: 0 },
                        {
                            x: way * DRIFT,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: group,
                                start: 'top bottom',
                                end: 'bottom top',
                                scrub: true,
                            },
                        },
                    );
                });
        }

        for (const wall of walls) {
            wall.querySelectorAll<HTMLElement>('[data-bcols-col]').forEach(
                (column) => {
                    const fast = column.dataset.bcolsCol === 'fast';

                    gsap.fromTo(
                        column,
                        { y: fast ? RISE / 2 : 0 },
                        {
                            y: fast ? -RISE / 2 : 0,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: wall,
                                start: 'top bottom',
                                end: 'bottom top',
                                scrub: true,
                            },
                        },
                    );
                },
            );
        }
    });
}

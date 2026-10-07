import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * Screen-high cards stacking on scroll — every [data-product-stack] on the
 * page: our products (components/site/own-products, layout "stack") and the
 * services on the home page (components/site/service-stack).
 * From 1024px:
 *
 * - Each card comes in as the card on mews.fm does: a little smaller, lower
 *   and tilted, straightening and growing to full size by the time it
 *   reaches the top, just past straight before it settles.
 * - It then holds (pinned) until the last card has arrived, so the next
 *   one slides up over it — while it shrinks and tilts, as on gethyped.nl.
 * - At every width the card holding the middle of the screen is marked
 *   `is-current`.
 *
 * All scrubbed, so it plays back on the way up. Below 1024px, and with
 * reduced motion, the cards simply follow one another.
 */

export async function initProductStack(): Promise<void> {
    const sections = Array.from(
        document.querySelectorAll<HTMLElement>('[data-product-stack]'),
    );

    if (sections.length === 0 || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    for (const section of sections) {
        const cards = Array.from(
            section.querySelectorAll<HTMLElement>('[data-product-card]'),
        );

        if (cards.length === 0) {
            continue;
        }

        // The card on top, at every width: `is-current` while it holds the
        // middle of the screen (its own scene can play on it).
        for (const card of cards) {
            ScrollTrigger.create({
                trigger: card,
                start: 'top center',
                end: 'bottom center',
                toggleClass: 'is-current',
            });
        }

        gsap.matchMedia().add('(min-width: 1024px)', () => {
            const last = cards[cards.length - 1];

            cards.forEach((card, index) => {
                const panel = card.querySelector<HTMLElement>(
                    '[data-product-panel]',
                );
                const frame = card.querySelector<HTMLElement>(
                    '[data-product-entry]',
                );
                const next = cards[index + 1];

                if (!panel || !frame) {
                    return;
                }

                // Comes in as the card on mews.fm: a little smaller, lower and
                // tilted, straightening and growing to full size as it rises —
                // just past straight, then settling (back.out).
                gsap.fromTo(
                    frame,
                    { scale: 0.88, rotation: index % 2 === 0 ? -4 : 4, y: 80 },
                    {
                        scale: 1,
                        rotation: 0,
                        y: 0,
                        ease: 'back.out(1.4)',
                        scrollTrigger: {
                            trigger: card,
                            start: 'top bottom',
                            end: 'top top',
                            scrub: true,
                        },
                    },
                );

                if (!next) {
                    return;
                }

                // Holds while the cards after it arrive over it.
                ScrollTrigger.create({
                    trigger: card,
                    start: 'top top',
                    endTrigger: last,
                    end: 'top top',
                    pin: true,
                    pinSpacing: false,
                });

                // Shrinks and tilts (each the other way) as the next one
                // covers it, from its top, so its top peeks above it.
                gsap.to(panel, {
                    scale: 0.84,
                    rotation: index % 2 === 0 ? -2 : 2,
                    y: 24,
                    ease: 'none',
                    scrollTrigger: {
                        trigger: next,
                        start: 'top bottom',
                        end: 'top top',
                        scrub: true,
                    },
                });
            });

            return () => {
                gsap.set(
                    section.querySelectorAll(
                        '[data-product-panel], [data-product-entry]',
                    ),
                    { clearProps: 'all' },
                );
            };
        });
    }
}

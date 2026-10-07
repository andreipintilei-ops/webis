import { loadGsap, prefersReducedMotion } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * "Proiecte selectate" on the home page (components/site/selected-projects,
 * layout "showcase"), from desktop width:
 * - The left card stays in view, centred on the screen, while the project
 *   cards scroll past it. CSS `position: sticky` cannot do this under
 *   ScrollSmoother (the page is moved by a transform), so it is pinned by
 *   ScrollTrigger instead; without smooth scrolling, CSS sticky does it.
 * - It shows the details of the project whose card is nearest the middle of
 *   the screen, the next coming in as the last goes.
 * - Each interface drifts up a little within its card as it passes.
 *
 * With reduced motion the details still change, without the movement.
 */
export async function initProjectShowcase(): Promise<void> {
    const section = document.querySelector<HTMLElement>(
        '[data-project-showcase]',
    );
    const lead = section?.querySelector<HTMLElement>('[data-showcase-lead]');
    const column = section?.querySelector<HTMLElement>('[data-showcase-cards]');

    if (!section || !lead || !column) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    const still = prefersReducedMotion();
    const panels = Array.from(
        section.querySelectorAll<HTMLElement>('[data-showcase-panel]'),
    );
    const cards = Array.from(
        section.querySelectorAll<HTMLElement>('[data-showcase-card]'),
    );
    let active = 0;

    const show = (index: number): void => {
        if (index === active || !panels[index]) {
            return;
        }

        const leaving = panels[active];
        const coming = panels[index];
        const down = index > active;

        active = index;
        leaving.classList.remove('is-active');
        coming.classList.add('is-active');

        if (still) {
            return;
        }

        gsap.fromTo(
            leaving,
            { autoAlpha: 1, y: 0 },
            {
                autoAlpha: 0,
                y: down ? -16 : 16,
                duration: 0.35,
                ease: 'power2.in',
                overwrite: true,
                clearProps: 'y,opacity,visibility',
            },
        );
        gsap.fromTo(
            coming,
            { autoAlpha: 0, y: down ? 20 : -20 },
            {
                autoAlpha: 1,
                y: 0,
                duration: 0.55,
                delay: 0.2,
                ease: 'power3.out',
                overwrite: true,
                clearProps: 'y,opacity,visibility',
            },
        );
    };

    gsap.matchMedia().add('(min-width: 1024px)', () => {
        // Centred: as far from the top of the screen as from the bottom.
        const top = (): number => (innerHeight - lead.offsetHeight) / 2;

        if (getSmoother()) {
            // Held from when it reaches its place until the last card's
            // bottom is level with its own.
            ScrollTrigger.create({
                trigger: lead,
                pin: true,
                pinSpacing: false,
                start: () => `top ${top()}px`,
                endTrigger: column,
                end: () => `bottom ${top() + lead.offsetHeight}px`,
                invalidateOnRefresh: true,
            });
        } else {
            lead.style.position = 'sticky';
            lead.style.top = `${top()}px`;
        }

        // The step in view: the card whose middle is nearest the middle of
        // the screen — so it is always the one being looked at, gaps between
        // the cards or not, scrolling down or up.
        const pick = (): void => {
            let nearest = 0;
            let best = Infinity;

            cards.forEach((card, index) => {
                const box = card.getBoundingClientRect();
                const distance = Math.abs(
                    box.top + box.height / 2 - innerHeight / 2,
                );

                if (distance < best) {
                    [nearest, best] = [index, distance];
                }
            });

            show(nearest);
        };

        ScrollTrigger.create({
            trigger: column,
            start: 'top bottom',
            end: 'bottom top',
            onUpdate: pick,
            onRefresh: pick,
        });

        cards.forEach((card) => {
            const screen = card.querySelector('[data-showcase-screen]');

            if (screen && !still) {
                gsap.fromTo(
                    screen,
                    { yPercent: 8 },
                    {
                        yPercent: -4,
                        ease: 'none',
                        scrollTrigger: {
                            trigger: card,
                            start: 'top bottom',
                            end: 'bottom top',
                            scrub: true,
                        },
                    },
                );
            }
        });

        return () => {
            lead.style.removeProperty('position');
            lead.style.removeProperty('top');
        };
    });
}

import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The projects as a walkthrough (components/site/selected-projects, layout
 * "steps"; css/site/project-steps.css), after sendpotion.com. From 1024px:
 *
 * - Each step is held centred in view while its card passes, then pushed on
 *   by the next as its row ends — sticky, worked out from the row's place on
 *   screen each frame (CSS sticky cannot do it under ScrollSmoother).
 * - Its lines come in one after another, from a little to one side and
 *   tilted, settling on a slight overshoot; back out if the page scrolls
 *   back past it.
 * - The interface window drifts up within its card as the card passes.
 *
 * Reduced motion: the steps are still held, nothing eases or drifts.
 */
export async function initProjectSteps(): Promise<void> {
    const section = document.querySelector<HTMLElement>('[data-project-steps]');
    const rows = Array.from(
        section?.querySelectorAll<HTMLElement>('[data-step-row]') ?? [],
    );

    if (!section || rows.length === 0) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    const still = prefersReducedMotion();

    gsap.matchMedia().add('(min-width: 1024px)', () => {
        const steps = rows.map((row) => ({
            row,
            text: row.querySelector<HTMLElement>('[data-step-text]'),
        }));

        // Held: centred on the screen, kept within its own row — leaving
        // a fifth of the screen free at the row's end, so the next step
        // arrives with room between them.
        const hold = (): void => {
            for (const { row, text } of steps) {
                if (!text) {
                    continue;
                }

                const box = row.getBoundingClientRect();
                const height = text.offsetHeight;
                const centred = (innerHeight - height) / 2 - box.top;
                const room = Math.max(
                    0,
                    box.height - height - innerHeight * 0.2,
                );
                const y = gsap.utils.clamp(0, room, centred);

                text.style.transform = `translate3d(0, ${y}px, 0)`;
            }
        };

        // Only while the section is on screen.
        const watch = ScrollTrigger.create({
            trigger: section,
            start: 'top bottom',
            end: 'bottom top',
            onToggle: (self) =>
                self.isActive
                    ? gsap.ticker.add(hold)
                    : gsap.ticker.remove(hold),
        });

        hold();

        if (!still) {
            for (const { row, text } of steps) {
                const parts = text?.querySelectorAll('[data-step-part]') ?? [];
                const window = row.querySelector('[data-step-window]');

                // The lines come in one after another, each from a little
                // to one side and tilted, settling on a slight overshoot.
                gsap.from(parts, {
                    y: 60,
                    x: (index: number) => (index % 2 === 0 ? -24 : 24),
                    rotation: (index: number) => (index % 2 === 0 ? -4 : 3),
                    opacity: 0,
                    duration: 0.9,
                    ease: 'back.out(1.5)',
                    stagger: 0.08,
                    transformOrigin: '0% 50%',
                    scrollTrigger: {
                        trigger: row,
                        start: 'top 55%',
                        toggleActions: 'play none none reverse',
                    },
                });

                // The window drifts up within its card.
                if (window) {
                    gsap.fromTo(
                        window,
                        { yPercent: 10 },
                        {
                            yPercent: -10,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: row,
                                start: 'top bottom',
                                end: 'bottom top',
                                scrub: true,
                            },
                        },
                    );
                }
            }
        }

        return () => {
            watch.kill();
            gsap.ticker.remove(hold);

            for (const { text } of steps) {
                text?.style.removeProperty('transform');
            }
        };
    });
}

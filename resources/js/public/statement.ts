import { loadGsap, prefersReducedMotion } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * Statement blocks (blocks/statement.blade.php), after integratedbio.com:
 *
 * - The statement's words light up, grey to full colour, one after another
 *   as the section scrolls through the screen — scrubbed, so scrolling back
 *   dims them again.
 * - From tablet width up, the label on the left stays in view while the
 *   right column scrolls past it. CSS `position: sticky` cannot do this under
 *   ScrollSmoother (the page is moved by a transform), so it is pinned by
 *   ScrollTrigger instead; without smooth scrolling, CSS sticky does it.
 *
 * Nothing happens with reduced motion: the statement is simply dark.
 */

/** How dim a word is before it lights up: on white, and on the dark background. */
const DIM = { light: 0.18, dark: 0.3 };
/** The label's distance from the top of the screen while held, px. */
const PIN_TOP = 140;

export async function initStatements(): Promise<void> {
    const sections = Array.from(
        document.querySelectorAll<HTMLElement>('[data-statement]'),
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
        const words = Array.from(
            section.querySelectorAll<HTMLElement>('.statement__word'),
        );

        gsap.fromTo(
            words,
            {
                opacity: section.classList.contains('statement--dark')
                    ? DIM.dark
                    : DIM.light,
            },
            {
                opacity: 1,
                ease: 'none',
                stagger: 0.1,
                scrollTrigger: {
                    trigger: section.querySelector('[data-statement-text]'),
                    start: 'top 85%',
                    end: 'bottom 45%',
                    scrub: true,
                },
            },
        );

        const label = section.querySelector<HTMLElement>(
            '[data-statement-pin]',
        );
        const column = label?.parentElement?.nextElementSibling;

        if (!label || !(column instanceof HTMLElement)) {
            continue;
        }

        gsap.matchMedia().add('(min-width: 768px)', () => {
            if (!getSmoother()) {
                label.style.position = 'sticky';
                label.style.top = `${PIN_TOP}px`;

                return () => {
                    label.style.position = '';
                    label.style.top = '';
                };
            }

            // Held from when it reaches PIN_TOP until the column beside it
            // ends level with it.
            ScrollTrigger.create({
                trigger: label,
                pin: true,
                pinSpacing: false,
                start: `top ${PIN_TOP}px`,
                endTrigger: column,
                end: () => `bottom ${PIN_TOP + label.offsetHeight}px`,
            });

            return undefined;
        });
    }
}

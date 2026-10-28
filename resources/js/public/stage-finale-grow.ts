import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The stage's closing panel on the home page (css/site/variant-stage.css):
 * laid out to the screen's edges and a little taller than the card, and
 * clipped back to the rounded card, it snaps open to them once it is well on
 * the screen — the clip's sides, top and bottom going to nothing and its
 * corners square, in a short timed move rather than with the scroll (that
 * dragged), and back to the card when scrolled back above. Reduced motion:
 * full size from the start.
 */

/** The card's width at most (max-w-[92rem]), and its corners (px). */
const CARD_MAX = 92 * 16;
const RADIUS = { narrow: 24, wide: 40 };

export async function initStageFinaleGrow(): Promise<void> {
    const panel = document.querySelector<HTMLElement>(
        '.stage [data-stage-finale]',
    );
    const section = panel?.closest<HTMLElement>('.stage');

    if (!panel || !section) {
        return;
    }

    const open = 'inset(0px 0px round 0px)';

    if (prefersReducedMotion()) {
        panel.style.clipPath = open;

        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    const wide = window.matchMedia('(min-width: 1024px)');

    /** The card, as a clip of the full panel: its sides, ends and corners. */
    const card = (): string => {
        const gap = parseFloat(getComputedStyle(section).paddingLeft) || 0;
        // The rise above and below the card is the panel's own padding.
        const rise = parseFloat(getComputedStyle(panel).paddingTop) || 0;
        const width = panel.getBoundingClientRect().width;
        const side = Math.max(gap, (width - CARD_MAX) / 2);
        const radius = wide.matches ? RADIUS.wide : RADIUS.narrow;

        return `inset(${rise}px ${side}px round ${radius}px)`;
    };

    gsap.fromTo(
        panel,
        { clipPath: card },
        {
            clipPath: open,
            duration: 0.9,
            ease: 'expo.out',
            scrollTrigger: {
                trigger: panel,
                // Once its top is near the screen's middle, as the W comes
                // together on it.
                start: 'top 55%',
                toggleActions: 'play none none reverse',
                invalidateOnRefresh: true,
            },
        },
    );
}

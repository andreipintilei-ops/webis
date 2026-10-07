import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The illustrated service cards (blocks/partials/service-cards.blade.php),
 * entering as the home page's do (js/public/services.ts): as the list
 * scrolls into view, the cards drop in from below one after another —
 * leaning a little and settling flat on a springy curve, like cards dealt
 * onto a table — and the lines in their illustrations draw on. The cards
 * follow the scroll (scrubbed): they have settled when the list's top
 * reaches 35% of the screen, and go back the same way on the way up.
 *
 * The cards' transform is CSS (css/site/service-cards.css), built from
 * custom properties; the entry animates those (--in-y, --in-r, --in-o) —
 * never `transform` itself.
 *
 * Reduced motion: no entry, and the dots that travel along the
 * illustrations' lines (SVG animation) are stopped.
 */
export async function initServiceCards(): Promise<void> {
    const list = document.querySelector<HTMLElement>('[data-service-cards]');
    const cards = Array.from(
        list?.querySelectorAll<HTMLElement>('.svc-card') ?? [],
    );

    if (!list || cards.length === 0) {
        return;
    }

    if (prefersReducedMotion()) {
        list.querySelectorAll<SVGSVGElement>('svg.il').forEach((svg) =>
            svg.pauseAnimations(),
        );

        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    // Lines undrawn until the cards come in.
    list.dataset.entry = 'waiting';

    // The lines draw on once, as the cards start coming in.
    ScrollTrigger.create({
        trigger: list,
        start: 'top 85%',
        once: true,
        onEnter: () => {
            list.dataset.entry = 'in';
        },
    });

    // The cards come in with the scroll: from when the list's top is at 85%
    // of the screen until it reaches 35% — each card over a long stretch of it,
    // overlapping the next, so a slow scroll sees it all — and go back the
    // same way.
    // Both ends given (fromTo), so GSAP owns the starting values too — a
    // scroll refresh reverts what it set, and would otherwise lose them.
    gsap.fromTo(
        cards,
        {
            '--in-o': 0,
            '--in-y': '6rem',
            // Every other card leans the other way as it comes in.
            '--in-r': (index: number) => `${index % 2 === 0 ? -4 : 4}deg`,
        },
        {
            '--in-o': 1,
            '--in-y': '0rem',
            '--in-r': '0deg',
            duration: 1,
            ease: 'power2.out',
            stagger: 0.3,
            scrollTrigger: {
                trigger: list,
                start: 'top 85%',
                // …or as far as the page scrolls, if it ends sooner.
                end: 'clamp(top 35%)',
                scrub: 0.6,
            },
        },
    );
}

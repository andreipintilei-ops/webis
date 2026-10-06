import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The home page's service cards (blocks/partials/services.blade.php): as the
 * section scrolls into view, the cards drop in from below one after another
 * — starting further tilted and settling into their resting angle on a
 * springy curve, like cards dealt onto a table. Plays once.
 *
 * The cards' resting pose and hover are CSS (css/site/services.css), one
 * `transform` built from custom properties; the entry animates its own
 * (--in-y, --in-r, --in-o), which that transform adds in, and removes them at
 * the end — never `transform`, `rotate` or `translate` themselves, which GSAP
 * would write as an inline transform that overrides the tilt and the hover.
 * Reduced motion: no entry.
 */
export async function initServices(): Promise<void> {
    const cards = Array.from(
        document.querySelectorAll<HTMLElement>('.services .service-card'),
    );

    if (cards.length === 0 || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    // Every other card leans the other way as it comes in.
    cards.forEach((card, index) => {
        const lean = index % 2 === 0 ? -1 : 1;

        card.style.setProperty('--in-o', '0');
        card.style.setProperty('--in-y', '6rem');
        card.style.setProperty('--in-r', `${lean * 8}deg`);
    });

    ScrollTrigger.create({
        trigger: cards[0],
        start: 'top 85%',
        once: true,
        onEnter: () => {
            gsap.to(cards, {
                '--in-o': 1,
                '--in-y': '0rem',
                '--in-r': '0deg',
                duration: 1.1,
                ease: 'back.out(1.6)',
                stagger: 0.12,
                onComplete: () => {
                    for (const card of cards) {
                        card.style.removeProperty('--in-o');
                        card.style.removeProperty('--in-y');
                        card.style.removeProperty('--in-r');
                    }
                },
            });
        },
    });
}

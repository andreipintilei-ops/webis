import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The service rows on /clienti (blocks/partials/services-plain.blade.php):
 * each row comes in once, as it scrolls into view — its colour rule drawn
 * from the left and its square set, the title rising into place, then the
 * text and the arrow. Reduced motion: shown as they are.
 */
export async function initServicesPlain(): Promise<void> {
    const section = document.querySelector<HTMLElement>(
        '[data-services-plain]',
    );

    if (!section || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    const label = section.querySelector<HTMLElement>('[data-row-label]');

    if (label) {
        gsap.from(label, {
            opacity: 0,
            y: 12,
            duration: 0.6,
            ease: 'power2.out',
            scrollTrigger: { trigger: label, start: 'top 90%', once: true },
        });
    }

    section.querySelectorAll<HTMLElement>('[data-row]').forEach((row) => {
        const part = (name: string) =>
            row.querySelectorAll<HTMLElement>(`[data-row-${name}]`);

        gsap.timeline({
            defaults: { ease: 'power3.out' },
            scrollTrigger: { trigger: row, start: 'top 85%', once: true },
        })
            .from(part('rule'), { scaleX: 0, duration: 1.1 })
            .from(
                part('mark'),
                { scale: 0, duration: 0.5, ease: 'back.out(2)' },
                0.15,
            )
            .from(part('title'), { yPercent: 110, duration: 0.9 }, 0.1)
            .from(
                part('fade'),
                { opacity: 0, y: 16, duration: 0.7, stagger: 0.08 },
                0.3,
            );
    });
}

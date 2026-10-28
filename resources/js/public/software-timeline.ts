import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The delivery timeline on Software la comandă (public/software.blade.php,
 * 03; css/site/software.css): its segments light up one after another —
 * the planning ones neutral, the modules in the accent with their "în
 * folosință" tags, then maintenance, fading on — once, the first time the
 * track comes into view (ScrollTrigger, which follows ScrollSmoother). The
 * timing is the stylesheet's (transition delays per segment); this only
 * switches the state: "waiting" (unlit) as soon as it runs, "playing" on
 * entry. Served lit, so without the script — and with reduced motion,
 * where nothing is switched — it shows its final state at once.
 */
export async function initSoftwareTimeline(): Promise<void> {
    const timeline = document.querySelector<HTMLElement>('[data-sw-timeline]');

    if (!timeline || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    // Already scrolled past it (a reload further down): leave it lit.
    if (timeline.getBoundingClientRect().bottom < 0) {
        return;
    }

    timeline.dataset.state = 'waiting';

    ScrollTrigger.create({
        trigger: timeline,
        start: 'top 80%',
        once: true,
        onEnter: () => {
            // A frame in the unlit state first, so the transitions run.
            requestAnimationFrame(() => {
                timeline.dataset.state = 'playing';
            });
        },
    });
}

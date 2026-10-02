import { loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * Smooth scrolling with GSAP ScrollSmoother, after dennissnellenberg.com: the
 * page eases after the native scroll instead of jumping with it, and any
 * element can move at its own speed from the template — `data-speed="0.5"`
 * (half speed; "clamp(0.5)" for something already on the first screen, so it
 * starts in place) or `data-lag="0.2"` (catches up late).
 *
 * The native scrollbar, keyboard and find-in-page keep working: the browser
 * still scrolls; ScrollSmoother only draws the content where the scroll is
 * heading. Touch screens keep native scrolling (the speed effects still
 * apply), and with reduced motion nothing is set up at all.
 *
 * Markup: #smooth-wrapper > #smooth-content in layouts/public.blade.php.
 * Anything fixed to the screen must stay outside the wrapper.
 */

type Smoother = InstanceType<
    typeof import('gsap/ScrollSmoother').ScrollSmoother
>;

let smoother: Smoother | null = null;

/** The running smoother, if smooth scrolling is on for this visit. */
export function getSmoother(): Smoother | null {
    return smoother;
}

export async function initSmoothScroll(): Promise<void> {
    const wrapper = document.getElementById('smooth-wrapper');
    const content = document.getElementById('smooth-content');

    if (!wrapper || !content || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }, { ScrollSmoother }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
        import('gsap/ScrollSmoother'),
    ]);

    gsap.registerPlugin(ScrollTrigger, ScrollSmoother);

    smoother = ScrollSmoother.create({
        wrapper,
        content,
        smooth: 1, // seconds for the page to catch up with the scroll
        effects: true, // data-speed / data-lag
        smoothTouch: false,
    });
}

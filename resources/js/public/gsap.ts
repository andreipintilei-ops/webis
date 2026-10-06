/**
 * GSAP, loaded on demand and shared by every public animation. It is kept out
 * of the page bundle: fetched when the browser is idle after load, or at once
 * when something needs it, and downloaded only once either way.
 */

export type Gsap = typeof import('gsap').gsap;

/**
 * The site's easing family (expo), as GSAP names. The CSS twins live in
 * resources/css/site/motion.css — keep the two in step.
 */
export const EASE = {
    /** Things arriving: fast start, long smooth settle. */
    out: 'expo.out',
    /** Things leaving: they gather speed and go. */
    in: 'expo.in',
    /** Things travelling across the screen (the hero intro's growth). */
    inOut: 'expo.inOut',
    /**
     * The curtains (menu, page transition): quartic, gentler than expo — at
     * ~0.6s expo crams the move into its middle and reads as a snap; this
     * spreads it over more of the time and still eases at both ends.
     */
    curtain: 'power3.inOut',
} as const;

let gsapPromise: Promise<Gsap> | null = null;

export function loadGsap(): Promise<Gsap> {
    gsapPromise ??= import('gsap').then((module) => module.gsap);

    return gsapPromise;
}

export function prefetchGsapWhenIdle(): void {
    const start = () => void loadGsap();

    if ('requestIdleCallback' in window) {
        window.requestIdleCallback(start, { timeout: 3000 });
    } else {
        setTimeout(start, 1500);
    }
}

export const prefersReducedMotion = (): boolean =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/**
 * One curtain move as a single continuous tween: from the shape it currently
 * has, through its curved mid-shape, to its end shape, on one easing curve —
 * so there is no visible joint between the curve forming and flattening.
 *
 * @param curveAt how far through the move (0–1) the curve peaks
 */
export function curtainMove(
    via: string,
    to: string,
    duration: number,
    ease: string = EASE.curtain,
    curveAt = 0.6,
): gsap.TweenVars {
    return {
        keyframes: {
            [`${Math.round(curveAt * 100)}%`]: { attr: { d: via } },
            '100%': { attr: { d: to } },
            easeEach: 'none',
        },
        duration,
        ease,
    };
}

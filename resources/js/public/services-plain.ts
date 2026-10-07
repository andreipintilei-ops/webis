import { prefersReducedMotion } from '@/public/gsap';

/**
 * The service bands on /clienti (blocks/partials/services-plain.blade.php):
 * once, as they come into view, each band fades and rises into place
 * (css/site/services-plain.css; 80ms apart) while its numeral counts up
 * from 00. No GSAP — an IntersectionObserver and CSS transitions.
 *
 * Nothing is hidden until this has run: without it, or with reduced motion,
 * the bands are simply there.
 */

/** The bands' entry duration (--slow) and the step between them. */
const FALLBACK_MS = 520;
const STAGGER_MS = 80;

export function initServicesPlain(): void {
    const bands = document.querySelector<HTMLElement>('[data-bands]');

    if (
        !bands ||
        prefersReducedMotion() ||
        !('IntersectionObserver' in window)
    ) {
        return;
    }

    const numerals = Array.from(
        bands.querySelectorAll<HTMLElement>('[data-numeral]'),
    );
    const duration =
        parseFloat(getComputedStyle(bands).getPropertyValue('--slow')) ||
        FALLBACK_MS;

    bands.dataset.entry = 'waiting';
    numerals.forEach((numeral) => (numeral.textContent = '00.'));

    const observer = new IntersectionObserver(
        ([entry]) => {
            if (!entry?.isIntersecting) {
                return;
            }

            observer.disconnect();
            bands.dataset.entry = 'in';
            numerals.forEach((numeral, index) =>
                countUp(
                    numeral,
                    Number(numeral.dataset.numeral),
                    duration,
                    index * STAGGER_MS,
                ),
            );
        },
        { threshold: 0.15 },
    );

    observer.observe(bands);
}

/** "00." to "0n." over the duration, after the delay. */
function countUp(
    numeral: HTMLElement,
    to: number,
    duration: number,
    delay: number,
): void {
    const start = performance.now() + delay;
    const write = (value: number): void => {
        numeral.textContent = `${String(value).padStart(2, '0')}.`;
    };

    const frame = (now: number): void => {
        const progress = Math.min(1, Math.max(0, (now - start) / duration));

        write(Math.round(progress * to));

        if (progress < 1) {
            requestAnimationFrame(frame);
        }
    };

    requestAnimationFrame(frame);
}

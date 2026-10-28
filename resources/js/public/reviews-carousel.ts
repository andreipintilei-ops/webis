import { prefersReducedMotion } from '@/public/gsap';

/**
 * The reviews carousel (components/site/reviews.blade.php;
 * css/site/reviews-carousel.css). The reviews lie stacked in one place; the
 * script places each by its distance from the one in focus — `position`, a
 * number that dragging moves continuously and the buttons animate — so as
 * they travel the cards grow toward the centre and shrink away from it, the
 * neighbours smaller on either side, the rest faded out beyond them. It
 * wraps around: the last is followed by the first.
 *
 * Swipe or drag the cards (a flick carries to the next), click a side one,
 * or, the carousel in focus, use the arrow keys. Each text is cut to the
 * same height; when it is longer, "Citește tot" opens the one in focus
 * downward, and it closes again when the focus moves on. Reduced motion:
 * the moves are instant.
 */

/** The side cards' scale; their contents' opacity; the gap between the cards. */
const SIDE_SCALE = 0.84;
const SIDE_OPACITY = 0.6;
const GAP = 24;

/**
 * A move: long, on an expo-out curve — fast away, then a long soft landing,
 * which is what makes the swipe feel elastic (as sendpotion.com's:
 * cubic-bezier(.16, 1, .3, 1) over 800ms).
 */
const MOVE_MS = 800;
const easeOut = cubicBezier(0.16, 1, 0.3, 1);

/** --slow (css/site/menu-panel.css), for opening a text. */
const SLOW_MS = 520;

export function initReviewsCarousels(): void {
    for (const carousel of document.querySelectorAll<HTMLElement>(
        '[data-reviews-carousel]',
    )) {
        setUp(carousel);
    }
}

function setUp(carousel: HTMLElement): void {
    const track = carousel.querySelector<HTMLElement>('[data-reviews-track]');
    const slides = Array.from(
        carousel.querySelectorAll<HTMLElement>('[data-review]'),
    );

    if (!track || slides.length < 2) {
        return;
    }

    const count = slides.length;
    const still = prefersReducedMotion();

    let position = 0;
    let active = 0;
    let step = 0;
    let frame = 0;

    /** A slide's distance from the focus, wrapped into [-count/2, count/2). */
    const offsetOf = (index: number): number => {
        const raw = index - position + count / 2;

        return (((raw % count) + count) % count) - count / 2;
    };

    const render = (): void => {
        slides.forEach((slide, index) => {
            const offset = offsetOf(index);
            const distance = Math.abs(offset);
            const near = Math.min(distance, 1);
            const scale = 1 - (1 - SIDE_SCALE) * near;
            // The card stays opaque out to the sides, then is gone half a
            // step beyond; its contents dim toward the sides (--fade).
            const opacity =
                distance <= 1 ? 1 : Math.max(0, (1.5 - distance) * 2);

            slide.style.transform = `translateX(${offset * step}px) scale(${scale})`;
            slide.style.opacity = String(opacity);
            slide.style.setProperty(
                '--fade',
                String(1 - (1 - SIDE_OPACITY) * near),
            );
            slide.style.zIndex = String(100 - Math.round(distance * 10));
            slide.style.visibility = opacity === 0 ? 'hidden' : '';
        });

        const now = ((Math.round(position) % count) + count) % count;

        if (now !== active) {
            settle(now);
        }
    };

    /** The focus has moved to this slide: the states. */
    const settle = (index: number): void => {
        const previous = slides[active];

        if (previous) {
            close(previous, true);
        }

        active = index;

        slides.forEach((slide, i) => {
            const on = i === index;
            const more = slide.querySelector<HTMLElement>('[data-review-more]');

            slide.classList.toggle('is-focus', on);
            slide.setAttribute('aria-hidden', String(!on));

            if (more) {
                more.tabIndex = on ? 0 : -1;
            }
        });
    };

    const measure = (): void => {
        const width = slides[0]?.offsetWidth ?? 0;

        step = (width * (1 + SIDE_SCALE)) / 2 + GAP;
        slides.forEach(clamp);
        render();
    };

    /** Animate `position` to this (unwrapped) value. */
    const moveTo = (target: number): void => {
        cancelAnimationFrame(frame);

        if (still) {
            position = target;
            render();

            return;
        }

        const from = position;
        const start = performance.now();

        const tick = (now: number): void => {
            const t = Math.min(1, (now - start) / MOVE_MS);

            position = from + (target - from) * easeOut(t);
            render();

            if (t < 1) {
                frame = requestAnimationFrame(tick);
            }
        };

        frame = requestAnimationFrame(tick);
    };

    /** By this many slides from the one in focus. */
    const go = (by: number): void => moveTo(Math.round(position) + by);

    // ---- The texts: cut to the same height, opened downward on demand. ----

    /** Show "Citește tot" only where the text is longer than its cut. */
    function clamp(slide: HTMLElement): void {
        const quote = slide.querySelector<HTMLElement>('[data-review-quote]');
        const more = slide.querySelector<HTMLElement>('[data-review-more]');

        if (!quote || !more || slide.classList.contains('is-open')) {
            return;
        }

        const longer = quote.scrollHeight > quote.clientHeight + 1;

        more.hidden = false;
        more.style.visibility = longer ? '' : 'hidden';
        slide.classList.toggle('is-cut', longer);
    }

    const open = (slide: HTMLElement): void => {
        const quote = slide.querySelector<HTMLElement>('[data-review-quote]');
        const more = slide.querySelector<HTMLElement>('[data-review-more]');

        const label = slide.querySelector<HTMLElement>(
            '[data-review-more-label]',
        );

        if (!quote || !more || !label) {
            return;
        }

        const from = quote.clientHeight;

        slide.classList.add('is-open');
        label.textContent = 'Mai puțin';
        more.setAttribute('aria-expanded', 'true');

        if (!still) {
            quote.animate(
                [
                    { height: `${from}px` },
                    { height: `${quote.scrollHeight}px` },
                ],
                { duration: SLOW_MS, easing: 'cubic-bezier(0.22, 1, 0.36, 1)' },
            );
        }
    };

    function close(slide: HTMLElement, quietly = false): void {
        const quote = slide.querySelector<HTMLElement>('[data-review-quote]');
        const more = slide.querySelector<HTMLElement>('[data-review-more]');

        const label = slide.querySelector<HTMLElement>(
            '[data-review-more-label]',
        );

        if (!quote || !more || !label || !slide.classList.contains('is-open')) {
            return;
        }

        const from = quote.clientHeight;

        slide.classList.remove('is-open');
        label.textContent = 'Citește tot';
        more.setAttribute('aria-expanded', 'false');

        if (!still && !quietly) {
            quote.animate(
                [
                    { height: `${from}px` },
                    { height: `${quote.clientHeight}px` },
                ],
                {
                    duration: SLOW_MS * 0.8,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                },
            );
        }
    }

    for (const slide of slides) {
        slide
            .querySelector('[data-review-more]')
            ?.addEventListener('click', () =>
                slide.classList.contains('is-open')
                    ? close(slide)
                    : open(slide),
            );
    }

    // ---- Dragging and swiping. ------------------------------------------------

    let pointer: number | null = null;
    let startX = 0;
    let startY = 0;
    let startPosition = 0;
    let dragging = false;
    let dragged = false;
    let lastX = 0;
    let lastTime = 0;
    let velocity = 0;

    track.addEventListener('pointerdown', (event) => {
        if (event.button !== 0 || pointer !== null) {
            return;
        }

        pointer = event.pointerId;
        startX = lastX = event.clientX;
        startY = event.clientY;
        lastTime = event.timeStamp;
        startPosition = position;
        dragging = false;
        dragged = false;
        velocity = 0;
    });

    track.addEventListener('pointermove', (event) => {
        if (event.pointerId !== pointer) {
            return;
        }

        const dx = event.clientX - startX;

        if (!dragging) {
            // Only a horizontal move is a drag; a vertical one scrolls.
            if (
                Math.abs(dx) < 8 ||
                Math.abs(dx) < Math.abs(event.clientY - startY)
            ) {
                return;
            }

            dragging = true;
            cancelAnimationFrame(frame);
            startPosition = position;
            startX = event.clientX;
            track.setPointerCapture(event.pointerId);
            carousel.classList.add('is-dragging');
        }

        const elapsed = Math.max(1, event.timeStamp - lastTime);

        velocity = (event.clientX - lastX) / elapsed;
        lastX = event.clientX;
        lastTime = event.timeStamp;
        position = startPosition - (event.clientX - startX) / step;
        render();
    });

    const release = (event: PointerEvent): void => {
        if (event.pointerId !== pointer) {
            return;
        }

        pointer = null;

        if (!dragging) {
            return;
        }

        dragging = false;
        dragged = true;
        carousel.classList.remove('is-dragging');

        // A flick carries on to the next; otherwise the nearest.
        const flick = Math.abs(velocity) > 0.35 ? -Math.sign(velocity) : 0;
        const base = Math.round(startPosition);
        const target =
            flick !== 0
                ? base + flick
                : Math.max(base - 1, Math.min(base + 1, Math.round(position)));

        moveTo(target);
    };

    track.addEventListener('pointerup', release);
    track.addEventListener('pointercancel', release);

    // A click after a drag is not a click; a click on a side card focuses it.
    track.addEventListener(
        'click',
        (event) => {
            if (dragged) {
                dragged = false;
                event.preventDefault();
                event.stopPropagation();

                return;
            }

            const slide = (event.target as Element).closest<HTMLElement>(
                '[data-review]',
            );
            const index = slide ? slides.indexOf(slide) : -1;

            if (index >= 0 && index !== active) {
                event.preventDefault();
                event.stopPropagation();
                go(Math.round(offsetOf(index)));
            }
        },
        true,
    );

    // ---- Keys, set-up. --------------------------------------------------

    carousel.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            go(event.key === 'ArrowLeft' ? -1 : 1);
        }
    });

    carousel.classList.add('is-ready');
    settle(0);
    measure();
    new ResizeObserver(measure).observe(track);
}

/** A CSS cubic-bezier() as a function of time (0–1) to progress (0–1). */
function cubicBezier(
    x1: number,
    y1: number,
    x2: number,
    y2: number,
): (t: number) => number {
    const at = (a: number, b: number, u: number): number =>
        3 * a * u * (1 - u) ** 2 + 3 * b * u ** 2 * (1 - u) + u ** 3;
    const slope = (a: number, b: number, u: number): number =>
        3 * a * (1 - u) ** 2 + 6 * (b - a) * u * (1 - u) + 3 * (1 - b) * u ** 2;

    return (t: number): number => {
        if (t <= 0 || t >= 1) {
            return Math.min(1, Math.max(0, t));
        }

        // The curve's parameter for this time (Newton's method, then bisection).
        let u = t;

        for (let i = 0; i < 8; i++) {
            const error = at(x1, x2, u) - t;
            const d = slope(x1, x2, u);

            if (Math.abs(error) < 1e-6) {
                return at(y1, y2, u);
            }

            if (Math.abs(d) < 1e-6) {
                break;
            }

            u -= error / d;
        }

        let low = 0;
        let high = 1;

        u = t;

        for (let i = 0; i < 30 && high - low > 1e-6; i++) {
            if (at(x1, x2, u) < t) {
                low = u;
            } else {
                high = u;
            }

            u = (low + high) / 2;
        }

        return at(y1, y2, u);
    };
}

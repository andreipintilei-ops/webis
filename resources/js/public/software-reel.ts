import { prefersReducedMotion } from '@/public/gsap';

/**
 * Software la comandă's modules as a carousel (public/software.blade.php,
 * "Ce construim"; css/site/software.css, .sw-reel), after the deck on
 * smilesoft.ro's hero: the current panel in front, its neighbours behind
 * it either side, smaller, fainter and turned a little, the whole deck
 * within the screen.
 *
 * - Dragged with the mouse or a finger, the panels follow the hand; let go
 *   past a fifth of a step, or with a flick, and it settles on the next
 *   one, else back.
 * - The arrows, the dots and the keyboard's arrow keys step through them,
 *   round from the last to the first; the counter follows ("03 / 06").
 *
 * Without the script the track is a row that scrolls sideways by itself,
 * snapping to each panel. Reduced motion: the same carousel, without the
 * gliding.
 */

/** How far along a step a release has to be, or how fast, to move on. */
const THRESHOLD = 0.2;
const FLICK = 0.5; // px per ms

export function initSoftwareReel(): void {
    const reel = document.querySelector<HTMLElement>('[data-sw-reel]');
    const stage = reel?.querySelector<HTMLElement>('[data-sw-reel-viewport]');
    const items = Array.from(
        reel?.querySelectorAll<HTMLElement>('[data-sw-reel-item]') ?? [],
    );

    if (!reel || !stage || items.length < 2) {
        return;
    }

    const current = reel.querySelector<HTMLElement>('[data-sw-reel-current]');
    const dots = Array.from(
        reel.querySelectorAll<HTMLButtonElement>('[data-sw-reel-dot]'),
    );
    const count = items.length;
    let active = 0;

    reel.classList.add('is-carousel');

    if (prefersReducedMotion()) {
        reel.classList.add('is-still');
    }

    /** The distance between two panels' centres: well under a panel's width,
     *  so the neighbours sit behind the current one. */
    const step = (): number => items[0].offsetWidth * 0.4;

    /** The panels' places, `drag` (in steps) shifting them all. */
    const place = (drag = 0): void => {
        const distance = step();

        items.forEach((item, i) => {
            // The shortest way round, so the last sits before the first.
            let offset = i - active;

            if (offset > count / 2) {
                offset -= count;
            } else if (offset < -count / 2) {
                offset += count;
            }

            const at = offset + drag;
            const away = Math.min(Math.abs(at), 2);

            item.style.transform = `translateX(${at * distance}px) scale(${1 - away * 0.16}) rotate(${at * 2}deg)`;
            item.style.opacity = String(away >= 1.6 ? 0 : 1 - away * 0.25);
            item.style.zIndex = String(10 - Math.round(away * 2));
            item.classList.toggle('is-current', offset === 0);
            item.setAttribute('aria-hidden', offset === 0 ? 'false' : 'true');
        });
    };

    const go = (index: number): void => {
        active = (index + count) % count;
        place();

        if (current) {
            current.textContent = String(active + 1).padStart(2, '0');
        }

        dots.forEach((dot, i) =>
            dot.setAttribute('aria-current', i === active ? 'true' : 'false'),
        );
    };

    /** The stage as tall as the tallest panel: they sit on top of each other. */
    const fit = (): void => {
        stage.style.height = `${Math.max(...items.map((item) => item.offsetHeight))}px`;
        place();
    };

    // ---- The controls ----------------------------------------------------------

    reel.querySelector('[data-sw-reel-prev]')?.addEventListener('click', () =>
        go(active - 1),
    );
    reel.querySelector('[data-sw-reel-next]')?.addEventListener('click', () =>
        go(active + 1),
    );
    dots.forEach((dot, i) => dot.addEventListener('click', () => go(i)));

    stage.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowRight' || event.key === 'ArrowLeft') {
            event.preventDefault();
            go(active + (event.key === 'ArrowRight' ? 1 : -1));
        }
    });

    // A click on a neighbour brings it forward.
    items.forEach((item, i) =>
        item.addEventListener('click', () => {
            if (i !== active && !moved) {
                go(i);
            }
        }),
    );

    // ---- Dragging --------------------------------------------------------------

    let startX = 0;
    let startY = 0;
    let lastX = 0;
    let lastTime = 0;
    let speed = 0;
    let dragging = false;
    let moved = false;

    stage.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) {
            return;
        }

        dragging = true;
        moved = false;
        startX = lastX = event.clientX;
        startY = event.clientY;
        lastTime = event.timeStamp;
        speed = 0;
    });

    stage.addEventListener('pointermove', (event) => {
        if (!dragging) {
            return;
        }

        const dx = event.clientX - startX;

        // A vertical swipe is the page's scroll, not the carousel's.
        if (!moved) {
            if (Math.abs(event.clientY - startY) > Math.abs(dx)) {
                dragging = false;

                return;
            }

            if (Math.abs(dx) < 6) {
                return;
            }

            moved = true;
            stage.setPointerCapture(event.pointerId);
            reel.classList.add('is-dragging');
        }

        const elapsed = Math.max(1, event.timeStamp - lastTime);
        speed = (event.clientX - lastX) / elapsed;
        lastX = event.clientX;
        lastTime = event.timeStamp;

        place(dx / step());
    });

    const release = (event: PointerEvent): void => {
        if (!dragging) {
            return;
        }

        dragging = false;
        reel.classList.remove('is-dragging');

        if (!moved) {
            return;
        }

        const along = (event.clientX - startX) / step();

        if (along < -THRESHOLD || speed < -FLICK) {
            go(active + 1);
        } else if (along > THRESHOLD || speed > FLICK) {
            go(active - 1);
        } else {
            place();
        }

        // The click that ends a drag is not a click on a neighbour.
        window.setTimeout(() => (moved = false), 0);
    };

    stage.addEventListener('pointerup', release);
    stage.addEventListener('pointercancel', release);

    // Refitted as the panels' size changes (not the stage's: fit sets it).
    new ResizeObserver(fit).observe(items[0]);
    fit();
    go(0);
}

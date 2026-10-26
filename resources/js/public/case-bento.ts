import { prefersReducedMotion } from '@/public/gsap';

/**
 * The case bento (components/section/case-bento), a carousel of projects,
 * one shown at a time:
 *
 * - The arrows step through them, round from the last to the first; the
 *   bars between them (one per project, its client's name under it) go
 *   straight to one. A swipe on a touch screen steps too.
 * - It plays on its own: the shown project's bar fills (css/site/
 *   sections.css, `is-playing`) and, full, the next one shows. Paused while
 *   pointed at, while focus is inside it and while it is out of view;
 *   stopped for good once someone steps through it themselves. Not at all
 *   with reduced motion.
 *
 * Changing project, the tiles stay where they are and only what is in them
 * changes: the old content lifts away and fades, quickly, then the new one
 * rises into place piece by piece (the number, the client, the line, the
 * link's label, each fact) while the screenshot settles in from a slight
 * zoom. The bars between the arrows do not move. With reduced motion the
 * swap is instant. Focus, when a control was used, moves to the same control
 * in the project now shown.
 */

/** How long each project shows while playing (the bar's fill). */
const DWELL_MS = 7000;

/** What changes inside the tiles, in the order it rises in. */
const CONTENT = [
    '.bento__no',
    '.bento__kicker',
    '.bento__client',
    '.bento__text',
    '.bento__link > span:first-child',
    '.bento__facts > div',
].join(', ');

const EASE_OUT = 'cubic-bezier(0.22, 1, 0.36, 1)';

/**
 * The old content away: lifted a little, faded, quickly. Not the screenshot:
 * faded, it would show the tile's pale ground for a moment — the new one
 * crossfades over it instead (enter).
 */
function leave(item: HTMLElement): Promise<unknown> {
    const parts = Array.from(item.querySelectorAll<HTMLElement>(CONTENT));

    return Promise.all(
        parts.map(
            (part, i) =>
                part.animate(
                    [
                        { opacity: 1, transform: 'none' },
                        { opacity: 0, transform: 'translateY(-10px)' },
                    ],
                    {
                        duration: 220,
                        delay: i * 15,
                        easing: 'cubic-bezier(0.4, 0, 1, 1)',
                        fill: 'forwards',
                    },
                ).finished,
        ),
    );
}

/**
 * The new content in: rising piece by piece; the screenshot settling from a
 * slight zoom over the old one, held under it as the tile's background until
 * it is fully in, so the tile never shows bare.
 */
function enter(item: HTMLElement, previous: HTMLImageElement | null): void {
    item.querySelectorAll<HTMLElement>(CONTENT).forEach((part, i) => {
        part.animate(
            [
                { opacity: 0, transform: 'translateY(18px)' },
                { opacity: 1, transform: 'none' },
            ],
            {
                duration: 650,
                delay: i * 55,
                easing: EASE_OUT,
                fill: 'backwards',
            },
        );
    });

    const media = item.querySelector<HTMLElement>('.bento__media');
    const image = media?.querySelector<HTMLImageElement>('img');

    if (!media || !image) {
        return;
    }

    const under = previous?.currentSrc || previous?.src;

    if (under) {
        media.style.backgroundImage = `url("${under}")`;
        media.style.backgroundSize = 'cover';
        media.style.backgroundPosition = 'center';
    }

    void image
        .animate(
            [
                { opacity: 0, transform: 'scale(1.06)' },
                { opacity: 1, transform: 'none' },
            ],
            { duration: 900, easing: EASE_OUT, fill: 'backwards' },
        )
        .finished.catch(() => undefined)
        .then(() => {
            media.style.backgroundImage = '';
        });
}

/** A project's screenshot loaded and decoded, so it is ready to show. */
async function ready(item: HTMLElement): Promise<void> {
    const image = item.querySelector<HTMLImageElement>('.bento__media img');

    if (!image) {
        return;
    }

    image.loading = 'eager';
    await image.decode().catch(() => undefined);
}

export function initCaseBentos(): void {
    document.querySelectorAll<HTMLElement>('[data-bento]').forEach((bento) => {
        const cases = Array.from(
            bento.querySelectorAll<HTMLElement>('[data-bento-case]'),
        );

        if (cases.length < 2) {
            return;
        }

        let current = 0;
        let changing = false;
        const still = prefersReducedMotion();

        const show = async (
            index: number,
            focus: string | null = null,
        ): Promise<void> => {
            const next = (index + cases.length) % cases.length;

            if (changing || next === current) {
                return;
            }

            changing = true;
            const from = cases[current];
            const previous =
                from.querySelector<HTMLImageElement>('.bento__media img');

            // The next screenshot ready while the old content leaves.
            await Promise.all([ready(cases[next]), still ? null : leave(from)]);

            current = next;
            cases.forEach((item, i) => {
                item.hidden = i !== current;
            });
            // The old content's end state off, for when it shows again.
            from.querySelectorAll<HTMLElement>('*').forEach((el) =>
                el.getAnimations().forEach((animation) => animation.cancel()),
            );

            if (!still) {
                enter(cases[current], previous);
            }

            if (focus) {
                cases[current].querySelector<HTMLElement>(focus)?.focus();
            }

            changing = false;
        };

        // ---- Playing on its own ------------------------------------------------

        const autoplay = !still;
        let pointed = false;
        let focused = false;
        let visible = false;

        const pause = (): void => {
            if (bento.classList.contains('is-playing')) {
                bento.classList.toggle(
                    'is-paused',
                    pointed || focused || !visible,
                );
            }
        };

        const stop = (): void => {
            bento.classList.remove('is-playing', 'is-paused');
        };

        if (autoplay) {
            bento.style.setProperty('--bento-dwell', `${DWELL_MS}ms`);
            bento.classList.add('is-playing');
            pause();

            // The lit bar full: the next project (its own bar starts empty).
            bento.addEventListener('animationend', (event) => {
                if (
                    bento.classList.contains('is-playing') &&
                    (event.target as Element).closest('.bento__bar')
                ) {
                    void show(current + 1);
                }
            });

            bento.addEventListener('pointerenter', () => {
                pointed = true;
                pause();
            });
            bento.addEventListener('pointerleave', () => {
                pointed = false;
                pause();
            });
            bento.addEventListener('focusin', () => {
                focused = true;
                pause();
            });
            bento.addEventListener('focusout', (event) => {
                focused = bento.contains(event.relatedTarget as Node | null);
                pause();
            });

            new IntersectionObserver(
                ([entry]) => {
                    visible = entry?.isIntersecting ?? false;
                    pause();
                },
                { threshold: 0.4 },
            ).observe(bento);
        }

        // ---- Stepping through it ----------------------------------------------

        bento.addEventListener('click', (event) => {
            const target = event.target as Element;
            const go = target.closest<HTMLElement>('[data-bento-go]');

            if (target.closest('[data-bento-next]')) {
                stop();
                void show(current + 1, '[data-bento-next]');
            } else if (target.closest('[data-bento-prev]')) {
                stop();
                void show(current - 1, '[data-bento-prev]');
            } else if (go) {
                stop();
                void show(
                    Number(go.dataset.bentoGo),
                    `[data-bento-go="${go.dataset.bentoGo}"]`,
                );
            }
        });

        // A horizontal swipe, on touch only (a mouse drag selects text).
        let startX: number | null = null;
        let startY = 0;

        bento.addEventListener('pointerdown', (event) => {
            if (event.pointerType !== 'mouse') {
                startX = event.clientX;
                startY = event.clientY;
            }
        });

        bento.addEventListener('pointerup', (event) => {
            if (startX === null) {
                return;
            }

            const dx = event.clientX - startX;
            const dy = event.clientY - startY;
            startX = null;

            if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.5) {
                stop();
                void show(current + (dx < 0 ? 1 : -1));
            }
        });
    });
}

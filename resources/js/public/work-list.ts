import { EASE, loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The list of every project (components/site/work-list; css/site/
 * work-list.css), after dennissnellenberg.com/work. With a mouse, from
 * 768px: as the pointer enters the list a preview grows in beside it with a
 * round "Vezi", both trailing the pointer (the preview a little behind the
 * label); pointing at a row slides its screenshot into the preview. They
 * shrink away as the pointer leaves the list.
 *
 * Both are moved to <body>: inside the smooth-scrolled content, position:
 * fixed would follow the content, not the screen. Reduced motion: they sit
 * on the pointer, without trailing.
 *
 * Grid or list ([data-work-view] buttons): the grid by default, the choice
 * kept in this browser; the preview follows the pointer in the list only.
 */
export function initWorkLists(): void {
    initViews();

    const mouse = window.matchMedia(
        '(hover: hover) and (pointer: fine) and (min-width: 768px)',
    );

    document
        .querySelectorAll<HTMLElement>('[data-work-list]')
        .forEach((list) => {
            const preview = list.querySelector<HTMLElement>(
                '[data-work-preview]',
            );
            const track = list.querySelector<HTMLElement>('[data-work-track]');
            const cursor =
                list.querySelector<HTMLElement>('[data-work-cursor]');

            if (!preview || !track || !cursor) {
                return;
            }

            document.body.append(preview, cursor);

            const still = prefersReducedMotion();
            const target = { x: 0, y: 0 };
            const at = { preview: { x: 0, y: 0 }, cursor: { x: 0, y: 0 } };
            let frame = 0;

            const place = (
                element: HTMLElement,
                x: number,
                y: number,
            ): void => {
                element.style.setProperty('--x', `${x}px`);
                element.style.setProperty('--y', `${y}px`);
            };

            // Trailing the pointer, each at its own pace, until it settles.
            const follow = (): void => {
                const lag = {
                    preview: still ? 1 : 0.12,
                    cursor: still ? 1 : 0.28,
                };

                for (const key of ['preview', 'cursor'] as const) {
                    at[key].x += (target.x - at[key].x) * lag[key];
                    at[key].y += (target.y - at[key].y) * lag[key];
                }

                place(preview, at.preview.x, at.preview.y);
                place(cursor, at.cursor.x, at.cursor.y);

                const moving =
                    Math.abs(target.x - at.preview.x) > 0.5 ||
                    Math.abs(target.y - at.preview.y) > 0.5;

                frame = moving ? requestAnimationFrame(follow) : 0;
            };

            const show = (visible: boolean): void => {
                for (const element of [preview, cursor]) {
                    element.style.setProperty('--show', visible ? '1' : '0');
                }
            };

            list.addEventListener('pointerenter', (event) => {
                if (!mouse.matches || list.dataset.view !== 'list') {
                    return;
                }

                // From where the pointer came in, not from the last place.
                target.x = at.preview.x = at.cursor.x = event.clientX;
                target.y = at.preview.y = at.cursor.y = event.clientY;
                place(preview, target.x, target.y);
                place(cursor, target.x, target.y);
                show(true);
            });

            list.addEventListener('pointermove', (event) => {
                target.x = event.clientX;
                target.y = event.clientY;

                if (!frame) {
                    frame = requestAnimationFrame(follow);
                }
            });

            list.addEventListener('pointerleave', () => show(false));

            list.querySelectorAll<HTMLElement>('[data-work-row]').forEach(
                (row) => {
                    row.addEventListener('pointerenter', () =>
                        track.style.setProperty(
                            '--i',
                            row.dataset.workRow ?? '0',
                        ),
                    );
                },
            );
        });
}

const VIEW_KEY = 'webis:work-view';

/** The grid/list switch: shows it, restores the choice, swaps the panes. */
function initViews(): void {
    const list = document.querySelector<HTMLElement>('[data-work-list]');
    // The switch sits in the page's head, beside the filter.
    const bar = document.querySelector<HTMLElement>('[data-work-views]');

    if (!list || !bar) {
        return;
    }

    const buttons = Array.from(
        bar.querySelectorAll<HTMLButtonElement>('[data-work-view]'),
    );
    const panes = Array.from(
        list.querySelectorAll<HTMLElement>('[data-work-pane]'),
    );

    const show = (view: string, animate: boolean): void => {
        list.dataset.view = view;

        for (const button of buttons) {
            button.setAttribute(
                'aria-pressed',
                String(button.dataset.workView === view),
            );
        }

        for (const pane of panes) {
            pane.hidden = pane.dataset.workPane !== view;
        }

        // Out of the list, the preview has nothing to follow.
        if (view !== 'list') {
            document
                .querySelectorAll<HTMLElement>(
                    '[data-work-preview], [data-work-cursor]',
                )
                .forEach((element) => element.style.setProperty('--show', '0'));
        }

        void import('gsap/ScrollTrigger').then(({ ScrollTrigger }) =>
            ScrollTrigger.refresh(),
        );

        if (!animate || prefersReducedMotion()) {
            return;
        }

        // The projects of the view shown rise in, one after another.
        const items = panes
            .find((pane) => !pane.hidden)
            ?.querySelectorAll<HTMLElement>('[data-work-type]:not([hidden])');

        if (items && items.length > 0) {
            void loadGsap().then((gsap) =>
                gsap.fromTo(
                    items,
                    { y: 24, autoAlpha: 0 },
                    {
                        y: 0,
                        autoAlpha: 1,
                        duration: 0.6,
                        stagger: 0.04,
                        ease: EASE.out,
                        clearProps: 'transform',
                    },
                ),
            );
        }
    };

    let saved: string | null = null;

    try {
        saved = window.localStorage.getItem(VIEW_KEY);
    } catch {
        // Storage blocked: the grid.
    }

    if (saved === 'list') {
        show('list', false);
    }

    bar.hidden = false;

    for (const button of buttons) {
        button.addEventListener('click', () => {
            const view = button.dataset.workView ?? 'grid';

            if (view === list.dataset.view) {
                return;
            }

            show(view, true);

            try {
                window.localStorage.setItem(VIEW_KEY, view);
            } catch {
                // Not kept: the grid next time.
            }
        });
    }
}

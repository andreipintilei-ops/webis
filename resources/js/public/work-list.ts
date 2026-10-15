import { prefersReducedMotion } from '@/public/gsap';

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
 * The type filter (the page's [data-work-filter] links) works in place:
 * the rows of other types hide, the address and the title follow
 * (?tip=magazin), and the page does not reload — nor run its transition.
 */
export function initWorkLists(): void {
    initFilter();

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
                if (!mouse.matches) {
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

/** The filter links: show one type's rows (or all), without leaving. */
function initFilter(): void {
    const links = Array.from(
        document.querySelectorAll<HTMLAnchorElement>('[data-work-filter]'),
    );
    const list = document.querySelector<HTMLElement>('[data-work-list]');

    if (links.length === 0 || !list) {
        return;
    }

    const rows = Array.from(
        list.querySelectorAll<HTMLElement>('[data-work-type]'),
    );
    const empty = list.querySelector<HTMLElement>('[data-work-empty]');
    // The page's own name, without a type before it.
    const title = document.title.replace(/^.+ · (?=Portofoliu)/, '');

    links.forEach((link) => {
        link.addEventListener('click', (event) => {
            // A new tab or window still opens the filtered address.
            if (
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey
            ) {
                return;
            }

            event.preventDefault();

            const type = link.dataset.workFilter ?? '';
            let shown = 0;

            for (const row of rows) {
                row.hidden = type !== '' && row.dataset.workType !== type;
                shown += row.hidden ? 0 : 1;
            }

            if (empty) {
                empty.hidden = shown > 0;
            }

            for (const other of links) {
                if (other === link) {
                    other.setAttribute('aria-current', 'page');
                } else {
                    other.removeAttribute('aria-current');
                }
            }

            const url = new URL(window.location.href);

            if (type === '') {
                url.searchParams.delete('tip');
            } else {
                url.searchParams.set('tip', type);
            }

            url.hash = '';
            window.history.replaceState(window.history.state, '', url);
            document.title =
                type === '' ? title : `${link.textContent?.trim()} · ${title}`;

            // The page below got shorter or longer: the scroll-linked parts
            // measure again.
            void import('gsap/ScrollTrigger').then(({ ScrollTrigger }) =>
                ScrollTrigger.refresh(),
            );
        });
    });
}

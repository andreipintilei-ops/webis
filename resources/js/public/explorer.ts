import { prefersReducedMotion } from '@/public/gsap';

/**
 * The explorers in the service chapters (components/site/explorer.blade.php;
 * css/site/explorer.css), in one of two modes, re-chosen when the width
 * crosses 1024px:
 *
 * - Tabs (from 1024px): vertical tabs. Hovering a row previews its type,
 *   leaving the list returns to the selected one; a click, Enter or Space
 *   selects; Up/Down move between rows (and select), Home/End go to the
 *   first/last. The stage shows the active panel: the outgoing one fades out
 *   upward, the incoming fades in from below, its metric counting up.
 * - Accordion (below): each row a button opening its panel in place, one at
 *   a time, the first open; the panel slides open (its content fading up)
 *   and shut, and the +/× icon turns with it, as the project accordion
 *   (js/public/project-accordion.ts). The open row closes on a second tap.
 *
 * Reduced motion: switching is instant, the metrics do not count.
 */

/** --base, --slow and --ease (css/site/menu-panel.css). */
const BASE_MS = 320;
const SLOW_MS = 520;
const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

export function initExplorers(): void {
    for (const explorer of document.querySelectorAll<HTMLElement>(
        '[data-explorer]',
    )) {
        setUp(explorer);
    }
}

function setUp(explorer: HTMLElement): void {
    const items = Array.from(explorer.children) as HTMLElement[];
    const rows = items.map((item) =>
        item.querySelector<HTMLButtonElement>('[data-explorer-row]')!,
    );
    const panels = items.map((item) =>
        item.querySelector<HTMLElement>('[data-explorer-panel]')!,
    );
    const still = prefersReducedMotion();
    const wide = matchMedia('(min-width: 1024px)');

    let selected = 0;
    let shown = 0;
    let open = 0;

    /** Tabs: show this item's panel (a preview or the selection). */
    const show = (index: number): void => {
        if (index === shown) {
            return;
        }

        const leaving = items[shown];

        leaving?.classList.remove('is-active');

        if (!still) {
            leaving?.classList.add('is-leaving');
            setTimeout(() => leaving?.classList.remove('is-leaving'), BASE_MS);
        }

        shown = index;
        items[index]?.classList.add('is-active');
        countUp(panels[index]);
    };

    const select = (index: number, focus = false): void => {
        selected = index;
        show(index);

        rows.forEach((row, i) => {
            row.setAttribute('aria-selected', String(i === index));
            row.tabIndex = i === index ? 0 : -1;
        });

        if (focus) {
            rows[index]?.focus();
        }
    };

    /** Accordion: slide a panel open or shut, from wherever it is now. */
    const slide = (panel: HTMLElement, on: boolean): void => {
        const from = panel.hidden ? 0 : panel.getBoundingClientRect().height;

        panel.getAnimations().forEach((animation) => animation.cancel());
        panel.hidden = false;

        if (still) {
            panel.hidden = !on;

            return;
        }

        const to = on ? panel.offsetHeight : 0;
        const motion = panel.animate(
            [{ height: `${from}px` }, { height: `${to}px` }],
            { duration: on ? SLOW_MS : SLOW_MS * 0.8, easing: EASE },
        );

        if (!on) {
            motion.onfinish = () => {
                panel.hidden = true;
            };

            return;
        }

        // The content fades up as the panel opens.
        const body = panel.firstElementChild;

        for (const [index, part] of Array.from(
            body?.children ?? [],
        ).entries()) {
            part.animate(
                [
                    { opacity: 0, transform: 'translateY(16px)' },
                    { opacity: 1, transform: 'none' },
                ],
                {
                    duration: SLOW_MS,
                    delay: 80 + index * 60,
                    easing: EASE,
                    fill: 'backwards',
                },
            );
        }
    };

    /**
     * Accordion: open this item (-1: none), closing the one open; at once
     * when the mode is set, sliding on a tap.
     */
    const toggle = (index: number, animate = false): void => {
        open = index;

        items.forEach((item, i) => {
            const on = i === index;
            const was = item.classList.contains('is-active');
            const panel = panels[i]!;

            item.classList.toggle('is-active', on);
            rows[i]?.setAttribute('aria-expanded', String(on));

            if (!animate) {
                panel.getAnimations().forEach((motion) => motion.cancel());
                panel.hidden = !on;
            } else if (was !== on) {
                slide(panel, on);
            }
        });

        countUp(panels[index]);
    };

    const toTabs = (): void => {
        explorer.classList.remove('is-accordion');
        explorer.classList.add('is-tabs');
        explorer.setAttribute('role', 'tablist');
        explorer.setAttribute('aria-orientation', 'vertical');

        items.forEach((item, i) => {
            item.setAttribute('role', 'presentation');
            rows[i]?.setAttribute('role', 'tab');
            rows[i]?.removeAttribute('aria-expanded');
            panels[i]?.setAttribute('role', 'tabpanel');
            panels[i]?.getAnimations().forEach((motion) => motion.cancel());
            panels[i]!.hidden = false;
            item.classList.toggle('is-active', i === selected);
        });

        shown = selected;
        select(selected);
    };

    const toAccordion = (): void => {
        explorer.classList.remove('is-tabs');
        explorer.classList.add('is-accordion');
        explorer.removeAttribute('role');
        explorer.removeAttribute('aria-orientation');

        items.forEach((item, i) => {
            item.removeAttribute('role');
            rows[i]?.removeAttribute('role');
            rows[i]?.removeAttribute('aria-selected');
            rows[i]?.removeAttribute('tabindex');
            panels[i]?.removeAttribute('role');
            item.classList.remove('is-leaving');
        });

        toggle(open);
    };

    const choose = (): void => (wide.matches ? toTabs() : toAccordion());

    rows.forEach((row, index) => {
        row.addEventListener('click', () => {
            if (wide.matches) {
                select(index);
            } else {
                toggle(index === open ? -1 : index, true);
            }
        });

        row.addEventListener('pointerenter', () => {
            if (wide.matches) {
                show(index);
            }
        });

        row.addEventListener('keydown', (event) => {
            if (!wide.matches) {
                return;
            }

            const last = rows.length - 1;
            const next =
                event.key === 'ArrowDown'
                    ? Math.min(last, index + 1)
                    : event.key === 'ArrowUp'
                      ? Math.max(0, index - 1)
                      : event.key === 'Home'
                        ? 0
                        : event.key === 'End'
                          ? last
                          : null;

            if (next !== null) {
                event.preventDefault();
                select(next, true);
            }
        });
    });

    // Leaving the list shows the selected type again.
    explorer.addEventListener('pointerleave', () => {
        if (wide.matches) {
            show(selected);
        }
    });

    wide.addEventListener('change', choose);
    choose();
}

/** The panel's metric, counted up from 0 to its value over --slow. */
function countUp(panel: HTMLElement | undefined): void {
    const number = panel?.querySelector<HTMLElement>('[data-count]');

    if (!number || prefersReducedMotion()) {
        return;
    }

    const text = (number.dataset.value ??= number.textContent ?? '');
    const match = /^([\d.]+)(.*)$/.exec(text);

    if (!match) {
        return;
    }

    const target = Number(match[1]!.replace(/\./g, ''));
    const suffix = match[2] ?? '';
    const start = performance.now();
    const format = (value: number): string =>
        value.toLocaleString('ro-RO').replace(/\s/g, '.') + suffix;

    const frame = (now: number): void => {
        const progress = Math.min(1, (now - start) / SLOW_MS);
        const eased = 1 - (1 - progress) ** 3;

        number.textContent = format(Math.round(target * eased));

        if (progress < 1) {
            requestAnimationFrame(frame);
        } else {
            number.textContent = text;
        }
    };

    requestAnimationFrame(frame);
}

import { prefersReducedMotion } from '@/public/gsap';

/**
 * The module explorer on Software la comandă (public/software.blade.php, 02;
 * css/site/software.css). Served as ARIA tabs — a tablist of six tabs, then
 * their six tabpanels — and run in one of two modes, re-chosen when the
 * width crosses 1024px:
 *
 * - Tabs (from 1024px): the list beside the stage. A click or keyboard
 *   focus selects a module; hovering one only previews it, and leaving the
 *   list shows the selected one again. Up/Down (and Left/Right) move to the
 *   previous or next tab, wrapping round, Home/End to the first or last —
 *   moving focus and selecting. The stage cross-fades over 200ms; at once
 *   with reduced motion.
 * - Accordion (below): each panel is moved under its own button, the roles
 *   dropped for aria-expanded, one open at a time — the first at the start
 *   — sliding open and shut (at once with reduced motion). A tap on the
 *   open one closes it.
 *
 * Without the script: the tabs as served, the first module showing.
 */

/** --slow and --ease (css/site/menu-panel.css). */
const SLOW_MS = 520;
const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

export function initSoftwareExplorer(): void {
    for (const explorer of document.querySelectorAll<HTMLElement>(
        '[data-sw-explorer]',
    )) {
        setUp(explorer);
    }
}

function setUp(explorer: HTMLElement): void {
    const list = explorer.querySelector<HTMLElement>('[data-sw-tablist]');
    const stage = explorer.querySelector<HTMLElement>('[data-sw-stage]');
    const tabs = Array.from(
        explorer.querySelectorAll<HTMLButtonElement>('[data-sw-tab]'),
    );
    const panels = Array.from(
        explorer.querySelectorAll<HTMLElement>('[data-sw-panel]'),
    );

    if (!list || !stage || tabs.length === 0 || tabs.length !== panels.length) {
        return;
    }

    const still = prefersReducedMotion();
    const wide = matchMedia('(min-width: 1024px)');
    const label = list.getAttribute('aria-label') ?? '';

    let selected = 0;
    let shown = 0;
    let open = 0;

    // ---- Tabs ---------------------------------------------------------------

    /** Show this module's panel: the selection, or a preview. */
    const show = (index: number): void => {
        shown = index;
        panels.forEach((panel, i) => panel.classList.toggle('is-shown', i === index));
        tabs.forEach((tab, i) =>
            tab.classList.toggle('is-preview', i === index && i !== selected),
        );
    };

    const select = (index: number, focus = false): void => {
        selected = index;

        tabs.forEach((tab, i) => {
            tab.setAttribute('aria-selected', String(i === index));
            tab.tabIndex = i === index ? 0 : -1;
        });

        show(index);

        if (focus) {
            tabs[index]?.focus();
        }
    };

    const toTabs = (): void => {
        explorer.classList.remove('is-accordion');
        explorer.classList.add('is-tabs');

        list.setAttribute('role', 'tablist');
        list.setAttribute('aria-orientation', 'vertical');

        if (label) {
            list.setAttribute('aria-label', label);
        }

        tabs.forEach((tab, i) => {
            const panel = panels[i]!;

            panel.getAnimations().forEach((motion) => motion.cancel());
            stage.append(panel);
            tab.setAttribute('role', 'tab');
            tab.removeAttribute('aria-expanded');
            panel.setAttribute('role', 'tabpanel');
            panel.setAttribute('tabindex', '0');
            // Every panel in the stage's one cell; the shown one visible
            // (css: .is-tabs .sw-panel.is-shown), so the cross-fade can run.
            panel.hidden = false;
        });

        select(selected);
    };

    // ---- Accordion ----------------------------------------------------------

    /** Slide a panel open or shut, from wherever it is now. */
    const slide = (panel: HTMLElement, on: boolean): void => {
        const from = panel.hidden ? 0 : panel.getBoundingClientRect().height;

        panel.getAnimations().forEach((motion) => motion.cancel());
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
        }
    };

    /** Open this module (-1: none), closing the open one. */
    const toggle = (index: number, animate = false): void => {
        open = index;

        tabs.forEach((tab, i) => {
            const on = i === index;
            const panel = panels[i]!;
            const was = !panel.hidden;

            tab.setAttribute('aria-expanded', String(on));

            if (!animate) {
                panel.getAnimations().forEach((motion) => motion.cancel());
                panel.hidden = !on;
            } else if (was !== on) {
                slide(panel, on);
            }
        });
    };

    const toAccordion = (): void => {
        explorer.classList.remove('is-tabs');
        explorer.classList.add('is-accordion');

        // A plain list of buttons, each followed by its panel.
        list.removeAttribute('role');
        list.removeAttribute('aria-orientation');
        list.removeAttribute('aria-label');

        tabs.forEach((tab, i) => {
            const panel = panels[i]!;

            tab.after(panel);
            tab.removeAttribute('role');
            tab.removeAttribute('aria-selected');
            tab.removeAttribute('tabindex');
            tab.classList.remove('is-preview');
            panel.setAttribute('role', 'region');
            panel.removeAttribute('tabindex');
            panel.classList.remove('is-shown');
        });

        toggle(open);
    };

    // ---- Input --------------------------------------------------------------

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            if (wide.matches) {
                select(index);
            } else {
                toggle(index === open ? -1 : index, true);
            }
        });

        // Keyboard focus selects; a mouse press selects on its click.
        tab.addEventListener('focus', () => {
            if (wide.matches && selected !== index) {
                select(index);
            }
        });

        tab.addEventListener('pointerenter', () => {
            if (wide.matches) {
                show(index);
            }
        });

        tab.addEventListener('keydown', (event) => {
            if (!wide.matches) {
                return;
            }

            const last = tabs.length - 1;
            const next =
                event.key === 'ArrowDown' || event.key === 'ArrowRight'
                    ? index === last
                        ? 0
                        : index + 1
                    : event.key === 'ArrowUp' || event.key === 'ArrowLeft'
                      ? index === 0
                          ? last
                          : index - 1
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

    // Leaving the list shows the selected module again.
    list.addEventListener('pointerleave', () => {
        if (wide.matches && shown !== selected) {
            show(selected);
        }
    });

    const choose = (): void => (wide.matches ? toTabs() : toAccordion());

    wide.addEventListener('change', choose);
    choose();
}

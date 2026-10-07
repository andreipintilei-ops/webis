import { prefersReducedMotion } from '@/public/gsap';

/**
 * The projects as rows that open (components/site/selected-projects, layout
 * "accordion"; css/site/project-accordion.css). Each is a native <details>;
 * this animates it: the case slides open (its height, its content fading
 * up) and shut, one open at a time, and the +/× icon turns with it — the
 * `is-open` class goes on as the opening starts and off as the closing
 * starts. With reduced motion the rows still open one at a time, at once.
 * Without the script they open and close as plain <details>.
 */

/** --slow and --ease (css/site/menu-panel.css). */
const DURATION = 520;
const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

export function initProjectAccordion(): void {
    const items = Array.from(
        document.querySelectorAll<HTMLDetailsElement>('[data-accordion-item]'),
    );

    if (items.length === 0) {
        return;
    }

    const still = prefersReducedMotion();
    const running = new Map<HTMLElement, Animation>();

    const caseOf = (item: HTMLDetailsElement): HTMLElement | null =>
        item.querySelector<HTMLElement>('[data-accordion-case]');

    /** Stop what this case was doing; its current height, mid-way or not. */
    const settle = (box: HTMLElement): number => {
        const height = box.getBoundingClientRect().height;

        running.get(box)?.cancel();
        running.delete(box);

        return height;
    };

    const open = (item: HTMLDetailsElement): void => {
        const box = caseOf(item);

        item.classList.add('is-open');

        if (still || !box) {
            item.open = true;

            return;
        }

        const from = item.open ? settle(box) : 0;

        item.open = true;

        const to = box.scrollHeight;
        const slide = box.animate(
            [{ height: `${from}px` }, { height: `${to}px` }],
            { duration: DURATION, easing: EASE },
        );

        running.set(box, slide);
        slide.onfinish = () => running.delete(box);

        // The content fades up as the case opens.
        for (const [index, part] of Array.from(box.children).entries()) {
            part.animate(
                [
                    { opacity: 0, transform: 'translateY(16px)' },
                    { opacity: 1, transform: 'none' },
                ],
                {
                    duration: DURATION,
                    delay: 80 + index * 80,
                    easing: EASE,
                    fill: 'backwards',
                },
            );
        }
    };

    const close = (item: HTMLDetailsElement): void => {
        const box = caseOf(item);

        item.classList.remove('is-open');

        if (still || !box) {
            item.open = false;

            return;
        }

        const from = settle(box);
        const slide = box.animate(
            [{ height: `${from}px` }, { height: '0px' }],
            { duration: DURATION * 0.8, easing: EASE },
        );

        running.set(box, slide);
        slide.onfinish = () => {
            running.delete(box);
            item.open = false;
        };
    };

    for (const item of items) {
        item.querySelector('summary')?.addEventListener('click', (event) => {
            event.preventDefault();

            if (item.classList.contains('is-open')) {
                close(item);

                return;
            }

            for (const other of items) {
                if (other !== item && other.classList.contains('is-open')) {
                    close(other);
                }
            }

            open(item);
        });
    }
}

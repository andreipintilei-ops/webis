/**
 * The slide-in navigation panel (components/site/panel.blade.php), after
 * dennissnellenberg.com. The motion is CSS (css/site/menu-panel.css), keyed
 * on `nav-open` on <html>; this script toggles it from whatever carries
 * data-menu-toggle="panel", closes on Escape and on the backdrop, locks the
 * page's scrolling and moves focus in and back out.
 */

import { lockScroll } from '@/public/site-nav';

export function initPanelMenu(): void {
    const root = document.documentElement;
    const panel = document.querySelector<HTMLElement>('[data-panel]');
    const backdrop = document.querySelector<HTMLElement>(
        '[data-panel-backdrop]',
    );
    const toggles = Array.from(
        document.querySelectorAll<HTMLElement>('[data-menu-toggle="panel"]'),
    );

    if (!panel || !backdrop || toggles.length === 0) {
        return;
    }

    let returnFocusTo: HTMLElement | null = null;

    panel.inert = true;

    const setOpen = (open: boolean): void => {
        root.classList.toggle('nav-open', open);
        panel.inert = !open;
        lockScroll(open);

        for (const toggle of toggles) {
            toggle.setAttribute('aria-expanded', String(open));
        }

        if (open) {
            panel
                .querySelector<HTMLElement>('a')
                ?.focus({ preventScroll: true });
        } else {
            returnFocusTo?.focus({ preventScroll: true });
        }
    };

    for (const toggle of toggles) {
        toggle.addEventListener('click', (event) => {
            event.preventDefault();
            const opening = !root.classList.contains('nav-open');

            if (opening) {
                returnFocusTo = toggle;
            }

            setOpen(opening);
        });
    }

    backdrop.addEventListener('click', (event) => {
        event.preventDefault();
        setOpen(false);
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && root.classList.contains('nav-open')) {
            setOpen(false);
        }
    });
}

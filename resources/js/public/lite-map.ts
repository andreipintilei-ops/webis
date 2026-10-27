/**
 * The contact page's lite map (components/site/contact.blade.php), as the
 * video block's lite embed (lite-video.ts): until "Arată harta" is clicked
 * the page holds only a panel with the address, a link to Google Maps. On
 * click the link gives way to Google's embedded map in the panel's frame —
 * so Google's script and its cookies load only for someone who asks for
 * the map. The connection is warmed up as the pointer comes over it.
 *
 * A click meant for a new tab (a modifier key, the middle button) is left
 * to the link. Without the script the link simply opens Google Maps.
 */
const ORIGIN = 'https://maps.google.com';

let warmed = false;

function warm(): void {
    if (warmed) {
        return;
    }

    warmed = true;

    const link = document.createElement('link');

    link.rel = 'preconnect';
    link.href = ORIGIN;
    document.head.append(link);
}

export function initLiteMaps(): void {
    for (const map of document.querySelectorAll<HTMLElement>(
        '[data-lite-map]',
    )) {
        const src = map.dataset.liteMap ?? '';
        const show = map.querySelector<HTMLAnchorElement>(
            '[data-lite-map-show]',
        );

        if (!src.startsWith(`${ORIGIN}/`) || !show) {
            continue;
        }

        show.addEventListener('pointerover', warm, { once: true });
        show.addEventListener('focus', warm, { once: true });

        show.addEventListener('click', (event) => {
            if (
                event.button !== 0 ||
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey
            ) {
                return;
            }

            event.preventDefault();

            const frame = document.createElement('iframe');

            frame.src = src;
            frame.title = map.dataset.title ?? '';
            frame.loading = 'lazy';
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            frame.className = 'contact-map__frame';

            map.replaceChildren(frame);
            map.classList.add('is-shown');
            frame.focus();
        });
    }
}

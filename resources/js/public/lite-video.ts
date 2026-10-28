/**
 * The video block's lite embed (blocks/video.blade.php): until the play
 * button is clicked the page holds only a poster and a link to the video
 * on YouTube. On click the link gives way to the youtube-nocookie.com
 * player, playing, in the poster's frame — so YouTube's ~800 KB of script
 * and its cookies load only for someone who wants to watch. The connection
 * is warmed up as the pointer comes over the button.
 *
 * A click meant for a new tab (a modifier key, the middle button) is left
 * to the link. Without the script the link simply opens YouTube.
 */
const ORIGIN = 'https://www.youtube-nocookie.com';

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

export function initLiteVideos(): void {
    for (const player of document.querySelectorAll<HTMLElement>(
        '[data-lite-video]',
    )) {
        const id = player.dataset.liteVideo ?? '';
        const play = player.querySelector<HTMLAnchorElement>(
            '[data-lite-video-play]',
        );

        if (!/^[\w-]{11}$/.test(id) || !play) {
            continue;
        }

        play.addEventListener('pointerover', warm, { once: true });
        play.addEventListener('focus', warm, { once: true });

        play.addEventListener('click', (event) => {
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

            frame.src = `${ORIGIN}/embed/${id}?autoplay=1&rel=0`;
            frame.title = player.dataset.title ?? '';
            frame.allow =
                'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share';
            frame.allowFullscreen = true;
            frame.referrerPolicy = 'strict-origin-when-cross-origin';
            frame.className = 'block-video__frame';

            player.replaceChildren(frame);
            player.classList.add('is-playing');
            frame.focus();
        });
    }
}

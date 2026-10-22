import { loadGsap, prefersReducedMotion } from '@/public/gsap';
import { getSmoother } from '@/public/smooth-scroll';

/**
 * The rich-text block's contents rail (blocks/rich-text.blade.php, 1024px
 * and up, three h2s or more):
 *
 * - The list stays in view while the text beside it scrolls past. CSS
 *   `position: sticky` cannot do this under ScrollSmoother (the page is
 *   moved by a transform), so it is pinned by ScrollTrigger, as the
 *   statement's label is (js/public/statement.ts). Without smooth scrolling,
 *   with reduced motion and below 1024px it is not held.
 * - Its links jump to their headings. Under ScrollSmoother a native jump
 *   lands wrong (the browser does not know where the transformed content
 *   is), so the smoother scrolls there itself and the address takes the
 *   heading's hash; otherwise the browser's own jump does, the heading
 *   clear of the top by its scroll-margin (css/site/block-rich-text.css).
 *   Each block looks its headings up in its own text, so two blocks with
 *   the same heading each go to their own.
 * - The link of the section being read — the last h2 above 40% of the
 *   screen — is marked (aria-current), by an IntersectionObserver, which
 *   sees the drawn positions with or without the smoother.
 */

/** The list's distance from the top of the screen while held, px. */
const PIN_TOP = 140;
/** Where a heading lands after a jump, px from the top. */
const JUMP_TOP = 112;

type Entry = { link: HTMLAnchorElement; heading: HTMLElement };

function entriesOf(block: HTMLElement): Entry[] {
    const text = block.querySelector<HTMLElement>('[data-toc-text]');

    if (!text) {
        return [];
    }

    return Array.from(
        block.querySelectorAll<HTMLAnchorElement>('[data-toc-link]'),
    ).flatMap((link) => {
        const id = decodeURIComponent(link.hash.slice(1));
        const heading = id
            ? text.querySelector<HTMLElement>(`#${CSS.escape(id)}`)
            : null;

        return heading ? [{ link, heading }] : [];
    });
}

function jumpOnClick({ link, heading }: Entry): void {
    link.addEventListener('click', (event) => {
        const smoother = getSmoother();

        if (!smoother || event.button !== 0 || event.metaKey || event.ctrlKey) {
            return;
        }

        event.preventDefault();
        smoother.scrollTo(heading, true, `top ${JUMP_TOP}px`);
        history.pushState(null, '', link.hash);
    });
}

function markWhileReading(entries: Entry[]): void {
    const mark = (): void => {
        const line = window.innerHeight * 0.4;
        let current: Entry | undefined;

        for (const entry of entries) {
            if (entry.heading.getBoundingClientRect().top <= line) {
                current = entry;
            }
        }

        for (const entry of entries) {
            if (entry === current) {
                entry.link.setAttribute('aria-current', 'true');
            } else {
                entry.link.removeAttribute('aria-current');
            }
        }
    };

    // Called as any heading crosses the 40% line, either way.
    const observer = new IntersectionObserver(mark, {
        rootMargin: '0px 0px -60% 0px',
    });

    for (const entry of entries) {
        observer.observe(entry.heading);
    }
}

export async function initRichTextTocs(): Promise<void> {
    const blocks = Array.from(
        document.querySelectorAll<HTMLElement>('[data-toc]'),
    );

    if (blocks.length === 0) {
        return;
    }

    for (const block of blocks) {
        const entries = entriesOf(block);

        entries.forEach(jumpOnClick);
        markWhileReading(entries);
    }

    if (prefersReducedMotion() || !getSmoother()) {
        return;
    }

    const [gsap, { ScrollTrigger }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
    ]);

    gsap.registerPlugin(ScrollTrigger);

    for (const block of blocks) {
        const list = block.querySelector<HTMLElement>('[data-toc-pin]');
        const text = block.querySelector<HTMLElement>('[data-toc-text]');

        if (!list || !text) {
            continue;
        }

        gsap.matchMedia().add('(min-width: 1024px)', () => {
            // Held from when it reaches PIN_TOP until the text beside it
            // ends level with the list's foot.
            ScrollTrigger.create({
                trigger: list,
                pin: true,
                pinSpacing: false,
                start: `top ${PIN_TOP}px`,
                endTrigger: text,
                end: () => `bottom ${PIN_TOP + list.offsetHeight}px`,
            });
        });
    }
}

import { closeTitlePills, openTitlePills } from '@/public/title-pill';
import { EASE, loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The sections' entry, after the hero and the service cards: each part plays
 * once, as it comes into view (its top at 85% of the screen), on the hero's
 * expo curve.
 *
 * - A head (`data-reveal="head"`: a section's label, title, intro, links):
 *   what stands before the title fades up, then the title rises line by line
 *   from under masks (SplitText, by words, held to the lines the browser
 *   gives the whole title; put back together once in),
 *   then what follows it fades up, and the title's chips open.
 * - A subsection (`.chapter-sub`): its label fades in and its hairline draws
 *   from the left (--rule), then its content rises.
 * - Any other part (`data-reveal`): its items (`data-reveal-child`) rise one
 *   after another — or, without items, the part itself.
 *
 * Inside `data-reveal-pace="fast"` (/clienti) everything plays four times as
 * fast — about 0.3s a part instead of 1.2s.
 *
 * Reduced motion: nothing moves, and nothing is hidden.
 */

const RISE = { duration: 1.2, ease: EASE.out };
const START = 'top 85%';

/** How fast a part plays: 4× inside `data-reveal-pace="fast"`. */
const pace = (part: HTMLElement): number =>
    part.closest('[data-reveal-pace="fast"]') ? 4 : 1;

/**
 * The line each of a (whole, unsplit) title's characters sits on, its
 * whitespace left out: 0 for the first line, 1 for the next…
 */
function linesOf(title: HTMLElement): number[] {
    const lines: number[] = [];
    const tops: number[] = [];
    const range = document.createRange();
    const walker = document.createTreeWalker(title, NodeFilter.SHOW_TEXT);

    for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const text = node.textContent ?? '';

        for (let i = 0; i < text.length; i++) {
            if (/\s/.test(text[i])) {
                continue;
            }

            range.setStart(node, i);
            range.setEnd(node, i + 1);

            const top = range.getBoundingClientRect().top;
            let line = tops.findIndex((t) => Math.abs(t - top) < 8);

            if (line === -1) {
                line = tops.push(top) - 1;
            }

            lines.push(line);
        }
    }

    return lines;
}

export async function initReveals(): Promise<void> {
    const parts = Array.from(
        document.querySelectorAll<HTMLElement>('[data-reveal]'),
    );

    if (parts.length === 0 || prefersReducedMotion()) {
        return;
    }

    const [gsap, { ScrollTrigger }, { SplitText }] = await Promise.all([
        loadGsap(),
        import('gsap/ScrollTrigger'),
        import('gsap/SplitText'),
    ]);

    gsap.registerPlugin(ScrollTrigger, SplitText);

    // The titles are split into lines in their real font.
    await document.fonts.ready;

    /** Play this (paused) timeline once, as the part comes into view. */
    const onEntry = (part: HTMLElement, tl: gsap.core.Timeline): void => {
        ScrollTrigger.create({
            trigger: part,
            start: START,
            once: true,
            onEnter: () => void tl.timeScale(pace(part)).play(),
        });
    };

    const head = (part: HTMLElement): void => {
        const title = part.querySelector<HTMLElement>('h1, h2');

        if (!title) {
            return;
        }

        // The head's other pieces, in order, around the title: its siblings,
        // and the siblings of whatever wraps it.
        const pieces = Array.from(
            part.querySelectorAll<HTMLElement>(
                ':scope > *, :scope > :has(h1, h2) > *',
            ),
        ).filter((el) => el !== title && !el.contains(title));
        const before = pieces.filter(
            (el) =>
                el.compareDocumentPosition(title) &
                Node.DOCUMENT_POSITION_FOLLOWING,
        );
        const after = pieces.filter((el) => !before.includes(el));

        // The title by lines, rising into place. Its lines are read off the
        // whole title first, as the browser balances it; then it is split
        // into words, each in its own mask, and the words are held to those
        // lines (a break before each line's first word, no wrapping between),
        // so putting it back together once in moves nothing. Left to wrap on
        // their own, the masked words can break a near-even title one word
        // differently from the whole one, and the line jumps as it reverts.
        // Measured and split again if the width changes before it plays.
        let rise: gsap.core.Timeline | null = null;
        let lineOf = linesOf(title);
        let played = false;

        const split: SplitText.Vars = {
            type: 'words',
            mask: 'words',
            wordsClass: 'reveal-word',
            onSplit: (self) => {
                const words = self.words as HTMLElement[];
                const lines: HTMLElement[][] = [];
                let at = 0;

                title.style.whiteSpace = 'nowrap';

                // Every word's top read at once, before any break goes in:
                // reading after each one would lay the whole page out again
                // per word. Held to one line (nowrap), a break moves the
                // words after it together, so whether two words share a line
                // — all this asks — is the same before the breaks as after.
                const masks = words.map((word) => word.parentElement ?? word);
                const tops = masks.map(
                    (mask) => mask.getBoundingClientRect().top,
                );

                words.forEach((word, index) => {
                    const line = lineOf[at] ?? Math.max(0, lines.length - 1);

                    at += (word.textContent ?? '').replace(/\s/g, '').length;

                    // A new line: break before it, unless a break typed in
                    // the title already puts it there.
                    if (
                        index > 0 &&
                        line >= lines.length &&
                        tops[index]! <= tops[index - 1]! + 4
                    ) {
                        masks[index]!.before(document.createElement('br'));
                    }

                    (lines[line] ??= []).push(word);
                });

                rise = gsap
                    .timeline({
                        paused: true,
                        onComplete: () => self.revert(),
                    })
                    .timeScale(pace(part));

                lines
                    .filter(Boolean)
                    .forEach((line, index) =>
                        rise?.from(
                            line,
                            { yPercent: 110, ...RISE },
                            index * 0.09,
                        ),
                    );

                return rise;
            },
            onRevert: () => {
                title.style.whiteSpace = '';
            },
        };
        const splitter = SplitText.create(title, split);

        // The screen's width, not the head's: held to its lines, the split
        // title sets its head's width itself where the head fits its content.
        const screenWidth = (): number => document.documentElement.clientWidth;
        let width = screenWidth();
        const resized = (): void => {
            if (played || !splitter.isSplit) {
                window.removeEventListener('resize', resized);
            } else if (screenWidth() !== width) {
                width = screenWidth();
                splitter.revert();
                lineOf = linesOf(title);
                splitter.split(split);
            }
        };

        window.addEventListener('resize', resized);

        closeTitlePills(title);

        const tl = gsap.timeline({ paused: true });

        if (before.length > 0) {
            tl.from(before, { y: 16, autoAlpha: 0, stagger: 0.06, ...RISE }, 0);
        }

        tl.call(
            () => {
                played = true;
                void rise?.play();
            },
            [],
            0.08,
        );

        if (after.length > 0) {
            tl.from(
                after,
                { y: 24, autoAlpha: 0, stagger: 0.08, ...RISE },
                0.3,
            );
        }

        tl.call(() => openTitlePills(title), [], 0.55);

        onEntry(part, tl);
    };

    const subsection = (part: HTMLElement): void => {
        const header = part.querySelector<HTMLElement>('.chapter-sub__header');
        const items = Array.from(
            part.querySelectorAll<HTMLElement>('[data-reveal-child]'),
        );
        const content = Array.from(part.children).filter(
            (el) => el !== header,
        ) as HTMLElement[];
        const tl = gsap.timeline({ paused: true });

        if (header) {
            tl.fromTo(
                header,
                { '--rule': 0 },
                { '--rule': 1, duration: 1.4, ease: EASE.inOut },
                0,
            ).from(
                header.children,
                { y: 10, autoAlpha: 0, stagger: 0.06, ...RISE },
                0.1,
            );
        }

        tl.from(
            items.length > 0 ? items : content,
            { y: 48, autoAlpha: 0, stagger: 0.09, ...RISE },
            0.25,
        );

        onEntry(part, tl);
    };

    const rest = (part: HTMLElement): void => {
        const items = Array.from(
            part.querySelectorAll<HTMLElement>('[data-reveal-child]'),
        );
        const tl = gsap.timeline({ paused: true });

        tl.from(items.length > 0 ? items : part, {
            y: 48,
            autoAlpha: 0,
            stagger: 0.09,
            ...RISE,
        });

        onEntry(part, tl);
    };

    const setUp = (part: HTMLElement): void => {
        if (part.dataset.reveal === 'head') {
            head(part);
        } else if (part.classList.contains('chapter-sub')) {
            subsection(part);
        } else {
            rest(part);
        }
    };

    // Each part set up as it comes within a screen and a half below the
    // screen, not all of them at load: splitting a title and measuring it
    // lays the page out, and a long page's worth at once held the first
    // seconds up. Those near the top on load are set up straight away (the
    // first report covers every part). One reached later that is already on
    // screen — a jump down the page — is left as it is rather than hidden
    // and played.
    let first = true;
    const near = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue;
                }

                near.unobserve(entry.target);

                const shown =
                    entry.boundingClientRect.top < window.innerHeight * 0.85;

                if (first || !shown) {
                    setUp(entry.target as HTMLElement);
                }
            }

            first = false;
        },
        { rootMargin: '0px 0px 150% 0px' },
    );

    parts.forEach((part) => near.observe(part));
}

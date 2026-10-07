import { closeTitlePills, openTitlePills } from '@/public/title-pill';
import { EASE, loadGsap, prefersReducedMotion } from '@/public/gsap';

/**
 * The sections' entry, after the hero and the service cards: each part plays
 * once, as it comes into view (its top at 85% of the screen), on the hero's
 * expo curve.
 *
 * - A head (`data-reveal="head"`: a section's label, title, intro, links):
 *   what stands before the title fades up, then the title rises line by line
 *   from under masks (SplitText, by words, so the browser keeps its own line
 *   breaks; put back together once in),
 *   then what follows it fades up, and the title's chips open.
 * - A subsection (`.chapter-sub`): its label fades in and its hairline draws
 *   from the left (--rule), then its content rises.
 * - Any other part (`data-reveal`): its items (`data-reveal-child`) rise one
 *   after another — or, without items, the part itself.
 *
 * Reduced motion: nothing moves, and nothing is hidden.
 */

const RISE = { duration: 1.2, ease: EASE.out };
const START = 'top 85%';

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
            onEnter: () => void tl.play(),
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

        // The title by lines, rising into place. Split into words, each in
        // its own mask, so the browser still lays the title out (balanced
        // lines, as when it is whole) — then the words are grouped by the
        // line they sit on, and each line rises together, one after another.
        // Re-split if the width changes before it has played; put back
        // together once in.
        let rise: gsap.core.Timeline | null = null;

        SplitText.create(title, {
            type: 'words',
            mask: 'words',
            wordsClass: 'reveal-word',
            autoSplit: true,
            onSplit: (self) => {
                const lines = new Map<number, HTMLElement[]>();

                for (const word of self.words as HTMLElement[]) {
                    const top = Math.round(
                        (word.parentElement ?? word).getBoundingClientRect()
                            .top,
                    );
                    const line =
                        [...lines.keys()].find((t) => Math.abs(t - top) < 8) ??
                        top;

                    lines.set(line, [...(lines.get(line) ?? []), word]);
                }

                rise = gsap.timeline({
                    paused: true,
                    onComplete: () => self.revert(),
                });

                [...lines.values()].forEach((words, index) =>
                    rise?.from(words, { yPercent: 110, ...RISE }, index * 0.09),
                );

                return rise;
            },
        });

        closeTitlePills(title);

        const tl = gsap.timeline({ paused: true });

        if (before.length > 0) {
            tl.from(before, { y: 16, autoAlpha: 0, stagger: 0.06, ...RISE }, 0);
        }

        tl.call(() => void rise?.play(), [], 0.08);

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

    for (const part of parts) {
        if (part.dataset.reveal === 'head') {
            head(part);
        } else if (part.classList.contains('chapter-sub')) {
            subsection(part);
        } else {
            rest(part);
        }
    }
}

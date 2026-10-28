import { prefersReducedMotion } from '@/public/gsap';

/**
 * The stats block's figures (blocks/stats, `data-count-up`) count up from 0
 * the first time they come into view — "1820", "7+", "78%", "2,5 %" — each
 * in its own form: its prefix and suffix kept, its decimals (comma) and its
 * thousands dots as written. A share's hairline (--share) fills with it.
 * Each figure keeps the width of its final text, so the row does not move
 * while it counts.
 *
 * The page carries the final figures: without the script, and with reduced
 * motion, they simply stand.
 */

const DURATION = 1600;

type Figure = {
    el: HTMLElement;
    item: HTMLElement | null;
    text: string;
    prefix: string;
    suffix: string;
    target: number;
    decimals: number;
    grouped: boolean;
    share: number | null;
};

export function initStatCounters(): void {
    const figures = Array.from(
        document.querySelectorAll<HTMLElement>('[data-count-up]'),
    )
        .map(parse)
        .filter((figure): figure is Figure => figure !== null);

    if (figures.length === 0 || prefersReducedMotion()) {
        return;
    }

    for (const figure of figures) {
        figure.el.style.minWidth = `${figure.el.getBoundingClientRect().width}px`;
        draw(figure, 0);
    }

    const observer = new IntersectionObserver(
        (entries) => {
            for (const entry of entries) {
                if (!entry.isIntersecting) {
                    continue;
                }

                observer.unobserve(entry.target);
                const figure = figures.find((f) => f.el === entry.target);

                if (figure) {
                    run(figure);
                }
            }
        },
        { threshold: 0.5 },
    );

    figures.forEach((figure) => observer.observe(figure.el));
}

function parse(el: HTMLElement): Figure | null {
    const text = (el.textContent ?? '').trim();
    const match = /^(\D*?)(\d[\d.,]*)(.*)$/.exec(text);

    if (!match) {
        return null;
    }

    const [, prefix = '', digits = '', suffix = ''] = match;
    // A comma marks decimals ("2,5"); dots group thousands ("1.200").
    const [whole = '', fraction = ''] = digits.split(',');
    const target = Number(`${whole.replace(/\./g, '')}.${fraction || '0'}`);
    const item = el.closest<HTMLElement>('.block-stats__item--share');

    return {
        el,
        item,
        text,
        prefix,
        suffix,
        target,
        decimals: fraction.length,
        grouped: whole.includes('.'),
        share: item ? Math.min(100, target) : null,
    };
}

function format(figure: Figure, value: number): string {
    const number = value.toLocaleString('ro-RO', {
        minimumFractionDigits: figure.decimals,
        maximumFractionDigits: figure.decimals,
        useGrouping: figure.grouped,
    });

    return figure.prefix + number.replace(/\s/g, '.') + figure.suffix;
}

function draw(figure: Figure, progress: number): void {
    figure.el.textContent = format(figure, figure.target * progress);

    if (figure.item && figure.share !== null) {
        figure.item.style.setProperty('--share', `${figure.share * progress}%`);
    }
}

function run(figure: Figure): void {
    const start = performance.now();

    const frame = (now: number): void => {
        const progress = Math.min(1, (now - start) / DURATION);

        draw(figure, 1 - (1 - progress) ** 3);

        if (progress < 1) {
            requestAnimationFrame(frame);
        } else {
            // The figure as written, exactly.
            figure.el.textContent = figure.text;
        }
    };

    requestAnimationFrame(frame);
}

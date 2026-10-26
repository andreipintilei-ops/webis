/**
 * The case bento (components/section/case-bento): the arrows on its panel
 * step through the projects, one shown at a time, round from the last to
 * the first. The newly shown one fades in (css/site/sections.css); focus
 * stays on the arrow pressed, now in the shown project.
 */
export function initCaseBentos(): void {
    document.querySelectorAll<HTMLElement>('[data-bento]').forEach((bento) => {
        const cases = Array.from(
            bento.querySelectorAll<HTMLElement>('[data-bento-case]'),
        );

        if (cases.length < 2) {
            return;
        }

        let current = 0;

        const show = (index: number, from: 'prev' | 'next'): void => {
            current = (index + cases.length) % cases.length;

            cases.forEach((item, i) => {
                item.hidden = i !== current;
            });

            cases[current]
                .querySelector<HTMLElement>(`[data-bento-${from}]`)
                ?.focus();
        };

        bento.addEventListener('click', (event) => {
            const target = event.target as Element;

            if (target.closest('[data-bento-next]')) {
                show(current + 1, 'next');
            } else if (target.closest('[data-bento-prev]')) {
                show(current - 1, 'prev');
            }
        });
    });
}

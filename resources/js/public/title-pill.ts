/**
 * The chips between the words of the titles
 * (components/ui/title-pill.blade.php; css/site/title-pill.css). The
 * sections' entry (js/public/reveals.ts) closes them before the titles come
 * in and opens them, from their middle outward, as the title settles.
 * Untouched — without the script, with reduced motion — they are open.
 */

/** Close the chips in this title (or element), until they are opened. */
export function closeTitlePills(root: ParentNode): void {
    root.querySelectorAll<HTMLElement>('[data-title-pill]').forEach((pill) => {
        pill.dataset.state = 'waiting';
    });
}

/** Open the chips in this title (or element). */
export function openTitlePills(root: ParentNode): void {
    root.querySelectorAll<HTMLElement>('[data-title-pill]').forEach((pill) => {
        pill.dataset.state = 'open';
    });
}

/**
 * The planes in an internal page's hero (views/partials/hero-planes.blade.php;
 * css/site/hero-planes.css) float only while they are on screen: off it,
 * their animation is paused, so it does not run under the rest of a long
 * page.
 */
export function initHeroPlanes(): void {
    const planes = document.querySelectorAll<HTMLElement>('[data-hero-planes]');

    if (planes.length === 0) {
        return;
    }

    const observer = new IntersectionObserver((entries) => {
        for (const entry of entries) {
            (entry.target as HTMLElement).toggleAttribute(
                'data-paused',
                !entry.isIntersecting,
            );
        }
    });

    planes.forEach((element) => observer.observe(element));
}

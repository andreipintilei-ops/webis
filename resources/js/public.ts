import type { Component } from 'vue';
import { initContactForm } from '@/public/contact-form';
import { initHero } from '@/public/hero-intro';
import { initPageTransitions } from '@/public/page-transition';
import { initPanelMenu } from '@/public/panel-menu';
import { initProjectShowcase } from '@/public/project-showcase';
import { initSiteNav } from '@/public/site-nav';
import { initSmoothScroll } from '@/public/smooth-scroll';
import { initServices } from '@/public/services';
import { initServicesPlain } from '@/public/services-plain';
import { initStatements } from '@/public/statement';
import { initTheodoreMenu } from '@/public/theodore-menu';

/**
 * Entry for the public (Blade-rendered) side of the site.
 *
 * Public pages are server-rendered Blade so that crawlers get complete HTML.
 * This file adds behaviour: plain enhancements (the menu) and Vue "islands"
 * for anything genuinely interactive. There is deliberately no Inertia runtime
 * here — never import `@inertiajs/*` from an island.
 *
 * Vue itself is only downloaded when a page contains an island, so pages
 * without one ship just this small script.
 *
 * Island usage from Blade:
 *   <div data-vue="QuoteForm" data-props='@json(['service' => $slug])'></div>
 *
 * Islands (QuoteForm, PortfolioFilter, ConsentBanner) are added in Phase 3,
 * along with mounting below-the-fold islands only once visible.
 */
const registry: Record<string, () => Promise<{ default: Component }>> = {};

function parseProps(el: HTMLElement): Record<string, unknown> {
    const raw = el.dataset.props;

    if (!raw) {
        return {};
    }

    try {
        return JSON.parse(raw) as Record<string, unknown>;
    } catch (error) {
        console.error(
            `[islands] Invalid data-props JSON on "${el.dataset.vue}" island.`,
            error,
        );

        return {};
    }
}

async function mountIsland(el: HTMLElement): Promise<void> {
    const name = el.dataset.vue;

    if (!name) {
        return;
    }

    const loader = registry[name];

    if (!loader) {
        console.warn(`[islands] No component registered under "${name}".`);

        return;
    }

    const [{ createApp }, { default: component }] = await Promise.all([
        import('vue'),
        loader(),
    ]);

    createApp(component, parseProps(el)).mount(el);
}

function boot(): void {
    // Each does nothing on pages without its markup. The hero's scroll-linked
    // leaving and the statements (which pin differently with and without
    // smooth scrolling) wait for smooth scrolling to be set up.
    const smooth = initSmoothScroll();

    void initHero(smooth);
    initPageTransitions();
    initSiteNav();
    initPanelMenu();
    initTheodoreMenu();
    initContactForm();
    void smooth.then(() => {
        void initStatements();
        void initServices();
        void initServicesPlain();
        void initProjectShowcase();
    });

    // The WebGL gradient ships as its own chunk, fetched only where it is used.
    if (document.querySelector('canvas[data-soffit]')) {
        void import('@/public/soffit').then(({ initSoffit }) => initSoffit());
    }

    document.querySelectorAll<HTMLElement>('[data-vue]').forEach((el) => {
        void mountIsland(el);
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}

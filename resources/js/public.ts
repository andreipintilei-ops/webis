import type { Component } from 'vue';
import { initAnchorScroll } from '@/public/anchor-scroll';
import { initCaseBentos } from '@/public/case-bento';
import { initContactForm } from '@/public/contact-form';
import { initCookieConsent } from '@/public/cookie-consent';
import { initExplorers } from '@/public/explorer';
import { initReveals } from '@/public/reveals';
import { initLogoWalls } from '@/public/logo-wall';
import { initReviewsCarousels } from '@/public/reviews-carousel';
import { initHero } from '@/public/hero-intro';
import { initHeroPlanes } from '@/public/hero-planes';
import { initLiteVideos } from '@/public/lite-video';
import { initWorkLists } from '@/public/work-list';
import { initPageTransitions } from '@/public/page-transition';
import { initPanelMenu } from '@/public/panel-menu';
import { initProjectAccordion } from '@/public/project-accordion';
import { initProductStack } from '@/public/product-stack';
import { initProjectShowcase } from '@/public/project-showcase';
import { initProjectSteps } from '@/public/project-steps';
import { initRichTextTocs } from '@/public/rich-text-toc';
import { initSiteNav } from '@/public/site-nav';
import { initSmoothScroll } from '@/public/smooth-scroll';
import { initSoftwareReel } from '@/public/software-reel';
import { initSoftwareTimeline } from '@/public/software-timeline';
import { initServices } from '@/public/services';
import { initServiceCards } from '@/public/service-cards';
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
    initCookieConsent();
    initContactForm();
    initLiteVideos();
    initExplorers();
    initAnchorScroll(smooth);
    initReviewsCarousels();
    initServicesPlain();
    initHeroPlanes();
    initWorkLists();
    initProjectAccordion();
    initCaseBentos();
    void smooth.then(() => {
        void initStatements();
        void initRichTextTocs();
        void initServices();
        void initServiceCards();
        void initReveals();
        void initProjectShowcase();
        void initProjectSteps();
        void initProductStack();
        void initSoftwareTimeline();
        initSoftwareReel();
        initLogoWalls();

        // The stage's 3D object (Three.js), its own chunk: fetched and built
        // once the stage is within two screens of coming into view — it
        // opens below the hero and the service cards — not at load, where
        // it held up the first screen.
        const stage = document.querySelector('[data-stage]');

        if (stage && document.querySelector('[data-shapeshifter]')) {
            const near = new IntersectionObserver(
                ([entry]) => {
                    if (!entry?.isIntersecting) {
                        return;
                    }

                    near.disconnect();
                    void import('@/public/shapeshifter').then(
                        ({ initShapeshifter }) => initShapeshifter(),
                    );
                },
                { rootMargin: '200% 0px' },
            );

            near.observe(stage);
        }

        // The benefit rows drifting apart (/web).
        if (document.querySelector('[data-brows], [data-bcols]')) {
            void import('@/public/benefit-rows').then(({ initBenefitRows }) =>
                initBenefitRows(),
            );
        }

        // The stats block's figures counting up (blocks/stats).
        if (document.querySelector('[data-count-up]')) {
            void import('@/public/stat-counters').then(({ initStatCounters }) =>
                initStatCounters(),
            );
        }

        // Split sections' heads holding beside their content (/web).
        if (document.querySelector('[data-split]')) {
            void import('@/public/split-heads').then(({ initSplitHeads }) =>
                initSplitHeads(),
            );
        }

        // The stage's panel opening out to full width (the home page).
        if (document.querySelector('.stage [data-stage-finale]')) {
            void import('@/public/stage-finale-grow').then(
                ({ initStageFinaleGrow }) => initStageFinaleGrow(),
            );
        }

        // A closing panel's own W, where there is no stage (/portofoliu, the
        // footer's call on most pages). Three.js and the scene are heavy and
        // it sits at the page's foot: fetched and built only once it is
        // within a screen and a half of coming into view, not at load.
        const finaleW = document.querySelectorAll('[data-finale-w]');

        if (finaleW.length > 0) {
            const near = new IntersectionObserver(
                (entries) => {
                    if (!entries.some((entry) => entry.isIntersecting)) {
                        return;
                    }

                    near.disconnect();
                    void import('@/public/finale-w').then(({ initFinaleW }) =>
                        initFinaleW(),
                    );
                },
                { rootMargin: '150% 0px' },
            );

            finaleW.forEach((panel) => near.observe(panel));
        }

        // The services story (/poveste): Three.js, its own chunks, only there.
        if (document.querySelector('[data-story]')) {
            void import('@/public/services-story').then(
                ({ initServicesStory }) => initServicesStory(),
            );
        }
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

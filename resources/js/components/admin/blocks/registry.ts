import type { Component } from 'vue';
import { defineAsyncComponent } from 'vue';

/** Editor component per block type key (App\Blocks\*Block::type()). */
export const blockEditors: Record<string, Component> = {
    hero: defineAsyncComponent(() => import('./editors/HeroEditor.vue')),
    statement: defineAsyncComponent(
        () => import('./editors/StatementEditor.vue'),
    ),
    rich_text: defineAsyncComponent(
        () => import('./editors/RichTextBlockEditor.vue'),
    ),
    services_grid: defineAsyncComponent(
        () => import('./editors/ServicesGridEditor.vue'),
    ),
    features: defineAsyncComponent(
        () => import('./editors/FeaturesEditor.vue'),
    ),
    portfolio_showcase: defineAsyncComponent(
        () => import('./editors/PortfolioShowcaseEditor.vue'),
    ),
    client_logos: defineAsyncComponent(
        () => import('./editors/ClientLogosEditor.vue'),
    ),
    notable_clients: defineAsyncComponent(
        () => import('./editors/NotableClientsEditor.vue'),
    ),
    testimonials: defineAsyncComponent(
        () => import('./editors/TestimonialsEditor.vue'),
    ),
    stats: defineAsyncComponent(() => import('./editors/StatsEditor.vue')),
    process_steps: defineAsyncComponent(
        () => import('./editors/ProcessStepsEditor.vue'),
    ),
    pricing_tiers: defineAsyncComponent(
        () => import('./editors/PricingTiersEditor.vue'),
    ),
    faq: defineAsyncComponent(() => import('./editors/FaqEditor.vue')),
    image_text: defineAsyncComponent(
        () => import('./editors/ImageTextEditor.vue'),
    ),
    gallery: defineAsyncComponent(() => import('./editors/GalleryEditor.vue')),
    video: defineAsyncComponent(() => import('./editors/VideoEditor.vue')),
    blog_teaser: defineAsyncComponent(
        () => import('./editors/BlogTeaserEditor.vue'),
    ),
    cta_banner: defineAsyncComponent(
        () => import('./editors/CtaBannerEditor.vue'),
    ),
    quote_form: defineAsyncComponent(
        () => import('./editors/QuoteFormEditor.vue'),
    ),
};

/** One-line Romanian description per type, shown in the "add block" menu. */
export const blockDescriptions: Record<string, string> = {
    hero: 'Titlul paginii (h1), text scurt și butoane',
    statement: 'Etichetă în stânga, text mare în dreapta, paragraf și link',
    rich_text: 'Text formatat: paragrafe, subtitluri, liste, linkuri',
    services_grid: 'Carduri cu paginile de servicii sau de industrii',
    features: 'Avantaje sau beneficii, cu iconiță, titlu și text',
    portfolio_showcase: 'Proiecte din portofoliu, recomandate sau alese',
    client_logos: 'Logo-urile clienților, toți sau aleși',
    notable_clients: 'Clienți importanți, de exemplu instituții publice',
    testimonials: 'Păreri ale clienților',
    stats: 'Câteva cifre-cheie, cu etichetă',
    process_steps: 'Etapele de lucru, numerotate',
    pricing_tiers: 'Pachete cu preț, ce includ și buton',
    faq: 'Întrebări și răspunsuri (apar și în Google)',
    image_text: 'O imagine alături de text și un buton opțional',
    gallery: 'O grilă de imagini cu legende',
    video: 'Un videoclip de pe YouTube, cu titlu și descriere',
    blog_teaser: 'Cele mai noi articole, dintr-o categorie sau alese',
    cta_banner: 'Bandă cu îndemn și buton (ex. „Cere o ofertă”)',
    quote_form: 'Formularul de cerere ofertă',
};

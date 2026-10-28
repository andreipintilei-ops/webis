{{-- A CMS page at the site root, built from its blocks. --}}
@php
    // The illustrated service cards: the projects follow them (below).
    $serviceCards = collect($page->blocks ?? [])->contains(
        fn ($block) => ($block['type'] ?? null) === 'features' && ($block['data']['layout'] ?? null) === 'cards',
    );

    // An article opens on the internal pages' gradient hero (no planes), its
    // title in the text face rather than the display one: some run to a
    // dozen words. Its category and date above; its own hero block, the
    // title again, is left out of the blocks.
    $article = $page->type === \App\Enums\PageType::Article;
    $blocks = $page->blocks ?? [];

    if ($article) {
        $first = $blocks[0] ?? null;
        $heading = is_array($first) && ($first['type'] ?? null) === \App\Blocks\HeroBlock::type()
            ? (string) ($first['data']['heading'] ?? '')
            : '';
        $blocks = $heading !== '' ? array_slice($blocks, 1) : $blocks;
        $label = collect([
            $page->category?->name,
            $page->published_at?->locale('ro')->translatedFormat('j F Y'),
        ])->filter()->implode(' · ');
    }

    // An article's cover (Support\Blog\Cover): in its hero, and its share
    // image (1200×630).
    $cover = $article ? \App\Support\Blog\Cover::of($page) : null;

    // Sharing: the page's own share image, else the cover's, else its
    // hero image's; else the layout's default.
    $shareImage = $page->seo->ogImageAssetId
        ? \App\Support\Seo\StructuredData::image(\App\Models\Asset::with('media')->find($page->seo->ogImageAssetId))
        : ($cover?->shareImage() ?? \App\Support\Seo\StructuredData::image(\App\Models\Asset::with('media')->find($page->hero_asset_id)));
    $schema = $article
        ? [app(\App\Support\Seo\StructuredData::class)->blogPosting($page, $heading ?: $page->title, \App\Support\PublicUrls::canonical($page), $shareImage['url'] ?? null)]
        : [];

    // An industry page shows its breadcrumbs (Acasă › Site-uri tematice ›
    // the page) above its hero's title: the trail its BreadcrumbList gives
    // (the layout), as paths.
    $crumbs = $page->type === \App\Enums\PageType::Industry
        ? array_map(
            fn (array $crumb): array => [$crumb[0], parse_url($crumb[1], PHP_URL_PATH) ?: '/'],
            \App\Support\Seo\StructuredData::breadcrumbs(request()->getPathInfo(), $page->title),
        )
        : null;
@endphp
<x-layouts.public
    :title="$page->seo->title ?: $page->title"
    :description="$page->seo->description ?: $page->excerpt"
    :canonical="$page->seo->canonical"
    :noindex="$page->seo->noindex"
    :og-image="$shareImage"
    :og-type="$article ? 'article' : 'website'"
    :local-business="$page->type === \App\Enums\PageType::Contact"
    :crumb="$article ? ($heading ?: $page->title) : $page->title"
    :crumb-parent="$article ? ['Blog', '/blog/'] : null"
    :schema="$schema"
    :header-theme="$article ? 'dark' : \App\Blocks\HeroBlock::headerThemeFor($page->blocks)"
    :footer-cta="! \App\Blocks\QuoteFormBlock::appearsIn($page->blocks)"
    :footer-cta-key="$article ? 'blog' : null"
>
    @if ($article)
        <x-site.page-hero
            :label="$label ?: 'Blog'"
            :title="$heading ?: $page->title"
            :planes="false"
            :class="$cover ? 'page-hero--article page-hero--cover' : 'page-hero--article'"
        >
            {{-- The cover beside the title from 1024px, under it below;
                 the page's main image, so not lazy. --}}
            @if ($cover)
                <x-slot:after>
                    <figure @class(['article-cover', 'is-cutout' => $cover->cutout])>
                        <img src="{{ $cover->src }}" srcset="{{ $cover->srcset }}" sizes="(min-width: 1024px) 40vw, 92vw" width="{{ $cover->width }}" height="{{ $cover->height }}" alt="{{ $cover->alt }}" fetchpriority="high" decoding="async">
                    </figure>
                </x-slot:after>
            @endif
        </x-site.page-hero>
    @endif

    <x-blocks :blocks="$blocks" :crumbs="$crumbs" />

    {{-- After the illustrated service cards: the projects,
         on the same lavender as the cards. --}}
    @if ($serviceCards)
        <x-site.selected-projects layout="accordion" />
        {{-- Trial, to compare: the same projects as a walkthrough. --}}
        <x-site.selected-projects layout="steps" />
        {{-- Our products, as full-screen cards stacking on scroll. --}}
        <x-site.own-products layout="stack" />
    @endif

    {{-- Software la comandă and Web și e-commerce: up to three client
         projects of their kind (nothing on other pages, never a product). --}}
    <x-site.related-projects :types="\App\Enums\ProjectType::forServicePage($page->slug)" />
</x-layouts.public>

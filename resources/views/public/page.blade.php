{{-- A CMS page at the site root, built from its blocks. --}}
@php
    // The illustrated service cards rise as a sheet over the hero's
    // background, so that background stays full width as they come (as on
    // /clienti) rather than drawing in to a card.
    $serviceCards = collect($page->blocks ?? [])->contains(
        fn ($block) => ($block['type'] ?? null) === 'features' && ($block['data']['layout'] ?? null) === 'cards',
    );
    $heroLeave = $serviceCards ? 'overlap' : null;

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
@endphp
<x-layouts.public
    :title="$page->seo->title ?: $page->title"
    :canonical="$page->seo->canonical"
    :noindex="$page->seo->noindex"
    :header-theme="$article ? 'dark' : \App\Blocks\HeroBlock::headerThemeFor($page->blocks)"
    :hero-leave="$heroLeave"
    :footer-cta="! \App\Blocks\QuoteFormBlock::appearsIn($page->blocks)"
>
    @if ($article)
        <x-site.page-hero
            :label="$label ?: 'Blog'"
            :title="$heading ?: $page->title"
            :planes="false"
            class="page-hero--article"
        />
    @endif

    <x-blocks :blocks="$blocks" />

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

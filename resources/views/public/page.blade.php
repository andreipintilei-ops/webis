{{-- A CMS page at the site root, built from its blocks. --}}
@php
    // The illustrated service cards rise as a sheet over the hero's
    // background, so that background stays full width as they come (as on
    // /clienti) rather than drawing in to a card.
    $serviceCards = collect($page->blocks ?? [])->contains(
        fn ($block) => ($block['type'] ?? null) === 'features' && ($block['data']['layout'] ?? null) === 'cards',
    );
    $heroLeave = $serviceCards ? 'overlap' : null;
@endphp
<x-layouts.public
    :title="$page->seo->title ?: $page->title"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page->blocks)"
    :hero-leave="$heroLeave"
>
    <x-blocks :blocks="$page->blocks ?? []" />

    {{-- After the illustrated service cards (for now /despre): the projects,
         on the same lavender as the cards. --}}
    @if ($serviceCards)
        <x-site.selected-projects layout="accordion" />
        {{-- Trial, to compare: the same projects as a walkthrough. --}}
        <x-site.selected-projects layout="steps" />
        {{-- Our products, as full-screen cards stacking on scroll. --}}
        <x-site.own-products layout="stack" />
    @endif
</x-layouts.public>

{{--
    The home page, built from its blocks alone: the hero on the violet
    gradient, "Despre noi" on it, then the illustrated service cards — which
    rise as a sheet over the hero's background, so that background stays
    full width as they come (hero-leave "overlap").
--}}
<x-layouts.public
    :title="$page?->seo->title ?: $page?->title"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page?->blocks)"
    hero-leave="overlap"
>
    <x-blocks :blocks="$page->blocks ?? []" />

    {{-- The three services in depth, as chapters, then the reviews and the latest articles. --}}
    <x-site.service-chapters />
    <x-site.reviews />
    <x-site.blog-posts />
</x-layouts.public>

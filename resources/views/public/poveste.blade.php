{{-- /poveste — a design trial kept for later, on a copy of the home page
     (HomeController@poveste): the three services as one pinned story around
     one Webis object (components/site/services-story;
     js/public/services-story.ts; css/site/variant-poveste.css). It was
     /clienti's trial until that page got its own content. --}}
<x-layouts.public
    title="Poveste"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page?->blocks)"
    :lower-tone="config('site.lower_tone')"
    variant="poveste"
    hero-leave="overlap"
    :footer-cta="! \App\Blocks\QuoteFormBlock::appearsIn($page?->blocks)"
>
    <x-blocks :blocks="$page->blocks ?? []" />
    <x-site.services-story />
    <x-site.reviews />
    <x-site.blog-posts />
    <x-site.contact />
</x-layouts.public>

{{--
    The home page, built from its blocks and then "The Shapeshifter": the
    three services as one stage around one Webis object of five thin matte
    plates that change both their pose and their outline per chapter, then
    gather into a W on a purple panel (components/site/stage.blade.php;
    js/public/shapeshifter.ts; css/site/variant-stage.css).

    The stage's styling is scoped to data-variant="stage" rather than written
    as the site's default, so the pages that share its classes — /clienti and
    its own trial — are left alone.
--}}
<x-layouts.public
    :title="$page?->seo->title ?: $page?->title"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page?->blocks)"
    :lower-tone="config('site.lower_tone')"
    variant="stage"
    hero-leave="overlap"
>
    <x-blocks :blocks="$page->blocks ?? []" />
    <x-site.stage />
    <x-site.reviews />
    <x-site.blog-posts>
        <x-slot:title>Ultimele <span class="whitespace-nowrap">articole <x-ui.title-pill variant="blog" /></span></x-slot:title>
    </x-site.blog-posts>

    {{-- The object: drawn between the sections' backgrounds and their text.
         Last, so the hero's leave still meets the chapters right after it. --}}
    <div class="shapeshifter" aria-hidden="true" data-shapeshifter></div>
</x-layouts.public>

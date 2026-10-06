{{-- The home page, built from its blocks. --}}
<x-layouts.public
    :title="$page?->seo->title ?: $page?->title"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page?->blocks)"
    :lower-tone="config('site.lower_tone')"
>
    <x-blocks :blocks="$page->blocks ?? []" features-layout="services" />
    {{-- After the CMS blocks (hero, Despre noi, Ce dezvoltăm): the rest of the
         home page outline (v2) — Proiecte, Produsele noastre, Cum lucrăm,
         Contact. --}}
    <x-site.selected-projects layout="showcase" />
    <x-site.own-products />
    <x-site.how-we-work />
    <x-site.contact />
</x-layouts.public>

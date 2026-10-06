{{-- /clienti — for now a copy of the home page (HomeController@clienti), to try
     design choices. Change this template or css/site/variant-clienti.css;
     the home page is untouched. --}}
<x-layouts.public
    title="Clienți"
    :header-theme="\App\Blocks\HeroBlock::headerThemeFor($page?->blocks)"
    :lower-tone="config('site.lower_tone')"
    variant="clienti"
    hero-leave="overlap"
>
    {{-- Ce dezvoltăm here: a title with gathering points, then a minimal
         grid (blocks/partials/services-plain, css/site/services-plain.css). --}}
    <x-blocks :blocks="$page->blocks ?? []" features-layout="services-plain" />
    {{-- After the CMS blocks (hero, Despre noi, Ce dezvoltăm): the rest of the
         home page outline (v2) — Proiecte, Produsele noastre, Cum lucrăm,
         Contact. --}}
    <x-site.selected-projects />
    <x-site.own-products />
    <x-site.how-we-work />
    <x-site.contact />
</x-layouts.public>

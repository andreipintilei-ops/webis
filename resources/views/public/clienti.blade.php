{{--
    /portofoliu — the client work, "Clienți" in the menu
    (Public\ClientsController): the internal pages' hero with the planes and
    the figures under it (components/site/figures); the featured projects
    as stacking cards (components/site/featured-work); every project as a
    grid or a list, filtered by type — a row of links, so each filter has
    its own address (?tip=magazin) — (components/site/work-list); the home
    page's wall of logos (components/site/logo-wall); then the home page's
    closing panel, its W forming out of nowhere, instead of the footer's
    call (components/site/cta-panel).

    Client work only: Webis's own products (SmileSoft, RIMS, Bugetare
    participativă, Confirmo) are on Produse SaaS and their own pages, never
    here. Entry: fast and quiet (data-reveal-pace="fast").
--}}
@php
    use App\Enums\ProjectType;
    use App\Models\Project;

    // A project as the featured walkthrough and the list take it.
    $image = fn (Project $project): ?string => $project->cover?->file()?->getUrl();
    $site = fn (?string $url): ?string => $url ? preg_replace('#^https?://(www\.)?|/$#', '', $url) : null;
    $item = fn (Project $project): array => [
        'href' => route('projects.show', $project->slug),
        'client' => $project->client?->name ?? $project->title,
        'type' => $project->type,
        'line' => $project->summary,
        'site' => $site($project->url),
        'image' => $image($project),
    ];

    $featuredItems = $featured->map($item)->all();
    $rows = $projects->map($item)->all();

    // The page's CMS entry (ClientsController) titles it, and the filter in
    // effect goes in front: "Magazine · Portofoliu". Only an SEO title set in
    // the admin replaces "Portofoliu" — the entry's own title names it in the
    // page list, where the menu's word for it ("Clienți") belongs.
    $title = $page?->seo->title ?: 'Portofoliu';
    $title = $type ? $type->plural().' · '.$title : $title;
    $description = $page?->seo->description
        ?: 'Proiecte Webis pentru clienți: software la comandă, site-uri de prezentare, magazine online și identitate vizuală.';

    // DUMMY DATA: until the projects are in the admin — a few of the live
    // webis.ro's, their screenshots straight from it, to check the design.
    // Nothing of this is in the database; the links lead nowhere yet.
    $old = 'https://www.webis.ro/wp-content/uploads/';
    $dummy = [
        ['zyanya', 'Zyanya', ProjectType::Shop, 'zyanya.ro', '2024/03/zyanya.ro-final-e1709281891782-1-768x510.jpg', 'Magazin online de bijuterii, cu un design minimalist și curat, care pune în valoare bijuteriile.'],
        ['floraria-armony', 'Floraria Armony', ProjectType::Shop, 'floraria-armony.ro', '2024/03/armony-11-e1709283660316-768x510.jpg', 'Magazin online pentru o florărie, cu un design proaspăt, pe măsura aranjamentelor.'],
        ['little-tokyo', 'Little Tokyo', ProjectType::Shop, 'littletokyo.ro', '2024/07/littletokyo.ro-1-768x573.png', 'Site pentru restaurantul japonez Little Tokyo din Iași, cu un design inspirat din cultura japoneză.'],
        ['duo-stone', 'Duo Stone', ProjectType::Presentation, 'duostone.ro', '2024/07/duostoen.ro-1-768x512.jpg', 'Site de prezentare elegant pentru Duo Stone, care pune în valoare marmura.'],
        ['carmangeria-familiei-tale', 'Carmangeria Familiei Tale', ProjectType::Presentation, 'tastymeat.ro', '2024/03/tastymeat.ro-final-768x512.jpg', 'Site de prezentare apetisant și curat pentru TastyMeat.'],
        ['viniga', 'Viniga', ProjectType::Presentation, 'viniga.eu', '2024/07/viniga.eu-1-768x512.jpg', 'Site de prezentare cu lucrările de construcții și renovări ale echipei.'],
        ['bm-parts', 'BM Parts', ProjectType::Shop, 'bmparts.ro', '2024/07/bmparts.ro-1-768x512.jpg', 'Magazin online de piese pentru utilaje, cu o navigare simplă.'],
        ['zyanya-logo', 'Zyanya', ProjectType::Logo, 'zyanya.ro', '2024/03/zyanya.ro_-768x512.jpg', 'Logo cu linii delicate, pentru un brand de bijuterii.'],
        ['carmangeria-faimilei-tale-logo', 'Carmangeria Familiei Tale', ProjectType::Logo, 'tastymeat.ro', '2024/03/tastymeat.ro-logo-final-768x512.jpg', 'Logo cu simboluri rustice și culori calde.'],
    ];
    $dummyItems = array_map(fn (array $d): array => [
        'href' => route('projects.show', $d[0]),
        'client' => $d[1],
        'type' => $d[2],
        'site' => $d[3],
        'image' => $old.$d[4],
        'line' => $d[5],
    ], $dummy);

    if ($featuredItems === [] && $projects->isEmpty() && Project::query()->published()->doesntExist()) {
        $featuredItems = [$dummyItems[0], $dummyItems[2], $dummyItems[3]];
        $rows = $dummyItems;
    }

    // The filter: links, so each type has its own address — followed only
    // without the script, which filters the list in place
    // (js/public/work-list.ts); never through the page transition.
    $filters = [
        ['label' => 'Toate', 'value' => '', 'href' => route('projects.index').'#proiecte', 'current' => $type === null],
        ...array_map(fn (ProjectType $option): array => [
            'label' => $option->plural(),
            'value' => $option->value,
            'href' => route('projects.index', ['tip' => $option->value]).'#proiecte',
            'current' => $type === $option,
        ], ProjectType::cases()),
    ];
@endphp

<x-layouts.public
    :title="$title"
    :description="$description"
    header-theme="dark"
    :footer-cta="false"
>
    <x-site.page-hero
        label="Clienți · Webis"
        :title="['Proiecte', 'pentru clienți.']"
        intro="Software la comandă, site-uri de prezentare și magazine online realizate de Webis."
        data-reveal-pace="fast"
        :flush="true"
    />

    {{-- ============================================= The page's own --}}
    {{-- Whatever the admin puts on the page, between the hero and the work
         (nothing by default: the entry exists for the title and the SEO). --}}
    <x-blocks :blocks="$page->blocks ?? []" />

    {{-- ================================================== The figures --}}
    <x-site.figures />

    {{-- ================================================== Featured --}}
    @if ($featuredItems !== [])
        <x-site.featured-work :items="$featuredItems" />
    @endif

    {{-- ================================================== Every project --}}
    <section id="proiecte" class="chapter chapter--light portfolio" aria-labelledby="portfolio-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="portfolio__head" data-reveal="head">
                <h2 id="portfolio-title" class="chapter__title">Toate proiectele</h2>

                @if ($rows !== [])
                    <x-site.work-views />
                @endif

                <nav class="portfolio-filter" aria-label="Tipul proiectului">
                    <ul>
                        @foreach ($filters as $filter)
                            <li>
                                <a href="{{ $filter['href'] }}" class="portfolio-filter__link" data-work-filter="{{ $filter['value'] }}" data-no-transition @if ($filter['current']) aria-current="page"@endif>{{ $filter['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </header>

            @if ($rows !== [])
                <x-site.work-list :rows="$rows" :filter="$type" />            @else
                <p class="portfolio-empty">Încă nu am publicat proiecte.</p>
            @endif
        </div>

        {{-- ================================================== Who we worked with --}}
        {{-- The home page's wall of logos, on the list's ground: the section
             runs on under it, out to the screen's edges. --}}
        <x-site.logo-wall :embedded="true" class="portfolio-clients" />

        {{-- ================================================== The closing call --}}
        {{-- The home page's panel, word for word, the W forming on it out of
             nowhere; it stands in for the footer's call. --}}
        <x-site.cta-panel :standalone="true" class="portfolio-cta" />
    </section>
</x-layouts.public>

{{--
    /portofoliu — the client work, "Clienți" in the menu
    (Public\ClientsController): the internal pages' hero with the planes and
    the figures under it (components/site/figures); the featured projects
    as stacking cards (components/site/featured-work); every project as a
    list, after dennissnellenberg.com/work, filtered by type — a row of
    links, so each filter has its own address (?tip=magazin) — a preview
    following the pointer (components/site/work-list); the home page's strip
    of logos; a violet panel to Produse SaaS; the footer's closing call,
    reworded.

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

    // The products panel's W: five plates, a Lucide icon in each.
    $plates = ['flask', 'vote', 'layers', 'smile', 'calendar-check'];
    $productIcon = fn (string $name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.[
        'flask' => '<path d="M14 2v6a2 2 0 0 0 .245.96l5.51 10.08A2 2 0 0 1 18 22H6a2 2 0 0 1-1.755-2.96l5.51-10.08A2 2 0 0 0 10 8V2"/><path d="M6.453 15h11.094"/><path d="M8.5 2h7"/>',
        'vote' => '<path d="m9 12 2 2 4-4"/><path d="M5 7c0-1.1.9-2 2-2h10a2 2 0 0 1 2 2v12H5V7Z"/><path d="M22 19H2"/>',
        'layers' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'calendar-check' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="m9 16 2 2 4-4"/>',
    ][$name].'</svg>';
@endphp

<x-layouts.public
    :title="$type ? $type->plural().' · Portofoliu' : 'Portofoliu'"
    description="Proiecte Webis pentru clienți: software la comandă, site-uri de prezentare, magazine online și identitate vizuală."
    header-theme="dark"
    footer-cta-title="Ai un proiect asemănător?"
    footer-cta-intro="Spune-ne ce vrei să construiești și revenim cu o propunere."
>
    <x-site.page-hero
        label="Clienți · Webis"
        :title="['Proiecte', 'pentru clienți.']"
        intro="Software la comandă, site-uri de prezentare și magazine online realizate de Webis."
        data-reveal-pace="fast"
        :flush="true"
    />

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
                <x-site.work-list :rows="$rows" :filter="$type" />
            @else
                <p class="portfolio-empty">Încă nu am publicat proiecte.</p>
            @endif
        </div>

        {{-- ================================================== Who we worked with --}}
        {{-- The home page's strip of logos, darkened, on the same ground: the
             list's section runs on under it, out to the screen's edges. --}}
        <div class="portfolio-clients">
            <x-logo-marquee tone="light" class="portfolio-clients__strip" />
        </div>

        {{-- ================================================== Our own products --}}
        {{-- A violet panel: what they are and the way to Produse SaaS; beside
             it five plates standing as the Webis W, each with an icon — no
             product named here: this page is client work only. --}}
        <div class="portfolio-products">
            <div class="portfolio-products__panel" data-reveal>
                <div class="portfolio-products__text" data-reveal-child>
                    <p class="portfolio-products__label">Produse SaaS</p>
                    <h2 id="products-title" class="portfolio-products__title">Aplicații dezvoltate de noi, gata de folosit.</h2>
                    <p class="portfolio-products__intro">Pe lângă proiectele pentru clienți, construim și întreținem produse proprii, disponibile pe abonament.</p>
                    <x-ui.pill href="/produse" tone="dark" class="portfolio-products__link">Vezi produsele</x-ui.pill>
                </div>

                <div class="portfolio-products__mark" aria-hidden="true" data-reveal-child>
                    @foreach ($plates as $glyph)
                        <span class="portfolio-products__plate" style="--i: {{ $loop->index }}"><span>{!! $productIcon($glyph) !!}</span></span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>

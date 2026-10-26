{{--
    /creare-magazin-online/ — online shops (routes/web.php), the old site's
    page with the most impressions after the home page, at its address. It
    replaces the CMS page of that name, which no longer shows. Built from
    /web's, /software-la-comanda/'s and /produse/'s pieces, nothing of its
    own:

    - the internal pages' hero, flush, with the two ways on and /produse's
      strip of four across its foot;
    - the home page's "Despre noi" statement, on the hero's backdrop;
    Then the light panel, rising over it:
    - what a shop includes: white cards in three staggered columns
      (components/section/benefit-columns), as /software-la-comanda/;
    - shops we made: /web's project cards, three online shops;
    - how we work: the numbered columns on the indigo, as /web;
    - what the price depends on: the title beside hairline rows (.rows);
    - after launch: /web's pair;
    - questions, the title beside them (no FAQPage JSON-LD);
    then the footer's closing call, worded for shops.

    Breadcrumbs: Acasă › Web și e-commerce › Creare magazin online.

    TODO(de confirmat, before going live): the payment processors, the
    couriers, the invoicing integrations and e-Factura (the cards name
    none of them); what a move from another shop imports (products,
    customers, orders); whether we write the legal texts or only add the
    pages; several languages and currencies; a starting price ("de la X
    lei"), if one is to be published.
--}}
@php
    use App\Enums\ProjectType;
    use App\Models\Project;

    $canonical = 'https://www.webis.ro/creare-magazin-online/';

    // Across the hero's foot.
    $strip = [
        ['Catalog și stocuri', 'Produse, variante și filtre'],
        ['Plăți online', 'Card, ramburs sau transfer'],
        ['Livrare', 'Curier și calcul automat'],
        ['Facturare', 'Facturi emise la comandă'],
    ];

    // What a shop includes: short names, lines of about the same length.
    $included = [
        ['package', 'Catalog fără limită', 'Produse, variante de mărime sau culoare, categorii și filtre, ca clienții să găsească repede ce caută.'],
        ['card', 'Plăți online și ramburs', 'Clienții plătesc cu cardul, ramburs sau prin transfer bancar.'],
        ['truck', 'Livrare prin curier', 'Costul livrării se calculează automat, iar AWB-ul se generează din comandă.'],
        ['receipt', 'Facturi automate', 'Factura se emite la comandă, fără să o mai completezi de mână.'],
        ['search', 'SEO de la început', 'Fiecare produs și categorie are adresa, titlul și descrierea lui, iar paginile se încarcă repede.'],
        ['edit', 'Administrare simplă', 'Adaugi produse, actualizezi stocuri și vezi comenzile dintr-un singur panou. Instruirea e inclusă.'],
    ];

    // Three shops with the strongest screenshots, each its own line; others
    // from the portfolio stand in for any no longer published.
    $picked = [
        'zyanya' => 'Magazin online de bijuterii, cu un design minimalist și curat, pe măsura bijuteriilor.',
        'floraria-armony' => 'Florărie online, cu un design proaspăt care pune în față aranjamentele florale.',
        'bm-parts' => 'Piese pentru utilaje, organizate clar, cu o navigare simplă prin catalog.',
    ];
    $projects = Project::query()->published()->where('type', ProjectType::Shop)
        ->whereIn('slug', array_keys($picked))
        ->with(['client:id,name', 'cover.media'])
        ->get()
        ->sortBy(fn (Project $project): int => array_search($project->slug, array_keys($picked), true))
        ->values();
    if ($projects->count() < 3) {
        $projects = $projects->concat(Project::query()->published()->where('type', ProjectType::Shop)
            ->whereNotIn('id', $projects->modelKeys())
            ->with(['client:id,name', 'cover.media'])
            ->orderBy('sort_order')->latest('id')
            ->limit(3 - $projects->count())
            ->get());
    }
    $cards = $projects->map(fn (Project $project): array => [
        'href' => '/proiect/'.$project->slug.'/',
        'client' => $project->client?->name ?? $project->title,
        'type' => $project->type,
        'image' => $project->cover?->file()?->getUrl(),
        'line' => $picked[$project->slug] ?? $project->summary,
    ])->all();

    $steps = [
        ['Analiză', 'Vorbim despre produse, livrare, plăți și facturare, ca magazinul să se potrivească cu felul în care lucrezi acum.'],
        ['Design', 'Pornim de la brandul tău și construim un concept propriu, gândit pentru telefon la fel de mult ca pentru desktop.'],
        ['Dezvoltare', 'Construim catalogul, coșul, plățile, livrarea și facturarea, apoi le legăm de programele pe care le folosești deja.'],
        ['Produse și testare', 'Importăm produsele, plasăm comenzi de test și verificăm fiecare pas, de la coș până la factură.'],
        ['Lansare și instruire', 'Punem magazinul online și îți arătăm cum adaugi produse și cum gestionezi comenzile.'],
    ];

    // What changes the price.
    $price = [
        ['Produsele', 'Numărul de produse și de variante, și dacă le importăm noi.'],
        ['Integrările', 'Facturare, curier, plăți, stocuri sau ERP.'],
        ['Designul', 'Câte pagini au un design propriu.'],
        ['Limbi și monede', 'În câte limbi vinzi și în ce monede afișezi prețurile.'],
        ['Funcții în plus', 'Conturi pentru firme, prețuri diferite pe clienți sau abonamente.'],
    ];

    $after = [
        ['Mentenanță', 'Actualizări, backup și securitate, ca magazinul să rămână rapid și sigur.', '/mentenanta-site-wordpress/'],
        ['Optimizare SEO', 'Lucrăm în continuare la SEO, ca magazinul să urce în Google.', '/seo-search-engine-optimization/'],
    ];

    // No FAQPage JSON-LD: Google no longer shows FAQ results for business sites.
    $questions = [
        ['Cât costă realizarea unui magazin online?', 'Depinde de numărul de produse, de integrări și de design. După o discuție scurtă îți trimitem o ofertă pe etape, ca să știi de la început ce primești.'],
        ['Cât durează?', 'Depinde de numărul de produse și de integrări. După analiză primești un calendar pe etape.'],
        ['Pot administra singur magazinul?', 'Da. Adaugi și modifici produse, stocuri și prețuri dintr-un panou simplu, iar instruirea e inclusă.'],
        ['Am deja un magazin online. Îl puteți muta?', 'Da. Importăm produsele, clienții și comenzile și redirecționăm adresele vechi, ca să nu pierzi pozițiile din Google.'],
        ['Magazinul respectă legislația?', 'Primești paginile de termeni și condiții, confidențialitate și cookie-uri, plus legăturile ANPC (SAL și SOL) cerute pentru magazinele online din România.'],
        ['Poate avea mai multe limbi sau monede?', 'Da, magazinul poate fi tradus în mai multe limbi și poate afișa prețuri în mai multe monede.'],
    ];

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

{{-- The title and description held to 60 and 155 characters. --}}
<x-layouts.public
    title="Creare magazin online: design, plăți și livrare"
    description="Realizare magazin online pe brandul tău: catalog, plăți cu cardul, livrare prin curier și SEO de la început. Vezi ce influențează prețul."
    crumb="Creare magazin online"
    :crumb-parent="['Web și e-commerce', '/web/']"
    :canonical="$canonical"
    header-theme="dark"
    footer-cta-title="Vrei să vinzi online?"
    footer-cta-intro="Spune-ne ce produse ai și cum livrezi acum, iar noi revenim cu pașii următori și o ofertă pe etape."
    footer-cta-button="Cere o ofertă"
    :footer-w="true"
>

    {{-- ================================================== Hero --}}
    {{-- Flush: the statement carries on, on the same backdrop. Under the
         intro, the two ways on, as /produse; across its foot, what a shop
         holds, on /produse's hairline. --}}
    <x-site.page-hero
        label="Magazine online · Webis"
        :title="['Creare magazin', 'online.']"
        intro="Magazine online făcute pe brandul tău, ușor de administrat de echipa ta și gândite să vândă."
        class="page-hero--led saas-hero"
        :flush="true"
        data-reveal-pace="fast"
    >
        @include('blocks.partials.ctas', [
            'primary' => ['label' => 'Cere o ofertă', 'url' => '/contact/'],
            'secondary' => ['label' => 'Vezi magazine realizate', 'url' => '#magazine'],
            'tone' => 'dark',
            'class' => 'saas-hero__actions',
        ])

        <x-slot:after>
            <ul class="saas-hero-products">
                @foreach ($strip as [$name, $for])
                    <li>
                        <div class="saas-hero-product">
                            <span class="saas-hero-product__no">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="saas-hero-product__name">{{ $name }}</span>
                            <span class="saas-hero-product__for">{{ $for }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </x-slot:after>
    </x-site.page-hero>

    {{-- ================================================== About online shops --}}
    {{-- The home page's "Despre noi", on the hero's backdrop: the dark area's
         last section. --}}
    @include('blocks.statement', [
        'block' => ['id' => 'despre-magazine-online'],
        'onBackdrop' => true,
        'data' => [
            'eyebrow' => 'Despre magazine online',
            'statement' => "Un magazin online bun\nse vinde ușor și se administrează\nla fel de ușor.",
            'text' => 'Realizarea unui magazin online pornește de la produsele tale și de la felul în care vinzi acum: ce stoc ai, cum livrezi, cum emiți facturile. Designul e făcut pentru brandul tău, nu pornit dintr-o temă, iar magazinul vine cu SEO de la început, ca să fie găsit în Google. Lucrăm din Iași, cu magazine pentru clienți din toată țara.',
            'link' => ['label' => 'Ce include', 'url' => '#ce-include'],
        ],
    ])

    {{-- ================================================== What it includes --}}
    <section id="ce-include" class="chapter chapter--light sw-why" aria-labelledby="shop-included-title" data-scroll-target data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="shop-included-title"
                label="Ce include"
                title="Tot ce ține de vânzare, la un loc."
                highlight="la un loc"
            />

            <x-section.benefit-columns :items="$included" />
        </div>
    </section>

    {{-- ================================================== Shops we made --}}
    @if ($cards !== [])
        <section id="magazine" class="chapter chapter--light web-projects" aria-labelledby="shop-projects-title" data-scroll-target data-reveal-pace="fast">
            <div class="chapter__inner">
                <header class="chapter__head" data-reveal="head">
                    <div class="chapter__head-main">
                        <p class="chapter__label">Proiecte</p>
                        <h2 id="shop-projects-title" class="chapter__title">Câteva magazine online realizate de noi.</h2>
                    </div>
                    <div class="web-projects__ways">
                        <x-ui.arrow-link :href="route('projects.index', ['tip' => 'magazin'])">Toate magazinele online</x-ui.arrow-link>
                    </div>
                </header>

                {{-- /web's projects: the site's card, its screenshot at the top. --}}
                <ul class="web-projects__list web-projects__list--even" data-reveal>
                    @foreach ($cards as $card)
                        <li data-reveal-child>
                            <a href="{{ $card['href'] }}" class="post-card web-project">
                                <span class="post-card__frame" aria-hidden="true">
                                    <span class="post-card__bg"></span>
                                    <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
                                </span>
                                <span class="web-project__media">
                                    @if ($card['image'])
                                        <img src="{{ $card['image'] }}" alt="" loading="lazy" decoding="async">
                                    @endif
                                </span>
                                <span class="post-card__meta"><span class="post-card__category">{{ $card['type']->label() }}</span></span>
                                <span class="post-card__title"><span>{{ $card['client'] }}</span></span>
                                <span class="post-card__foot">{{ $card['line'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ================================================== How we work --}}
    <x-section.numbered-columns
        id="shop-steps-title"
        title="Cum lucrăm."
        label="Cum lucrăm"
        statement="De la prima discuție la primele comenzi, în cinci pași."
        :items="$steps"
    />

    {{-- ================================================== Price --}}
    {{-- For the price searches: what the price depends on, not a table. --}}
    <section class="chapter chapter--light" aria-labelledby="shop-price-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split">
                <header class="web-split__head" data-reveal="head">
                    <p class="chapter__label">Preț</p>
                    <h2 id="shop-price-title" class="chapter__title">Cât costă un magazin online.</h2>
                    <p class="web-split__line">Prețul unui magazin online depinde de ce face magazinul, nu de numărul de pagini. După o discuție scurtă primești o ofertă pe etape, cu ce intră în fiecare.</p>
                    <x-ui.pill href="/contact/">Cere o ofertă</x-ui.pill>
                </header>

                <ol class="rows web-split__body" data-reveal>
                    @foreach ($price as [$name, $line])
                        <li data-reveal-child>
                            <span class="chapter__label">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <h3 class="rows__title">{{ $name }}</h3>
                            <p class="rows__line">{{ $line }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </div>
    </section>

    {{-- ================================================== After launch --}}
    <section class="chapter chapter--light" aria-labelledby="shop-after-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="shop-after-title" class="chapter__title">După lansare.</h2>
                </div>
                <p class="chapter__intro">Un magazin online nu se termină la lansare. Ne ocupăm în continuare de actualizări, backup și securitate, și putem lucra la SEO ca magazinul să urce în Google.</p>
            </header>

            <ul class="web-after" data-reveal>
                @foreach ($after as [$title, $line, $href])
                    <li data-reveal-child>
                        <a href="{{ $href }}" class="web-after__item">
                            <span class="web-after__title">{{ $title }}</span>
                            <span class="web-after__line">{{ $line }}</span>
                            <span class="web-after__go">Află mai multe <span aria-hidden="true">{!! $arrow !!}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================================================== Questions --}}
    <section class="chapter chapter--light web-faq" aria-labelledby="shop-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="shop-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                    <p class="web-split__line">Nu găsești răspunsul? Scrie-ne și îți răspundem cât mai repede.</p>
                    <x-ui.arrow-link href="/contact/">Scrie-ne</x-ui.arrow-link>
                </header>

                <div class="web-faq__list web-split__body" data-split-body data-reveal>
                    @foreach ($questions as [$question, $answer])
                        <details class="web-faq__item" data-accordion-item data-reveal-child>
                            <summary class="web-faq__question">
                                <span>{{ $question }}</span>
                                <span class="web-faq__icon" aria-hidden="true"></span>
                            </summary>
                            <div class="web-faq__answer" data-accordion-case>
                                <p>{{ $answer }}</p>
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>

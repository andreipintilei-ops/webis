{{--
    /produse/ — Produse SaaS (routes/web.php), on the language of the pages
    the owner names as the reference (the home page, /blog, /portofoliu,
    /contact) and /software-la-comanda/'s order; its own pieces in
    css/site/products.css.

    - /web's hero, with the planes;
    - rising over it, the products as /portofoliu's stacking cards: who it
      is for, what it
      does, the clients using it (components/site/used-by), the way to ask
      for a demo;
    - why a ready product: the home page's cells between hairlines;
    - how to start, and product or made to order: two short paragraphs;
    - questions, as /web's rows that open, and as FAQPage JSON-LD from the
      same list;
    then the footer's closing call.

    TODO(de confirmat, before going live): SmileSoft's features, how the
    participatory budgeting vote works, Confirmo's audience, updates being
    included, extensions made to order, what the price depends on, a free
    trial if there is one, and what happens to the data on leaving (its
    question is left out until then).
--}}
@php
    use App\Enums\Product;

    $canonical = 'https://www.webis.ro/produse/';

    // The products, in this order; each section's id is the product's value,
    // as Product::href() links to it.
    $products = [
        [
            'product' => Product::SmileSoft,
            'for' => 'Pentru clinici stomatologice',
            'text' => 'SmileSoft ține la un loc programările, fișele pacienților, planurile de tratament și încasările. Recepția și medicii lucrează pe aceleași informații, iar istoricul fiecărui pacient e la un click distanță.',
        ],
        [
            // For the university's staff, never presented as for students.
            'product' => Product::Rims,
            'for' => 'Pentru universități',
            'text' => 'RIMS (Research Information Management System) ține evidența proiectelor de cercetare ale universității: echipele, finanțările, bugetele și raportările. Conducerea și echipele de proiect lucrează pe aceleași date, iar rapoartele pentru finanțatori se pregătesc din aplicație.',
        ],
        [
            'product' => Product::ParticipatoryBudgeting,
            'for' => 'Pentru primării',
            'text' => 'O platformă prin care cetățenii propun proiecte pentru comunitate, votează și urmăresc cum sunt puse în practică. Primăria gestionează tot procesul dintr-un singur loc, de la depunerea propunerilor până la proiectele câștigătoare.',
        ],
        [
            'product' => Product::Confirmo,
            'for' => 'Pentru afaceri care lucrează cu programări',
            'text' => 'Programări și rezervări online. Clienții își aleg singuri ora, iar Confirmo trimite confirmarea și reamintirea prin SMS. Fiecare angajat are calendarul lui, iar programările se văd într-un singur loc.',
        ],
    ];

    $benefits = [
        ['name' => 'Pornești repede.', 'text' => 'Produsul există deja: îl configurăm pentru organizația ta și îl poți folosi fără să aștepți o dezvoltare.'],
        ['name' => 'Plătești lunar.', 'text' => 'Un abonament în locul unei investiții mari la început, fără contract pe termen lung.'],
        ['name' => 'Primești noutățile.', 'text' => 'Produsele se îmbunătățesc continuu, iar actualizările sunt incluse în abonament.'],
        ['name' => 'Instruirea e inclusă.', 'text' => 'Echipa ta învață să folosească produsul de la început, iar noi rămânem aproape pentru întrebări.'],
        ['name' => 'Se poate extinde.', 'text' => 'Dacă ai nevoie de ceva în plus, construim partea respectivă la comandă.'],
    ];

    // The questions: the accordion and the FAQPage JSON-LD both read this list.
    $questions = [
        ['Cât costă?', 'Depinde de produs și de numărul de utilizatori. Spune-ne ce produs te interesează și îți trimitem oferta.'],
        ['Pot vedea produsul înainte?', 'Da. Îți facem o prezentare, ca să vezi dacă se potrivește.'],
        ['Pot renunța oricând?', 'Da. Nu există contract pe termen lung.'],
        ['Se poate adapta la organizația mea?', 'Fiecare produs se configurează pentru organizația ta, iar funcțiile în plus le putem construi la comandă.'],
    ];

    $faq = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn (array $item): array => [
            '@type' => 'Question',
            'name' => $item[0],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item[1]],
        ], $questions),
    ];
@endphp

<x-layouts.public
    title="Produse SaaS – SmileSoft, RIMS, Bugetare participativă, Confirmo"
    description="Aplicații gata de folosit, pe abonament lunar: SmileSoft pentru clinici stomatologice, RIMS pentru universități, Bugetare participativă pentru primării și Confirmo pentru programări online."
    :canonical="$canonical"
    header-theme="dark"
>
    @push('head')
        <script type="application/ld+json">{!! json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
    @endpush

    {{-- ================================================== Hero --}}
    <x-site.page-hero
        label="Produse SaaS · Webis"
        :title="['Produse SaaS.']"
        intro="Aplicații gata de folosit, pe abonament lunar."
        class="page-hero--tall"
        data-reveal-pace="fast"
    />

    {{-- ================================================== The products --}}
    {{-- /portofoliu's featured projects (components/site/featured-work,
         css/site/work-stack.css): a screen-high card each, stacking on
         scroll from 1024px (js/public/product-stack.ts), each in a colour
         of the hero's. On the left the name at the top, what it does and
         the way to a demo at the foot; on the right a plain placeholder
         until there are screenshots. The sheet rises over the hero, as
         after every internal page's (css/site/page-hero.css). --}}
    <section id="produsele" class="work-stack saas-stack" aria-labelledby="saas-products-title" data-product-stack data-reveal-pace="fast">
        <header class="work-stack__head" data-reveal="head">
            <div class="chapter__head-main md:col-span-7">
                <h2 id="saas-products-title" class="chapter__title">Produsele noastre.</h2>
            </div>
        </header>

        <div class="work-stack__cards">
            @foreach ($products as $item)
                @php($product = $item['product'])
                <article id="{{ $product->value }}" class="work-stack__card work-stack__card--{{ $loop->iteration }}" style="--i: {{ $loop->index }}" aria-labelledby="{{ $product->value }}-title" data-product-card data-scroll-target>
                    {{-- The entry moves this frame; being covered moves the panel. --}}
                    <div class="work-stack__frame" data-product-entry>
                        <div class="work-stack__panel saas-stack__panel" data-product-panel>
                            <div class="saas-stack__main">
                                <div>
                                    <p class="work-stack__count">{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', count($products)) }} · {{ $item['for'] }}</p>
                                    <h3 id="{{ $product->value }}-title" class="work-stack__client">{{ $product->label() }}</h3>
                                </div>

                                <div class="saas-stack__foot">
                                    <p class="saas-stack__text">{{ $item['text'] }}</p>

                                    <div class="saas-stack__links">
                                        {{-- The white button, on the card's colour. --}}
                                        <x-ui.pill :href="'/contact/?produs='.$product->value" tone="dark" class="work-stack__link">Cere o prezentare</x-ui.pill>
                                        @if ($product->externalUrl())
                                            <x-ui.arrow-link :href="$product->externalUrl()" tone="dark" target="_blank" rel="noopener">{{ parse_url($product->externalUrl(), PHP_URL_HOST) }}</x-ui.arrow-link>
                                        @endif
                                    </div>

                                    <x-site.used-by :product="$product" />
                                </div>
                            </div>

                            {{-- TODO: the product's own screenshots. --}}
                            <div class="saas-stack__visual" aria-hidden="true"></div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ================================================== Why a ready product --}}
    {{-- As /software-la-comanda/'s "De ce software la comandă". --}}
    <section class="chapter chapter--light" aria-labelledby="saas-why-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="saas-why-title" class="chapter__title">De ce un produs gata de folosit.</h2>
                </div>
                <p class="chapter__intro">Produsele noastre pornesc de la ce au în comun organizațiile dintr-un domeniu, așa că poți începe fără să construiești nimic de la zero.</p>
            </header>

            <x-site.chapter.cells :cells="$benefits" :numbered="false" class="chapter-cells--flush" data-reveal />
        </div>
    </section>

    {{-- ================================================== How to start, product or made to order --}}
    {{-- Two short paragraphs side by side, each on a hairline. --}}
    <section class="chapter chapter--light" aria-label="Cum începi" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="saas-notes" data-reveal>
                <div class="saas-note" data-reveal-child>
                    <h2 class="saas-note__title">Cum începi.</h2>
                    <p class="saas-note__text">Totul începe cu o prezentare: îți arătăm produsul pe un exemplu apropiat de ce faci tu. Dacă se potrivește, configurăm contul, importăm datele de care ai nevoie și instruim echipa.</p>
                    <x-ui.arrow-link href="/contact/">Cere o prezentare</x-ui.arrow-link>
                </div>

                <div class="saas-note" data-reveal-child>
                    <h2 class="saas-note__title">Produs sau la comandă.</h2>
                    <p class="saas-note__text">Produsele acoperă nevoile comune dintr-un domeniu. Când procesul tău are reguli proprii, o aplicație la comandă e varianta potrivită, iar cele două se pot combina.</p>
                    <x-ui.arrow-link href="/software-la-comanda/">Despre software la comandă</x-ui.arrow-link>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================== Questions --}}
    {{-- /web's rows that open (js/public/project-accordion.ts). --}}
    <section class="chapter chapter--light web-faq" aria-labelledby="saas-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="saas-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                </div>
            </header>

            <div class="web-faq__list" data-reveal>
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
    </section>
</x-layouts.public>

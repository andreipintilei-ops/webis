{{--
    /produse/ — Produse SaaS (routes/web.php), on the language of the pages
    the owner names as the reference (the home page, /blog, /portofoliu,
    /contact) and /software-la-comanda/'s order; its own pieces in
    css/site/products.css.

    - the internal pages' hero, the four products across its foot, each a
      way down to its own;
    - straight after it, on the same backdrop, the products as
      /portofoliu's stacking cards, each in its own colour: who it is for,
      what it does and holds, the way to a demo, the clients using it
      (components/site/used-by), and its screen drawn
      (components/saas/mockup);
    Then the light panel, rising over the backdrop:
    - product or made to order: a centred head over the two side by side,
      then how to start in three steps;
    - questions, the title beside them as on /web (no FAQPage JSON-LD);
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
            'screen' => 'smilesoft',
            'for' => 'Pentru clinici stomatologice',
            'points' => ['Programări', 'Fișe de pacient', 'Planuri de tratament', 'Încasări'],
            'text' => 'SmileSoft ține la un loc programările, fișele pacienților, planurile de tratament și încasările. Recepția și medicii lucrează pe aceleași informații, iar istoricul fiecărui pacient e la un click distanță.',
        ],
        [
            // For the university's staff, never presented as for students.
            'product' => Product::Rims,
            'screen' => 'rims',
            'for' => 'Pentru universități',
            'points' => ['Proiecte', 'Echipe', 'Finanțări și bugete', 'Raportări'],
            'text' => 'Ține evidența proiectelor de cercetare ale universității: echipele, finanțările, bugetele și raportările. Conducerea și echipele de proiect lucrează pe aceleași date, iar rapoartele pentru finanțatori se generează direct din aplicație.',
        ],
        [
            'product' => Product::ParticipatoryBudgeting,
            'screen' => 'bugetare',
            'for' => 'Pentru primării',
            'points' => ['Propuneri', 'Vot online', 'Urmărirea proiectelor', 'Rezultate publice'],
            'text' => 'O platformă prin care cetățenii propun proiecte pentru comunitate, votează și urmăresc cum sunt puse în practică. Primăria gestionează tot procesul dintr-un singur loc, de la depunerea propunerilor până la proiectele câștigătoare.',
        ],
        [
            'product' => Product::Confirmo,
            'screen' => 'confirmo',
            'for' => 'Pentru afaceri cu programări',
            'points' => ['Rezervări online', 'Confirmare prin SMS', 'Reamintiri', 'Calendar pe angajat'],
            'text' => 'Programări și rezervări online. Clienții își aleg singuri ora, iar Confirmo trimite confirmarea și reamintirea prin SMS. Fiecare angajat are calendarul lui, iar programările se văd într-un singur loc.',
        ],
    ];

    // A ready product set against a made-to-order application.
    $compare = [
        ['Când pornești', 'În câteva zile, după configurare', 'După analiză și dezvoltare, pe etape'],
        ['Cum plătești', 'Abonament lunar, fără contract pe termen lung', 'O investiție pe etape; aplicația e a ta'],
        ['Actualizări', 'Incluse: produsul se îmbunătățește continuu', 'La cerere, odată cu mentenanța'],
        ['Potrivit când', 'Nevoile tale sunt comune în domeniu', 'Procesul tău are reguli proprii'],
        ['Ce lipsește', 'Construim la comandă, peste produs', 'Nimic: totul e făcut pentru tine'],
    ];

    // How to start with a product.
    $start = [
        'Îți arătăm produsul pe un exemplu apropiat de ce faci.',
        'Configurăm contul și importăm datele de care ai nevoie.',
        'Instruim echipa și rămânem aproape pentru întrebări.',
    ];

    // The questions, for the accordion. No FAQPage JSON-LD: Google no longer
    // shows FAQ results for business sites.
    $questions = [
        ['Cât costă?', 'Depinde de produs și de numărul de utilizatori. Spune-ne ce produs te interesează și îți trimitem oferta.'],
        ['Pot vedea produsul înainte?', 'Da. Îți facem o prezentare, ca să vezi dacă se potrivește.'],
        ['Pot renunța oricând?', 'Da. Nu există contract pe termen lung.'],
        ['Se poate adapta la felul în care lucrăm?', 'Fiecare produs se configurează pentru echipa ta, iar funcțiile în plus le putem construi la comandă.'],
    ];
@endphp

{{-- The title and description held to 60 and 155 characters. --}}
<x-layouts.public
    title="Produse SaaS: clinici, universități și primării"
    description="Aplicații pe abonament: SmileSoft pentru stomatologie, Research Information Management System pentru universități, Bugetare participativă și Confirmo."
    crumb="Produse SaaS"
    :canonical="$canonical"
    header-theme="dark"
    :footer-w="true"
>

    {{-- ================================================== Hero --}}
    {{-- Under the intro, the home page hero's two ways on (blocks/partials/
         ctas): a demo, and down to the first product. The four products
         close the hero, on a hairline across its foot (as /web's offers):
         each its name and who it is for, a way down to its card. --}}
    <x-site.page-hero
        label="Produse SaaS · Webis"
        :title="['Produse SaaS.']"
        intro="Aplicații gata de folosit, pe abonament lunar, fiecare făcută pentru un domeniu."
        class="page-hero--led saas-hero"
        :flush="true"
        data-reveal-pace="fast"
    >
        @include('blocks.partials.ctas', [
            'primary' => ['label' => 'Cere o prezentare', 'url' => '/contact/'],
            'secondary' => ['label' => 'Vezi produsele', 'url' => '#'.$products[0]['product']->value],
            'tone' => 'dark',
            'class' => 'saas-hero__actions',
        ])

        <x-slot:after>
            <ul class="saas-hero-products">
                @foreach ($products as $item)
                    <li>
                        <a href="#{{ $item['product']->value }}" class="saas-hero-product" data-no-transition>
                            <span class="saas-hero-product__no">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="saas-hero-product__name">{{ $item['product']->label() }}</span>
                            <span class="saas-hero-product__for">{{ $item['for'] }}</span>
                            <svg class="saas-hero-product__arrow" viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z" /></svg>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-slot:after>
    </x-site.page-hero>

    {{-- ================================================== The products --}}
    {{-- /portofoliu's featured projects (css/site/work-stack.css): a
         screen-high card each, stacking on scroll from 1024px
         (js/public/product-stack.ts), each in the product's own colour. On
         the left the name at the top, what it does and holds and the way to
         a demo at the foot; on the right its screen, drawn
         (components/saas/mockup). On the hero's backdrop, carrying on from
         it: no head of their own, the hero's title says it. --}}
    <section id="produsele" class="work-stack saas-stack" aria-labelledby="saas-products-title" data-product-stack data-backdrop-area data-nav-tone="dark" data-reveal-pace="fast">
        <h2 id="saas-products-title" class="sr-only">Produsele noastre</h2>

        <div class="work-stack__cards">
            @foreach ($products as $item)
                @php($product = $item['product'])
                <article id="{{ $product->value }}" class="work-stack__card work-stack__card--{{ $loop->iteration }} saas-stack__card--{{ $item['screen'] }}" style="--i: {{ $loop->index }}" aria-labelledby="{{ $product->value }}-title" data-product-card data-scroll-target>
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

                                    <p class="saas-stack__points">
                                        @foreach ($item['points'] as $point)
                                            <span>{{ $point }}</span>
                                        @endforeach
                                    </p>

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

                            {{-- Its screen, drawn: the product at work. --}}
                            <div class="saas-stack__visual">
                                <x-saas.mockup :variant="$item['screen']" />
                            </div>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    {{-- ================================================== Product or made to order --}}
    {{-- What a ready product means, set against a made-to-order application,
         side by side; then how to start, in three steps. --}}
    <section class="chapter chapter--light saas-compare" aria-labelledby="saas-compare-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="saas-compare-title"
                label="Produs sau la comandă"
                title="Pornești din prima zi și plătești lunar."
                highlight="din prima zi"
            />

            <div class="saas-table" data-reveal>
                <table>
                    <thead>
                        <tr>
                            <td></td>
                            <th scope="col" class="saas-table__ours">Produs SaaS</th>
                            <th scope="col">Software la comandă</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($compare as [$row, $ours, $made])
                            <tr data-reveal-child>
                                <th scope="row">{{ $row }}</th>
                                <td class="saas-table__ours">{{ $ours }}</td>
                                <td>{{ $made }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="saas-start" data-reveal>
                <ol class="saas-start__steps">
                    @foreach ($start as $step)
                        <li data-reveal-child><span>{{ sprintf('%02d', $loop->iteration) }}</span>{{ $step }}</li>
                    @endforeach
                </ol>
                <div class="saas-start__links" data-reveal-child>
                    <x-ui.pill href="/contact/">Cere o prezentare</x-ui.pill>
                    <x-ui.arrow-link href="/software-la-comanda/">Despre software la comandă</x-ui.arrow-link>
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================== Questions --}}
    {{-- /web's rows that open (js/public/project-accordion.ts), the title
         beside them, held while they scroll (js/public/split-heads.ts). --}}
    <section class="chapter chapter--light web-faq" aria-labelledby="saas-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="saas-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                    <p class="web-split__line">Nu găsești răspunsul? Spune-ne ce produs te interesează și îți răspundem în aceeași zi lucrătoare.</p>
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

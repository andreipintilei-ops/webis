{{--
    /creare-site-de-prezentare/ — presentation sites (routes/web.php),
    Webis's core web service, at the old site's address. It replaces the
    CMS page of that name, which no longer shows. The same pieces, in the
    same order, as /creare-magazin-online/ (public/shop):

    - the internal pages' hero, flush, with the two ways on and /produse's
      strip of four across its foot;
    - the home page's "Despre noi" statement, on the hero's backdrop;
    Then the light panel, rising over it:
    - what you get: white cards in three staggered columns
      (components/section/benefit-columns), worded apart from /web's;
    - sites we made: /web's project cards, three presentation sites from
      different fields;
    - how we work: the numbered columns on the indigo;
    - what the price depends on: the title beside hairline rows (.rows);
    - after launch: /web's pair;
    - questions, the title beside them (no FAQPage JSON-LD);
    then the footer's closing call, worded for presentation sites.

    Not aimed at "creare site Iași": that search is the home page's.

    Breadcrumbs: Acasă › Web și e-commerce › Creare site de prezentare.

    TODO(de confirmat, before going live): whether copywriting is included
    or extra; whether we register the domain and set up email and hosting,
    or only advise; the money-back offer, left out (it may come back as a
    plainly worded answer); a starting price ("de la X lei"), if one is to
    be published.
--}}
@php
    use App\Enums\ProjectType;
    use App\Models\Project;

    $canonical = 'https://www.webis.ro/creare-site-de-prezentare/';

    // Across the hero's foot.
    $strip = [
        ['Design propriu', 'Pe brandul tău, fără teme'],
        ['Găsit în Google', 'SEO de la început'],
        ['Pe orice ecran', 'Gândit întâi pentru telefon'],
        ['Editabil', 'Texte și poze, actualizate de tine'],
    ];

    // What you get: more concrete than /web's overview cards.
    $included = [
        ['layers', 'Paginile de care ai nevoie', 'Acasă, servicii, despre, proiecte și contact, plus pagini pentru fiecare serviciu important, ca fiecare să poată fi găsit separat în Google.'],
        ['text', 'Texte care explică', 'Te ajutăm să scrii sau să îmbunătățești textele, ca vizitatorul să înțeleagă repede ce oferi.'],
        ['mail', 'Formulare care ajung la tine', 'Cereri de ofertă, programări sau întrebări, primite direct pe email, cu protecție împotriva spamului.'],
        ['search', 'Pregătit pentru Google', 'Titluri și descrieri pentru fiecare pagină, adrese curate, date de contact structurate și legătura cu profilul tău de Google Business.'],
        ['roles', 'Rapid și sigur', 'Imagini optimizate, certificat SSL și backup, ca site-ul să se încarce repede și să rămână online.'],
        ['edit', 'Îl actualizezi singur', 'Schimbi texte, poze sau proiecte dintr-un panou simplu, iar instruirea e inclusă.'],
    ];

    // Three sites from different fields, with the strongest screenshots,
    // each its own line; others from the portfolio stand in for any no
    // longer published.
    $picked = [
        'duo-stone' => 'Marmură prezentată într-un design elegant, care pune în valoare frumusețea materialului.',
        'dentalux-prime' => 'Clinică stomatologică, cu un design curat și profesional care pune în față confortul pacienților.',
        'phoenix-green-construct' => 'Amenajări de grădini, cu un site ușor de parcurs și lucrările la vedere.',
    ];
    $projects = Project::query()->published()->where('type', ProjectType::Presentation)
        ->whereIn('slug', array_keys($picked))
        ->with(['client:id,name', 'cover.media'])
        ->get()
        ->sortBy(fn (Project $project): int => array_search($project->slug, array_keys($picked), true))
        ->values();
    if ($projects->count() < 3) {
        $projects = $projects->concat(Project::query()->published()->where('type', ProjectType::Presentation)
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
        ['Analiză', 'Aflăm ce face firma ta, cine sunt clienții și ce vrei să facă un vizitator după ce intră pe site.'],
        ['Structură și texte', 'Stabilim paginile și ce spune fiecare, înainte de design, ca site-ul să fie clar de la prima vizită.'],
        ['Design', 'Construim un concept propriu pe brandul tău, gândit pentru telefon la fel de mult ca pentru desktop.'],
        ['Dezvoltare și SEO', 'Construim site-ul, optimizăm viteza și pregătim fiecare pagină pentru Google.'],
        ['Lansare și instruire', 'Punem site-ul online, îl legăm de Google Search Console și îți arătăm cum îl actualizezi.'],
    ];

    // What changes the price.
    $price = [
        ['Paginile', 'Numărul de pagini și câte au un design propriu.'],
        ['Textele și fotografiile', 'Le ai deja sau le pregătim împreună.'],
        ['Limbile', 'În câte limbi apare site-ul.'],
        ['Funcții în plus', 'Programări online, rezervări, hărți sau zone cu acces pe cont.'],
        ['Conținutul vechi', 'Mutarea paginilor și a textelor de pe un site vechi.'],
    ];

    $after = [
        ['Mentenanță', 'Actualizări, backup și securitate, ca site-ul să rămână rapid și sigur.', '/mentenanta-site-wordpress/'],
        ['Optimizare SEO', 'Lucrăm în continuare la SEO, ca site-ul să urce în Google.', '/seo-search-engine-optimization/'],
    ];

    // No FAQPage JSON-LD: Google no longer shows FAQ results for business
    // sites. A third item: a link under the answer.
    $questions = [
        ['Cât costă realizarea unui site de prezentare?', 'Depinde de numărul de pagini, de texte și de funcțiile în plus. După o discuție scurtă îți trimitem o ofertă clară, ca să știi de la început ce primești.'],
        ['Cât durează?', 'Depinde de câte pagini are site-ul și de cât de repede avem textele și pozele. După analiză primești un calendar pe etape.'],
        ['Pot modifica singur site-ul?', 'Da. Schimbi texte, poze și proiecte dintr-un panou simplu, iar instruirea e inclusă.'],
        ['Am deja un site. Îl puteți reface fără să pierd pozițiile din Google?', 'Da. Păstrăm sau redirecționăm adresele vechi și mutăm conținutul care merită păstrat, ca site-ul nou să pornească de unde a rămas cel vechi.'],
        ['Vă ocupați și de domeniu și email?', 'Da. Te ajutăm cu domeniul, găzduirea și adresele de email, ca totul să fie pregătit la lansare.'],
        ['Site de prezentare sau magazin online?', 'Dacă vrei să prezinți firma și să primești cereri, un site de prezentare e suficient. Dacă vrei să vinzi și să primești plăți online, ai nevoie de un magazin online.', ['Creare magazin online', '/creare-magazin-online/']],
    ];

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

{{-- The title and description held to 60 and 155 characters. --}}
<x-layouts.public
    title="Creare site de prezentare pentru firma ta"
    description="Realizare site de prezentare pe brandul tău: rapid, ușor de găsit în Google și ușor de editat. Vezi ce include, cum lucrăm și ce influențează prețul."
    crumb="Creare site de prezentare"
    :crumb-parent="['Web și e-commerce', '/web/']"
    :canonical="$canonical"
    header-theme="dark"
    footer-cta-title="Vrei un site nou pentru firma ta?"
    footer-cta-intro="Spune-ne ce face firma ta și ce vrei să afle clienții, iar noi revenim cu pașii următori și o ofertă."
    footer-cta-button="Cere o ofertă"
    :footer-w="true"
>

    {{-- ================================================== Hero --}}
    {{-- Flush: the statement carries on, on the same backdrop. --}}
    <x-site.page-hero
        label="Site de prezentare · Webis"
        :title="['Creare site de', 'prezentare.']"
        intro="Un site care spune clar ce face firma ta, se încarcă repede și îl poți actualiza singur."
        class="page-hero--led saas-hero"
        :flush="true"
        data-reveal-pace="fast"
    >
        @include('blocks.partials.ctas', [
            'primary' => ['label' => 'Cere o ofertă', 'url' => '/contact/'],
            'secondary' => ['label' => 'Vezi site-uri realizate', 'url' => '#site-uri'],
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

    {{-- ================================================== About presentation sites --}}
    @include('blocks.statement', [
        'block' => ['id' => 'despre-site-uri-de-prezentare'],
        'onBackdrop' => true,
        'data' => [
            'eyebrow' => 'Despre site-uri de prezentare',
            'statement' => "Primul loc în care\nun client nou te verifică.",
            'text' => 'Înainte să sune sau să scrie, aproape oricine caută firma în Google și se uită pe site. Realizarea unui site de prezentare pornește de la ce vrei să afle acel client: ce faci, pentru cine, cum lucrezi și cum te contactează. Restul, de la design la viteză și SEO, e construit în jurul acestor răspunsuri. Lucrăm din Iași, cu firme din toată țara.',
            'link' => ['label' => 'Ce primești', 'url' => '#ce-primesti'],
        ],
    ])

    {{-- ================================================== What you get --}}
    <section id="ce-primesti" class="chapter chapter--light sw-why" aria-labelledby="site-included-title" data-scroll-target data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="site-included-title"
                label="Ce primești"
                title="Un site complet, nu doar câteva pagini."
                highlight="nu doar câteva pagini"
            />

            <x-section.benefit-columns :items="$included" />
        </div>
    </section>

    {{-- ================================================== Sites we made --}}
    @if ($cards !== [])
        <section id="site-uri" class="chapter chapter--light web-projects" aria-labelledby="site-projects-title" data-scroll-target data-reveal-pace="fast">
            <div class="chapter__inner">
                <header class="chapter__head" data-reveal="head">
                    <div class="chapter__head-main">
                        <p class="chapter__label">Proiecte</p>
                        <h2 id="site-projects-title" class="chapter__title">Câteva <span class="whitespace-nowrap">site-uri</span> de prezentare realizate de noi.</h2>
                    </div>
                    <div class="web-projects__ways">
                        <x-ui.arrow-link :href="route('projects.index', ['tip' => 'prezentare'])">Toate site-urile de prezentare</x-ui.arrow-link>
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
        id="site-steps-title"
        title="Cum lucrăm."
        label="Cum lucrăm"
        statement="De la prima discuție la un site pe care îl actualizezi singur, în cinci pași."
        :items="$steps"
    />

    {{-- ================================================== Price --}}
    <section class="chapter chapter--light" aria-labelledby="site-price-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split">
                <header class="web-split__head" data-reveal="head">
                    <p class="chapter__label">Preț</p>
                    <h2 id="site-price-title" class="chapter__title">Cât costă un site de prezentare.</h2>
                    <p class="web-split__line">Prețul depinde de câte pagini are site-ul și de ce face fiecare. După o discuție scurtă primești o ofertă clară, cu ce intră în ea.</p>
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
    <section class="chapter chapter--light" aria-labelledby="site-after-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="site-after-title" class="chapter__title">După lansare.</h2>
                </div>
                <p class="chapter__intro">După lansare ne ocupăm în continuare de actualizări, backup și securitate, iar dacă vrei să urci mai sus în Google, putem lucra la SEO.</p>
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
    <section class="chapter chapter--light web-faq" aria-labelledby="site-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="site-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                    <p class="web-split__line">Nu găsești răspunsul? Scrie-ne și îți răspundem cât mai repede.</p>
                    <x-ui.arrow-link href="/contact/">Scrie-ne</x-ui.arrow-link>
                </header>

                <div class="web-faq__list web-split__body" data-split-body data-reveal>
                    @foreach ($questions as $item)
                        <details class="web-faq__item" data-accordion-item data-reveal-child>
                            <summary class="web-faq__question">
                                <span>{{ $item[0] }}</span>
                                <span class="web-faq__icon" aria-hidden="true"></span>
                            </summary>
                            <div class="web-faq__answer" data-accordion-case>
                                <p>{{ $item[1] }}</p>
                                @isset($item[2])
                                    <p><x-ui.arrow-link :href="$item[2][1]">{{ $item[2][0] }}</x-ui.arrow-link></p>
                                @endisset
                            </div>
                        </details>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>

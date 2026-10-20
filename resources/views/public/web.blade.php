{{--
    /web — Web și e-commerce, the first page on the site's visual language
    (css/site/system.css, css/site/web.css): each section under its
    numbered display heading (components/ui/section-head); links as the
    notched card, the rest as hairline rows with the W's plate; the
    lavender throughout, the hero's indigo for the steps.
    - the internal pages' hero with the planes;
    - 01 what suits you: the presentation site and the online shop;
    - 02 what every project gets: six rows, two columns;
    - 03 how we work: five steps along a line, on the indigo;
    - 04 projects: three from the portfolio, the way to the rest;
    - 05 after launch: maintenance and SEO;
    - 06 questions, as rows that open (js/public/project-accordion.ts);
    then the footer's closing call.

    [Bracketed] values are placeholders to fill in.
--}}
@php
    use App\Enums\ProjectType;
    use App\Models\Project;

    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

    $offers = [
        [
            'title' => 'Site de prezentare',
            'for' => 'Pentru firme care vor să fie găsite și să explice clar ce fac.',
            'points' => ['Pagini de servicii, despre, contact, blog', 'Formulare de contact și cerere de ofertă', 'Structură gândită pentru Google'],
            'href' => '/creare-site-de-prezentare',
        ],
        [
            'title' => 'Magazin online',
            'for' => 'Pentru cine vinde produse și vrea comenzi online.',
            'points' => ['Produse și categorii nelimitate', 'Plată cu cardul, ramburs sau transfer bancar', 'Facturare automată'],
            'href' => '/magazin-online',
        ],
    ];

    $included = [
        ['Design propriu.', 'Nu folosim teme cumpărate. Fiecare site are concept grafic făcut pentru el.'],
        ['Pe orice ecran.', 'Arată și funcționează corect pe telefon, tabletă și desktop.'],
        ['Pregătit pentru Google.', 'Structură, viteză și texte optimizate de la început.'],
        ['Îl administrezi singur.', 'Panou de administrare și editor vizual Elementor PRO inclus, plus instruire la predare.'],
        ['E al tău.', 'Site-ul îți aparține. Nu plătești licențe lunare către noi.'],
        ['Poate crește.', 'Blog, mai multe limbi sau un magazin adăugat mai târziu, fără să o iei de la zero.'],
    ];

    $steps = [
        ['Analiză.', 'Stabilim ce trebuie să facă site-ul pe termen scurt și lung.'],
        ['Design.', 'Propunem conceptul grafic și structura paginilor.'],
        ['Dezvoltare.', 'Construim site-ul și funcțiile de care ai nevoie: plăți, facturare, formulare.'],
        ['Testare.', 'Verificăm totul pe date reale sau demo, pe toate dispozitivele.'],
        ['Lansare și instruire.', 'Punem site-ul online și îți arătăm cum îl administrezi.'],
    ];

    $after = [
        ['Mentenanță WordPress.', 'Actualizări, backup și verificări de securitate, ca site-ul să meargă fără griji.'],
        ['SEO.', 'Optimizare continuă, ca site-ul să urce în rezultatele Google.'],
    ];

    $questions = [
        ['Cât costă un site?', 'Depinde de numărul de pagini și de funcții. După o discuție scurtă primești o ofertă detaliată.'],
        ['Cât durează?', 'Un site de prezentare, de obicei [X] săptămâni. Un magazin online, în jur de 30 de zile.'],
        ['Pot modifica singur conținutul?', 'Da. Primești acces la panoul de administrare și instruire la predare.'],
        ['Am deja un site. Îl puteți reface?', 'Da. Păstrăm adresele importante sau le redirecționăm, ca să nu pierzi pozițiile din Google.'],
        ['Pot adăuga mai târziu un magazin sau o limbă nouă?', 'Da, site-ul e construit să poată fi extins.'],
    ];

    // Projects: up to three published sites and shops from the portfolio.
    $projects = Project::query()
        ->published()
        ->whereIn('type', [ProjectType::Shop, ProjectType::Presentation])
        ->with(['client:id,name', 'cover.media'])
        ->orderBy('sort_order')
        ->latest('id')
        ->limit(3)
        ->get();

    // DUMMY DATA: until the portfolio has them — three of the live webis.ro's,
    // their screenshots straight from it. The links lead nowhere yet.
    $old = 'https://www.webis.ro/wp-content/uploads/';
    $samples = [
        ['zyanya', 'Zyanya', ProjectType::Shop, '2024/03/zyanya.ro-final-e1709281891782-1-768x510.jpg', 'Magazin online de bijuterii, cu un design minimalist și curat.'],
        ['floraria-armony', 'Florăria Armony', ProjectType::Shop, '2024/03/armony-11-e1709283660316-768x510.jpg', 'Magazin online pentru o florărie, cu un design proaspăt.'],
        ['duo-stone', 'Duo Stone', ProjectType::Presentation, '2024/07/duostoen.ro-1-768x512.jpg', 'Site de prezentare elegant, care pune în valoare marmura.'],
    ];

    // The projects as the cards take them: the portfolio's, else the samples.
    $cards = $projects->isNotEmpty()
        ? $projects->map(fn (Project $project): array => [
            'href' => route('projects.show', $project->slug),
            'client' => $project->client?->name ?? $project->title,
            'type' => $project->type,
            'image' => $project->cover?->file()?->getUrl(),
            'line' => $project->summary,
        ])->all()
        : array_map(fn (array $sample): array => [
            'href' => route('projects.show', $sample[0]),
            'client' => $sample[1],
            'type' => $sample[2],
            'image' => $old.$sample[3],
            'line' => $sample[4],
        ], $samples);

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public
    title="Site-uri și magazine online"
    description="Site-uri de prezentare și magazine online cu design propriu, pe care le administrezi singur după lansare."
    header-theme="dark"
>
    <x-site.page-hero
        label="Web și e-commerce · Webis"
        :title="['Site-uri și', 'magazine online.']"
        intro="Site-uri de prezentare și magazine online cu design propriu, pe care le administrezi singur după lansare."
        data-reveal-pace="fast"
    />

    {{-- ================================================== What suits you --}}
    <section class="chapter chapter--light web-offers" aria-labelledby="web-offers-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="01" title="Ce ți se potrivește" id="web-offers-title" />

            {{-- The site's card (the notched one, css/site/blog-posts.css):
                 the presentation site on white, the shop on the indigo. --}}
            <ul class="web-offers__list" data-reveal>
                @foreach ($offers as $offer)
                    <li data-reveal-child>
                        <a href="{{ $offer['href'] }}" @class(['post-card web-offer', 'web-offer--dark' => $loop->last])>
                            <span class="post-card__frame" aria-hidden="true">
                                <span class="post-card__bg"></span>
                                <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
                            </span>
                            <span class="web-offer__title">{{ $offer['title'] }}</span>
                            <span class="web-offer__for">{{ $offer['for'] }}</span>
                            <span class="web-offer__points">
                                @foreach ($offer['points'] as $point)
                                    <span class="web-offer__point">{!! $check !!}{{ $point }}</span>
                                @endforeach
                            </span>
                            <span class="post-card__foot">Vezi detalii</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================================================== What every project gets --}}
    <section class="chapter chapter--light" aria-labelledby="web-included-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="02" title="Ce primești în orice proiect" id="web-included-title" />

            <ul class="rows rows--two" data-reveal>
                @foreach ($included as [$title, $line])
                    <li data-reveal-child>
                        <span class="plate" aria-hidden="true"><span>{{ sprintf('%02d', $loop->iteration) }}</span></span>
                        <h3 class="rows__title">{{ $title }}</h3>
                        <p class="rows__line">{{ $line }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================================================== How we work --}}
    {{-- On the hero's indigo: the page's one dark band. --}}
    <section class="chapter chapter--dark web-steps" aria-labelledby="web-steps-title" data-nav-tone="dark" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="03" title="Cum lucrăm" id="web-steps-title" tone="dark" intro="Un magazin online durează, de obicei, în jur de 30 de zile, în funcție de complexitate." />

            <ol class="web-steps__list" data-reveal>
                @foreach ($steps as [$title, $line])
                    <li class="web-steps__step" data-reveal-child>
                        <span class="plate" aria-hidden="true"><span>{{ sprintf('%02d', $loop->iteration) }}</span></span>
                        <h3 class="web-steps__name">{{ $title }}</h3>
                        <p class="web-steps__line">{{ $line }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================================================== Projects --}}
    <section class="chapter chapter--light web-projects" aria-labelledby="web-projects-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="04" title="Proiecte" id="web-projects-title" />

            {{-- The site's card again, its screenshot at the top. --}}
            <ul class="web-projects__list" data-reveal>
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

            <div class="web-projects__foot">
                <x-ui.arrow-link :href="route('projects.index', ['tip' => 'magazin'])">Magazine online</x-ui.arrow-link>
                <x-ui.arrow-link :href="route('projects.index', ['tip' => 'prezentare'])">Site-uri de prezentare</x-ui.arrow-link>
                <x-ui.pill :href="route('projects.index')">Vezi toate proiectele</x-ui.pill>
            </div>
        </div>
    </section>

    {{-- ================================================== After launch --}}
    <section class="chapter chapter--light" aria-labelledby="web-after-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="05" title="După lansare" id="web-after-title" />

            <ul class="rows" data-reveal>
                @foreach ($after as [$title, $line])
                    <li data-reveal-child>
                        <span class="plate" aria-hidden="true"><span>{{ sprintf('%02d', $loop->iteration) }}</span></span>
                        <h3 class="rows__title">{{ $title }}</h3>
                        <p class="rows__line">{{ $line }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ================================================== Questions --}}
    <section class="chapter chapter--light web-faq" aria-labelledby="web-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-ui.section-head number="06" title="Întrebări frecvente" id="web-faq-title" />

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

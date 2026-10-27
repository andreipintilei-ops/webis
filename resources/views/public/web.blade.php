{{--
    /web — Web și e-commerce, built from the reusable sections
    (components/section/*, css/site/sections.css) and the site's chapters
    (css/site/web.css):
    - the internal pages' hero, the two offers across its foot;
    - what every project gets: white cards in two offset rows;
    - projects: the case bento, one at a time;
    - visual identity: what it includes, in the feature panel;
    - how we work: the numbered columns, on the indigo;
    - after launch, as a pair;
    - questions, the title beside them;
    then the footer's closing call.
--}}
@php
    use App\Enums\ProjectType;
    use App\Models\Project;
    use Illuminate\Support\Str;

    // Projects: up to three published sites and shops from the portfolio.
    $projects = Project::query()
        ->published()
        ->whereIn('type', [ProjectType::Shop, ProjectType::Presentation])
        ->with(['client:id,name', 'cover.media'])
        ->orderBy('sort_order')
        ->latest('id')
        ->limit(3)
        ->get();

    $cover = fn (?Project $project): ?string => $project?->cover?->file()?->getUrl();

    $offers = [
        [
            'meta' => 'Pagini, blog, formulare',
            'title' => 'Creare site de prezentare',
            'text' => 'Pentru firme care vor să fie găsite și să explice clar ce fac, cu un site pe care îl actualizează singure.',
            'href' => '/creare-site-de-prezentare/',
        ],
        [
            'meta' => 'Produse, plăți, livrare',
            'title' => 'Creare magazin online',
            'text' => 'Pentru cine vinde produse și vrea comenzi online, cu plata, livrarea și facturarea legate între ele.',
            'href' => '/creare-magazin-online/',
        ],
    ];

    // What every project gets: what it does for the visitor and the owner,
    // not the tools it is built with — each with a small visual proving it.
    // Short names, one line each; lines of about the same length, so the
    // cards read as one set.
    $benefits = [
        ['speed', 'Se încarcă repede', 'Pagini rapide pe orice conexiune, pe care Google le pune mai sus.'],
        ['search', 'Găsit în Google', 'Structură și adrese gândite de la început, ca paginile să apară în căutări.'],
        ['brand', 'Pe brandul tău', 'Culorile și tonul firmei tale, nu o temă pe care o folosesc și alții. Dacă nu ai încă un brand, îl construim împreună.'],
        ['screens', 'Pe orice ecran', 'Arată și funcționează la fel de bine pe telefon, tabletă și desktop.'],
        ['edit', 'Îl editezi singur', 'Schimbi texte, imagini și produse fără să ne suni de fiecare dată.'],
        ['own', 'E al tău', 'Site-ul, domeniul și tot conținutul îți aparțin, fără licențe lunare.'],
    ];

    // Visual identity, for a firm starting out or whose brand fell behind.
    $identity = [
        ['logo', 'Logo', 'Cu variante pentru fundal deschis și închis.'],
        ['brand', 'Culori și fonturi', 'Alese să funcționeze pe ecran și la print.'],
        ['guide', 'Reguli de folosire', 'Cum și unde apare logo-ul.'],
        ['files', 'Fișiere pentru orice folosință', 'În format vectorial pentru print și la dimensiunile potrivite pentru online.'],
    ];

    $steps = [
        ['Analiză', 'Stabilim ce trebuie să facă site-ul pe termen scurt și lung.'],
        ['Design', 'Pornim de la brandul tău sau îl construim împreună, apoi facem un concept propriu pentru site.'],
        ['Dezvoltare', 'Construim site-ul și funcțiile: plăți, facturare, formulare.'],
        ['Testare', 'Verificăm totul pe date reale, pe toate dispozitivele.'],
        ['Lansare', 'Punem site-ul online și îți arătăm cum îl actualizezi.'],
    ];

    $after = [
        ['Mentenanță și găzduire', 'Actualizări, copii de siguranță și monitorizare, ca site-ul să rămână rapid și sigur.', '/mentenanta-site-wordpress/'],
        ['SEO', 'Optimizare continuă, ca site-ul să urce și să rămână sus în rezultatele Google.', '/seo-search-engine-optimization/'],
    ];

    $questions = [
        ['Cât costă un site?', 'Depinde de numărul de pagini și de funcții. După o discuție scurtă primești o ofertă detaliată.'],
        ['Cât durează?', 'Depinde de numărul de pagini, de produse și de funcții. Termenul îl stabilim împreună și îl găsești în ofertă.'],
        ['Pot modifica singur conținutul?', 'Da. Primești acces la panoul de administrare și instruire la predare.'],
        ['Nu am încă logo. Mă puteți ajuta?', 'Da. Creăm identitatea vizuală înaintea site-ului: logo, culori și fonturi, cu fișiere pentru online și print, pe care le poți folosi pe orice material.'],
        ['Am deja un site. Îl puteți reface?', 'Da. Păstrăm adresele importante sau le redirecționăm, ca să nu pierzi pozițiile din Google.'],
        ['Pot adăuga mai târziu un magazin sau o limbă nouă?', 'Da, site-ul e construit să poată fi extins.'],
    ];

    // The bento's cases: the client, a line, the screenshot, three facts.
    $cases = $projects->map(fn (Project $project): array => [
        'client' => $project->client?->name ?? $project->title,
        'text' => Str::limit(Str::before((string) $project->summary, '. '), 150).'.',
        'href' => route('projects.show', $project->slug),
        'image' => $cover($project),
        'facts' => array_values(array_filter([
            ['Tip', $project->type?->label()],
            $project->year ? ['An', (string) $project->year] : null,
            $project->url ? ['Site', preg_replace('#^https?://(www\.)?|/$#', '', $project->url)] : null,
        ])),
    ])->all();

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public
    title="Site-uri, magazine online și identitate vizuală"
    crumb="Web și e-commerce"
    description="Site-uri de prezentare și magazine online pe brandul tău, cu SEO de la început. Dacă nu ai încă un brand, creăm și identitatea vizuală."
    header-theme="dark"
    hero-leave="overlap"
    :footer-w="true"
>
    <x-site.page-hero
        label="Web și e-commerce · Webis"
        :title="['Site-uri și', 'magazine online.']"
        intro="Site-uri și magazine online rapide, ușor de găsit în Google și făcute pe brandul tău."
        class="page-hero--led"
        data-reveal-pace="fast"
    >
        {{-- Beside the intro: the sites made for one domain each. --}}
        <x-ui.arrow-link :href="route('services.index')" tone="dark">Vezi site-uri tematice pe domenii</x-ui.arrow-link>

        {{-- The two offers close the hero, side by side on a hairline across
             its foot, as the home page's rating and logos close its own. The section
             after rises over the hero on a rounded top, as on the home page. --}}
        <x-slot:after>
            <ul class="web-hero-offers">
                @foreach ($offers as $offer)
                    <li>
                        <a href="{{ $offer['href'] }}" class="web-hero-offer">
                            <span class="web-hero-offer__meta">{{ $offer['meta'] }}</span>
                            <span class="web-hero-offer__title">{{ $offer['title'] }}</span>
                            <span class="web-hero-offer__side">
                                <span class="web-hero-offer__text">{{ $offer['text'] }}</span>
                                <span class="blog-lead__arrow" aria-hidden="true">{!! $arrow !!}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-slot:after>
    </x-site.page-hero>

    {{-- ================================================== What every project gets --}}
    <section class="chapter chapter--light" aria-labelledby="web-included-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="web-included-title"
                label="În orice proiect"
                title="Un site care lucrează pentru tine, nu doar arată bine."
                highlight="lucrează pentru tine"
            />

            <x-section.benefit-rows :items="$benefits" />
        </div>
    </section>

    {{-- ================================================== Projects --}}
    @if ($cases !== [])
        <section class="chapter chapter--light web-projects" aria-labelledby="web-projects-title" data-reveal-pace="fast">
            <div class="chapter__inner">
                <header class="chapter__head" data-reveal="head">
                    <div class="chapter__head-main">
                        <h2 id="web-projects-title" class="chapter__title">Proiecte.</h2>
                    </div>
                    <div class="web-projects__ways">
                        <x-ui.arrow-link :href="route('projects.index', ['tip' => 'magazin'])">Magazine online</x-ui.arrow-link>
                        <x-ui.arrow-link :href="route('projects.index', ['tip' => 'prezentare'])">Site-uri de prezentare</x-ui.arrow-link>
                        <x-ui.pill :href="route('projects.index')">Vezi toate proiectele</x-ui.pill>
                    </div>
                </header>

                <x-section.case-bento :cases="$cases" />
            </div>
        </section>
    @endif

    {{-- ================================================== Visual identity --}}
    <section class="chapter chapter--light" aria-labelledby="web-identity-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="web-identity-title"
                label="Identitate vizuală"
                title="Logo, culori și fonturi, înainte de prima pagină."
                highlight="înainte de prima pagină"
                intro="Dacă pornești de la zero sau brandul tău a rămas în urmă, creăm identitatea vizuală împreună cu site-ul, ca firma ta să arate la fel pe site, pe social media și pe materialele tipărite."
            />

            <x-section.feature-panel :items="$identity" />
        </div>
    </section>

    {{-- ================================================== How we work --}}
    <x-section.numbered-columns
        id="web-steps-title"
        title="Cum lucrăm."
        label="Cum lucrăm"
        statement="De la prima discuție la lansare, în cinci pași."
        :items="$steps"
    />

    {{-- ================================================== After launch --}}
    <section class="chapter chapter--light" aria-labelledby="web-after-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="web-after-title" class="chapter__title">După lansare.</h2>
                </div>
                <p class="chapter__intro">Un site are nevoie de grijă și după ce e online. Rămânem alături de tine.</p>
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
    <section class="chapter chapter--light web-faq" aria-labelledby="web-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="web-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                    <p class="web-split__line">Nu găsești răspunsul? Scrie-ne și îți răspundem în aceeași zi lucrătoare.</p>
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

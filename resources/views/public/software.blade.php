{{--
    /software-la-comanda/ — Software la comandă (Public\SoftwareController),
    on the site's visual language (css/site/system.css) and /web's spacing,
    its sections unnumbered; its own pieces in css/site/software.css.

    One dark area first, as the home page's hero and "Despre noi":
    - the internal pages' hero with the planes, flush, so they fade into
      what follows instead of being cut;
    - the home page's statement (blocks/statement), on the hero's backdrop;
    - the modules as a strip of pills, where the home page has its logos.
    Then the light panel, rising over it:
    - why made to order: a centred head, then what it changes as white
      cards in three staggered columns (components/section/benefit-columns);
    - what we build: the modules as a carousel to drag through, each its
      interface beside its name (js/public/software-reel.ts);
    - how we work, on the indigo: the delivery as a timeline lighting up
      once (js/public/software-timeline.ts);
    - what every project gets: a centred head over one white panel of
      cells (components/section/feature-panel);
    - projects, only when there are software ones;
    - questions, the title beside them as on /web (no FAQPage JSON-LD)
      from the same list;
    then the footer's closing call.
--}}
@php
    use App\Models\Project;

    $canonical = 'https://www.webis.ro/software-la-comanda/';

    $strip = [
        ['ERP', 'layers'],
        ['CRM', 'users'],
        ['Registratură', 'inbox'],
        ['Fluxuri de aprobare', 'workflow'],
        ['Rapoarte', 'chart'],
        ['e-Factura', 'receipt'],
        ['SPV', 'landmark'],
    ];

    // What an application made to order changes: short names, lines of
    // about the same length, so the cards read as one set.
    $benefits = [
        ['route', 'Fiecare știe ce are de făcut', 'Cererile ajung singure la omul potrivit, iar stadiul lor se vede oricând.'],
        ['branch', 'Cazurile speciale, rezolvate', 'Regulile și excepțiile sunt în aplicație, nu în memoria oamenilor.'],
        ['database', 'Date într-un singur loc', 'Se introduc o dată și sunt aceleași pentru toată echipa, de la birou la conducere.'],
        ['chart', 'Rapoarte gata oricând', 'Generate din datele reale, în formatul pe care îl folosești deja.'],
        ['plug', 'Legată de ce folosești deja', 'Comunică cu programele tale, ca nimic să nu mai fie copiat de mână.'],
        ['grow', 'Crește odată cu echipa', 'Adăugăm module noi peste ce există, atunci când ai nevoie de ele.'],
    ];

    // What every project gets.
    $included = [
        ['history', 'Istoric complet', 'Fiecare modificare rămâne înregistrată: cine, ce și când.'],
        ['roles', 'Acces pe roluri', 'Fiecare om vede și modifică doar ce îi e permis.'],
        ['browser', 'În browser, oriunde', 'Fără instalări, de la birou sau de pe teren, pe orice dispozitiv.'],
        ['backup', 'Backup și securitate', 'Copii de siguranță automate și acces securizat la date.'],
        ['training', 'Instruire inclusă', 'Echipa ta știe cum să folosească aplicația din prima zi.'],
        ['layers', 'Poate crește', 'Module noi se adaugă peste ce există, fără să o iei de la zero.'],
    ];

    $modules = [
        ['erp', 'ERP', 'Resurse, stocuri, achiziții și contabilitate într-un singur loc.'],
        ['crm', 'CRM', 'Clienți, oferte, contracte și istoricul comunicării.'],
        ['registratura', 'Registratură și documente', 'Intrări, ieșiri, circuitul documentelor și arhiva, cu numere de înregistrare automate.'],
        ['aprobari', 'Fluxuri de aprobare', 'Cereri, referate și aprobări pe mai multe niveluri, cu notificări la fiecare pas.'],
        ['rapoarte', 'Rapoarte și tablouri de bord', 'Situațiile de care ai nevoie, generate din date reale, fără Excel intermediar.'],
        ['integrari', 'Integrări', 'e-Factura, SPV și programele pe care le folosești deja.'],
    ];

    // The delivery, step by step: each its segment of the track (planning,
    // development, then on) and, under it, what happens then. Development's
    // segment is three short bars, the modules delivered one after another.
    $stages = [
        ['Analiză', 'plan', 'Analiză.', 'Vorbim cu oamenii care vor folosi aplicația și vedem cum lucrează acum.'],
        ['Propunere', 'plan', 'Propunere.', 'Stabilim modulele, etapele și bugetul, ca să știi de la început ce primești.'],
        ['Dezvoltare', 'build', 'Dezvoltare.', 'Livrăm modul cu modul, cu datele importate și echipa instruită la fiecare.'],
        ['Mentenanță', 'care', 'Mentenanță.', 'După lansare ne ocupăm de suport, actualizări și funcții noi.'],
    ];

    // When each step lights (in --step's): the modules' bars at 2, 3 and 4,
    // so maintenance follows the last of them.
    $at = ['plan' => null, 'build' => 2, 'care' => 5];

    // The questions, for the accordion. No FAQPage JSON-LD: Google no longer
    // shows FAQ results for business sites.
    $questions = [
        ['Cât costă o aplicație la comandă?', 'Depinde de câte module are și de cât de complexe sunt fluxurile. După analiză primești o ofertă pe etape, așa că poți începe cu partea cea mai importantă.'],
        ['Cât durează?', 'Depinde de amploare. Pentru că lucrăm pe etape, primele module funcționale ajung la tine devreme, iar restul se adaugă pe parcurs.'],
        ['Putem porni de la datele pe care le avem deja?', 'Da. Importăm datele din Excel sau din sistemul pe care îl folosești acum.'],
        ['Se poate lega de e-Factura, SPV sau alte programe?', 'Da. Integrăm aplicația cu platformele de stat și cu programele pe care le folosești deja, acolo unde acestea permit.'],
        ['Ce se întâmplă după lansare?', 'Rămânem alături de tine pentru suport, actualizări și funcții noi, pe măsură ce nevoile se schimbă.'],
    ];

    // The projects as /web's cards take them; at their addresses' slash form.
    $cards = $projects->map(fn (Project $project): array => [
        'href' => '/proiect/'.$project->slug.'/',
        'client' => $project->client?->name ?? $project->title,
        'type' => $project->type,
        'image' => $project->cover?->file()?->getUrl(),
        'line' => $project->summary,
    ])->all();

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public
    title="Software la comandă – aplicații pe procesele tale"
    description="Aplicații construite pe procesele tale: ERP, CRM, registratură, fluxuri de aprobare, rapoarte și integrări cu e-Factura și SPV."
    crumb="Software la comandă"
    :canonical="$canonical"
    header-theme="dark"
    :footer-w="true"
>

    {{-- ================================================== Hero --}}
    {{-- Flush: nothing rises over it here — the statement carries on, on
         the same backdrop — so the planes fade out at its foot. Its text
         stays put: sinking back, it would pass under the statement.
         Along its foot, where the home page's hero has its logos: the
         modules, under a hairline. A track of identical sets sliding by one
         set, forever (css/site/marquee.css); only the first is read out. --}}
    <x-site.page-hero
        label="Software la comandă · Webis"
        :title="['Software la', 'comandă.']"
        intro="Aplicații construite pe felul în care lucrează echipa ta."
        :flush="true"
        class="sw-hero"
        data-reveal-pace="fast"
    >
        <x-slot:after>
            <div class="sw-hero__modules" aria-label="Module">
                <div class="logo-marquee sw-marquee">
                    <div class="logo-marquee__track" style="--marquee-items: {{ count($strip) * 3 }}">
                        @foreach (range(1, 6) as $copy)
                            <ul class="logo-marquee__set" @if ($copy > 1) aria-hidden="true" @endif>
                                @foreach ($strip as [$label, $icon])
                                    <li class="logo-marquee__item sw-strip__item">
                                        <span class="sw-module"><x-software.icon :name="$icon" /><span class="sw-module__label">{{ $label }}</span></span>
                                    </li>
                                @endforeach
                            </ul>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-slot:after>
    </x-site.page-hero>

    {{-- ================================================== Why made to order --}}
    {{-- The home page's "Despre noi", on the hero's backdrop: the dark
         area's last section, as on the home page, its hairline the same
         distance below the modules as theirs above them. --}}
    @include('blocks.statement', [
        'block' => ['id' => 'de-ce-la-comanda'],
        'onBackdrop' => true,
        'data' => [
            'eyebrow' => 'De ce la comandă',
            'statement' => "Aplicația se adaptează\nla felul în care lucrezi,\nnu invers.",
            'text' => 'Pornim de la procesul tău așa cum e acum, cu documentele, aprobările și excepțiile lui, și îl transformăm într-o aplicație pe care echipa o folosește fără soluții ocolitoare. O livrăm pe etape, o legăm de programele pe care le ai deja și ne ocupăm de ea și după lansare.',
            'link' => ['label' => 'Cum lucrăm', 'url' => '#cum-lucram'],
        ],
    ])

    {{-- ================================================== What it changes --}}
    {{-- The light panel, rising over the dark area. What it changes, under a
         centred head, as cards in three staggered columns. --}}
    <section class="chapter chapter--light sw-why" aria-labelledby="sw-why-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="sw-why-title"
                label="Ce se schimbă"
                title="Munca de rutină o face aplicația,
                    echipa face ce contează."
                highlight="o face aplicația"
            />

            <x-section.benefit-columns :items="$benefits" />
        </div>
    </section>

    {{-- ================================================== What we build --}}
    {{-- The modules as a carousel: a panel each, its interface large beside
         its name, dragged or stepped through with the arrows and dots; a
         row that scrolls sideways by itself without the script
         (js/public/software-reel.ts). --}}
    <section class="chapter chapter--light sw-build sw-reel" aria-labelledby="sw-build-title" data-reveal-pace="fast" data-sw-reel>
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="sw-build-title" class="chapter__title">Ce construim.</h2>
                </div>
                <div class="sw-reel__aside">
                    <p class="chapter__intro">Module pe care le combinăm după felul în care lucrezi, de la unul singur la tot sistemul.</p>
                    <p class="sw-reel__count" aria-hidden="true"><span data-sw-reel-current>01</span> / {{ sprintf('%02d', count($modules)) }}</p>
                </div>
            </header>

            <div class="sw-reel__viewport" tabindex="0" role="group" aria-roledescription="carusel" aria-label="Module" data-sw-reel-viewport>
                <ol class="sw-reel__track" data-sw-reel-track>
                    @foreach ($modules as [$key, $name, $text])
                        <li class="sw-reel__item" data-sw-reel-item>
                            <article id="modul-{{ $key }}" class="sw-modcard" aria-labelledby="modul-{{ $key }}-title">
                                <div class="sw-modcard__copy">
                                    <span class="sw-modcard__no" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                                    <div>
                                        <h3 id="modul-{{ $key }}-title" class="sw-modcard__name">{{ $name }}</h3>
                                        <p class="sw-modcard__text">{{ $text }}</p>
                                    </div>
                                </div>
                                <div class="sw-modcard__screen">
                                    <x-software.mockup :variant="$key" />
                                </div>
                            </article>
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- The way through: arrows either side of a dot per module. --}}
            <div class="sw-reel__controls">
                <button type="button" class="sw-reel__step sw-reel__step--prev" aria-label="Modulul anterior" data-sw-reel-prev>{!! $arrow !!}</button>
                <div class="sw-reel__dots">
                    @foreach ($modules as [$key, $name])
                        <button type="button" class="sw-reel__dot" aria-label="{{ $name }}" aria-current="{{ $loop->first ? 'true' : 'false' }}" data-sw-reel-dot></button>
                    @endforeach
                </div>
                <button type="button" class="sw-reel__step" aria-label="Modulul următor" data-sw-reel-next>{!! $arrow !!}</button>
            </div>
        </div>
    </section>

    {{-- ================================================== How we work --}}
    {{-- On the hero's indigo. Four steps, each its segment of the track over
         its own text; the segments light up one after another, once
         (js/public/software-timeline.ts); drawn lit without the script and
         with reduced motion. The track is a picture of the steps' text, so
         it is hidden from screen readers. --}}
    <section id="cum-lucram" class="chapter chapter--dark sw-steps" aria-labelledby="sw-steps-title" data-nav-tone="dark" data-scroll-target data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <h2 id="sw-steps-title" class="chapter__title">Cum lucrăm.</h2>
                </div>
                <p class="chapter__intro">Lucrăm pe etape, așa că primele module ajung la tine devreme, nu la finalul proiectului.</p>
            </header>

            <ol class="sw-timeline" data-sw-timeline>
                {{-- Development carries the "în folosință" tag, once its last
                     module is in; the other steps keep its place empty, so
                     every title starts level. --}}
                @foreach ($stages as $index => [$label, $kind, $name, $line])
                    <li class="sw-stage sw-stage--{{ $kind }}" style="--i: {{ $at[$kind] ?? $index }}">
                        <div class="sw-seg sw-seg--{{ $kind }}" aria-hidden="true">
                            <span class="sw-seg__label">{{ $label }}</span>
                            @if ($kind === 'build')
                                <span class="sw-seg__bars">
                                    @foreach ([2, 3, 4] as $i)
                                        <span class="sw-seg__bar" style="--i: {{ $i }}"><span class="sw-seg__fill"></span></span>
                                    @endforeach
                                </span>
                                <span class="sw-seg__tag" style="--i: 4">în folosință</span>
                            @else
                                <span class="sw-seg__bar"><span class="sw-seg__fill"></span></span>
                                <span class="sw-seg__tag sw-seg__tag--none">în folosință</span>
                            @endif
                        </div>
                        <div class="sw-stage__text">
                            <h3 class="sw-stage__name">{{ $name }}</h3>
                            <p class="sw-stage__line">{{ $line }}</p>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ================================================== What every project gets --}}
    <section class="chapter chapter--light sw-gets" aria-labelledby="sw-gets-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <x-section.center-head
                id="sw-gets-title"
                label="Inclus mereu"
                title="Tot ce face o aplicație sigură și ușor de folosit, inclus din prima zi."
                highlight="inclus din prima zi"
            />

            <x-section.feature-panel :items="$included" />
        </div>
    </section>

    {{-- ================================================== Projects --}}
    {{-- Only with published software projects: otherwise nothing at all. --}}
    @if ($cards !== [])
        <section class="chapter chapter--light web-projects sw-projects" aria-labelledby="sw-projects-title" data-reveal-pace="fast">
            <div class="chapter__inner">
                <header class="chapter__head" data-reveal="head">
                    <div class="chapter__head-main">
                        <h2 id="sw-projects-title" class="chapter__title">Proiecte.</h2>
                    </div>
                </header>

                {{-- /web's projects: the site's card, its screenshot at the top. --}}
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
                    <a href="/portofoliu/?tip=software" class="sw-link">Vezi proiectele →</a>
                </div>
            </div>
        </section>
    @endif

    {{-- ================================================== Questions --}}
    {{-- /web's rows that open (js/public/project-accordion.ts), the title
         beside them, held while they scroll (js/public/split-heads.ts). --}}
    <section class="chapter chapter--light web-faq sw-faq" aria-labelledby="sw-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <div class="web-split" data-split>
                <header class="web-split__head" data-split-head data-reveal="head">
                    <h2 id="sw-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                    <p class="web-split__line">Fiecare proiect e altfel. Spune-ne cum lucrați și îți răspundem cât mai repede.</p>
                    <x-ui.arrow-link href="/contact/">Scrie-ne</x-ui.arrow-link>
                </header>

                <div class="web-faq__list web-split__body" data-split-body data-reveal data-sw-faq>
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

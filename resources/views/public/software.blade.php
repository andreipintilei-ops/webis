{{--
    /software-la-comanda/ — Software la comandă (Public\SoftwareController),
    on the site's visual language (css/site/system.css), /web's spacing and
    numbering; its own pieces in css/site/software.css.

    One dark area first, as the home page's hero and "Despre noi":
    - the internal pages' hero with the planes, flush, so they fade into
      what follows instead of being cut;
    - the home page's statement (blocks/statement), on the hero's backdrop;
    - the modules as a strip of pills, where the home page has its logos.
    Then the light panel, rising over it:
    - 01 when it makes sense: now and with an application, row by row;
    - 02 what we build: the modules as tabs beside their mock-ups, an
      accordion below 1024px (js/public/software-explorer.ts);
    - 03 how we work, on the indigo: the delivery as a timeline lighting up
      once (js/public/software-timeline.ts);
    - 04 what every project gets, as a bento grid;
    - 05 projects, only when there are software ones;
    - questions, as /web's rows that open, and as FAQPage JSON-LD from the
      same list;
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

    // Now, and with an application: the same four rows, side by side.
    $compare = [
        ['Lucrezi în Excel și pe email.', 'Datele sunt împrăștiate în fișiere și conversații, iar fiecare raport înseamnă ore de copiat și verificat.', 'Toate datele într-un singur loc.'],
        ['Hârtia încă circulă între birouri.', 'Cererile se semnează pe rând, pe hârtie, și nimeni nu știe exact unde au rămas.', 'Cereri și aprobări online, cu status vizibil.'],
        ['Ai sisteme care nu comunică între ele.', 'Aceleași date se introduc de două ori, în locuri diferite.', 'Datele se introduc o singură dată.'],
        ['Nu ai o imagine clară.', 'Ca să afli unde e o cerere sau o comandă, trebuie să întrebi pe cineva.', 'Rapoarte în timp real, fără Excel intermediar.'],
    ];

    $modules = [
        ['erp', 'ERP', 'Resurse, stocuri, achiziții și contabilitate într-un singur loc.'],
        ['crm', 'CRM', 'Clienți, oferte, contracte și istoricul comunicării.'],
        ['registratura', 'Registratură și documente', 'Intrări, ieșiri, circuitul documentelor și arhiva, cu numere de înregistrare automate.'],
        ['aprobari', 'Fluxuri de aprobare', 'Cereri, referate și aprobări pe mai multe niveluri, cu notificări la fiecare pas.'],
        ['rapoarte', 'Rapoarte și tablouri de bord', 'Situațiile de care ai nevoie, generate din date reale, fără Excel intermediar.'],
        ['integrari', 'Integrări', 'e-Factura, SPV și programele pe care le folosești deja.'],
    ];

    // The delivery: its segments (planning, the modules, then on), and the
    // five stages described under them.
    $stages = [
        ['Analiză.', 'Vorbim cu oamenii care vor folosi aplicația și înțelegem cum lucrează acum.'],
        ['Propunere.', 'Stabilim modulele, etapele și bugetul, ca să știi de la început ce primești.'],
        ['Dezvoltare pe etape.', 'Primești fiecare modul pe măsură ce e gata și îl folosești pe parcurs.'],
        ['Implementare.', 'Importăm datele existente și instruim echipa ta.'],
        ['Mentenanță.', 'Rămânem după lansare pentru suport, actualizări și funcții noi.'],
    ];

    $log = [
        ['14:02', 'Ana P. a aprobat cererea #2381'],
        ['13:47', 'Mihai R. a modificat suma'],
        ['11:15', 'Ioana D. a încărcat documentul'],
        ['10:38', 'Radu T. a trimis cererea spre aprobare'],
        ['09:54', 'Elena M. a creat cererea #2381'],
    ];

    // The questions: the accordion and the FAQPage JSON-LD both read this list.
    $questions = [
        ['Cât costă o aplicație la comandă?', 'Depinde de câte module are și de cât de complexe sunt fluxurile. După analiză primești o ofertă pe etape, așa că poți începe cu partea cea mai importantă.'],
        ['Cât durează?', 'Depinde de amploare. Pentru că lucrăm pe etape, primele module funcționale ajung la tine devreme, iar restul se adaugă pe parcurs.'],
        ['Putem porni de la datele pe care le avem deja?', 'Da. Importăm datele din Excel sau din sistemul pe care îl folosești acum.'],
        ['Se poate lega de e-Factura, SPV sau alte programe?', 'Da. Integrăm aplicația cu platformele de stat și cu programele pe care le folosești deja, acolo unde acestea permit.'],
        ['Ce se întâmplă după lansare?', 'Rămânem alături de tine pentru suport, actualizări și funcții noi, pe măsură ce nevoile se schimbă.'],
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

    // The projects as /web's cards take them; at their addresses' slash form.
    $cards = $projects->map(fn (Project $project): array => [
        'href' => '/proiect/'.$project->slug.'/',
        'client' => $project->client?->name ?? $project->title,
        'type' => $project->type,
        'image' => $project->cover?->file()?->getUrl(),
        'line' => $project->summary,
    ])->all();

    // The questions follow on in the numbering, with or without the projects.
    $faqNumber = $cards === [] ? '05' : '06';

    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<x-layouts.public
    title="Software la comandă – aplicații pentru organizații"
    description="Aplicații construite pe procesele tale: ERP, CRM, registratură, fluxuri de aprobare, rapoarte și integrări cu e-Factura și SPV."
    :canonical="$canonical"
    header-theme="dark"
>
    @push('head')
        <script type="application/ld+json">{!! json_encode($faq, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>
    @endpush

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
        intro="Aplicații construite pe felul în care lucrează organizația ta."
        :parallax="false"
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
            'statement' => "Programele gata făcute te obligă să lucrezi ca ele.\nNoi construim aplicația care lucrează ca tine.",
            'text' => 'Pornim de la procesul tău așa cum e acum, cu documentele, aprobările și excepțiile lui, și îl transformăm într-o aplicație pe care echipa o folosește fără ocolișuri. O livrăm pe etape, o legăm de programele pe care le ai deja și rămânem alături de tine după lansare.',
            'link' => ['label' => 'Cum lucrăm ↓', 'url' => '#cum-lucram'],
        ],
    ])

    {{-- ================================================== 01 When it makes sense --}}
    {{-- The light panel, rising over the dark area. Two panels whose rows
         line up: one grid, each panel's ground behind its column. Phones:
         the two pictures, then each row now followed by its counterpart. --}}
    <section class="chapter chapter--light sw-compare" aria-labelledby="sw-compare-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">01</p>
                    <h2 id="sw-compare-title" class="chapter__title">Când are sens.</h2>
                </div>
            </header>

            <div class="sw-compare__grid" data-reveal>
                <div class="sw-compare__ground sw-compare__ground--now" aria-hidden="true"></div>
                <div class="sw-compare__ground sw-compare__ground--app" aria-hidden="true"></div>

                <h3 class="sw-compare__label sw-compare__label--now">Acum</h3>
                <div class="sw-compare__art sw-compare__art--now" data-reveal-child>
                    @include('public.software.now-collage')
                </div>

                <h3 class="sw-compare__label sw-compare__label--app">Cu o aplicație</h3>
                <div class="sw-compare__art sw-compare__art--app" data-reveal-child>
                    @include('public.software.app-screen')
                </div>

                @foreach ($compare as [$title, $line, $better])
                    <div class="sw-compare__now" style="--row-at: {{ $loop->iteration + 2 }}">
                        <span class="sw-compare__mark" aria-hidden="true"><x-software.icon name="x" /></span>
                        <p><strong>{{ $title }}</strong> {{ $line }}</p>
                    </div>
                    <div class="sw-compare__app" style="--row-at: {{ $loop->iteration + 2 }}">
                        <span class="sw-compare__mark" aria-hidden="true"><x-software.icon name="check" /></span>
                        <p>{{ $better }}</p>
                    </div>
                @endforeach
            </div>

            <p class="sw-compare__foot">
                Dacă nevoia ta e comună în domeniu, un produs gata de folosit poate fi mai potrivit.
                <a href="/produse/" class="sw-link">Vezi produsele SaaS →</a>
            </p>
        </div>
    </section>

    {{-- ================================================== 02 What we build --}}
    {{-- Tabs: the modules on the left, the active one's mock-up on the
         right; an accordion below 1024px. Without the script: the first
         module, the others behind their tabs. --}}
    <section class="chapter chapter--light sw-build" aria-labelledby="sw-build-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">02</p>
                    <h2 id="sw-build-title" class="chapter__title">Ce construim.</h2>
                </div>
            </header>

            <div class="sw-explorer" data-sw-explorer data-reveal>
                <div class="sw-explorer__tabs" role="tablist" aria-label="Module" aria-orientation="vertical" data-sw-tablist>
                    @foreach ($modules as [$key, $name, $text])
                        <button type="button" role="tab" id="modul-{{ $key }}-tab" class="sw-tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="modul-{{ $key }}" @unless ($loop->first) tabindex="-1" @endunless data-sw-tab>
                            <span class="sw-tab__index" aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                            <span class="sw-tab__name">{{ $name }}</span>
                            <span class="sw-tab__icon" aria-hidden="true"></span>
                        </button>
                    @endforeach
                </div>

                <div class="sw-explorer__stage" data-sw-stage>
                    @foreach ($modules as [$key, $name, $text])
                        <div role="tabpanel" id="modul-{{ $key }}" class="sw-panel" aria-labelledby="modul-{{ $key }}-tab" tabindex="0" @unless ($loop->first) hidden @endunless data-sw-panel>
                            <div class="sw-panel__body">
                                <div class="sw-panel__copy">
                                    <h3 class="sw-panel__title">{{ $name }}</h3>
                                    <p class="sw-panel__text">{{ $text }}</p>
                                </div>
                                <x-software.mockup :variant="$key" />
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- ================================================== 03 How we work --}}
    {{-- On the hero's indigo. The segments light up one after another, once
         (js/public/software-timeline.ts); drawn lit without the script and
         with reduced motion. The track is a picture of the stages listed
         under it, so it is hidden from screen readers. --}}
    <section id="cum-lucram" class="chapter chapter--dark sw-steps" aria-labelledby="sw-steps-title" data-nav-tone="dark" data-scroll-target data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">03</p>
                    <h2 id="sw-steps-title" class="chapter__title">Cum lucrăm.</h2>
                </div>
                <p class="chapter__intro">Lucrând pe etape, primele module ajung la tine devreme, nu la finalul proiectului.</p>
            </header>

            <ol class="sw-timeline" data-sw-timeline>
                {{-- Planning: two neutral segments, each with its stage. --}}
                @foreach ([0, 1] as $index)
                    <li class="sw-stage sw-stage--plan">
                        <div class="sw-stage__track" aria-hidden="true">
                            <span class="sw-seg sw-seg--plan" style="--i: {{ $index }}">
                                <span class="sw-seg__label">{{ rtrim($stages[$index][0], '.') }}</span>
                                <span class="sw-seg__bar"><span class="sw-seg__fill"></span></span>
                            </span>
                        </div>
                        <div class="sw-stage__text" style="--at: {{ $index }}">
                            <h3 class="sw-stage__name">{{ $stages[$index][0] }}</h3>
                            <p class="sw-stage__line">{{ $stages[$index][1] }}</p>
                        </div>
                    </li>
                @endforeach

                {{-- The modules: each in use as soon as it is delivered. --}}
                <li class="sw-stage sw-stage--build">
                    <div class="sw-stage__track" aria-hidden="true">
                        @foreach ([1, 2, 3] as $module)
                            <span class="sw-seg sw-seg--module" style="--i: {{ $module + 1 }}">
                                <span class="sw-seg__label">Modul {{ $module }}</span>
                                <span class="sw-seg__bar"><span class="sw-seg__fill"></span></span>
                                <span class="sw-seg__tag">în folosință</span>
                            </span>
                        @endforeach
                    </div>
                    {{-- Phones: the tags over the two stages, the track being a line. --}}
                    <p class="sw-stage__chips" aria-hidden="true">
                        @foreach ([1, 2, 3] as $module)
                            <span>Modul {{ $module }} · în folosință</span>
                        @endforeach
                    </p>
                    {{-- Development lights with the first module, implementation with the last. --}}
                    @foreach ([2 => 2, 3 => 4] as $index => $at)
                        <div class="sw-stage__text" style="--at: {{ $at }}">
                            <h3 class="sw-stage__name">{{ $stages[$index][0] }}</h3>
                            <p class="sw-stage__line">{{ $stages[$index][1] }}</p>
                        </div>
                    @endforeach
                </li>

                {{-- And on: maintenance fades out to the right. --}}
                <li class="sw-stage sw-stage--care">
                    <div class="sw-stage__track" aria-hidden="true">
                        <span class="sw-seg sw-seg--care" style="--i: 5">
                            <span class="sw-seg__label">Mentenanță</span>
                            <span class="sw-seg__bar"><span class="sw-seg__fill"></span></span>
                        </span>
                    </div>
                    <div class="sw-stage__text" style="--at: 5">
                        <h3 class="sw-stage__name">{{ $stages[4][0] }}</h3>
                        <p class="sw-stage__line">{{ $stages[4][1] }}</p>
                    </div>
                </li>
            </ol>
        </div>
    </section>

    {{-- ================================================== 04 What every project gets --}}
    <section class="chapter chapter--light sw-gets" aria-labelledby="sw-gets-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">04</p>
                    <h2 id="sw-gets-title" class="chapter__title">Ce primești în orice proiect.</h2>
                </div>
            </header>

            <ul class="sw-bento" data-reveal>
                <li class="sw-card sw-card--log" data-reveal-child>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">Istoric complet.</h3>
                        <p class="sw-card__text">Fiecare modificare rămâne înregistrată: cine, ce și când.</p>
                    </div>
                    <div class="sw-log" aria-hidden="true">
                        <p class="sw-log__head"><span>Cererea #2381</span><span>Istoric</span></p>
                        <ol class="sw-log__lines">
                            @foreach ($log as [$time, $entry])
                                <li><time>{{ $time }}</time><span>·</span><span>{{ $entry }}</span></li>
                            @endforeach
                        </ol>
                    </div>
                </li>

                <li class="sw-card sw-card--roles" data-reveal-child>
                    <div class="sw-roles" aria-hidden="true">
                        <span class="sw-role sw-role--open"><x-software.icon name="lock-open" />Administrator</span>
                        <span class="sw-role sw-role--open"><x-software.icon name="lock-open" />Aprobator</span>
                        <span class="sw-role"><x-software.icon name="lock" />Utilizator</span>
                    </div>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">Acces pe roluri.</h3>
                        <p class="sw-card__text">Fiecare utilizator vede și modifică doar ce îi e permis.</p>
                    </div>
                </li>

                <li class="sw-card sw-card--devices" data-reveal-child>
                    <div class="sw-devices" aria-hidden="true">
                        <span class="sw-devices__laptop">
                            <span class="sw-devices__screen">@include('public.software.device-screen')</span>
                            <span class="sw-devices__base"></span>
                        </span>
                        <span class="sw-devices__phone">
                            <span class="sw-devices__screen">@include('public.software.device-screen')</span>
                        </span>
                    </div>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">În browser, pe orice dispozitiv.</h3>
                        <p class="sw-card__text">Fără instalări, de la birou sau de pe teren.</p>
                    </div>
                </li>

                <li class="sw-card sw-card--backup" data-reveal-child>
                    <p class="sw-backup" aria-hidden="true"><span class="sw-backup__dot"></span>Ultimul backup: azi, 03:00</p>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">Backup și securitate.</h3>
                        <p class="sw-card__text">Copii de siguranță automate și acces securizat.</p>
                    </div>
                </li>

                <li class="sw-card sw-card--plain" data-reveal-child>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">Documentație și instruire.</h3>
                        <p class="sw-card__text">Echipa ta știe cum să folosească aplicația de la prima zi.</p>
                    </div>
                </li>

                <li class="sw-card sw-card--plain" data-reveal-child>
                    <div class="sw-card__copy">
                        <h3 class="sw-card__title">Poate crește.</h3>
                        <p class="sw-card__text">Module noi se adaugă peste ce există, fără să o iei de la zero.</p>
                    </div>
                </li>
            </ul>
        </div>
    </section>

    {{-- ================================================== 05 Projects --}}
    {{-- Only with published software projects: otherwise nothing at all. --}}
    @if ($cards !== [])
        <section class="chapter chapter--light web-projects sw-projects" aria-labelledby="sw-projects-title" data-reveal-pace="fast">
            <div class="chapter__inner">
                <header class="chapter__head" data-reveal="head">
                    <div class="chapter__head-main">
                        <p class="chapter__label">05</p>
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
    {{-- /web's rows that open (js/public/project-accordion.ts). --}}
    <section class="chapter chapter--light web-faq sw-faq" aria-labelledby="sw-faq-title" data-reveal-pace="fast">
        <div class="chapter__inner">
            <header class="chapter__head" data-reveal="head">
                <div class="chapter__head-main">
                    <p class="chapter__label">{{ $faqNumber }}</p>
                    <h2 id="sw-faq-title" class="chapter__title">Întrebări frecvente.</h2>
                </div>
            </header>

            <div class="web-faq__list" data-reveal data-sw-faq>
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

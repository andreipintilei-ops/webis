{{--
    The three services as full-width chapters on the home page, after the
    small service cards: 01 Software la comandă (white), 02 Produse SaaS
    (dark), 03 Web și e-commerce (lavender); the reviews follow as their own
    section (components/site/reviews). Each: a head (label, title,
    intro), subsections (a mono label over a hairline: what we build, the
    projects or products), then a CTA row. Shared pieces:
    components/site/chapter/*; css/site/service-chapters.css; each
    part comes in once, on entry (js/public/reveals.ts).

    Projects, metrics and screenshots are DUMMY DATA or TODO placeholders,
    marked where they are.
--}}
@php
    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';

    // TODO: the number of clinics using SmileSoft — shown only once set.
    $smilesoftClinics = null;
@endphp

{{-- ================================================================== 01 --}}
<section id="software-la-comanda" class="chapter chapter--light" aria-labelledby="chapter-1-title">
    <div class="chapter__inner">
        <header class="chapter__head" data-reveal="head">
            <div class="chapter__head-main">
                <p class="chapter__label">01 · Software la comandă</p>
                <h2 id="chapter-1-title" class="chapter__title">Sisteme <x-ui.title-pill variant="software" /> construite de la zero.</h2>
            </div>
            <p class="chapter__intro">Când procesele nu se potrivesc cu un produs de pe piață, construim sistemul pornind de la felul în care lucrezi deja. Îl dezvoltăm, îl implementăm și rămânem responsabili de el după lansare.</p>
        </header>

        <x-site.chapter.subsection label="Ce construim">
            <x-site.chapter.cells :cells="[
                ['name' => 'ERP', 'text' => 'Resurse, stocuri, achiziții și contabilitate într-un singur sistem, în loc de cinci programe care nu comunică.', 'example' => 'companii de producție și distribuție.'],
                ['name' => 'CRM', 'text' => 'Clienți, oferte, contracte și istoricul comunicării, în același loc.', 'example' => 'echipe de vânzări și servicii.'],
                ['name' => 'Registratură electronică', 'text' => 'Documente înregistrate, repartizate și urmărite până la soluționare, cu termene și notificări.', 'example' => 'primării și consilii județene.'],
                ['name' => 'Proiecte și finanțări', 'text' => 'Bugete, deconturi, achiziții și rapoarte pentru proiecte cu finanțare.', 'example' => 'universități și institute de cercetare.'],
                ['name' => 'Portaluri online', 'text' => 'Cereri, plăți și urmărirea lor online, pentru cetățeni sau clienți.', 'example' => 'servicii publice digitale.'],
                ['name' => 'Integrări', 'text' => 'Legăm sistemele noi de cele existente: e-Factura, SPV, contabilitate, platformele partenerilor.', 'example' => 'orice sistem care trebuie să vorbească cu altele.'],
            ]" />
        </x-site.chapter.subsection>

        <x-site.chapter.subsection label="Proiecte">
            <!-- DUMMY DATA: replace with approved projects before launch. -->
            <ul class="chapter-projects">
                @foreach ([
                    ['Universitate', 'university', 'Platformă de gestiune a proiectelor de cercetare', 'Proiecte, finanțări, deconturi și rapoarte într-un singur loc.', '240', 'Proiecte gestionate'],
                    ['Instituție publică', 'institution', 'Registru electronic de documente', 'Înregistrare, repartizare și urmărire, fără registre pe hârtie.', '3.500', 'Documente pe lună'],
                    ['Companie', 'company', 'Aplicație de gestiune a comenzilor', 'Vânzări, depozit și contabilitate, legate între ele.', '2×', 'Procesare mai rapidă'],
                ] as [$type, $variant, $title, $line, $number, $label])
                    <li style="--i: {{ $loop->index }}" data-reveal-child>
                        {{-- TODO: the project's own page. --}}
                        <a href="{{ route('projects.index') }}" class="project-card">
                            {{-- TODO: the real (anonymised) screenshot. --}}
                            <span class="project-card__frame" aria-hidden="true">
                                <x-site.project-interface :variant="$variant" />
                            </span>
                            <span class="chapter-chip">{{ $type }}</span>
                            <span class="project-card__title">{{ $title }}<span class="project-card__arrow">{!! $arrow !!}</span></span>
                            <span class="project-card__line">{{ $line }}</span>
                            <span class="chapter-metric"><b>{{ $number }}</b><span>{{ $label }}</span></span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-site.chapter.subsection>

        <div class="chapter-cta" data-reveal>
            <x-ui.pill href="/contact">Discută un proiect</x-ui.pill>
            <x-ui.arrow-link href="/software-la-comanda">Software la comandă</x-ui.arrow-link>
        </div>
    </div>
</section>

{{-- ================================================================== 02 --}}
<section id="produse-saas" class="chapter chapter--dark" aria-labelledby="chapter-2-title" data-nav-tone="dark">
    <div class="chapter__inner">
        <header class="chapter__head" data-reveal="head">
            <div class="chapter__head-main">
                <p class="chapter__label">02 · Produse SaaS</p>
                <h2 id="chapter-2-title" class="chapter__title">Produse <x-ui.title-pill variant="saas" /> gata de folosit.</h2>
            </div>
            <p class="chapter__intro">Pentru procesele comune unui domeniu nu are sens să construiești de la zero. Produsele noastre se activează în câteva zile, se plătesc lunar și le actualizăm continuu.</p>
        </header>

        <x-site.chapter.subsection label="Produse">
            <div class="chapter-products">
                <article class="product-panel product-panel--feature" data-reveal-child>
                    <div class="product-panel__text">
                        {{-- TODO: the SmileSoft logo (white). --}}
                        <h4 class="product-panel__name">SmileSoft</h4>
                        <p class="product-panel__for">Pentru clinici stomatologice.</p>
                        <ul class="product-panel__checks">
                            @foreach (['Programări și confirmări automate', 'Fișe și planuri de tratament', 'Încasări și comisioane', 'Aplicația pacientului'] as $feature)
                                <li>{!! $check !!}{{ $feature }}</li>
                            @endforeach
                        </ul>
                        @if ($smilesoftClinics)
                            <p class="chapter-metric"><b>{{ $smilesoftClinics }}</b><span>Clinici</span></p>
                        @endif
                        <x-ui.arrow-link href="https://smilesoft.ro" tone="dark" target="_blank" rel="noopener" class="product-panel__link">smilesoft.ro</x-ui.arrow-link>
                    </div>

                    {{-- TODO: the real screenshots (the Programări calendar, the patient app). --}}
                    <div class="product-devices" aria-hidden="true">
                        <div class="product-devices__browser">
                            <span class="product-devices__bar"><i></i><i></i><i></i><b>Programări</b></span>
                            <div class="calendar">
                                <span class="calendar__corner"></span>
                                @foreach (['Lun 10', 'Mar 11', 'Mie 12', 'Joi 13', 'Vin 14'] as $day)
                                    <span class="calendar__day">{{ $day }}</span>
                                @endforeach
                                @foreach (['09:00', '10:00', '11:00', '12:00', '13:00'] as $time)
                                    <span class="calendar__time" style="grid-row: {{ $loop->iteration + 1 }}">{{ $time }}</span>
                                @endforeach
                                @foreach ([[2, 2, 'Control', 'Dr. A. M.'], [3, 3, 'Igienizare', 'Dr. I. P.'], [4, 2, 'Tratament', 'Dr. A. M.'], [6, 5, 'Consult', 'Dr. E. C.']] as [$row, $col, $what, $who])
                                    <span class="calendar__slot" style="grid-row: {{ $row }} / span 1; grid-column: {{ $col }}"><b>{{ $what }}</b>{{ $who }}</span>
                                @endforeach
                            </div>
                        </div>
                        <div class="product-devices__phone">
                            <span class="product-devices__notch"></span>
                            <span class="product-devices__app-title">Programările mele</span>
                            @foreach ([['Mâine', '10:30', 'Control'], ['12.11', '14:00', 'Igienizare']] as [$day, $time, $what])
                                <span class="product-devices__slot"><b>{{ $day }} · {{ $time }}</b>{{ $what }}</span>
                            @endforeach
                        </div>
                    </div>
                </article>

                {{-- The smaller products, side by side below. TODO: their logos and real screenshots. --}}
                @foreach ([
                    [
                        'name' => 'RIMS',
                        'for' => 'Pentru universități și institute de cercetare.',
                        'checks' => ['Proiecte, bugete și termene într-un singur loc', 'Deconturi și achiziții urmărite pe fiecare proiect', 'Rapoarte pentru finanțatori, generate automat'],
                        'link' => ['label' => 'Cere o prezentare', 'href' => '/contact', 'external' => false],
                        'screen' => 'Proiecte de cercetare',
                        'rows' => [['Materiale avansate', 72, '72% din buget'], ['Energie regenerabilă', 45, '45% din buget'], ['Digitalizare în sănătate', 88, '88% din buget']],
                    ],
                    [
                        'name' => 'Bugetare participativă',
                        'for' => 'Pentru primării și administrații publice.',
                        'checks' => ['Validarea automată a propunerilor', 'Proiectele pe hartă', 'Notificări automate pentru cetățeni'],
                        'link' => ['label' => 'bugetare.ro', 'href' => 'https://bugetare.ro', 'external' => true],
                        'screen' => 'Proiecte propuse',
                        'rows' => [['Parc de cartier', 86, '1.284 voturi'], ['Piste pentru biciclete', 64, '976 voturi'], ['Loc de joacă', 42, '642 voturi']],
                    ],
                ] as $product)
                    <article class="product-panel product-panel--small" data-reveal-child>
                        <div class="product-panel__text">
                            <h4 class="product-panel__name">{{ $product['name'] }}</h4>
                            <p class="product-panel__for">{{ $product['for'] }}</p>
                            <ul class="product-panel__checks">
                                @foreach ($product['checks'] as $feature)
                                    <li>{!! $check !!}{{ $feature }}</li>
                                @endforeach
                            </ul>
                            @if ($product['link']['external'])
                                <x-ui.arrow-link :href="$product['link']['href']" tone="dark" target="_blank" rel="noopener" class="product-panel__link">{{ $product['link']['label'] }}</x-ui.arrow-link>
                            @else
                                <x-ui.arrow-link :href="$product['link']['href']" tone="dark" class="product-panel__link">{{ $product['link']['label'] }}</x-ui.arrow-link>
                            @endif
                        </div>

                        {{-- An illustration of the product (DUMMY DATA), cut by the panel's foot: rows with their progress. --}}
                        <div class="product-preview" aria-hidden="true">
                            <span class="product-devices__bar"><i></i><i></i><i></i><b>{{ $product['screen'] }}</b></span>
                            <ul class="product-preview__list">
                                @foreach ($product['rows'] as [$title, $share, $label])
                                    <li class="product-preview__row"><b>{{ $title }}</b><span class="product-preview__bar" style="--share: {{ $share }}%"></span><span>{{ $label }}</span></li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                @endforeach
            </div>
        </x-site.chapter.subsection>

        <x-site.chapter.subsection label="Ce include orice abonament">
            <x-site.chapter.cells :cells="[
                ['name' => 'Instruire inclusă', 'text' => 'Echipa ta învață să-l folosească din prima săptămână.'],
                ['name' => 'Fără contract pe termen lung', 'text' => 'Plătești lunar și poți renunța oricând.'],
                ['name' => 'Pe orice dispozitiv', 'text' => 'Calculator, tabletă sau telefon, fără instalare.'],
            ]" />
        </x-site.chapter.subsection>

        <div class="chapter-cta" data-reveal>
            <x-ui.pill href="https://smilesoft.ro" tone="dark" target="_blank" rel="noopener">smilesoft.ro</x-ui.pill>
            <x-ui.arrow-link href="/produse" tone="dark">Toate produsele</x-ui.arrow-link>
        </div>
    </div>
</section>

{{-- ================================================================== 03 --}}
<section id="web-si-e-commerce" class="chapter chapter--lavender" aria-labelledby="chapter-3-title">
    <div class="chapter__inner">
        <header class="chapter__head" data-reveal="head">
            <div class="chapter__head-main">
                <p class="chapter__label">03 · Web și e-commerce</p>
                <h2 id="chapter-3-title" class="chapter__title">Site-uri <x-ui.title-pill variant="web" /> și magazine online.</h2>
            </div>
            <p class="chapter__intro">De aici am pornit în 2016. Construim site-uri rapide, ușor de administrat și optimizate pentru căutare, și le întreținem după lansare.</p>
        </header>

        <x-site.chapter.subsection label="Ce construim">
            {{-- TODO: the real site and shop (from the webis.ro portfolio), their screenshots and pages. --}}
            <!-- DUMMY DATA: the two projects, until approved ones replace them. -->
            <x-site.explorer id="explorer-web" :items="[
                ['name' => 'Site de prezentare', 'text' => 'Un site clar despre ce faci, ușor de actualizat de tine.',
                    'link' => ['label' => 'Despre site-urile de prezentare', 'href' => '/creare-site-iasi'],
                    'project' => ['type' => 'Site de prezentare', 'visual' => 'site', 'title' => 'zyanya.ro', 'line' => 'Un site de prezentare pe care clientul îl actualizează singur.', 'href' => route('projects.index')]],
                ['name' => 'Magazin online', 'text' => 'Catalog, plăți, livrare și facturare, legate între ele.',
                    'link' => ['label' => 'Despre magazinele online', 'href' => '/creare-magazin-online'],
                    'project' => ['type' => 'Magazin online', 'visual' => 'site', 'title' => 'Nira Mob Design', 'line' => 'Catalogul de mobilier, comenzile și plățile, într-un singur magazin.', 'href' => route('projects.index')]],
                ['name' => 'Mentenanță și găzduire', 'text' => 'Actualizări, backup și securitate, fără să te ocupi tu.',
                    'link' => ['label' => 'Despre mentenanță', 'href' => '/mentenanta'],
                    'modules' => ['Actualizări și backup', 'Monitorizare', 'Securitate']],
            ]" />
        </x-site.chapter.subsection>

        <div class="chapter-cta" data-reveal>
            <x-ui.pill href="/contact">Discută despre site</x-ui.pill>
            <x-ui.arrow-link href="/web">Web și e-commerce</x-ui.arrow-link>
        </div>
    </div>
</section>

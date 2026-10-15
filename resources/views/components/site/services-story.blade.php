{{--
    The three services as one pinned story (trial on /clienti;
    js/public/services-story.ts, css/site/variant-clienti.css): one Webis
    object — four soft triangular fins crossing at a shared centre — that
    changes state with the scroll while the section holds still: open and
    architectural for 01, a closed shell for 02 (the ground turning indigo),
    flat and wide for 03. Each chapter's text takes its turn on the left;
    the object, its orbit, the screens it frames and its callouts sit on the
    right.

    Without the script (or with it failing), the chapters simply follow each
    other, as normal content. Products and screenshots are placeholders.
--}}
@php
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<section class="chapter services-story" aria-label="Ce facem" data-story>
    <div class="services-story__stage">
        {{-- The object (Three.js) and what is drawn on it (HTML). --}}
        <div class="services-story__canvas" aria-hidden="true" data-story-canvas></div>

        <div class="services-story__overlay" aria-hidden="true">
            {{-- The intro: an orbit around the object, the three chapters on it. --}}
            <div class="story-ring" data-story-ring>
                <svg viewBox="0 0 200 200" fill="none">
                    <circle cx="100" cy="100" r="99.5" />
                    <circle cx="100" cy="100" r="78" class="story-ring__inner" />
                    <path d="M58 10 Q 74 14 78 30" />
                    <path d="M186 104 Q 178 76 162 72" />
                    <path d="M150 172 Q 168 160 176 146" />
                    <circle cx="58" cy="10" r="1.6" class="story-ring__dot" />
                    <circle cx="78" cy="30" r="1.2" class="story-ring__dot" />
                    <circle cx="186" cy="104" r="1.6" class="story-ring__dot" />
                    <circle cx="150" cy="172" r="1.6" class="story-ring__dot" />
                </svg>
                <span class="story-ring__label story-ring__label--1"><b>01</b>Software<br>la comandă</span>
                <span class="story-ring__label story-ring__label--2"><b>02</b>Produse<br>SaaS</span>
                <span class="story-ring__label story-ring__label--3"><b>03</b>Web și<br>e-commerce</span>
            </div>

            {{-- 01: what the system is made of, pointed at on the object. --}}
            @foreach (['Module', 'Integrări', 'Date și rapoarte'] as $callout)
                <span @class(['story-callout', 'story-callout--left' => $loop->index === 1]) data-story-callout="{{ $loop->index }}"><i></i>{{ $callout }}</span>
            @endforeach

            {{-- 02: the product, inside the shell. TODO: the real SmileSoft screenshot. --}}
            <div class="story-screen story-screen--app" data-story-screen="saas">
                <span class="story-screen__bar"><i></i><i></i><i></i><b>SmileSoft · Programări</b></span>
                <span class="story-screen__calendar">
                    @foreach ([[1, 1, 2], [2, 2, 1], [3, 1, 3], [1, 3, 2], [3, 3, 1], [2, 4, 2]] as [$col, $row, $span])
                        <i style="grid-column: {{ $col }}; grid-row: {{ $row }} / span {{ $span }}"></i>
                    @endforeach
                </span>
            </div>

            {{-- 03: a site and a shop, on the two front slabs. TODO: real screenshots. --}}
            @foreach (['site', 'shop'] as $kind)
                <div class="story-screen story-screen--{{ $kind }}" data-story-screen="{{ $kind }}">
                    <span class="explorer-project__site"><span class="explorer-project__bar"><i></i><i></i><i></i></span><span class="explorer-project__page"><span></span><i></i><i></i><b></b></span></span>
                </div>
            @endforeach
        </div>

        <div class="services-story__inner">
            {{-- ======================================================== 01 --}}
            <article id="software-la-comanda" class="story-panel story-panel--light" data-story-panel aria-labelledby="story-1-title">
                <p class="chapter__label">01 · Software la comandă</p>
                <h2 id="story-1-title" class="chapter__title">Sisteme <x-ui.title-pill variant="software" /> construite de la zero.</h2>
                <p class="story-panel__intro">Când procesele nu se potrivesc cu un produs de pe piață, construim sistemul pornind de la felul în care lucrezi deja, și rămânem responsabili de el după lansare.</p>
                <ul class="story-panel__chips">
                    @foreach (['ERP', 'CRM', 'Registratură electronică', 'Proiecte și finanțări', 'Portaluri online', 'Integrări'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <div class="chapter-cta story-panel__cta">
                    <x-ui.pill href="/contact">Discută un proiect</x-ui.pill>
                    <x-ui.arrow-link href="/software-la-comanda">Software la comandă</x-ui.arrow-link>
                </div>
            </article>

            {{-- ======================================================== 02 --}}
            <article id="produse-saas" class="story-panel story-panel--dark" data-story-panel aria-labelledby="story-2-title">
                <p class="chapter__label">02 · Produse SaaS</p>
                <h2 id="story-2-title" class="chapter__title">Produse <x-ui.title-pill variant="saas" /> gata de folosit.</h2>
                <p class="story-panel__intro">Pentru procesele comune unui domeniu nu are sens să construiești de la zero. Produsele noastre se activează în câteva zile, se plătesc lunar și le actualizăm continuu.</p>
                <ul class="story-panel__products">
                    @foreach ([
                        ['SmileSoft', 'Clinici stomatologice', 'https://smilesoft.ro'],
                        ['RIMS', 'Universități și institute de cercetare', '/contact'],
                        ['Bugetare participativă', 'Primării', 'https://bugetare.ro'],
                    ] as [$name, $for, $href])
                        <li>
                            <a href="{{ $href }}" @if (str_starts_with($href, 'http')) target="_blank" rel="noopener" @endif>
                                <b>{{ $name }}</b><span>{{ $for }}</span><span class="story-panel__go" aria-hidden="true">{!! $arrow !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="chapter-cta story-panel__cta">
                    <x-ui.pill href="/contact" tone="dark">Cere o prezentare</x-ui.pill>
                    <x-ui.arrow-link href="/produse" tone="dark">Toate produsele</x-ui.arrow-link>
                </div>
            </article>

            {{-- ======================================================== 03 --}}
            <article id="web-si-e-commerce" class="story-panel story-panel--light" data-story-panel aria-labelledby="story-3-title">
                <p class="chapter__label">03 · Web și e-commerce</p>
                <h2 id="story-3-title" class="chapter__title">Site-uri <x-ui.title-pill variant="web" /> și magazine online.</h2>
                <p class="story-panel__intro">De aici am pornit în 2016. Construim site-uri rapide și ușor de administrat, și le întreținem după lansare.</p>
                <ul class="story-panel__chips">
                    @foreach (['Site de prezentare', 'Magazin online', 'Mentenanță și găzduire'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
                <div class="chapter-cta story-panel__cta">
                    <x-ui.pill href="/contact">Discută despre site</x-ui.pill>
                    <x-ui.arrow-link href="/web">Web și e-commerce</x-ui.arrow-link>
                </div>
            </article>
        </div>
    </div>
</section>

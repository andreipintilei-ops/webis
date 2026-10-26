{{--
    The three services as one stage, built around the Shapeshifter (trial on
    js/public/shapeshifter.ts, css/site/variant-stage.css): one
    lavender ground for all three, each chapter a column of text on one half,
    the other half kept for the object — five thin, matte plates that take a
    form per chapter, each form an abstract read of what that chapter says:
    01 on the left (the object right), 02 on the right (the object left), 03
    on the left again — or, with `side="right"`, the object on the right
    in all three, the text always on the left. After the three, a purple panel the plates fly into
    and stand in as a W, under a closing title. On phones the object takes a
    box above each chapter's text instead (`data-stage-slot`). Pointing at an
--}}
@props(['side' => null])

@php
    // Which half each chapter gives the object: right, left, right — or one
    // side throughout (the trial at /acasa-3d-dreapta).
    $sides = $side === 'right' ? ['right', 'right', 'right'] : ['right', 'left', 'right'];

    // Lucide icons.
    $lucide = [
        'database' => '<ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14a9 3 0 0 0 18 0V5"/><path d="M3 12a9 3 0 0 0 18 0"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'file-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
        'globe' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        'link' => '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
        'settings' => '<path d="m9.5 3-.5 2-2 1-2-.6L3 9l1.5 1.5v3L3 15l2 3.6 2-.6 2 1 .5 2h5l.5-2 2-1 2 .6 2-3.6-1.5-1.5v-3L21 9l-2-3.6-2 .6-2-1-.5-2z"/><circle cx="12" cy="12" r="3"/>',
        'headset' => '<path d="M3 14v-3a9 9 0 0 1 18 0v3"/><rect x="3" y="11" width="4" height="8" rx="2"/><rect x="17" y="11" width="4" height="8" rx="2"/><path d="M21 17v2a3 3 0 0 1-3 3h-4"/><path d="M10 22h4"/>',
        'smile' => '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" x2="9.01" y1="9" y2="9"/><line x1="15" x2="15.01" y1="9" y2="9"/>',
        'graduation-cap' => '<path d="M21.42 10.922a1 1 0 0 0-.019-1.838L12.83 5.18a2 2 0 0 0-1.66 0L2.6 9.08a1 1 0 0 0 0 1.832l8.57 3.908a2 2 0 0 0 1.66 0z"/><path d="M22 10v6"/><path d="M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>',
        'landmark' => '<line x1="3" x2="21" y1="22" y2="22"/><line x1="6" x2="6" y1="18" y2="11"/><line x1="10" x2="10" y1="18" y2="11"/><line x1="14" x2="14" y1="18" y2="11"/><line x1="18" x2="18" y1="18" y2="11"/><polygon points="12 2 20 7 4 7"/>',
        'monitor' => '<rect width="20" height="14" x="2" y="3" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/>',
        'shopping-bag' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
    ];
    $icon = fn (string $name) => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$lucide[$name].'</svg>';
    $check = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<section @class(['stage chapter chapter--light', 'stage--one-side' => $side === 'right']) aria-label="Ce facem" data-stage>
    <div class="chapter__inner">

        {{-- ============================================================ 01 --}}
        <article id="software-la-comanda" class="stage-chapter" data-stage-chapter data-side="{{ $sides[0] }}" aria-labelledby="chapter-1-title">
            <div class="stage-chapter__slot" data-stage-slot aria-hidden="true"></div>

            <div class="stage-chapter__body stage-software">
                <header class="stage-chapter__head" data-reveal="head">
                    <p class="chapter__label">01 · Software la comandă</p>
                    <h2 id="chapter-1-title" class="chapter__title">Sisteme <x-ui.title-pill variant="software" /><br>construite de la zero.</h2>
                    <p class="stage-chapter__intro">Transformăm procesele complexe în sisteme simple, adaptate exact modului în care lucrează organizația ta, pentru mai multă eficiență și control.</p>
                </header>

                <div class="chapter-cta stage-software__actions" data-reveal>
                    <x-ui.pill href="/contact/">Discută un proiect</x-ui.pill>
                    <x-ui.arrow-link href="/software-la-comanda/">Află mai multe</x-ui.arrow-link>
                </div>

                <ul class="stage-items stage-software__items" data-reveal>
                    @foreach ([
                        ['database', 'ERP', 'Resurse, stocuri și contabilitate.'],
                        ['users', 'CRM', 'Clienți, oferte și comunicare.'],
                        ['file-text', 'Registratură', 'Documente și fluxuri interne.'],
                        ['link', 'Integrări', 'e-Factura, SPV și platforme.'],
                    ] as [$glyph, $name, $text])
                        <li class="stage-item" data-reveal-child>
                            <span class="stage-item__icon">{!! $icon($glyph) !!}</span>
                            <span class="stage-item__name">{{ $name }}</span>
                            <span class="stage-item__text">{{ $text }}</span>
                        </li>
                    @endforeach
                </ul>
                <ul class="stage-software__benefits" data-reveal>
                    @foreach ([
                        ['settings', 'Implementare etapizată', 'De la analiză la lansare.'],
                        ['headset', 'Suport după lansare', 'Echipa noastră rămâne alături de tine.'],
                    ] as [$glyph, $name, $text])
                        <li class="stage-software__benefit" data-reveal-child>
                            <span class="stage-software__benefit-icon">{!! $icon($glyph) !!}</span>
                            <div>
                                <h3>{{ $name }}</h3>
                                <p>{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </article>

        {{-- ============================================================ 02 --}}
        <article id="produse-saas" class="stage-chapter" data-stage-chapter data-side="{{ $sides[1] }}" aria-labelledby="chapter-2-title">
            <div class="stage-chapter__slot" data-stage-slot aria-hidden="true"></div>

            <div class="stage-chapter__body">
                <header class="stage-chapter__head" data-reveal="head">
                    <p class="chapter__label">02 · Produse SaaS</p>
                    <h2 id="chapter-2-title" class="chapter__title">Produse <x-ui.title-pill variant="saas" /> gata de folosit.</h2>
                    <p class="stage-chapter__intro">Pentru procesele comune unui domeniu nu are sens să construiești de la zero. Produsele noastre se pun în funcțiune rapid, se plătesc lunar și le actualizăm continuu.</p>
                </header>

                {{-- TODO: the products' logos. --}}
                <ul class="stage-products" data-reveal>
                    @foreach ([
                        ['smile', 'SmileSoft', 'Programări, tratamente și încasări pentru clinici stomatologice.', 'https://smilesoft.ro'],
                        ['graduation-cap', 'RIMS', 'Proiecte și bugete pentru universități și cercetare.', '/contact/'],
                        ['landmark', 'Bugetare participativă', 'Propuneri și voturi pentru proiectele comunității.', 'https://bugetare.ro'],
                    ] as [$glyph, $name, $text, $href])
                        @php($external = str_starts_with($href, 'http'))
                        <li data-reveal-child>
                            <a href="{{ $href }}" class="stage-product" @if ($external) target="_blank" rel="noopener" @endif>
                                <span class="stage-item__icon stage-item__icon--solid">{!! $icon($glyph) !!}</span>
                                <span class="stage-product__body">
                                    <span class="stage-product__name">{{ $name }}</span>
                                    <span class="stage-product__text">{{ $text }}</span>
                                </span>
                                <span class="stage-project__go" aria-hidden="true">{!! $arrow !!}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <ul class="stage-checks" data-reveal aria-label="Ce include orice abonament">
                    @foreach (['Instruire inclusă', 'Fără contract pe termen lung', 'Pe orice dispozitiv'] as $line)
                        <li data-reveal-child>{!! $check !!}{{ $line }}</li>
                    @endforeach
                </ul>

                <div class="chapter-cta stage-chapter__cta" data-reveal>
                    <x-ui.pill href="/contact/">Cere o prezentare</x-ui.pill>
                    <x-ui.arrow-link href="/produse/">Toate produsele</x-ui.arrow-link>
                </div>
            </div>
        </article>

        {{-- ============================================================ 03 --}}
        <article id="web-si-e-commerce" class="stage-chapter" data-stage-chapter data-side="{{ $sides[2] }}" aria-labelledby="chapter-3-title">
            <div class="stage-chapter__slot" data-stage-slot aria-hidden="true"></div>

            <div class="stage-chapter__body stage-web">
                <header class="stage-chapter__head" data-reveal="head">
                    <p class="chapter__label">03 · Web și e-commerce</p>
                    <h2 id="chapter-3-title" class="chapter__title">Site-uri <x-ui.title-pill variant="web" /> și magazine online.</h2>
                    <p class="stage-chapter__intro">Construim site-uri rapide și ușor de administrat, pentru ca tu să te concentrezi pe afacerea ta.</p>
                </header>

                {{-- Three columns, as 01's four: the icon over its name and a
                     single line, hairlines between. --}}
                <ul class="stage-items stage-web__items" data-reveal>
                    @foreach ([
                        ['monitor', 'Site de prezentare', 'Un site clar, ușor de actualizat.', '/creare-site-de-prezentare/'],
                        ['shopping-bag', 'Magazin online', 'Catalog, plăți și livrare la un loc.', '/creare-magazin-online/'],
                        ['shield-check', 'Mentenanță și găzduire', 'Actualizări, backup și suport.', '/mentenanta-site-wordpress/'],
                    ] as [$glyph, $name, $text, $href])
                        <li data-reveal-child>
                            <a href="{{ $href }}" class="stage-item stage-item--link">
                                <span class="stage-item__icon">{!! $icon($glyph) !!}</span>
                                <span class="stage-item__name">{{ $name }}</span>
                                <span class="stage-item__text">{{ $text }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div class="chapter-cta stage-chapter__cta" data-reveal>
                    <x-ui.pill href="/contact/">Discută despre site</x-ui.pill>
                    <x-ui.arrow-link href="/web/">Web și e-commerce</x-ui.arrow-link>
                </div>
            </div>
        </article>
    </div>

    {{-- ============================================================ The finale
         The three services gather: the object's plates fly in and stand as a
         W of five rounded squares, then the closing call
         (components/site/cta-panel). The ground paints under the object's
         layer with the text over it; see css/site/variant-stage.css. --}}
    <x-site.cta-panel />
</section>

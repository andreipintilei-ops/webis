{{--
    "Ce dezvoltăm" on /clienti: a label, then the three services in a
    hairline grid within the content width — side by side from 900px,
    stacked below. Each cell: an isometric illustration and a mono numeral
    on top, the title, text and link low in the cell. The link is the
    cell's target (stretched over it), so the whole cell is clickable.

    The illustrations are built from one primitive: a rounded square,
    projected isometrically — lying flat (rotate 45°, then scale the height
    by 0.57735) or standing upright (scale the width by 0.866, then skew
    30°). Each plane is its own element, solid, in one of the mark's four
    colours (--webis-navy, -green, -yellow, -red; all four in each, in a
    different order), with a white edge around it like the gaps in the
    mark. Overlaps are paint order alone: what is behind is drawn first.

    Motion: css/site/services-plain.css (hover) and
    js/public/services-plain.ts (entry). Without JavaScript, or with reduced
    motion, everything shows in its final state.

    The content is set here for now, not taken from the CMS block, so the
    wording and links stay exactly as approved. Expects `$block`
    (blocks/features).
--}}
@php
    $bands = [
        [
            'art' => 'floors',
            'title' => 'Software la comandă',
            'text' => 'Sisteme construite pentru o singură organizație, pornind de la procesele ei. Analiză, dezvoltare, implementare, mentenanță.',
            'link' => ['label' => 'Află mai multe', 'url' => '/software-la-comanda'],
        ],
        [
            'art' => 'tiles',
            'title' => 'Produse SaaS',
            'text' => 'Produse proprii, pe abonament lunar. SmileSoft, pentru clinici stomatologice. Gata de folosit, fără proiect de implementare.',
            'link' => ['label' => 'Vezi produsele', 'url' => '/produse'],
        ],
        [
            'art' => 'pages',
            'title' => 'Web și e-commerce',
            'text' => 'Site-uri de prezentare și magazine online. De aici am pornit în 2016 și le facem în continuare.',
            'link' => ['label' => 'Află mai multe', 'url' => '/web'],
        ],
    ];

    // The two projections, as SVG transforms (the last applies first).
    $flat = 'scale(1 0.57735) rotate(45)';
    $upright = 'skewY(30) scale(0.866 1)';
@endphp

<section id="{{ $block['id'] }}" class="services-plain" aria-labelledby="{{ $block['id'] }}-label" data-services-plain>
    <div class="services-plain__inner">
        <h2 id="{{ $block['id'] }}-label" class="services-plain__label">Ce dezvoltăm</h2>
    </div>

    <div class="service-bands" data-bands>
        @foreach ($bands as $index => $band)
            <article class="service-band" style="--i: {{ $index }}" data-band>
                <div class="service-band__grid">
                    <svg class="service-band__art iso iso--{{ $band['art'] }}" viewBox="0 0 160 160" aria-hidden="true">
                        @switch($band['art'])
                            @case('floors')
                                {{-- Four floors, 12px apart, painted bottom to top: the top
                                     one whole (off its place along the isometric x-axis,
                                     being set down), the ones below as slivers. --}}
                                @foreach (['red', 'yellow', 'navy', 'green'] as $level => $colour)
                                    <g @class(['iso__plane', 'iso__plane--placing' => $loop->last]) style="--i: {{ $level }}">
                                        <g transform="translate(80 98) {{ $flat }}">
                                            <rect x="-38" y="-38" width="76" height="76" rx="10" style="fill: var(--webis-{{ $colour }})" />
                                        </g>
                                    </g>
                                @endforeach
                                @break

                            @case('tiles')
                                {{-- Four tiles on one level, 2×2 like the mark laid flat,
                                     painted back to front; larger than the others' planes,
                                     as flat and apart they weigh less. --i is their order in
                                     the ripple (left, back, right, front). --}}
                                @foreach ([[-26, -26, 1, 'red'], [-26, 26, 0, 'yellow'], [26, -26, 2, 'navy'], [26, 26, 3, 'green']] as [$x, $y, $order, $colour])
                                    <g class="iso__plane" style="--i: {{ $order }}">
                                        <g transform="translate(80 80) {{ $flat }}">
                                            <rect x="{{ $x - 23 }}" y="{{ $y - 23 }}" width="46" height="46" rx="7" style="fill: var(--webis-{{ $colour }})" />
                                        </g>
                                    </g>
                                @endforeach
                                @break

                            @case('pages')
                                {{-- Four upright pages, painted back to front: the front one
                                     whole, the ones behind peeking out up and to the right,
                                     along the depth axis. --i: how many pages in front. --}}
                                @foreach (['yellow', 'navy', 'green', 'red'] as $layer => $colour)
                                    <g class="iso__plane" style="--i: {{ 3 - $layer }}">
                                        <g transform="translate(62 90) {{ $upright }}">
                                            <rect x="-28" y="-38" width="56" height="76" rx="8" style="fill: var(--webis-{{ $colour }})" />
                                        </g>
                                    </g>
                                @endforeach
                                @break
                        @endswitch
                    </svg>

                    <span class="service-band__numeral" aria-hidden="true" data-numeral="{{ $index + 1 }}">{{ sprintf('%02d', $index + 1) }}.</span>

                    <h3 id="{{ $block['id'] }}-{{ $index + 1 }}" class="service-band__title">{{ $band['title'] }}</h3>

                    <p class="service-band__text">{{ $band['text'] }}</p>

                    <x-ui.arrow-link :href="$band['link']['url']" class="service-band__link" aria-describedby="{{ $block['id'] }}-{{ $index + 1 }}">{{ $band['link']['label'] }}</x-ui.arrow-link>
                </div>
            </article>
        @endforeach
    </div>
</section>

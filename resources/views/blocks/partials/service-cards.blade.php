{{--
    "Ce dezvoltăm" as three illustrated cards (features block, layout
    "cards"): a light lavender sheet rising with a rounded top over the dark
    section before it, a centred head, then three white cards — the
    illustration, the title, and text and link under a divider — dealt in
    as the home page's cards, then flat; on hover only the white card
    behind the content grows.
    Each opens on an illustration
    (after temp/Ilustratii servicii v2), one inline SVG each, in the old
    site's colours: violet and purple, two-colour gradients, soft inks.

    The link is stretched over its card, so the whole card is clickable.
    Motion: the cards drop in, and the illustrations' lines draw on, as the
    list comes into view; small dots travel along the lines
    (js/public/service-cards.ts, css/site/service-cards.css). Still with
    reduced motion.

    The copy is set here for now, not taken from the CMS block, so it stays
    exactly as approved. Expects `$block` (blocks/features).
--}}
@php
    $cards = [
        [
            'art' => 'software',
            'title' => 'Software la comandă',
            'text' => 'Sisteme construite pentru procesele organizației voastre.',
            'link' => ['label' => 'Află mai multe', 'url' => '/software-la-comanda'],
        ],
        [
            'art' => 'saas',
            'title' => 'Produse SaaS',
            'text' => 'Produse proprii, gata de folosit, pe abonament lunar.',
            'link' => ['label' => 'Vezi produsele', 'url' => '/produse'],
        ],
        [
            'art' => 'web',
            'title' => 'Web și e-commerce',
            'text' => 'Site-uri de prezentare și magazine online.',
            'link' => ['label' => 'Află mai multe', 'url' => '/web'],
        ],
    ];

@endphp

<section id="{{ $block['id'] }}" class="svc-cards" aria-labelledby="{{ $block['id'] }}-title">
    <div class="svc-cards__inner">
        <header class="svc-cards__head" data-reveal="head">
            <h2 id="{{ $block['id'] }}-title" class="svc-cards__title">Tot de ce ai nevoie, <x-ui.title-pill variant="stack" /><br> de la site la sistemul de gestiune.</h2>
            <p class="svc-cards__intro">Construit de la zero sau gata de folosit,<br> cu mentenanță și suport de la aceeași echipă.</p>
        </header>

        <ul class="svc-cards__list" data-service-cards>
            @foreach ($cards as $index => $card)
                @php($id = $block['id'].'-'.($index + 1))
                <li class="svc-cards__item" style="--i: {{ $index }}">
                    <article class="svc-card">
                        <div class="svc-card__body">
                            <div class="svc-card__art" aria-hidden="true">
                                <x-site.service-illustration :art="$card['art']" :id="$id" />
                            </div>

                            <h3 id="{{ $id }}" class="svc-card__title">{{ $card['title'] }}</h3>

                            <div class="svc-card__foot">
                                <p class="svc-card__text">{{ $card['text'] }}</p>
                                <x-ui.arrow-link :href="$card['link']['url']" class="svc-card__link" aria-describedby="{{ $id }}">{{ $card['link']['label'] }}</x-ui.arrow-link>
                            </div>
                        </div>
                    </article>
                </li>
            @endforeach
        </ul>
    </div>
</section>

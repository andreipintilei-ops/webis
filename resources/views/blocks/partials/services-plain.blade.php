{{--
    "Ce dezvoltăm" on /clienti: a white section that slides up over the hero
    with a rounded top, listing the three services as rows between hairlines
    — number, title, text (and note), an arrow. Each row has one colour of
    the home cards (blue, red, yellow): a small square by its number and a
    short rule along its top, which runs the full width on hover, as the
    arrow fills in that colour. Each row comes in as it scrolls into view —
    the rule drawn, the title rising, then the text
    (js/public/services-plain.ts). Each row with a link is one link.

    css/site/services-plain.css. Expects `$block`, `$data`, `$items` and
    `$hasLink` (blocks/features).
--}}
@php
    // One colour per service, as on the home cards (blocks/partials/services).
    $palette = ['blue', 'red', 'yellow'];
@endphp

<section id="{{ $block['id'] }}" class="services-plain" data-services-plain>
    <div class="services-plain__inner">
        @if (! empty($data['heading']))
            <p class="services-plain__label" data-row-label>{{ $data['heading'] }}</p>
        @endif

        <ul class="services-plain__rows">
            @foreach ($items as $index => $item)
                <li>
                    <{{ $hasLink($item) ? 'a' : 'div' }}
                        @if ($hasLink($item)) href="{{ $item['link']['url'] }}" @endif
                        class="plain-row plain-row--{{ $palette[$index % count($palette)] }}"
                        data-row
                    >
                        <span class="plain-row__rule" aria-hidden="true" data-row-rule><span></span></span>

                        <span class="plain-row__number" aria-hidden="true">
                            <i data-row-mark></i>{{ sprintf('%02d', $index + 1) }}
                        </span>

                        <h3 class="plain-row__title"><span data-row-title>{{ $item['title'] }}</span></h3>

                        <div class="plain-row__body" data-row-fade>
                            @if (! empty($item['text']))
                                <p class="plain-row__text">{{ $item['text'] }}</p>
                            @endif

                            @if (! empty($item['note']))
                                <p class="plain-row__note">{{ $item['note'] }}</p>
                            @endif
                        </div>

                        @if ($hasLink($item))
                            <span class="plain-row__arrow" aria-hidden="true" data-row-fade>
                                <svg viewBox="0 0 11 10" fill="currentColor">
                                    <path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z" />
                                </svg>
                            </span>
                        @endif
                    </{{ $hasLink($item) ? 'a' : 'div' }}>
                </li>
            @endforeach
        </ul>
    </div>
</section>

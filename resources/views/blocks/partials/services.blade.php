{{--
    The home page's services ("Ce dezvoltăm"), from the CMS features block,
    after the results cards on gethyped.nl: the section label, then one tall
    colour card per service, each a little tilted — title at the top; at the
    foot a rule, the text and the link. Each card is one link (its arrow
    link's hit area covers it).

    Hover (css/site/services.css, no script): the card straightens, grows a
    little and comes forward; the cards beside it lean further away and step
    aside. Stacked on phones.

    Expects `$block`, `$data`, `$items` and `$hasLink` (blocks/features).
--}}
@php
    // Each card's colour, and the tone of what is on it: white on blue and
    // red, dark on yellow.
    $themes = [
        ['colour' => 'blue', 'tone' => 'dark'],
        ['colour' => 'red', 'tone' => 'dark'],
        ['colour' => 'yellow', 'tone' => 'light'],
    ];

    // Titles on two lines, split where the lines come out most even; each
    // line is kept whole (css), so "e-commerce" never breaks at its hyphen.
    $twoLines = function (string $title): array {
        $words = preg_split('/\s+/', trim($title)) ?: [];

        if (count($words) < 2) {
            return [$title];
        }

        $best = 1;
        $bestLength = PHP_INT_MAX;

        foreach (range(1, count($words) - 1) as $at) {
            $longer = max(
                mb_strlen(implode(' ', array_slice($words, 0, $at))),
                mb_strlen(implode(' ', array_slice($words, $at))),
            );

            if ($longer < $bestLength) {
                [$best, $bestLength] = [$at, $longer];
            }
        }

        return [implode(' ', array_slice($words, 0, $best)), implode(' ', array_slice($words, $best))];
    };
@endphp

<section id="{{ $block['id'] }}" class="services">
    <div class="services__inner">
        <div class="services__head">
            @if (! empty($data['heading']))
                <h2 class="services__heading">{{ $data['heading'] }}</h2>
            @endif

            @if (! empty($data['intro']))
                <p class="services__intro">{{ $data['intro'] }}</p>
            @endif
        </div>

        <ul class="services__list">
            @foreach ($items as $index => $item)
                @php($theme = $themes[$index % count($themes)])
                <li @class(['service-card', 'service-card--'.$theme['colour'], 'service-card--on-dark' => $theme['tone'] === 'dark'])>
                    <span class="service-card__number" aria-hidden="true">{{ sprintf('%02d', $index + 1) }}</span>
                    <h3 class="service-card__title">@foreach ($twoLines($item['title']) as $line)<span>{{ $line }}</span> @endforeach</h3>

                    <div class="service-card__foot">
                        @if (! empty($item['text']))
                            <p class="service-card__text">{{ $item['text'] }}</p>
                        @endif

                        @if (! empty($item['note']))
                            <p class="service-card__note">{{ $item['note'] }}</p>
                        @endif

                        @if ($hasLink($item))
                            <x-ui.arrow-link :href="$item['link']['url']" :tone="$theme['tone']" class="service-card__link">{{ $item['link']['label'] }}</x-ui.arrow-link>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>

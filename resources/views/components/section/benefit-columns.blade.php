{{--
    Benefits as white cards in three columns, the middle one set lower, so
    the set reads as a staggered wall rather than a grid. As the section
    scrolls through, the outer columns rise a little faster than the
    middle one, from 1024px (js/public/benefit-rows.ts); the cards come in
    one after another (js/public/reveals.ts). Each a soft icon at its top
    left, a name and a line (css/site/sections.css). On the light chapters.

    `items`: list of [icon (components/section/icon), name, line], six at
    best, dealt across the columns in reading order.
--}}
@props(['items'])

@php
    // Dealt left to right, row by row: 1 2 3 / 4 5 6.
    $columns = [[], [], []];

    foreach (array_values($items) as $i => $item) {
        $columns[$i % 3][] = $item;
    }
@endphp

<div {{ $attributes->class('bcols') }} data-bcols>
    @foreach ($columns as $column)
        <ul @class(['bcols__col', 'bcols__col--low' => $loop->iteration === 2]) data-bcols-col="{{ $loop->iteration === 2 ? 'slow' : 'fast' }}" data-reveal>
            @foreach ($column as [$icon, $name, $line])
                <li class="bcol-card" data-reveal-child>
                    <span class="bcol-card__icon"><x-section.icon :name="$icon" /></span>
                    <h3 class="bcol-card__name">{{ $name }}</h3>
                    <p class="bcol-card__line">{{ $line }}</p>
                </li>
            @endforeach
        </ul>
    @endforeach
</div>

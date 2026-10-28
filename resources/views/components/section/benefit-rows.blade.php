{{--
    Benefits as white cards in two rows: each a name and a line from its top,
    and its icon, in the indigo on a soft lavender square, in a notch cut from the top right
    corner (the blog cards' notch, css/site/blog-posts.css, turned to the
    top). The rows sit offset, the first to the left and the second to the
    right, and drift apart a little as they scroll through, from 1024px
    (js/public/benefit-rows.ts);
    the cards rise in one after another (js/public/reveals.ts).
    css/site/sections.css. On the light chapters, under their head.

    `items`: list of [icon, name, line], six at best; the first half on the
    first row. `icon`: a name from components/section/icon.
--}}
@props(['items'])

@php
    $rows = array_chunk($items, (int) ceil(count($items) / 2));
@endphp

<div {{ $attributes->class('brows') }} data-brows>
    @foreach ($rows as $row)
        <ul @class(['brows__row', 'brows__row--second' => ! $loop->first]) data-brows-row="{{ $loop->first ? -1 : 1 }}" data-reveal>
            @foreach ($row as [$icon, $name, $line])
                <li class="bcard" data-reveal-child>
                    <span class="bcard__bg" aria-hidden="true"></span>
                    <span class="bcard__icon"><x-section.icon :name="$icon" /></span>
                    <h3 class="bcard__name">{{ $name }}</h3>
                    <p class="bcard__line">{{ $line }}</p>
                </li>
            @endforeach
        </ul>
    @endforeach
</div>

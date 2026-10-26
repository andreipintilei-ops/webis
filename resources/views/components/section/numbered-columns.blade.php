{{--
    A dark band: its label at the left and a statement set high on the
    right, then, at the foot, columns divided by hairlines, each a large
    numeral, a name and a line (css/site/sections.css). For steps and
    principles, three to five of them.

    `label`, `statement`: text; `title`: the band's heading for screen
    readers and the outline (shown as the label when no label is given).
    `items`: list of [name, line]. `id`: the heading's id.
--}}
@props(['label' => null, 'title', 'statement', 'items', 'id' => 'numbered-columns-title'])

<section {{ $attributes->class('ncols') }} aria-labelledby="{{ $id }}" data-nav-tone="dark" data-reveal-pace="fast">
    <div class="ncols__inner">
        <div class="ncols__top" data-reveal="head">
            <h2 id="{{ $id }}" class="ncols__label">{{ $label ?? $title }}</h2>
            <p class="ncols__statement">{{ $statement }}</p>
        </div>

        <ol class="ncols__list" style="--n: {{ count($items) }}" data-reveal>
            @foreach ($items as [$name, $line])
                <li class="ncols__item" data-reveal-child>
                    <span class="ncols__no">{{ sprintf('%02d', $loop->iteration) }}</span>
                    <h3 class="ncols__name">{{ $name }}</h3>
                    <p class="ncols__line">{{ $line }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

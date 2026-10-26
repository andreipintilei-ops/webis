{{--
    The hero of an internal page (/blog, and pages like it): on the animated
    violet gradient, fixed behind the page (blocks/partials/hero-backdrop),
    a label, the title in the display face, an intro — and, in the slot,
    whatever the page wants beside the intro (links, filters). It scrolls
    with the page — no parallax anywhere — and, as on the home page, the
    section after it rises a little over its foot on a slightly rounded top
    (32px, as css/site/service-cards' sheet), the fixed gradient showing
    down to it (the layout's hero-leave "overlap").
    The title comes in line by line (js/public/reveals.ts).
    css/site/page-hero.css.

    Use with `header-theme="dark"` on the layout, so the top bar is white.

    `label`, `intro`: text. `title`: a string, or an array of lines.
    `after` (slot): below the intro, across the hero (/clienti's logos).
    `planes`: the decorative planes at the right (views/partials/hero-planes);
    false where nothing rises over the hero's foot to cover theirs (the 404).
    `flush`: the section after it starts on the hero's edge, square, rather
    than rising over it: where what follows carries on over the same
    gradient (/portofoliu, /produse, /software-la-comanda); the planes fade
    out at the hero's foot.
--}}
@props(['label' => null, 'title', 'intro' => null, 'id' => 'page-hero-title', 'planes' => true, 'flush' => false])

@php($sheet = ! $flush)

{{-- The gradient fixed behind the page (as on the contact page), hidden
     once the page has covered it (js/public/hero-intro.ts). --}}
@push('backdrop')
    @include('blocks.partials.hero-backdrop', [
        'backdrop' => 'gradient',
        'palette' => 'violet',
        'image' => null,
        'isFirst' => true,
        'fixed' => true,
    ])
@endpush

<section {{ $attributes->class(['page-hero', 'has-hero-planes' => $planes, 'page-hero--flush' => $flush, 'page-hero--sheet' => $sheet]) }} aria-labelledby="{{ $id }}" data-nav-tone="dark" data-backdrop-area>
    @if ($planes)
        @include('partials.hero-planes')
    @endif

    <div class="page-hero__inner" data-reveal="head">
        @if ($label)
            <p class="page-hero__label">{{ $label }}</p>
        @endif
        <h1 id="{{ $id }}" class="page-hero__title">
            @foreach ((array) $title as $line)
                <span class="block">{{ $line }}</span>
            @endforeach
        </h1>
        @if ($intro || $slot->isNotEmpty())
            <div class="page-hero__foot">
                @if ($intro)
                    <p class="page-hero__intro">{{ $intro }}</p>
                @endif
                {{ $slot }}
            </div>
        @endif
        @isset($after)
            {{ $after }}
        @endisset
    </div>
</section>

{{--
    The hero of an internal page (/blog, and pages like it): on the animated
    violet gradient, fixed behind the page (blocks/partials/hero-backdrop),
    a label, the title in the display face, an intro — and, in the slot,
    whatever the page wants beside the intro (links, filters). As the page
    scrolls, the hero's text sinks back slower than the page and the section
    after it slides up over the gradient with a rounded top: the parallax.
    The title comes in line by line (js/public/reveals.ts).
    css/site/page-hero.css.

    Use with `header-theme="dark"` on the layout, so the top bar is white.

    `label`, `intro`: text. `title`: a string, or an array of lines.
    `after` (slot): below the intro, across the hero (/clienti's logos).
    `parallax`: false on a page with nothing to come over the hero (the
    404) — its text would sink into the footer.
    `planes`: the decorative planes at the right (views/partials/hero-planes);
    false where nothing rises over the hero's foot to cover theirs (the 404).
--}}
@props(['label' => null, 'title', 'intro' => null, 'id' => 'page-hero-title', 'parallax' => true, 'planes' => true])

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

<section {{ $attributes->class(['page-hero', 'has-hero-planes' => $planes]) }} aria-labelledby="{{ $id }}" data-nav-tone="dark" data-backdrop-area>
    @if ($planes)
        {{-- At the text's speed, so they sink back with it. --}}
        @include('partials.hero-planes', ['speed' => $parallax ? 'clamp(0.6)' : null])
    @endif

    {{-- Slower than the page (ScrollSmoother's data-speed, clamped so it
         starts in its own place at the top of the page): it sinks back
         as the next section comes up over it. --}}
    <div class="page-hero__inner" data-reveal="head" @if ($parallax) data-speed="clamp(0.6)" @endif>
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

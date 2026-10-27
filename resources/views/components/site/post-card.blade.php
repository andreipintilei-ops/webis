{{--
    One article card (css/site/blog-posts.css), as a list item: the ground
    with its notch and the arrow button in it, the category and date, the
    title. Used by the home page's blog section and the /blog listing.

    `post`: ['href', 'title', 'category' (or null), 'date' (CarbonInterface
    or null), 'cover' (Support\Blog\Cover, optional)]: with a cover, it
    opens the card, 16:9, on the hero's gradient where it is transparent;
    without one the card is text alone, as before. `index`: its place in
    the list (the reveal's stagger). `sizes`: the cover's width on the
    page, for srcset (the listing's cards are narrower).
    `alone`: it comes in on its own as it is reached (js/public/reveals.ts),
    rather than with the rest of its list.
--}}
@props(['post', 'index' => 0, 'alone' => false, 'sizes' => '(min-width: 1024px) 30vw, (min-width: 640px) 46vw, 92vw'])

@php
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<li style="--i: {{ $index }}" @if ($alone) data-reveal @else data-reveal-child @endif>
    <a href="{{ $post['href'] }}" class="post-card">
        {{-- The ground with its notch, and the button in it: one frame, which grows on hover. --}}
        <span class="post-card__frame" aria-hidden="true">
            <span class="post-card__bg"></span>
            <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
        </span>
        @if ($cover = $post['cover'] ?? null)
            <span @class(['post-card__cover', 'is-cutout' => $cover->cutout])>
                <img src="{{ $cover->src }}" srcset="{{ $cover->srcset }}" sizes="{{ $sizes }}" width="{{ $cover->width }}" height="{{ $cover->height }}" alt="{{ $cover->alt }}" loading="lazy" decoding="async">
            </span>
        @endif
        <span class="post-card__meta">
            @if ($post['category'])
                <span class="post-card__category">{{ $post['category'] }}</span>
            @endif
            @if ($post['date'])
                <time datetime="{{ $post['date']->toDateString() }}">{{ $post['date']->locale('ro')->translatedFormat('j F Y') }}</time>
            @endif
        </span>
        <span class="post-card__title"><span>{{ $post['title'] }}</span></span>
        <span class="post-card__foot">Citește articolul</span>
    </a>
</li>

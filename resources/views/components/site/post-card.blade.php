{{--
    One article card (css/site/blog-posts.css), as a list item: the ground
    with its notch and the arrow button in it, the category and date, the
    title. Used by the home page's blog section and the /blog listing.

    `post`: ['href', 'title', 'category' (or null), 'date' (CarbonInterface
    or null)]. `index`: its place in the list (the reveal's stagger).
--}}
@props(['post', 'index' => 0])

@php
    $arrow = '<svg viewBox="0 0 11 10" fill="currentColor" aria-hidden="true"><path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z"/></svg>';
@endphp

<li style="--i: {{ $index }}" data-reveal-child>
    <a href="{{ $post['href'] }}" class="post-card">
        {{-- The ground with its notch, and the button in it: one frame, which grows on hover. --}}
        <span class="post-card__frame" aria-hidden="true">
            <span class="post-card__bg"></span>
            <span class="post-card__button"><span class="post-card__arrow">{!! $arrow !!}</span><span class="post-card__arrow post-card__arrow--next">{!! $arrow !!}</span></span>
        </span>
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

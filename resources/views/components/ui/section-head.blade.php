{{--
    The site's section heading (css/site/system.css): the section's number
    over a hairline, then its title in the display face (Anton, uppercase);
    an intro beside it when there is one. The same on every section, so the
    pages read in one rhythm.

    `number`: "01"… `title`: the heading's text. `id`: the heading's id, for
    the section's aria-labelledby. `intro`: optional. `tone`: "light" (on
    the lavender) or "dark" (on the indigo).
--}}
@props(['number', 'title', 'id', 'intro' => null, 'tone' => 'light'])

<header {{ $attributes->class(['section-head', 'section-head--dark' => $tone === 'dark']) }} data-reveal="head">
    <p class="section-head__number" aria-hidden="true">{{ $number }}</p>
    <div class="section-head__row">
        <h2 id="{{ $id }}" class="section-head__title">{{ $title }}</h2>
        @if ($intro)
            <p class="section-head__intro">{{ $intro }}</p>
        @endif
    </div>
</header>

{{--
    Secondary button, after exoape.com: a small dot and an underlined label;
    on hover a circle with an arrow grows out of the dot (css/site/buttons.css).
    `tone`: "light" (for white backgrounds) or "dark" (for dark ones).
--}}
@props(['href', 'tone' => 'light'])

<a href="{{ $href }}" {{ $attributes->class(['arrow-link', 'btn-'.$tone]) }}>
    <span class="arrow-link__circle" aria-hidden="true">
        <span class="arrow-link__dot"></span>
        <span class="arrow-link__fill"></span>
        <svg class="arrow-link__icon" viewBox="0 0 11 10" fill="currentColor">
            <path d="M0 5.656V4.304l8.419.014-3.359-3.358L5.99 0l5.002 4.973-4.987 4.987-.93-.96 3.358-3.358L0 5.656Z" />
        </svg>
    </span>
    <span class="arrow-link__label">
        {{ $slot }}
        <span class="arrow-link__border" aria-hidden="true"></span>
    </span>
</a>

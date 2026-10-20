{{--
    A label that rolls letter by letter on hover, after the "What we do"
    cards on atomic.black: each letter in its own mask drops away while its
    copy falls in from above, one after another from the left
    (css/site/roll.css). The copies are drawn from data-l, so the label is
    in the page's text once, whole, its spaces plain. What sets it off — the
    link's hover or focus — is in the stylesheet.

    `text`: the label.
--}}
@props(['text'])

@php
    // Built here, with no stray whitespace between the letters.
    $letters = '';

    foreach (mb_str_split((string) $text) as $index => $letter) {
        $letters .= $letter === ' '
            ? ' '
            : sprintf('<span class="roll__char" data-l="%1$s" style="--i: %2$d"><span class="roll__letter">%1$s</span></span>', e($letter), $index);
    }
@endphp

<span {{ $attributes->class('roll') }}>{!! $letters !!}</span>

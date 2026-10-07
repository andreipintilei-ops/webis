{{--
    A chapter's subsection: its mono label (the h3) over a hairline, then
    its content. Its entry (js/public/reveals.ts): the hairline draws, the
    label fades in, the content rises.
    The `aside` slot sits beside the label (e.g. the Google rating).
--}}
@props(['label', 'aside' => null])

<div {{ $attributes->class(['chapter-sub']) }} data-reveal>
    <div class="chapter-sub__header">
        <h3 class="chapter-sub__label">{{ $label }}</h3>
        @if ($aside)
            <div class="chapter-sub__aside">{{ $aside }}</div>
        @endif
    </div>
    {{ $slot }}
</div>

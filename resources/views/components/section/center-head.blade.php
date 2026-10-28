{{--
    A section's head, centred: its label in the violet chip the home page's
    titles carry (components/ui/title-pill), opening from its middle as the
    head comes in (js/public/title-pill.ts), then a statement for a title
    with one phrase in it in the violet, the point the line turns on
    (css/site/sections.css). For a section that opens on its own, after a
    hero or between bands.

    `label`: text. `title`: the statement; a line break typed in it is kept.
    `highlight`: the phrase within it set in the violet (its first
    occurrence). `intro`: a line under it, optional. `id`: the title's id,
    for the section's aria-labelledby.
--}}
@props(['label' => null, 'title', 'highlight' => null, 'intro' => null, 'id' => 'center-head-title'])

@php
    $at = $highlight !== null ? mb_strpos($title, $highlight) : false;
    // Escaped, its typed line breaks as <br>.
    $lines = fn (string $text): string => nl2br(e($text), false);
@endphp

<header {{ $attributes->class('chead') }} data-reveal="head">
    @if ($label)
        <p class="chead__eyebrow"><span class="title-pill chead__pill" data-title-pill><span class="title-pill__inner">{{ $label }}</span></span></p>
    @endif
    <h2 id="{{ $id }}" class="chead__title">@if ($at !== false){!! $lines(mb_substr($title, 0, $at)) !!}<span class="chead__turn">{{ $highlight }}</span>{!! $lines(mb_substr($title, $at + mb_strlen($highlight))) !!}@else{!! $lines($title) !!}@endif</h2>
    @if ($intro)
        <p class="chead__intro">{{ $intro }}</p>
    @endif
</header>

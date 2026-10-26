{{--
    Primary button, after the "Contacts" button on cuberto.com: on hover a fill
    rises inside the pill and the label rolls letter by letter, as the menus'
    (css/site/buttons.css, css/site/roll.css).
    `tone`: "light" (dark pill, for white backgrounds) or "dark" (white pill,
    for dark backgrounds). Without `href` it is a <button> (`type`, default
    "submit"), for forms.
--}}
@props(['href' => null, 'tone' => 'light', 'type' => 'submit'])

@php
    // The slot is already-escaped HTML; trimmed so a label written on its own
    // line carries no stray spaces into the button or its rolled-in copy.
    $label = trim((string) $slot);
    // As text again, for the roll to split into letters.
    $text = html_entity_decode(strip_tags($label), ENT_QUOTES | ENT_HTML5);
@endphp

@if ($href !== null)
    {{-- A path in its canonical form (TrailingSlash::href): a block's link
         typed as "/contact" still takes no 301. --}}
    <a href="{{ \App\Support\TrailingSlash::href($href) }}" {{ $attributes->class(['pill', 'btn-'.$tone]) }}>
        <span class="pill__ripple" aria-hidden="true"><span></span></span>
        <span class="pill__title"><x-ui.roll :text="$text" /></span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class(['pill', 'btn-'.$tone]) }}>
        <span class="pill__ripple" aria-hidden="true"><span></span></span>
        <span class="pill__title"><x-ui.roll :text="$text" /></span>
    </button>
@endif

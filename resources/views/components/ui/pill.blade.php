{{--
    Primary button, after the "Contacts" button on cuberto.com: on hover a fill
    rises inside the pill and the label rolls over (css/site/buttons.css).
    `tone`: "light" (dark pill, for white backgrounds) or "dark" (white pill,
    for dark backgrounds). `size`: "md", or "sm" for the header.
--}}
@props(['href', 'tone' => 'light', 'size' => 'md'])

@php
    // The slot is already-escaped HTML; trimmed so a label written on its own
    // line carries no stray spaces into the button or its rolled-in copy.
    $label = trim((string) $slot);
@endphp

<a href="{{ $href }}" {{ $attributes->class(['pill', 'pill--sm' => $size === 'sm', 'btn-'.$tone]) }}>
    <span class="pill__ripple" aria-hidden="true"><span></span></span>
    {{-- The rolled-in copy comes from data-text via ::after; screen readers
         read the label once. --}}
    <span class="pill__title"><span data-text="{!! $label !!}">{!! $label !!}</span></span>
</a>

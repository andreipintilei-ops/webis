{{--
    A block's buttons: the primary as a pill, the secondary as an arrow link.
    Expects `$primary`, `$secondary` (each {label, url} or null), `$tone`
    ("light" | "dark") and `$class` for the row.
--}}
@php
    $usable = fn ($cta): bool => is_array($cta) && ($cta['label'] ?? '') !== '' && ($cta['url'] ?? '') !== '';
@endphp

@if ($usable($primary) || $usable($secondary))
    <div class="{{ $class }} flex flex-wrap items-center gap-x-10 gap-y-6">
        @if ($usable($primary))
            <x-ui.pill :href="$primary['url']" :tone="$tone">{{ $primary['label'] }}</x-ui.pill>
        @endif
        @if ($usable($secondary))
            <x-ui.arrow-link :href="$secondary['url']" :tone="$tone">{{ $secondary['label'] }}</x-ui.arrow-link>
        @endif
    </div>
@endif

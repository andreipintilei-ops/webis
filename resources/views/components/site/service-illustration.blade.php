{{--
    A service's illustration (after temp/Ilustratii servicii v2): one inline
    SVG — "software", "saas" or "web" — in the old site's colours, drawn by
    css/site/service-cards.css (.il-*). Used by the small service cards
    (blocks/partials/service-cards) and the stacking cards
    (components/site/service-stack). `id` makes its gradient and mask ids
    unique on the page.
--}}
@props(['art' => 'software', 'id'])

@php
    $card = ['art' => $art];

    // Lucide icons (as in the design), drawn into the illustrations at a
    // given place and size.
    $icons = [
        'file-text' => '<path d="M6 22a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h8a2.4 2.4 0 0 1 1.704.706l3.588 3.588A2.4 2.4 0 0 1 20 8v12a2 2 0 0 1-2 2z"/><path d="M14 2v5a1 1 0 0 0 1 1h5"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'workflow' => '<rect width="8" height="8" x="3" y="3" rx="2"/><path d="M7 11v4a2 2 0 0 0 2 2h4"/><rect width="8" height="8" x="13" y="13" rx="2"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'cloud' => '<path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/>',
        'calendar' => '<path d="M8 2v3"/><path d="M16 2v3"/><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/>',
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'refresh-cw' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'plus' => '<path d="M5 12h14"/><path d="M12 5v14"/>',
        'arrow-up-right' => '<path d="M7 7h10v10"/><path d="M7 17 17 7"/>',
    ];
    $icon = fn (string $name, float $cx, float $cy, float $size, string $class = '') => sprintf(
        '<svg class="il-icon %s" x="%s" y="%s" width="%s" height="%s" viewBox="0 0 24 24">%s</svg>',
        $class, $cx - $size / 2, $cy - $size / 2, $size, $size, $icons[$name],
    );

    // The pointer, as in the design.
    $cursor = fn (float $x, float $y) => sprintf(
        '<path class="il-cursor" transform="translate(%s %s)" d="M2 2 L2 16 L6 12 L9 18 L11.5 17 L8.5 11 L14 11 Z"/>', $x, $y,
    );
@endphp

<svg class="il" viewBox="0 0 400 300" preserveAspectRatio="xMidYMid slice">
    <defs>
        {{-- Shared: the grid, the glow, the tile's gradient and shadow. --}}
        <pattern id="{{ $id }}-grid" width="40" height="40" patternUnits="userSpaceOnUse">
            <path class="il-grid" d="M40 0H0V40" />
        </pattern>
        <radialGradient id="{{ $id }}-gridfade">
            <stop offset="0.15" stop-color="#fff" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </radialGradient>
        <mask id="{{ $id }}-gridmask">
            <rect width="400" height="300" fill="url(#{{ $id }}-gridfade)" />
        </mask>
        <radialGradient id="{{ $id }}-glow" cx="{{ ['software' => 0.62, 'saas' => 0.5, 'web' => 0.7][$card['art']] }}" cy="{{ ['software' => 0.55, 'saas' => 0.5, 'web' => 0.6][$card['art']] }}" r="0.5">
            <stop offset="0" class="il-stop-glow" />
            <stop offset="0.5" class="il-stop-glow" stop-opacity="0.45" />
            <stop offset="1" class="il-stop-glow" stop-opacity="0" />
        </radialGradient>
        {{-- Soft things fade out before the edges, so the
             illustration has no visible rectangle on the card. --}}
        <radialGradient id="{{ $id }}-edgefade">
            <stop offset="0.55" stop-color="#fff" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </radialGradient>
        <mask id="{{ $id }}-edges">
            <rect width="400" height="300" fill="url(#{{ $id }}-edgefade)" />
        </mask>
        <linearGradient id="{{ $id }}-tile" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" class="il-stop-purple" />
            <stop offset="1" class="il-stop-violet" />
        </linearGradient>
        <linearGradient id="{{ $id }}-line" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" class="il-stop-violet" stop-opacity="0" />
            <stop offset="0.45" class="il-stop-violet" stop-opacity="0.9" />
            <stop offset="1" class="il-stop-purple" />
        </linearGradient>
        <filter id="{{ $id }}-lift" x="-50%" y="-50%" width="200%" height="220%">
            <feDropShadow dx="0" dy="16" stdDeviation="14" class="il-shadow" />
        </filter>
    </defs>

    <rect width="400" height="300" class="il-ground" />
    <rect width="400" height="300" fill="url(#{{ $id }}-glow)" mask="url(#{{ $id }}-edges)" />

    @switch($card['art'])
        @case('software')
            {{-- A document becoming a workflow: a line from the file to
                 the process, steps beyond it, a record at the foot. --}}
            <defs>
                <linearGradient id="{{ $id }}-fade-r" x1="0" x2="1">
                    <stop offset="0.4" stop-color="#fff" />
                    <stop offset="1" stop-color="#fff" stop-opacity="0" />
                </linearGradient>
                <mask id="{{ $id }}-steps"><rect x="300" y="130" width="100" height="40" fill="url(#{{ $id }}-fade-r)" /></mask>
                <linearGradient id="{{ $id }}-fade-x" x1="0" x2="1">
                    <stop offset="0" stop-color="#fff" stop-opacity="0" />
                    <stop offset="0.22" stop-color="#fff" />
                    <stop offset="0.78" stop-color="#fff" />
                    <stop offset="1" stop-color="#fff" stop-opacity="0" />
                </linearGradient>
                <mask id="{{ $id }}-record"><rect x="95" y="213" width="210" height="50" fill="url(#{{ $id }}-fade-x)" /></mask>
                <linearGradient id="{{ $id }}-trail" x1="0" x2="1">
                    <stop offset="0" class="il-stop-purple" />
                    <stop offset="1" class="il-stop-purple" stop-opacity="0" />
                </linearGradient>
            </defs>
            <rect width="400" height="300" fill="url(#{{ $id }}-grid)" mask="url(#{{ $id }}-gridmask)" />
            <path id="{{ $id }}-path" class="il-line il-draw" d="M20 86 H96 C140 86 140 150 186 150 H210" stroke="url(#{{ $id }}-line)" pathLength="1" />
            <path class="il-line" d="M290 150 H400" stroke="url(#{{ $id }}-trail)" stroke-dasharray="4 5" />
            <circle r="3.5" class="il-dot"><animateMotion dur="3.2s" repeatCount="indefinite"><mpath href="#{{ $id }}-path" /></animateMotion></circle>

            <rect x="40" y="62" width="48" height="48" rx="10" class="il-panel" />
            {!! $icon('file-text', 64, 86, 22, 'il-icon--ink') !!}

            <rect x="202" y="102" width="96" height="96" rx="26" class="il-halo" />
            <rect x="210" y="110" width="80" height="80" rx="18" fill="url(#{{ $id }}-tile)" filter="url(#{{ $id }}-lift)" />
            {!! $icon('workflow', 250, 150, 34, 'il-icon--white') !!}

            <g mask="url(#{{ $id }}-steps)">
                <circle cx="314" cy="150" r="14" class="il-panel" />
                {!! $icon('check', 314, 150, 14, 'il-icon--accent') !!}
                <circle cx="352" cy="150" r="14" class="il-panel" />
                <circle cx="390" cy="150" r="14" class="il-panel" />
            </g>

            <g mask="url(#{{ $id }}-record)">
                <rect x="96" y="214" width="208" height="48" rx="12" class="il-panel il-panel--soft" />
                <circle cx="119" cy="238" r="11" fill="url(#{{ $id }}-tile)" />
                <rect x="140" y="235" width="70" height="6" rx="3" class="il-ink" />
                <rect x="220" y="235" width="40" height="6" rx="3" class="il-faint" />
            </g>

            {!! $cursor(278, 182) !!}
            @break

        @case('saas')
            {{-- A product in the cloud at the centre, its features in orbit. --}}
            <g mask="url(#{{ $id }}-edges)">
                <circle cx="200" cy="150" r="180" class="il-ring il-ring--dashed" />
                <circle cx="200" cy="150" r="130" class="il-ring" />
                <circle cx="200" cy="150" r="95" class="il-ring il-ring--near" />
            </g>
            <path id="{{ $id }}-orbit" d="M200 55 A95 95 0 1 1 199.9 55" fill="none" />
            <path id="{{ $id }}-orbit-far" d="M200 20 A130 130 0 1 0 200.1 20" fill="none" />
            <circle r="3" class="il-dot"><animateMotion dur="9s" repeatCount="indefinite"><mpath href="#{{ $id }}-orbit" /></animateMotion></circle>
            <circle r="2.5" class="il-dot il-dot--far"><animateMotion dur="14s" repeatCount="indefinite"><mpath href="#{{ $id }}-orbit-far" /></animateMotion></circle>

            <rect x="146" y="96" width="108" height="108" rx="30" class="il-halo" />
            <rect x="156" y="106" width="88" height="88" rx="20" fill="url(#{{ $id }}-tile)" filter="url(#{{ $id }}-lift)" />
            {!! $icon('cloud', 200, 150, 40, 'il-icon--white') !!}

            <circle cx="112" cy="86" r="20" class="il-panel" />
            {!! $icon('calendar', 112, 86, 18, 'il-icon--ink') !!}
            <circle cx="296" cy="114" r="20" class="il-panel" />
            {!! $icon('user', 296, 114, 18, 'il-icon--ink') !!}
            <circle cx="272" cy="222" r="20" class="il-panel" />
            {!! $icon('refresh-cw', 272, 222, 18, 'il-icon--accent') !!}
            <g opacity="0.7">
                <circle cx="101" cy="213" r="17" class="il-panel il-panel--soft" />
                {!! $icon('shield-check', 101, 213, 16, 'il-icon--muted') !!}
            </g>

            @break

        @case('web')
            {{-- A site in a browser, a product lifted out of it, a path
                 climbing — and the orders. --}}
            <defs>
                <linearGradient id="{{ $id }}-fade-y" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0.55" stop-color="#fff" />
                    <stop offset="0.98" stop-color="#fff" stop-opacity="0" />
                </linearGradient>
                <mask id="{{ $id }}-window"><rect x="35" y="33" width="302" height="242" fill="url(#{{ $id }}-fade-y)" /></mask>
                <linearGradient id="{{ $id }}-hero" x1="0" y1="0" x2="1" y2="0.6">
                    <stop offset="0" stop-color="#f1edff" />
                    <stop offset="1" style="stop-color: var(--lavender-300)" />
                </linearGradient>
            </defs>
            <g mask="url(#{{ $id }}-window)">
                <rect x="36" y="34" width="300" height="240" rx="14" class="il-panel" />
                <path class="il-rule" d="M37 64.5H335" />
                <circle cx="51.5" cy="49" r="3.5" class="il-faint" />
                <circle cx="64.5" cy="49" r="3.5" class="il-faint" />
                <circle cx="77.5" cy="49" r="3.5" class="il-faint" />
                <rect x="92" y="42" width="120" height="14" rx="5" class="il-sunken" />
                <rect x="48" y="76" width="276" height="84" rx="8" fill="url(#{{ $id }}-hero)" />
                <rect x="62" y="98" width="96" height="8" rx="3" class="il-ink" />
                <rect x="62" y="114" width="64" height="6" rx="3" class="il-ink il-ink--soft" />
                <rect x="62" y="130" width="52" height="16" rx="5" class="il-cta" />
                <rect x="48" y="172" width="85.3" height="58" rx="6" class="il-sunken" />
                <rect x="143.3" y="172" width="85.3" height="58" rx="6" class="il-sunken" />
                <rect x="238.7" y="172" width="85.3" height="58" rx="6" class="il-sunken" />
            </g>

            {{-- Growth: one smooth curve, flat at first, then climbing ever
                 faster — behind the product card, out of its top right corner
                 to the cursor. --}}
            <path id="{{ $id }}-path" class="il-line il-line--wide il-draw" d="M24 216 C150 213 250 192 370 60" stroke="url(#{{ $id }}-line)" pathLength="1" />
            <circle r="3" class="il-dot"><animateMotion dur="3.6s" repeatCount="indefinite"><mpath href="#{{ $id }}-path" /></animateMotion></circle>

            <rect x="226" y="112" width="118" height="132" rx="14" class="il-panel" filter="url(#{{ $id }}-lift)" />
            <rect x="235" y="121" width="100" height="58" rx="7" fill="url(#{{ $id }}-tile)" />
            <rect x="235" y="186" width="72" height="6" rx="3" class="il-ink" />
            <rect x="235" y="199" width="40" height="5" rx="2.5" class="il-faint" />
            <rect x="235" y="213" width="100" height="22" rx="6" class="il-cta" />
            {!! $icon('plus', 285, 224, 14, 'il-icon--white') !!}

            <circle cx="370" cy="60" r="12" class="il-halo" />
            <circle cx="370" cy="60" r="6" class="il-dot" />
            {!! $cursor(372, 60) !!}

            <rect x="52" y="224" width="108" height="34" rx="17" class="il-panel" />
            {!! $icon('arrow-up-right', 68, 241, 16, 'il-icon--accent') !!}
            <text x="82" y="245.5" class="il-text">Comenzi</text>
            @break
    @endswitch
</svg>

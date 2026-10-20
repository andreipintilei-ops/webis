{{--
    /despre's illustrations for how we work, in the service cards' drawn
    style (css/site/service-cards.css, .il-*): white panels, a violet tile
    with its glow, fine lines with dots travelling along them, on a grid
    that fades out before the edges — so they sit on the page's lavender
    without a frame.

    `art`: "team" (the same people, no one in between), "steps" (stages,
    a turn possible on the way), "plain" (a conversation, the plain answer)
    or "after" (looked after, round and round). `id`: unique on the page.
--}}
@props(['art', 'id'])

@php
    $icons = [
        'user' => '<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'users' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'message' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        'shield-check' => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
        'refresh' => '<path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M8 16H3v5"/>',
        'trending' => '<path d="M16 7h6v6"/><path d="m22 7-8.5 8.5-5-5L2 17"/>',
        'layers' => '<path d="M12.83 2.18a2 2 0 0 0-1.66 0L2.6 6.08a1 1 0 0 0 0 1.83l8.58 3.91a2 2 0 0 0 1.66 0l8.58-3.9a1 1 0 0 0 0-1.83z"/><path d="m22 17.65-9.17 4.16a2 2 0 0 1-1.66 0L2 17.65"/><path d="m22 12.65-9.17 4.16a2 2 0 0 1-1.66 0L2 12.65"/>',
    ];
    $icon = fn (string $name, float $cx, float $cy, float $size, string $class) => sprintf(
        '<svg class="il-icon %s" x="%s" y="%s" width="%s" height="%s" viewBox="0 0 24 24">%s</svg>',
        $class, $cx - $size / 2, $cy - $size / 2, $size, $size, $icons[$name],
    );
@endphp

<svg class="il about-il" viewBox="0 0 400 240" aria-hidden="true">
    <defs>
        <pattern id="{{ $id }}-grid" width="32" height="32" patternUnits="userSpaceOnUse">
            <path class="il-grid" d="M32 0H0V32" />
        </pattern>
        <radialGradient id="{{ $id }}-fade">
            <stop offset="0.2" stop-color="#fff" />
            <stop offset="1" stop-color="#fff" stop-opacity="0" />
        </radialGradient>
        <mask id="{{ $id }}-mask">
            <rect width="400" height="240" fill="url(#{{ $id }}-fade)" />
        </mask>
        <radialGradient id="{{ $id }}-glow" r="0.5">
            <stop offset="0" class="il-stop-glow" />
            <stop offset="1" class="il-stop-glow" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="{{ $id }}-tile" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" class="il-stop-purple" />
            <stop offset="1" class="il-stop-violet" />
        </linearGradient>
        <filter id="{{ $id }}-lift" x="-50%" y="-50%" width="200%" height="220%">
            <feDropShadow dx="0" dy="12" stdDeviation="12" class="il-shadow" />
        </filter>
    </defs>

    <rect width="400" height="240" fill="url(#{{ $id }}-grid)" mask="url(#{{ $id }}-mask)" />
    <ellipse cx="200" cy="120" rx="190" ry="110" fill="url(#{{ $id }}-glow)" />

    @switch($art)
        @case('team')
            {{-- You, straight to the team: one line, nobody between. --}}
            <path id="{{ $id }}-direct" class="il-line" d="M88 120 H164" stroke="var(--violet-500)" />
            <path class="il-line" d="M236 120 C262 120 268 70 300 70 M236 120 H312 M236 120 C262 120 268 170 300 170" stroke="rgb(119 90 252 / 0.35)" />
            <circle r="3.5" class="il-dot"><animateMotion dur="2.6s" repeatCount="indefinite"><mpath href="#{{ $id }}-direct" /></animateMotion></circle>

            <circle cx="64" cy="120" r="26" class="il-panel" />
            {!! $icon('user', 64, 120, 22, 'il-icon--ink') !!}

            <rect x="160" y="80" width="80" height="80" rx="24" class="il-halo" />
            <rect x="166" y="86" width="68" height="68" rx="18" fill="url(#{{ $id }}-tile)" filter="url(#{{ $id }}-lift)" />
            {!! $icon('users', 200, 120, 30, 'il-icon--white') !!}

            @foreach ([[318, 70], [330, 120], [318, 170]] as [$x, $y])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="18" class="il-panel" />
                {!! $icon('user', $x, $y, 16, 'il-icon--accent') !!}
            @endforeach
            @break

        @case('steps')
            {{-- Stages along a line: two done, one under way, one to come;
                 a turn is still open where the work is. --}}
            <path class="il-line" d="M60 150 H340" stroke="rgb(119 90 252 / 0.25)" />
            <path id="{{ $id }}-done" class="il-line il-line--wide" d="M60 150 H246" stroke="var(--violet-500)" />
            <path class="il-line" d="M246 150 C246 104 270 84 314 84" stroke="rgb(119 90 252 / 0.45)" stroke-dasharray="4 5" />
            <circle r="3.5" class="il-dot"><animateMotion dur="3s" repeatCount="indefinite"><mpath href="#{{ $id }}-done" /></animateMotion></circle>

            @foreach ([60, 153] as $x)
                <circle cx="{{ $x }}" cy="150" r="17" fill="url(#{{ $id }}-tile)" />
                {!! $icon('check', $x, 150, 16, 'il-icon--white') !!}
            @endforeach

            <circle cx="246" cy="150" r="30" class="il-halo">
                <animate attributeName="r" values="24;32;24" dur="2.8s" repeatCount="indefinite" />
            </circle>
            <circle cx="246" cy="150" r="18" class="il-panel" />
            <circle cx="246" cy="150" r="6" class="il-dot" />

            <circle cx="340" cy="150" r="14" class="il-panel il-panel--soft" />
            <rect x="300" y="72" width="64" height="24" rx="12" class="il-panel" />
            <rect x="312" y="81" width="40" height="6" rx="3" class="il-faint" />

            {{-- How far along: a bar over the stages. --}}
            <rect x="60" y="62" width="160" height="40" rx="12" class="il-panel" />
            <rect x="74" y="76" width="64" height="5" rx="2.5" class="il-ink" />
            <rect x="74" y="88" width="132" height="5" rx="2.5" class="il-sunken" />
            <rect x="74" y="88" width="84" height="5" rx="2.5" fill="url(#{{ $id }}-tile)" />
            @break

        @case('plain')
            {{-- A conversation: the question, then the plain answer — and
                 how long it takes. --}}
            <rect x="64" y="40" width="250" height="160" rx="16" class="il-panel" />
            <path class="il-rule" d="M65 68.5H313" />
            <circle cx="80" cy="54" r="3.5" class="il-faint" />
            <circle cx="92" cy="54" r="3.5" class="il-faint" />
            <circle cx="104" cy="54" r="3.5" class="il-faint" />

            <rect x="82" y="84" width="128" height="40" rx="14" class="il-sunken" />
            <rect x="96" y="97" width="80" height="5" rx="2.5" class="il-ink il-ink--soft" />
            <rect x="96" y="108" width="54" height="5" rx="2.5" class="il-ink il-ink--soft" />

            <rect x="150" y="136" width="146" height="46" rx="14" fill="url(#{{ $id }}-tile)" filter="url(#{{ $id }}-lift)" />
            <rect x="166" y="151" width="92" height="5" rx="2.5" fill="#fff" />
            <rect x="166" y="163" width="64" height="5" rx="2.5" fill="#fff" fill-opacity="0.7" />
            <circle cx="278" cy="159" r="9" fill="#fff" />
            {!! $icon('check', 278, 159, 11, 'il-icon--accent') !!}

            <circle cx="334" cy="64" r="22" class="il-panel" />
            {!! $icon('clock', 334, 64, 20, 'il-icon--accent') !!}
            <circle cx="40" cy="168" r="18" class="il-panel il-panel--soft" />
            {!! $icon('message', 40, 168, 15, 'il-icon--muted') !!}
            @break

        @case('after')
            {{-- Looked after: kept safe at the centre, updates going round. --}}
            <circle cx="200" cy="120" r="92" class="il-ring il-ring--dashed" />
            <circle cx="200" cy="120" r="64" class="il-ring il-ring--near" />
            <path id="{{ $id }}-orbit" d="M200 56 A64 64 0 1 1 199.9 56" fill="none" />
            <path id="{{ $id }}-orbit-far" d="M200 28 A92 92 0 1 0 200.1 28" fill="none" />
            <circle r="3.5" class="il-dot"><animateMotion dur="8s" repeatCount="indefinite"><mpath href="#{{ $id }}-orbit" /></animateMotion></circle>
            <circle r="3" class="il-dot il-dot--far"><animateMotion dur="12s" repeatCount="indefinite"><mpath href="#{{ $id }}-orbit-far" /></animateMotion></circle>

            <rect x="162" y="82" width="76" height="76" rx="22" class="il-halo" />
            <rect x="168" y="88" width="64" height="64" rx="18" fill="url(#{{ $id }}-tile)" filter="url(#{{ $id }}-lift)" />
            {!! $icon('shield-check', 200, 120, 28, 'il-icon--white') !!}

            @foreach ([[264, 120, 'refresh'], [155, 75, 'trending'], [155, 165, 'layers']] as [$x, $y, $name])
                <circle cx="{{ $x }}" cy="{{ $y }}" r="17" class="il-panel" />
                {!! $icon($name, $x, $y, 15, 'il-icon--accent') !!}
            @endforeach
            @break
    @endswitch
</svg>

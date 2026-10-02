{{-- `menu`: "site" (the real one), or a kept test variant: "panel", "theodore".
     `headerTheme`: "dark" when the page opens on a dark hero (white logo + links). --}}
@props(['title' => null, 'menu' => 'site', 'headerTheme' => 'light'])

@php
    $siteName = app(\App\Settings\CompanySettings::class)->name;
    $suffix = app(\App\Settings\SeoSettings::class)->title_suffix;
    $fullTitle = $title ? $title.$suffix : $siteName;
@endphp

<!DOCTYPE html>
<html lang="ro">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{--
            Page transitions: a page reached by an animated link starts covered
            by the curtain, decided here — before anything paints — so content
            never flashes ahead of the reveal. See js/public/page-transition.ts.
        --}}
        <script>
            try {
                if (sessionStorage.getItem('webis:curtain')) {
                    document.documentElement.classList.add('curtain-cover');
                }
            } catch (e) {}
        </script>

        {{--
            The opening intro (js/public/hero-intro.ts): decided here too, so
            the hero starts hidden instead of flashing. On every full load —
            a visit, a reload — but not behind the page curtain, not when
            going Back/Forward, never with reduced motion. If the script has
            not taken over within 4s, the page is simply shown.
        --}}
        <script>
            try {
                var root = document.documentElement;
                var nav = performance.getEntriesByType('navigation')[0];

                if (! root.classList.contains('curtain-cover')
                    && ! (nav && nav.type === 'back_forward')
                    && ! window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    root.classList.add('intro');
                    setTimeout(function () {
                        if (! root.classList.contains('intro-running')) {
                            root.classList.remove('intro');
                        }
                    }, 4000);
                }
            } catch (e) {}
        </script>

        <title>{{ $fullTitle }}</title>

        {{-- Nothing outside production may be indexed. SEO head tags come in Phase 4. --}}
        @unless (app()->isProduction())
            <meta name="robots" content="noindex, nofollow">
        @endunless

        @include('partials.favicons')

        @fonts

        {{-- Public entry only: Vue islands, never the Inertia admin runtime. --}}
        @vite(['resources/css/app.css', 'resources/js/public.ts'])

        {{-- Pushed by the page, e.g. the hero image preload from <x-picture lcp>. --}}
        @stack('head')

        {{--
            Start fetching a page as soon as a link is hovered (or pressed on
            touch), so the time behind the transition curtain is short. Prefetch,
            not prerender: a prerendered page would run its <head> script before
            the click that sets the curtain flag.
        --}}
        <script type="speculationrules">
            {
                "prefetch": [{
                    "where": {
                        "and": [
                            { "href_matches": "/*" },
                            { "not": { "href_matches": "/admin/*" } },
                            { "not": { "selector_matches": "[data-no-transition]" } }
                        ]
                    },
                    "eagerness": "moderate"
                }]
            }
        </script>
    </head>
    <body class="min-h-screen bg-white font-sans text-neutral-900 antialiased">
        {{--
            Fixed to the screen — the round button and the menu — so outside
            the smooth-scrolled content below (a transformed parent would carry
            them away). The kept test variants sit here whole.
        --}}
        @switch ($menu)
            @case ('theodore')
                <x-site.header-theodore />
                @break
            @case ('panel')
                <x-site.header-panel :theme="$headerTheme" />
                @break
            @default
                {{-- The round button is the menu's only toggle (it turns into the ✕). --}}
                <x-site.burger menu="meniu" kind="theodore" />
                <x-site.theodore-menu id="meniu" :close="false" />
        @endswitch

        {{--
            A background fixed behind the page: the opening hero's (pushed by
            blocks/hero.blade.php). The page scrolls over it, above it in the
            stacking order (z-[1] below).
        --}}
        @stack('backdrop')

        {{--
            Smooth scrolling (GSAP ScrollSmoother, js/public/smooth-scroll.ts):
            everything that scrolls lives in here. Without JavaScript, or with
            reduced motion, these are plain blocks and the page scrolls natively.
        --}}
        <div id="smooth-wrapper" class="relative z-[1]">
            <div id="smooth-content">
                @if ($menu === 'site')
                    <x-site.top-bar menu="meniu" kind="theodore" :theme="$headerTheme" />
                @endif

                {{-- data-theo-shift: lifted out of view while the Theodore menu opens. --}}
                <main data-transition-shift data-theo-shift>
                    {{ $slot }}
                </main>
            </div>
        </div>

        {{-- Page-transition curtain; paths use a 0–100 box stretched to the screen. --}}
        <div class="page-curtain-cover" aria-hidden="true"></div>
        <svg class="page-curtain" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
            <defs>
                {{-- The curtain's shape as a clip for its grain layer below:
                     0–1 of that layer's box, so the 0–100 path is scaled down. --}}
                <clipPath id="page-curtain-clip" clipPathUnits="objectBoundingBox">
                    <use href="#page-curtain-path" transform="scale(0.01)" />
                </clipPath>
            </defs>
            <path id="page-curtain-path" data-curtain-path d="M 0 0 V 0 Q 50 0 100 0 V 0 z" />
        </svg>
        <div class="page-curtain-grain" aria-hidden="true"></div>
    </body>
</html>

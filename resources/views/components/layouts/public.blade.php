{{-- `menu`: "site" (the real one), or a kept test variant: "panel", "theodore".
     `headerTheme`: "dark" when the page opens on a dark hero (white logo + links).
     `lowerTone`: "dark" or "light" — the home page's sections below the hero,
     and the page around them; other pages leave it out
     (config/site.php; css/site/lower-tone.css).
     `heroLeave`: "overlap", on every page — the next section slides over
     the hero's fixed backdrop with a rounded top, the backdrop holding still
     (js/public/hero-intro.ts); "card", the backdrop shrinking into a card
     instead, is kept for trials.
     `description`: the meta description (else the SEO settings' default).
     `fullTitle`: the whole <title>, as given — without the site suffix.
     `footerCtaKey`: whose closing call the footer gives (Support\FooterCta,
     its wording in Setări › Subsol); null for the default.
     `footerW`: the Webis W forming beside the footer's call: on every page
     but the home page's (false there), whose call is text alone.
     `canonical`: the page's canonical URL, as <link rel="canonical">. Left
     out, the address asked for, on the canonical site, with its trailing
     slash and without its query string (PublicUrls::canonical); false for
     none (the error pages).
     `noindex`: true to keep the page out of search engines (its SEO data).
     `variant`: a design variant under test (e.g. "clienti"), as data-variant
     on <html>, for styles scoped to it.
     Sharing and structured data (Support\Seo\StructuredData):
     `ogImage`: the share image ({url, width, height}), else the SEO
     settings' default; `ogType`: "website" or "article".
     `localBusiness`: the Organization is a LocalBusiness too (home, /contact/).
     `crumb`: the page's name in its breadcrumbs (else `title`);
     `crumbParent`: [name, path] in place of the path's section (an article
     under Blog); `schema`: the page's own JSON-LD nodes (a BlogPosting). --}}
@props(['title' => null, 'fullTitle' => null, 'menu' => 'site', 'headerTheme' => 'light', 'lowerTone' => null, 'variant' => null, 'heroLeave' => 'overlap', 'footerCta' => true, 'footerOnBackdrop' => false, 'description' => null, 'footerCtaKey' => null, 'footerW' => true, 'canonical' => null, 'noindex' => false, 'ogImage' => null, 'ogType' => 'website', 'localBusiness' => false, 'crumb' => null, 'crumbParent' => null, 'schema' => []])

@php
    $siteName = app(\App\Settings\CompanySettings::class)->name;
    $suffix = app(\App\Settings\SeoSettings::class)->title_suffix;
    $fullTitle = $fullTitle ?? ($title ? $title.$suffix : $siteName);
    // The page's own, or the site's default (SEO settings).
    $metaDescription = $description ?: app(\App\Settings\SeoSettings::class)->default_description;
    $canonical = $canonical === false ? null : ($canonical ?: \App\Support\PublicUrls::canonical(request()->getPathInfo()));

    $structured = app(\App\Support\Seo\StructuredData::class);
    $ogImage ??= $structured->shareImage();
    // An error page (no canonical) gets no breadcrumbs.
    $breadcrumbs = $canonical === null ? null : \App\Support\Seo\StructuredData::breadcrumbs(request()->getPathInfo(), $crumb ?? $title ?? $siteName, $crumbParent);
    $jsonLd = $structured->graph($localBusiness, $breadcrumbs, $schema);
@endphp

<!DOCTYPE html>
<html lang="ro" @if ($lowerTone) data-lower="{{ $lowerTone }}" @endif @if ($variant) data-variant="{{ $variant }}" @endif @if ($heroLeave) data-hero-leave="{{ $heroLeave }}" @endif>
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

        {{-- Cookie consent: Google's signals denied until the visitor says otherwise. --}}
        @include('partials.consent-head')

        <title>{{ $fullTitle }}</title>
        @if ($canonical)
            <link rel="canonical" href="{{ $canonical }}">
        @endif
        @if ($metaDescription)
            <meta name="description" content="{{ $metaDescription }}">
        @endif

        {{-- Nothing outside production on the canonical host may be indexed
             (Support\Seo\Indexing, also sent as X-Robots-Tag); nor may a
             page set to noindex. --}}
        @unless (\App\Support\Seo\Indexing::siteAllowed(request()))
            <meta name="robots" content="noindex, nofollow">
        @else
            @if ($noindex)
                <meta name="robots" content="noindex, follow">
            @endif
        @endunless

        {{-- Sharing: the title and description as above, the canonical URL. --}}
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:site_name" content="{{ $siteName }}">
        <meta property="og:locale" content="ro_RO">
        <meta property="og:title" content="{{ $fullTitle }}">
        @if ($metaDescription)
            <meta property="og:description" content="{{ $metaDescription }}">
        @endif
        @if ($canonical)
            <meta property="og:url" content="{{ $canonical }}">
        @endif
        @if ($ogImage)
            <meta property="og:image" content="{{ $ogImage['url'] }}">
            @if ($ogImage['width'] && $ogImage['height'])
                <meta property="og:image:width" content="{{ $ogImage['width'] }}">
                <meta property="og:image:height" content="{{ $ogImage['height'] }}">
            @endif
        @endif
        @php($analytics = app(\App\Settings\AnalyticsSettings::class))
        @if ($analytics->google_site_verification)
            <meta name="google-site-verification" content="{{ $analytics->google_site_verification }}">
        @endif
        @if ($analytics->bing_site_verification)
            <meta name="msvalidate.01" content="{{ $analytics->bing_site_verification }}">
        @endif

        <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">

        <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_THROW_ON_ERROR) !!}</script>

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
    {{-- Background and text colour: .site-body (css/site/lower-tone.css). --}}
    <body class="site-body min-h-screen font-sans antialiased">
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

                <x-site.footer :show-cta="$footerCta" :on-backdrop="$footerOnBackdrop" :cta="\App\Support\FooterCta::for($footerCtaKey)" :w="$footerW" />
            </div>
        </div>

        {{-- Fixed at the bottom left until a choice is made; under the curtain. --}}
        <x-site.cookie-consent />

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

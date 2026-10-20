{{--
    The site's menu: a panel on the right of the screen (the whole screen on
    phones), revealed by Codrops' "Theodore" curtain (MIT) — an SVG band with
    curved edges that sweeps across the panel from the right and uncovers it
    behind itself; closing, the band sweeps back over it. The page behind
    dims, and a click on it closes the menu. Behaviour:
    js/public/theodore-menu.ts; it is opened by whatever carries
    data-menu-toggle="theodore" (and [data-theo-open]). Without JavaScript,
    CSS :target shows it.

    Inside, after the panel on dennissnellenberg.com: a label over a
    hairline, the pages large (Contact last; the current one with a dot; on hover
    the label rolls letter by letter, as in the top bar),
    the three services smaller, and at the foot how to reach us.

    `close`: render the menu's own ✕ — not needed when an outside toggle (the
    round header button) already turns into one.
--}}
@props(['id' => 'meniu', 'close' => true])

@php
    // The pages: home, the sections, Contact last.
    $pages = array_values(array_filter(
        \App\Support\SiteNavigation::links(),
        fn ($link) => $link['href'] === '/' || $link['place'] === 'section' || $link['cta'] !== null,
    ));

    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $socials = array_filter($company->social);
@endphp

{{-- Dims the page beside the panel; a click on it closes the menu. --}}
<a href="#" class="theo-backdrop" data-theo-backdrop tabindex="-1" aria-hidden="true"></a>

<div id="{{ $id }}" class="theo-menu" data-theo-menu>
    <div class="theo-menu__inner">
        <nav class="theo-menu__nav" aria-label="Meniu principal">
            <p class="theo-menu__label" data-theo-reveal>Navigare</p>
            <hr class="theo-menu__stripe" data-theo-reveal>

            <ul class="theo-menu__links">
                @foreach ($pages as $link)
                    <li>
                        <a href="{{ $link['href'] }}" @class(['theo-menu__link', 'is-active' => $link['current']]) data-theo-item @if ($link['current']) aria-current="page" @endif><x-ui.roll :text="$link['label']" /></a>
                    </li>
                @endforeach
            </ul>

            <div data-theo-reveal>
                <p class="theo-menu__label theo-menu__label--services">Servicii</p>
                <ul class="theo-menu__services">
                    @foreach (\App\Support\SiteNavigation::services() as $link)
                        <li>
                            <a href="{{ $link['href'] }}" @class(['theo-menu__service', 'is-active' => $link['current']]) data-theo-link @if ($link['current']) aria-current="page" @endif><x-ui.roll :text="$link['label']" /></a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <div class="theo-menu__foot" data-theo-reveal>
            @if ($company->email || $phoneHref)
                <div>
                    <p class="theo-menu__label">Contact</p>
                    <ul class="theo-menu__details">
                        @if ($company->email)
                            <li><a href="mailto:{{ $company->email }}">{{ $company->email }}</a></li>
                        @endif
                        @if ($phoneHref)
                            <li><a href="{{ $phoneHref }}">{{ $company->phone }}</a></li>
                        @endif
                    </ul>
                </div>
            @endif

            @if ($socials !== [])
                <div>
                    <p class="theo-menu__label">Social</p>
                    <ul class="theo-menu__details">
                        @foreach ($socials as $platform => $url)
                            <li><a href="{{ $url }}" target="_blank" rel="noopener">{{ ucfirst($platform) }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    @if ($close)
        {{-- Sits exactly where the menu button was, so open and close share a spot. --}}
        <a href="#" class="theo-menu__close" data-theo-close>
            <span class="sr-only">Închide meniul</span>
            <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
                <path d="M2 2l14 14M16 2L2 16" />
            </svg>
        </a>
    @endif
</div>

{{-- The curtain. Paths use a 0–100 box stretched to the viewport. --}}
<svg class="theo-overlay" width="100%" height="100%" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
    <defs>
        {{-- The curtain's shape as a clip for its grain layer below (0–1 of
             that layer's box, so the 0–100 path is scaled down). --}}
        <clipPath id="{{ $id }}-curtain-clip" clipPathUnits="objectBoundingBox">
            <use href="#{{ $id }}-curtain-path" transform="scale(0.01)" />
        </clipPath>
    </defs>
    <path id="{{ $id }}-curtain-path" class="theo-overlay__path" data-theo-overlay vector-effect="non-scaling-stroke" d="M 0 100 V 100 Q 50 100 100 100 V 100 z" />
</svg>
<div class="theo-overlay-grain" style="clip-path: url(#{{ $id }}-curtain-clip)" aria-hidden="true"></div>

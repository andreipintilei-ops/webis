{{--
    The full-screen menu. The curtain that opens and closes it is Codrops'
    "Theodore" (MIT): an SVG shape with a curved edge that sweeps over the
    page, then lifts to reveal the menu. Behaviour: js/public/theodore-menu.ts;
    it is opened by whatever carries data-menu-toggle="theodore" (and
    [data-theo-open]). Without JavaScript, CSS :target shows it.

    Inside, on the mid navy with grain and on the page's content column, one
    column top to bottom:
    - top: the white logo, centred on the round button, over a hairline;
    - the three services as rows (Anton, large), each with a line saying
      what it is — at the right on large screens, under the
      label on small ones; they rise in as it opens; on hover the others
      dim and the label rolls;
    - under them, smaller, the other pages (Clienți, Despre, Blog);
    - past a hairline, the one way to Contact: the call to action, with the
      company's e-mail and phone beside it, its address and profiles;
    - then © and the legal links.

    `close`: render the menu's own ✕ — not needed when an outside toggle (the
    round header button) already turns into one.
--}}
@props(['id' => 'meniu', 'close' => true])

@php
    $navigation = \App\Support\SiteNavigation::links(withHome: false);
    $services = \App\Support\SiteNavigation::services();
    $pages = array_values(array_filter($navigation, fn ($link) => $link['place'] === 'section'));
    $cta = collect($navigation)->first(fn ($link) => $link['cta'] !== null);
    $legal = \App\Support\SiteNavigation::legal();

    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));
    // Profiles come from Setări → Companie; the row is left out until there
    // is at least one.
    $socials = array_filter($company->social);
    $logo = app(\App\Support\Brand::class)->logoNegative();
@endphp

<div id="{{ $id }}" class="theo-menu" data-theo-menu>
    <div class="theo-menu__inner">
        <div class="theo-menu__top" data-theo-reveal>
            <a href="/" class="theo-menu__logo" data-theo-link aria-label="{{ $company->name }} — prima pagină">
                @if ($logo)
                    <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" class="h-7 w-auto md:h-8">
                @else
                    {{ $company->name }}
                @endif
            </a>
        </div>

        <div class="theo-menu__body">
            <nav class="theo-menu__nav" aria-label="Meniu principal">
                <ul class="theo-menu__list">
                    {{-- The three services, each with what it is. --}}
                    @foreach ($services as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="theo-menu__item" data-theo-item @if ($link['current']) aria-current="page" @endif>
                                {{-- The label rolls on hover: a copy waits below (css). --}}
                                <span class="theo-menu__mask"><span class="theo-menu__label">{{ $link['label'] }}</span></span>
                                <span class="theo-menu__blurb">{{ $link['blurb'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <ul class="theo-menu__pages" data-theo-reveal>
                    @foreach ($pages as $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="theo-menu__page" data-theo-link @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            </nav>
        </div>

        {{-- The one way to Contact: the call to action, and how else to reach us. --}}
        <div class="theo-menu__contact" data-theo-reveal>
            @if ($cta)
                <x-ui.pill :href="$cta['href']" tone="dark" data-theo-link :aria-current="$cta['current'] ? 'page' : null" class="theo-menu__cta">{{ $cta['cta'] }}</x-ui.pill>
            @endif

            @if ($company->email || $phoneHref)
                <p class="theo-menu__row">
                    @if ($company->email)
                        <a href="mailto:{{ $company->email }}" class="theo-menu__detail">{{ $company->email }}</a>
                    @endif
                    @if ($company->email && $phoneHref)
                        <span aria-hidden="true">·</span>
                    @endif
                    @if ($phoneHref)
                        <a href="{{ $phoneHref }}" class="theo-menu__detail">{{ $company->phone }}</a>
                    @endif
                </p>
            @endif

            @if ($address !== '' || $socials !== [])
                <div class="theo-menu__where">
                    @if ($address !== '')
                        <p class="theo-menu__row theo-menu__row--muted">{{ $address }}</p>
                    @endif

                    @if ($socials !== [])
                        <p class="theo-menu__row">
                            @foreach ($socials as $platform => $url)
                                @if (! $loop->first)
                                    <span aria-hidden="true">·</span>
                                @endif
                                <a href="{{ $url }}" class="theo-menu__detail" target="_blank" rel="noopener">{{ ucfirst($platform) }}</a>
                            @endforeach
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="theo-menu__foot" data-theo-reveal>
            <span>© {{ now()->year }} {{ $company->name }}</span>
            <nav aria-label="Informații legale" class="theo-menu__legal">
                @foreach ($legal as $link)
                    @if (! $loop->first)
                        <span aria-hidden="true">·</span>
                    @endif
                    <a href="{{ $link['href'] }}" class="theo-menu__detail" data-theo-link>{{ $link['label'] }}</a>
                @endforeach
            </nav>
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

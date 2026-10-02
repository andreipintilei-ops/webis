{{--
    The full-screen menu. The curtain that opens and closes it is Codrops'
    "Theodore" (MIT): an SVG shape with a curved edge that sweeps over the
    page, then lifts to reveal the menu. Behaviour: js/public/theodore-menu.ts;
    it is opened by whatever carries data-menu-toggle="theodore" (and
    [data-theo-open]). Without JavaScript, CSS :target shows it.

    Inside, on the mid navy with grain and on the page's content column:
    - top: the white logo, where the header's logo sits;
    - left: the sections, numbered, large (Anton), each with its small word
      under it — they rise in as it opens; on hover the others dim and the
      label rolls;
    - right, past a hairline: the grouped links (App\Support\SiteNavigation::
      groups()), then contact (Setări → Companie) and the call to action;
    - foot: a hairline, © and the legal links.

    `close`: render the menu's own ✕ — not needed when an outside toggle (the
    round header button) already turns into one.
--}}
@props(['id' => 'meniu', 'close' => true])

@php
    $navigation = \App\Support\SiteNavigation::links(withHome: false);
    $sections = array_values(array_filter($navigation, fn ($link) => $link['menu']));
    $cta = collect($navigation)->first(fn ($link) => $link['cta'] !== null);
    $groups = \App\Support\SiteNavigation::groups();
    $legal = \App\Support\SiteNavigation::legal();

    $company = app(\App\Settings\CompanySettings::class);
    $phoneHref = $company->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $company->phone) : null;
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));
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
                <ol class="theo-menu__list">
                    @foreach ($sections as $index => $link)
                        <li>
                            <a href="{{ $link['href'] }}" class="theo-menu__item" data-theo-item @if ($link['current']) aria-current="page" @endif>
                                <span class="theo-menu__index" aria-hidden="true">{{ sprintf('%02d', $index + 1) }}</span>
                                <span class="theo-menu__text">
                                    {{-- The label rolls on hover: a copy waits one line below (css). --}}
                                    <span class="theo-menu__mask"><span class="theo-menu__label">{{ $link['label'] }}</span></span>
                                    @if ($link['tiny'])
                                        <span class="theo-menu__tiny">{{ $link['tiny'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </nav>

            <div class="theo-menu__aside">
                <div class="theo-menu__groups" data-theo-reveal>
                    @foreach ($groups as $group)
                        <nav aria-label="{{ $group['label'] }}">
                            <p class="theo-menu__heading">{{ $group['label'] }}</p>
                            <ul class="theo-menu__details">
                                @foreach ($group['links'] as $link)
                                    <li><a href="{{ $link['href'] }}" class="theo-menu__detail" data-theo-link>{{ $link['label'] }}</a></li>
                                @endforeach
                            </ul>
                        </nav>
                    @endforeach
                </div>

                <div class="theo-menu__contact" data-theo-reveal>
                    @if ($company->email || $company->phone || $address !== '')
                        <p class="theo-menu__heading">Contact</p>
                        <ul class="theo-menu__details">
                            @if ($phoneHref)
                                <li><a href="{{ $phoneHref }}" class="theo-menu__detail">{{ $company->phone }}</a></li>
                            @endif
                            @if ($company->email)
                                <li><a href="mailto:{{ $company->email }}" class="theo-menu__detail">{{ $company->email }}</a></li>
                            @endif
                            @if ($address !== '')
                                <li class="theo-menu__detail">{{ $address }}</li>
                            @endif
                        </ul>
                    @endif

                    @if ($cta)
                        <x-ui.pill :href="$cta['href']" tone="dark" data-theo-link :aria-current="$cta['current'] ? 'page' : null" class="mt-8">{{ $cta['cta'] }}</x-ui.pill>
                    @endif
                </div>
            </div>
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

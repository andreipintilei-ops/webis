{{--
    The top bar, after dennissnellenberg.com: logo and links, scrolling away
    with the page. Its partner is the round button (site/burger), which scales
    in once the bar has gone — past ~30% of the first screen.

    `menu` is the id of the menu the phone "Meniu" link opens; `kind` says
    which script drives it ("theodore" or "panel"). Scroll behaviour:
    js/public/site-nav.ts.
--}}
@props(['menu', 'kind', 'theme' => 'light'])

@php
    // On a dark first screen (an image hero) the bar is white-on-dark.
    $brand = app(\App\Support\Brand::class);
    $logo = $theme === 'dark' ? $brand->logoNegative() : $brand->logo();
    $company = app(\App\Settings\CompanySettings::class);
@endphp

<header @class(['site-nav', 'site-nav--dark' => $theme === 'dark']) @if ($theme === 'dark') data-nav-tone="dark" @endif data-transition-shift>
    <a href="/" class="site-nav__logo" aria-label="{{ $company->name }} — prima pagină">
        @if ($logo)
            <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" class="h-7 w-auto md:h-8">
        @else
            {{ $company->name }}
        @endif
    </a>

    <nav aria-label="Principal">
        <ul class="site-nav__links">
            @foreach (\App\Support\SiteNavigation::links() as $link)
                @continue (! $link['bar'])

                @if ($link['cta'])
                    {{-- The call to action: the main button, as in the hero. --}}
                    <li class="hidden md:ml-4 md:block">
                        <x-ui.pill :href="\App\Support\TrailingSlash::href($link['href'])" :tone="$theme" :aria-current="$link['current'] ? 'page' : null">
                            {{ $link['cta'] }}
                        </x-ui.pill>
                    </li>
                @elseif ($link['children'] !== [])
                    {{--
                        A dropdown that opens on hover and on keyboard focus
                        (:focus-within), so tabbing walks into its links. The
                        item itself stays a link to the section overview.
                    --}}
                    <li class="site-nav__dropdown hidden md:block">
                        <a href="{{ \App\Support\TrailingSlash::href($link['href']) }}" @class(['site-link', 'is-active' => $link['current']]) @if ($link['current']) aria-current="page" @endif>
                            <x-ui.roll :text="$link['label']" />
                            {{-- A chevron: it turns over while the dropdown is open. --}}
                            <svg class="site-nav__chevron" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="M3 4.75 6 7.75l3-3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <div class="site-dropdown">
                            <ul class="site-dropdown__list">
                                @foreach ($link['children'] as $child)
                                    <li>
                                        <a href="{{ \App\Support\TrailingSlash::href($child['href']) }}" class="site-dropdown__link">{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @else
                    <li class="hidden md:block">
                        {{-- The label rolls letter by letter on hover (css/site/roll.css). --}}
                        <a href="{{ \App\Support\TrailingSlash::href($link['href']) }}" @class(['site-link', 'is-active' => $link['current']]) @if ($link['current']) aria-current="page" @endif>
                            <x-ui.roll :text="$link['label']" />
                        </a>
                    </li>
                @endif
            @endforeach
            <li class="md:hidden">
                <a href="#{{ $menu }}" class="site-link" data-menu-toggle="{{ $kind }}" aria-controls="{{ $menu }}" aria-expanded="false">
                    Meniu
                </a>
            </li>
        </ul>
    </nav>
</header>


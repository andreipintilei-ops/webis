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
            @foreach (\App\Support\SiteNavigation::links(withHome: false) as $link)
                @continue (! $link['bar'])

                @if ($link['cta'])
                    {{-- The call to action: the main button, small. --}}
                    <li class="hidden md:ml-4 md:block">
                        <x-ui.pill :href="$link['href']" size="sm" :tone="$theme" :aria-current="$link['current'] ? 'page' : null">
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
                        <a href="{{ $link['href'] }}" @class(['site-link', 'is-active' => $link['current']]) @if ($link['current']) aria-current="page" @endif>
                            <span class="site-link__mask"><span class="site-link__text">{{ $link['label'] }}</span></span>
                            <svg class="site-nav__caret" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                <path d="m3 4.5 3 3 3-3" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </a>
                        <div class="site-dropdown">
                            <ul class="site-dropdown__list">
                                @foreach ($link['children'] as $child)
                                    <li>
                                        <a href="{{ $child['href'] }}" class="site-dropdown__link">{{ $child['label'] }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </li>
                @else
                    <li class="hidden md:block">
                        {{-- Label in a mask so it can roll on hover (css/site/menu-panel.css). --}}
                        <a href="{{ $link['href'] }}" @class(['site-link', 'is-active' => $link['current']]) @if ($link['current']) aria-current="page" @endif>
                            <span class="site-link__mask"><span class="site-link__text">{{ $link['label'] }}</span></span>
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


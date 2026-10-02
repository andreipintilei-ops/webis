{{--
    Slide-in navigation panel, after dennissnellenberg.com: a dark panel slides
    in from the right, its curved left edge flattening as it arrives; the page
    dims and the links slide in one by one. Open/close is CSS driven by
    `nav-open` on <html> (css/site/menu-panel.css); js/public/panel-menu.ts
    toggles it. Without JavaScript, CSS :target opens it.
--}}
@php
    $socials = array_filter(app(\App\Settings\CompanySettings::class)->social);
@endphp

{{-- Dims the page; a click (or the #-link without JS) closes the panel. --}}
<a href="#" class="site-panel-backdrop" data-panel-backdrop tabindex="-1" aria-hidden="true"></a>

<div id="site-panel" class="site-panel" data-panel>
    {{--
        The bulge on the panel's leading edge, drawn like the Theodore curtain:
        one quadratic curve from the top corner, out to the strip's left edge
        at mid-height, and back to the bottom corner. The strip narrows to
        nothing as the panel lands, so the curve flattens into a straight edge.
    --}}
    <svg class="site-panel__curve" viewBox="0 0 100 100" preserveAspectRatio="none" aria-hidden="true">
        <path d="M 100 0 Q -100 50 100 100 Z" />
    </svg>

    <div class="site-panel__inner">
        <nav class="site-panel__section" aria-label="Meniu">
            <p class="site-panel__label">Navigare</p>
            <hr class="site-panel__stripe">
            <ul class="site-panel__links">
                @foreach (\App\Support\SiteNavigation::links() as $link)
                    <li class="site-panel__item">
                        <a href="{{ $link['href'] }}" @class(['site-panel__link', 'is-active' => $link['current']]) @if ($link['current']) aria-current="page" @endif>
                            {{ $link['label'] }}
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @if ($socials !== [])
            <div class="site-panel__section">
                <p class="site-panel__label">Social</p>
                <ul class="site-panel__socials">
                    @foreach ($socials as $platform => $url)
                        <li>
                            <a href="{{ $url }}" class="site-link site-link--light" target="_blank" rel="noopener">
                                {{ ucfirst($platform) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
</div>

{{--
    The site's footer, on every public page, on the violet WebGL gradient
    (js/public/soffit.ts, which pauses it while it is off screen): a closing
    call to talk with the ways to reach us, then the site's links in groups
    (App\Support\SiteNavigation) and the company's details
    (CompanySettings), then the logo, the legal line and links.
    css/site/footer.css.
--}}
@php
    $company = app(\App\Settings\CompanySettings::class);
    $logo = app(\App\Support\Brand::class)->logoNegative();
    $phoneHref = $company->phone ? 'tel:'.preg_replace('/[^\d+]/', '', $company->phone) : null;
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));

    $pages = array_values(array_filter(
        \App\Support\SiteNavigation::links(withHome: false),
        fn ($link) => $link['menu'] || $link['cta'],
    ));
@endphp

<footer class="site-footer" data-nav-tone="dark">
    <div class="site-footer__backdrop" aria-hidden="true">
        <canvas data-soffit data-palette="violet" class="absolute inset-0 block size-full"></canvas>
        <div class="site-footer__wash"></div>
    </div>

    <div class="site-footer__inner">
        {{-- The closing call, and the ways to reach us. --}}
        <div class="site-footer__cta" data-reveal="head">
            <div>
                <p class="site-footer__label">Contact</p>
                <h2 class="site-footer__title">Ai un proces care merită<br> un sistem mai bun?</h2>
                <p class="site-footer__intro">Spune-ne cum lucrezi acum. Îți răspundem în aceeași zi lucrătoare.</p>
            </div>
            <div class="site-footer__reach">
                <x-ui.pill href="/contact" tone="dark">Hai să discutăm</x-ui.pill>
                <div class="site-footer__direct">
                    @if ($phoneHref)
                        <a href="{{ $phoneHref }}">{{ $company->phone }}</a>
                    @endif
                    @if ($company->email)
                        <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                    @endif
                </div>
            </div>
        </div>

        {{-- The site, in groups; then where we are. --}}
        <nav class="site-footer__nav" aria-label="Subsol">
            @foreach (\App\Support\SiteNavigation::groups() as $group)
                <div class="site-footer__group">
                    <h3 class="site-footer__group-label">{{ $group['label'] }}</h3>
                    <ul>
                        @foreach ($group['links'] as $link)
                            <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="site-footer__group">
                <h3 class="site-footer__group-label">Webis</h3>
                <ul>
                    @foreach ($pages as $page)
                        <li><a href="{{ $page['href'] }}">{{ $page['label'] }}</a></li>
                    @endforeach
                </ul>
            </div>

            <div class="site-footer__group">
                <h3 class="site-footer__group-label">Unde ne găsești</h3>
                <address class="site-footer__address">
                    @if ($address !== '')
                        <span>{{ $address }}</span>
                    @endif
                    @if ($phoneHref)
                        <a href="{{ $phoneHref }}">{{ $company->phone }}</a>
                    @endif
                    @if ($company->email)
                        <a href="mailto:{{ $company->email }}">{{ $company->email }}</a>
                    @endif
                </address>
                <x-site.google-rating tone="dark" class="site-footer__rating" />
            </div>
        </nav>

        {{-- The logo, the legal line and links. --}}
        <div class="site-footer__bottom">
            <a href="/" class="site-footer__logo" aria-label="{{ $company->name }} — prima pagină">
                @if ($logo)
                    <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" loading="lazy">
                @else
                    {{ $company->name }}
                @endif
            </a>
            <p class="site-footer__legal">
                © {{ now()->year }} {{ $company->legal_name }}
                @if ($company->vat_id)
                    · CUI {{ $company->vat_id }}
                @endif
                @if ($company->registration_number)
                    · {{ $company->registration_number }}
                @endif
            </p>
            <ul class="site-footer__legal-links">
                @foreach (\App\Support\SiteNavigation::legal() as $link)
                    <li><a href="{{ $link['href'] }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</footer>

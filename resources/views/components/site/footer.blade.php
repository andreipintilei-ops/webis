{{--
    The site's footer, on every public page, on the violet WebGL gradient
    (js/public/soffit.ts, which pauses it while it is off screen): a closing
    call to talk with the ways to reach us, then the site's links in groups
    (App\Support\SiteNavigation) and the company's details
    (CompanySettings), then the logo, the legal line and links.
    css/site/footer.css.

    On a page that already carries the gradient fixed behind it, pass
    `on-backdrop` and the footer shows through to that one instead.
    `cta-title`, `cta-intro`: a page's own wording for the closing call
    (/clienti), in place of the default.
--}}
@props(['showCta' => true, 'onBackdrop' => false, 'ctaTitle' => null, 'ctaIntro' => null])

@php
    $company = app(\App\Settings\CompanySettings::class);
    $logo = app(\App\Support\Brand::class)->logoNegative();
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));

    $pages = array_values(array_filter(
        \App\Support\SiteNavigation::links(withHome: false),
        fn ($link) => $link['menu'] || $link['cta'],
    ));
@endphp

{{-- `onBackdrop`: the page already has this gradient fixed behind it (the
     contact page), so the footer shows through to it instead of drawing a
     second one. It becomes a backdrop area, which is what keeps that fixed
     gradient alive this far down the page. --}}
<footer @class(['site-footer', 'site-footer--compact' => ! $showCta, 'site-footer--on-backdrop' => $onBackdrop]) data-nav-tone="dark" @if ($onBackdrop) data-backdrop-area @endif>
    <div class="site-footer__backdrop" aria-hidden="true">
        @unless ($onBackdrop)
            <canvas data-soffit data-palette="violet" class="absolute inset-0 block size-full"></canvas>
        @endunless
        <div class="site-footer__wash"></div>
    </div>

    <div class="site-footer__inner">
        {{-- The closing call, and the ways to reach us. --}}
        @if ($showCta)
        <div class="site-footer__cta" data-reveal="head">
            <div>
                <p class="site-footer__label">Contact</p>
                <h2 class="site-footer__title">@if ($ctaTitle){{ $ctaTitle }}@else Site,<br> platformă sau aplicație?@endif</h2>
                <p class="site-footer__intro">{{ $ctaIntro ?? 'Spune-ne cum lucrezi acum. Îți răspundem în aceeași zi lucrătoare.' }}</p>
            </div>
            <div class="site-footer__reach">
                <x-ui.pill href="/contact/" tone="dark">Hai să discutăm</x-ui.pill>
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
        @endif

        {{-- The site, in groups; then where we are. --}}
        <nav class="site-footer__nav" aria-label="Subsol">
            @foreach (\App\Support\SiteNavigation::groups() as $group)
                <div class="site-footer__group">
                    <h3 class="site-footer__group-label">{{ $group['label'] }}</h3>
                    <ul>
                        @foreach ($group['links'] as $link)
                            <li><a href="{{ \App\Support\TrailingSlash::href($link['href']) }}">{{ $link['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <div class="site-footer__group">
                <h3 class="site-footer__group-label">Webis</h3>
                <ul>
                    @foreach ($pages as $page)
                        <li><a href="{{ \App\Support\TrailingSlash::href($page['href']) }}">{{ $page['label'] }}</a></li>
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
                    <li><a href="{{ \App\Support\TrailingSlash::href($link['href']) }}">{{ $link['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</footer>

{{--
    The site's footer, on every public page, on the violet WebGL gradient
    (js/public/soffit.ts, which pauses it while it is off screen): a closing
    call to talk with the ways to reach us, then the site's links in groups
    (App\Support\SiteNavigation) and the company's details
    (CompanySettings): the services over the site's pages, the legal ones,
    the address with the Google rating and the social profiles; then the
    logo, the legal line and the ANPC SAL/SOL pictograms.
    css/site/footer.css.

    On a page that already carries the gradient fixed behind it, pass
    `on-backdrop` and the footer shows through to that one instead.
    `cta-title`, `cta-intro`, `cta-button`: a page's own wording for the closing call
    (/clienti), in place of the default. `w`: the call's words and ways in
    the left half, and the Webis W forming in the right half, over the
    gradient, as it scrolls in (js/public/finale-w.ts; /portofoliu,
    /software-la-comanda, /web, /produse).
--}}
@props(['showCta' => true, 'onBackdrop' => false, 'ctaTitle' => null, 'ctaIntro' => null, 'ctaButton' => null, 'w' => false])

@php
    $company = app(\App\Settings\CompanySettings::class);
    $logo = app(\App\Support\Brand::class)->logoNegative();
    $phoneHref = \App\Support\PhoneLink::href($company->phone);
    $address = implode(', ', array_filter([$company->street_address, $company->locality]));

    // The profiles set in Setări (CompanySettings::$social), by their names.
    $socials = array_filter($company->social);
    $socialNames = ['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'];

    // Not Servicii, no page of its own: the services are their own group,
    // first; the industry index is in "Web și e-commerce".
    $pages = array_values(array_filter(
        \App\Support\SiteNavigation::links(withHome: false),
        fn ($link) => ($link['menu'] || $link['cta']) && $link['href'] !== null,
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
        <div @class(['site-footer__cta', 'site-footer__cta--w' => $w]) data-reveal="head">
            <div>
                @unless ($w)
                    <p class="site-footer__label">Contact</p>
                @endunless
                <h2 class="site-footer__title">{{ $ctaTitle ?? 'Ai un proiect în minte?' }}</h2>
                <p class="site-footer__intro">{{ $ctaIntro ?? 'Un site de prezentare, un magazin online sau software la comandă: spune-ne ce vrei să construiești și revenim cu pașii următori.' }}</p>
            </div>
            @if ($w)
                {{-- The W, forming as the footer comes up; drawn in CSS
                     without WebGL (the script adds stage-finale--still). --}}
                <div class="site-footer__w" data-finale-w data-w-fill="0.92" aria-hidden="true">
                    <div class="site-footer__w-slot" data-stage-slot>
                        <div class="stage-finale__still"><i></i><i></i><i></i><i></i><i></i></div>
                    </div>
                </div>
            @endif
            <div class="site-footer__reach">
                <x-ui.pill href="/contact/" tone="dark">{{ $ctaButton ?? 'Hai să discutăm' }}</x-ui.pill>
                {{-- Beside the W, only the button: the phone and e-mail are in
                     the last column below. --}}
                @unless ($w)
                <div class="site-footer__direct">
                    @if ($phoneHref)
                        <a href="{{ $phoneHref }}" class="site-footer__link"><x-ui.roll :text="$company->phone" /></a>
                    @endif
                    @if ($company->email)
                        <a href="mailto:{{ $company->email }}" class="site-footer__link"><x-ui.roll :text="$company->email" /></a>
                    @endif
                </div>
                @endunless
            </div>
        </div>
        @endif

        {{-- The site, in groups — the services first, over the pages; then
             the legal ones, and where we are. --}}
        <nav class="site-footer__nav" aria-label="Subsol">
            @foreach (\App\Support\SiteNavigation::groups() as $group)
                <div class="site-footer__group">
                    <h3 class="site-footer__group-label">{{ $group['label'] }}</h3>
                    <ul>
                        @foreach ($group['links'] as $link)
                            <li><a href="{{ \App\Support\TrailingSlash::href($link['href']) }}" class="site-footer__link"><x-ui.roll :text="$link['label']" /></a></li>
                        @endforeach
                    </ul>

                    @if ($loop->first)
                        <h3 class="site-footer__group-label site-footer__group-label--follow">Webis</h3>
                        <ul>
                            @foreach ($pages as $page)
                                <li><a href="{{ \App\Support\TrailingSlash::href($page['href']) }}" class="site-footer__link"><x-ui.roll :text="$page['label']" /></a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach

            <div class="site-footer__group">
                <h3 class="site-footer__group-label">Informații legale</h3>
                <ul>
                    @foreach (\App\Support\SiteNavigation::legal() as $link)
                        <li><a href="{{ \App\Support\TrailingSlash::href($link['href']) }}" class="site-footer__link"><x-ui.roll :text="$link['label']" /></a></li>
                    @endforeach
                    {{-- Reopens the cookie banner (js/public/cookie-consent.ts), while there is something to choose. --}}
                    @if (app(\App\Support\Consent\CookieConsent::class)->needed())
                        <li><button type="button" class="site-footer__link" data-cookie-settings><x-ui.roll text="Setări cookies" /></button></li>
                    @endif
                </ul>
            </div>

            <div class="site-footer__group">
                <h3 class="site-footer__group-label">Unde ne găsești</h3>
                <address class="site-footer__address">
                    @if ($address !== '')
                        <span>{{ $address }}</span>
                    @endif
                    @if ($phoneHref)
                        <a href="{{ $phoneHref }}" class="site-footer__link"><x-ui.roll :text="$company->phone" /></a>
                    @endif
                    @if ($company->email)
                        <a href="mailto:{{ $company->email }}" class="site-footer__link"><x-ui.roll :text="$company->email" /></a>
                    @endif
                </address>
                <x-site.google-rating tone="dark" class="site-footer__rating" />

                @if ($socials !== [])
                    <h3 class="site-footer__group-label site-footer__group-label--follow">Social</h3>
                    <ul class="site-footer__socials">
                        @foreach ($socials as $platform => $url)
                            <li>
                                {{-- The logo and its copy above it: they roll on hover, as the
                                     links' letters (css/site/footer.css). --}}
                                <a href="{{ $url }}" class="site-footer__social" target="_blank" rel="noopener" aria-label="Webis pe {{ $socialNames[$platform] ?? ucfirst($platform) }}">
                                    <span class="site-footer__social-roll">
                                        <x-ui.social-icon :platform="$platform" />
                                        <x-ui.social-icon :platform="$platform" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </nav>

        {{-- The logo, the legal line and the ANPC pictograms. --}}
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
            {{-- The ANPC pictograms for alternative and online dispute
                 resolution (SAL, SOL), which Romanian law requires on the site. --}}
            <ul class="site-footer__anpc">
                <li>
                    <a href="https://anpc.ro/ce-este-sal/" target="_blank" rel="nofollow noopener">
                        <img src="/images/legal/anpc-sal.png" alt="ANPC — Soluționarea alternativă a litigiilor" width="195" height="50" loading="lazy">
                    </a>
                </li>
                <li>
                    <a href="https://ec.europa.eu/consumers/odr/main/index.cfm?event=main.home2.show&amp;lng=RO" target="_blank" rel="nofollow noopener">
                        <img src="/images/legal/anpc-sol.png" alt="Soluționarea online a litigiilor" width="195" height="50" loading="lazy">
                    </a>
                </li>
            </ul>
        </div>
    </div>
</footer>

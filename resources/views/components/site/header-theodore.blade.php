{{--
    Codrops' "Theodore" (MIT) as published: its own header with a round menu
    button, and the full-screen menu. Not used at the moment (layout menu="theodore").
--}}
@php
    $brand = app(\App\Support\Brand::class);
    $logo = $brand->logo();
    $logoNegative = $brand->logoNegative();
@endphp

<header class="theo-header" data-theo-header>
    {{-- Both logos are in the page; CSS shows the negative one on the open menu. --}}
    <a href="/" class="theo-header__logo" aria-label="{{ app(\App\Settings\CompanySettings::class)->name }} — prima pagină">
        @if ($logo)
            <img src="{{ $logo['url'] }}" alt="" width="{{ $logo['width'] }}" height="{{ $logo['height'] }}" class="theo-header__logo-image">
            <img src="{{ $logoNegative['url'] }}" alt="" width="{{ $logoNegative['width'] }}" height="{{ $logoNegative['height'] }}" class="theo-header__logo-image theo-header__logo-image--negative">
        @else
            webis
        @endif
    </a>

    <a href="#meniu-theodore" class="theo-header__button" data-theo-open aria-controls="meniu-theodore" aria-expanded="false">
        <span class="sr-only">Deschide meniul</span>
        <svg width="19" height="12" viewBox="0 0 19 12" aria-hidden="true">
            <path d="m.742 3.26.485.874c.043-.024.13-.07.26-.136.22-.11.476-.233.765-.361A22.92 22.92 0 0 1 4.997 2.62c4.476-1.34 8.75-1.219 12.241 1.1.18.12.357.245.531.376l.6-.8a12.46 12.46 0 0 0-.578-.408C14.008.375 9.443.246 4.71 1.663c-1.037.31-2 .675-2.865 1.06a18.83 18.83 0 0 0-1.103.536Z" />
            <path d="m.742 6.748.485.874c.043-.023.13-.07.26-.135.22-.111.476-.233.765-.362A22.92 22.92 0 0 1 4.997 6.11c4.476-1.34 8.75-1.22 12.241 1.1.18.12.357.245.531.375l.6-.8a12.46 12.46 0 0 0-.578-.408C14.008 3.864 9.443 3.735 4.71 5.152c-1.037.31-2 .675-2.865 1.06a18.83 18.83 0 0 0-1.103.536Z" />
            <path d="m.742 10.237.485.874c.043-.024.13-.07.26-.136.22-.11.476-.232.765-.36a22.92 22.92 0 0 1 2.745-1.016c4.476-1.34 8.75-1.22 12.241 1.1.18.12.357.244.531.375l.6-.8a12.46 12.46 0 0 0-.578-.408C14.008 7.353 9.443 7.224 4.71 8.64c-1.037.31-2 .674-2.865 1.06a18.83 18.83 0 0 0-1.103.536Z" />
        </svg>
    </a>
</header>

<x-site.theodore-menu id="meniu-theodore" />

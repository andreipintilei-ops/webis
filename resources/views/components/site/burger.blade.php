{{--
    The round menu button, after dennissnellenberg.com (behaviour:
    js/public/site-nav.ts). `menu` is the id of the menu it opens; `kind` says
    which script drives it ("theodore" or "panel").

    Fixed to the screen, so it lives outside the smooth-scrolled content
    (a transformed parent would carry it away).

    Appears once the top bar has scrolled away; stays put while the menu is
    open. Two layers: the wrapper only scales in and out, the round button
    inside carries the look — so the two transforms never fight. Its colour
    flips to stay readable over dark surfaces (site-nav.ts).
--}}
@props(['menu', 'kind'])

<div class="site-burger">
    <a href="#{{ $menu }}" class="site-burger__button" data-menu-toggle="{{ $kind }}" data-burger aria-controls="{{ $menu }}" aria-expanded="false">
        <span class="site-burger__bars" aria-hidden="true"></span>
        <span class="sr-only">Meniu</span>
    </a>
</div>

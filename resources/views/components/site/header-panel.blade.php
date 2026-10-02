{{--
    The dennissnellenberg.com menu as built first: top bar and round button
    opening the slide-in panel. Not used at the moment (layout menu="panel").
--}}
@props(['theme' => 'light'])

<x-site.top-bar menu="site-panel" kind="panel" :theme="$theme" />
<x-site.burger menu="site-panel" kind="panel" />
<x-site.panel />

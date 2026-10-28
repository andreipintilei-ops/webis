{{--
    The closing call on its indigo panel, with the Webis W standing above the
    text: the home page's finale (components/site/stage), and /portofoliu's
    close. The ground is a plain CSS gradient; css/site/variant-stage.css.

    On the home page the W is the stage's own object, its plates flying in
    from the chapters above (js/public/shapeshifter.ts). `standalone`: no
    stage to come from, so the panel draws its own W, forming out of nowhere
    as it scrolls in (js/public/finale-w.ts). Without WebGL, either way, the
    five squares are drawn in CSS (the script adds `stage-finale--still`).

    `title`, `line`: the call; `href`, `label`: its button.
--}}
@props([
    'title' => 'Site, platformă sau aplicație?',
    'line' => 'Un site nou, un magazin online sau o platformă pentru toată echipa.',
    'href' => '/contact/',
    'label' => 'Discută un proiect',
    'standalone' => false,
])

<div {{ $attributes->class(['stage-finale', 'stage-finale--standalone' => $standalone]) }} {{ $standalone ? 'data-finale-w' : 'data-stage-finale' }} data-nav-tone="dark">
    {{-- The entry is on the ground, never on the panel: a transform or a
         part-way opacity there would make it a stacking context, and the
         home page's object would stop passing between this and the text. --}}
    <div class="stage-finale__ground" aria-hidden="true" data-reveal></div>

    {{-- Where the W stands. --}}
    <div class="stage-finale__slot" data-stage-slot aria-hidden="true">
        <div class="stage-finale__still"><i></i><i></i><i></i><i></i><i></i></div>
    </div>

    <div class="stage-finale__body" data-reveal="head">
        <h2 class="stage-finale__title">{{ $title }}</h2>
        <p class="stage-finale__line">{{ $line }}</p>
        <x-ui.pill :href="$href" tone="dark">{{ $label }}</x-ui.pill>
    </div>
</div>

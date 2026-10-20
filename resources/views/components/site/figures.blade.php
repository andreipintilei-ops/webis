{{--
    Webis in figures: a band right under an internal page's hero, on its
    background (statement--dark), four figures in a row (two by two on
    phones) under a hairline — /despre and /portofoliu. css/site/figures.css.

    DUMMY DATA: the two counts are placeholders, to replace with the real
    figures before launch.
--}}
@php
    $figures = [
        ['2016', 'Anul în care am început'],
        ['4', 'Produse SaaS proprii'],
        ['150+', 'Proiecte livrate'],
        ['40+', 'Organizații care folosesc produsele noastre'],
    ];
@endphp

<section {{ $attributes->class('statement statement--dark figures') }} aria-label="Webis în cifre" data-backdrop-area data-nav-tone="dark">
    <dl class="figures__list" data-reveal>
        @foreach ($figures as [$value, $label])
            <div class="figures__item" data-reveal-child>
                <dt class="sr-only">{{ $label }}</dt>
                <dd class="figures__value">{{ $value }}</dd>
                <dd class="figures__label" aria-hidden="true">{{ $label }}</dd>
            </div>
        @endforeach
    </dl>
</section>

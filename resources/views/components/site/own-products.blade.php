{{--
    Section 6, "Produsele noastre": Webis's own products, by name — not as
    portfolio. The second product's
    name is still to be decided (bracketed until it is).

    `layout`: "list" — two products side by side between hairlines; "stack"
    (the violet pages) — full-screen cards stacking on scroll.
--}}
@props(['layout' => 'list'])

@php
    $products = [
        [
            'name' => 'SmileSoft',
            'for' => 'Pentru clinici stomatologice.',
            'detail' => 'Software, echipamente și aplicație pentru pacienți.',
            'link' => ['label' => 'smilesoft.ro', 'href' => 'https://smilesoft.ro'],
            'variant' => 'institution',
        ],
        [
            'name' => '[Numele produsului]',
            'for' => 'Pentru universități.',
            'detail' => 'Administrarea proiectelor, a finanțărilor și a raportărilor.',
            'link' => ['label' => 'Află mai multe', 'href' => '/produse'],
            'variant' => 'university',
        ],
        // TODO: a placeholder for a third product — only in the stack for now.
        [
            'name' => '[Numele produsului]',
            'for' => 'Pentru instituții publice.',
            'detail' => 'Registratură, documente și termene, într-un singur loc.',
            'link' => ['label' => 'Află mai multe', 'href' => '/produse'],
            'variant' => 'company',
            'stack_only' => true,
        ],
    ];

    // The list (home page) keeps to its two products.
    $listed = array_values(array_filter($products, fn ($product) => empty($product['stack_only'])));
@endphp

@if ($layout === 'stack')
{{-- The products as screen-high rounded cards stacking on scroll: each comes
     in as the card on mews.fm does — a little smaller and tilted, settling
     straight and full size — then holds while the next slides up over it,
     shrinking and tilting as on gethyped.nl (css/site/product-stack.css,
     js/public/product-stack.ts). Below 1024px, and with reduced motion,
     simply cards one after another. --}}
<section id="produsele-noastre" class="product-stack" aria-labelledby="product-stack-title" data-product-stack data-nav-tone="dark">
    <header class="product-stack__head">
        <div class="md:col-span-7">
            <p class="product-stack__label">Produsele noastre</p>
            <h2 id="product-stack-title" class="product-stack__title">Software gata de folosit,<br>pe abonament.</h2>
        </div>
        <p class="product-stack__intro">Produse proprii, construite și întreținute de aceeași echipă.</p>
    </header>

    <div class="product-stack__cards">
        @foreach ($products as $product)
            <article class="product-card product-card--{{ $loop->iteration }}" style="--i: {{ $loop->index }}" aria-labelledby="product-card-{{ $loop->iteration }}" data-product-card>
                {{-- The entry moves this frame; being covered moves the panel. --}}
                <div class="product-card__frame" data-product-entry>
                <div class="product-card__panel" data-product-panel>
                    <div class="product-card__content">
                        <p class="product-card__count">{{ sprintf('%02d', $loop->iteration) }} / {{ sprintf('%02d', count($products)) }} · Produs propriu</p>
                        <h3 id="product-card-{{ $loop->iteration }}" class="product-card__name">{{ $product['name'] }}</h3>
                        <p class="product-card__for">{{ $product['for'] }}</p>
                        <p class="product-card__detail">{{ $product['detail'] }}</p>
                        <x-ui.pill :href="$product['link']['href']" tone="dark" class="product-card__link">{{ $product['link']['label'] }}</x-ui.pill>
                    </div>

                    {{-- TODO: the product's own screenshots. --}}
                    <div class="product-card__visual" aria-hidden="true">
                        <x-site.project-interface :variant="$product['variant']" />
                    </div>
                </div>
                </div>
            </article>
        @endforeach
    </div>
</section>
@else
<section id="produsele-noastre" class="own-products" aria-labelledby="own-products-title">
    <div class="own-products__inner">
        <h2 id="own-products-title" class="own-products__eyebrow">Produsele noastre</h2>

        <div class="own-products__list">
            @foreach ($listed as $product)
                <article class="own-product" aria-labelledby="own-product-{{ $loop->iteration }}">
                    <h3 id="own-product-{{ $loop->iteration }}" class="own-product__name">{{ $product['name'] }}</h3>
                    <p class="own-product__description">{{ $product['for'] }}</p>
                    <p class="own-product__detail">{{ $product['detail'] }}</p>
                    <div class="own-product__foot">
                        <x-ui.arrow-link :href="$product['link']['href']" class="own-product__link">{{ $product['link']['label'] }}</x-ui.arrow-link>
                    </div>
                </article>
            @endforeach
        </div>

    </div>
</section>
@endif

{{--
    Section 6, "Produsele noastre": Webis's own products, by name — not as
    portfolio. The second product's
    name is still to be decided (bracketed until it is).
--}}
@php
    $products = [
        [
            'name' => 'SmileSoft',
            'for' => 'Pentru clinici stomatologice.',
            'detail' => 'Software, echipamente și aplicație pentru pacienți.',
            'link' => ['label' => 'smilesoft.ro', 'href' => 'https://smilesoft.ro'],
        ],
        [
            'name' => '[Numele produsului]',
            'for' => 'Pentru universități.',
            'detail' => 'Administrarea proiectelor, a finanțărilor și a raportărilor.',
            'link' => ['label' => 'Află mai multe', 'href' => '/produse'],
        ],
    ];
@endphp

<section id="produsele-noastre" class="own-products" aria-labelledby="own-products-title">
    <div class="own-products__inner">
        <h2 id="own-products-title" class="own-products__eyebrow">Produsele noastre</h2>

        <div class="own-products__list">
            @foreach ($products as $product)
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

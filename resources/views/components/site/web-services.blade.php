{{-- Section 7: secondary web services, using the existing menu destinations. --}}
@php
    $services = [
        ['label' => 'Creare site de prezentare', 'href' => '/creare-site-de-prezentare'],
        ['label' => 'Creare magazin online', 'href' => '/magazin-online'],
    ];
@endphp

<section id="web-si-ecommerce" class="web-services" aria-labelledby="web-services-title">
    <div class="web-services__inner">
        <div class="web-services__heading">
            <p class="web-services__eyebrow">Web și e-commerce</p>
            <h2 id="web-services-title" class="web-services__title">Construim și site-uri și magazine online.</h2>
        </div>

        <ul class="web-services__list" role="list">
            @foreach ($services as $service)
                <li class="web-services__item">
                    <x-ui.arrow-link :href="$service['href']" class="web-services__link">{{ $service['label'] }}</x-ui.arrow-link>
                </li>
            @endforeach
        </ul>
    </div>
</section>

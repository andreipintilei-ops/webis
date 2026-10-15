{{--
    "Folosit de": the logos of the clients that use one of Webis's products
    (Clienți in the admin: the client's products), for that product's page.
    The same clients and logos as /portofoliu's wall — but only ever logos
    here, never project cards. Nothing at all while no client with a logo
    uses it. css/site/portfolio.css.

    `product`: an App\Enums\Product, or its value ("rims").
--}}
@props(['product'])

@php
    use App\Enums\Product;
    use App\Models\Client;

    $product = $product instanceof Product ? $product : Product::from($product);

    $clients = Client::query()
        ->with('logo.media')
        ->whereJsonContains('products', $product->value)
        ->whereNotNull('logo_asset_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get();
@endphp

@if ($clients->isNotEmpty())
    <div {{ $attributes->class('used-by') }} data-reveal>
        <p class="used-by__label">Folosit de</p>
        <ul class="client-logos" aria-label="Clienți care folosesc {{ $product->label() }}">
            @foreach ($clients as $client)
                <li data-reveal-child><x-picture :asset="$client->logo" :alt="$client->name" sizes="160px" /></li>
            @endforeach
        </ul>
    </div>
@endif

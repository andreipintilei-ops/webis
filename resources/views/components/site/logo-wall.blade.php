{{--
    The clients' logos, on the home page after the stage and on
    /portofoliu under the list: a diamond of tiles
    (1·2·3·2·1, the columns a half tile apart) in an endless field of
    logos, the tiles around it faded (js/public/logo-wall.ts;
    css/site/logo-wall.css). It forms from its middle as it scrolls into
    view; dragging moves the field, and it settles with one client in the
    middle, like the rest. A click on a tile brings it to the middle; so do
    the arrow keys, the wall in focus.

    For now the few logos repeat. Without the script, or with reduced
    motion's first paint: the logos as a plain row, each once.

    `embedded`: inside another section (/portofoliu's list), so a <div>
    on that section's ground rather than a light chapter of its own.
--}}
@props(['embedded' => false])

@php
    // As the logo strip (components/logo-marquee): every client marked
    // "show in logos", in their admin order.
    $clients = \App\Models\Client::query()
        ->with('logo.media')
        ->where('show_in_logos', true)
        ->whereNotNull('logo_asset_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get()
        ->filter(fn ($client) => $client->logo?->file() !== null)
        ->values();

    // Optical balance, as in the strip: a long wordmark drawn shorter.
    $scale = fn ($asset) => $asset->width && $asset->height
        ? round(min(1, max(0.42, 1 / sqrt($asset->width / $asset->height))), 3)
        : 0.7;
@endphp

@if ($clients->isNotEmpty())
    @php($wrapper = $embedded ? 'div role="region"' : 'section id="clienti"')
    <{!! $wrapper !!} {{ $attributes->class(['logo-wall', 'chapter chapter--light' => ! $embedded, 'logo-wall--embedded' => $embedded]) }} aria-labelledby="logo-wall-title">
        <div class="chapter__inner">
            <header class="chapter__head logo-wall__head" data-reveal="head">
                <h2 id="logo-wall-title" class="chapter__title">Cine <span class="whitespace-nowrap">ne-a ales <x-ui.title-pill variant="reviews" /></span></h2>
                <x-site.google-rating tone="light" class="chapter-rating logo-wall__rating" />
            </header>

            <div class="logo-wall__stage" tabindex="0" aria-label="Clienții noștri (trage sau folosește săgețile)" data-logo-wall>
                {{-- The logos, each once: the script's source for the tiles. --}}
                <ul class="logo-wall__logos" aria-label="Clienți" data-logo-wall-logos>
                    @foreach ($clients as $client)
                        <li class="logo-wall__logo" style="--logo-scale: {{ $scale($client->logo) }}">
                            <x-picture :asset="$client->logo" :alt="$client->name" sizes="200px" />
                        </li>
                    @endforeach
                </ul>

                <div class="logo-wall__field" aria-hidden="true" data-logo-wall-field></div>
            </div>
        </div>
    </{{ $embedded ? 'div' : 'section' }}>
@endif

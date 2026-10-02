{{--
    An endless strip of client logos — every client marked "show in logos",
    in their admin order (css/site/marquee.css). Logos are drawn in one flat
    colour to suit the background: white for `tone` "dark", black for "light".

    The set is repeated until one half of the track is wider than a large
    screen, then the track holds two identical halves and slides by exactly
    one half, so the loop has no seam. Only the first set is announced to
    screen readers; the repeats are hidden from them.
--}}
@props(['tone' => 'light'])

@php
    $clients = \App\Models\Client::query()
        ->with('logo.media')
        ->where('show_in_logos', true)
        ->whereNotNull('logo_asset_id')
        ->orderBy('sort_order')
        ->orderBy('name')
        ->get()
        ->filter(fn ($client) => $client->logo?->file() !== null)
        ->values();

    // Enough copies per half that the strip never runs short on a wide screen.
    $copies = max(1, (int) ceil(14 / max(1, $clients->count())));

    // Optical balance: every logo covers about the same area, so a long
    // wordmark is drawn shorter than a square emblem (height ∝ 1/√ratio).
    $scale = fn ($asset) => $asset->width && $asset->height
        ? round(min(1.1, max(0.4, 1 / sqrt($asset->width / $asset->height))), 3)
        : 0.7;
@endphp

@if ($clients->isNotEmpty())
    <div {{ $attributes->class(['logo-marquee', 'logo-marquee--'.$tone]) }}>
        <div class="logo-marquee__track" style="--marquee-items: {{ $clients->count() * $copies }}">
            @foreach (range(1, $copies * 2) as $copy)
                <ul class="logo-marquee__set" @if ($copy > 1) aria-hidden="true" @else aria-label="Clienți" @endif>
                    @foreach ($clients as $client)
                        <li class="logo-marquee__item">
                            <x-picture
                                :asset="$client->logo"
                                :alt="$copy === 1 ? $client->name : ''"
                                sizes="240px"
                                class="logo-marquee__logo"
                                style="--logo-scale: {{ $scale($client->logo) }}"
                            />
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </div>
    </div>
@endif

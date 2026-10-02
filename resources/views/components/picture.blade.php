{{--
    A media-library image as the public site shows it: the WebP rendition with
    its responsive srcset, intrinsic width/height (no layout shift), alt text
    from the asset, lazy loading by default.

    `lcp`: the page's largest first-screen image — loaded eagerly at high
    priority and preloaded from <head> instead of lazily.
    `sizes`: how wide the image is shown, so the browser picks the right file.
--}}
@props(['asset' => null, 'sizes' => '100vw', 'lcp' => false, 'alt' => null])

@php
    $file = $asset?->file();
    $hasWeb = $file?->hasGeneratedConversion('web') ?? false;
    $src = $file ? ($hasWeb ? $file->getUrl('web') : $file->getUrl()) : null;
    // Until the queue has made the WebP versions, the original stands in.
    $srcset = $hasWeb ? $file->getSrcset('web') : '';
@endphp

@if ($src)
    @if ($lcp)
        @push('head')
            <link rel="preload" as="image" href="{{ $src }}" @if ($srcset) imagesrcset="{{ $srcset }}" imagesizes="{{ $sizes }}" @endif fetchpriority="high">
        @endpush
    @endif

    <img
        src="{{ $src }}"
        @if ($srcset) srcset="{{ $srcset }}" sizes="{{ $sizes }}" @endif
        @if ($asset->width && $asset->height) width="{{ $asset->width }}" height="{{ $asset->height }}" @endif
        alt="{{ $alt ?? $asset->alt ?? '' }}"
        @if ($lcp) fetchpriority="high" @else loading="lazy" @endif
        decoding="async"
        {{ $attributes }}
    >
@endif

{{--
    Gallery block (App\Blocks\GalleryBlock): media-library images in a grid
    of two, three or four columns (`columns`; two on phones, one for a
    two-column gallery), under the numbered section heading when there is
    one, on the light chapter's lavender; css/site/block-gallery.css.

    Each image in the site's card corner (20px), cropped to one shape so
    the rows line up, through components/picture (srcset, width/height,
    alt from the asset, lazy); its caption, if any, a mono line under it.
    Images whose asset has gone are left out.
--}}
@php
    $columns = in_array((int) ($data['columns'] ?? 3), [2, 3, 4], true) ? (int) $data['columns'] : 3;
    $images = collect($data['images'] ?? [])
        ->filter(fn ($image) => is_array($image) && isset($image['asset_id']))
        ->map(fn (array $image) => ['asset' => $assets->get($image['asset_id']), 'caption' => trim((string) ($image['caption'] ?? ''))])
        ->filter(fn (array $image) => $image['asset']?->file() !== null)
        ->values();
    $heading = trim((string) ($data['heading'] ?? ''));

    // How wide each image is shown, so the browser picks the right file.
    $sizes = match ($columns) {
        2 => '(min-width: 768px) 50vw, 100vw',
        3 => '(min-width: 1024px) 33vw, 50vw',
        default => '(min-width: 1024px) 25vw, 50vw',
    };
@endphp

@if ($images->isNotEmpty())
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-gallery" @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif data-reveal-pace="fast">
        <div class="chapter__inner">
            @if ($heading !== '')
                @php $number = isset($sectionNumber) ? $sectionNumber() : ''; @endphp
                <x-ui.section-head :number="$number" :title="$heading" :id="$block['id'].'-title'" />
            @endif

            <ul class="block-gallery__grid block-gallery__grid--{{ $columns }}" data-reveal>
                @foreach ($images as $image)
                    <li data-reveal-child>
                        <figure class="block-gallery__item">
                            <div class="block-gallery__media">
                                <x-picture :asset="$image['asset']" :sizes="$sizes" />
                            </div>
                            @if ($image['caption'] !== '')
                                <figcaption class="block-gallery__caption">{{ $image['caption'] }}</figcaption>
                            @endif
                        </figure>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif

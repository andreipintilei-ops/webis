{{--
    Image-and-text block (App\Blocks\ImageTextBlock): an image beside a short
    passage, on the light chapter's lavender; css/site/block-image-text.css.

    From 1024px the two share the 12-column grid — the image six columns,
    the text five, a column between them — the image on the side
    `image_position` asks for; below that the image comes first. Without
    its image (deleted since) the text stands alone.

    The text column opens like the site's section heading (components/ui/
    section-head): the number over a hairline, then the title in the
    display face, sized down for half the width. The passage is rich text
    (components/rich-text), the button an arrow link. The image keeps its
    own shape, in the card corner, through components/picture.
--}}
@php
    $image = isset($data['image_asset_id']) ? $assets->get($data['image_asset_id']) : null;
    $image = $image?->file() !== null ? $image : null;
    $html = trim((string) ($data['content_html'] ?? ''));
    $heading = trim((string) ($data['heading'] ?? ''));
    $left = ($data['image_position'] ?? 'right') === 'left';
    $cta = $data['cta'] ?? null;
    $hasCta = is_array($cta) && ($cta['label'] ?? '') !== '' && ($cta['url'] ?? '') !== '';
@endphp

@if ($image || $html !== '' || $heading !== '')
    <section id="{{ $block['id'] }}" @class([
        'chapter chapter--light block-image-text',
        'block-image-text--left' => $left,
        'block-image-text--solo' => ! $image,
    ]) @if ($heading !== '') aria-labelledby="{{ $block['id'] }}-title" @endif data-reveal-pace="fast">
        <div class="block-image-text__grid">
            @if ($image)
                <div class="block-image-text__media" data-reveal>
                    <x-picture :asset="$image" sizes="(min-width: 1024px) 42rem, 100vw" :lcp="$isFirst ?? false" />
                </div>
            @endif

            <div class="block-image-text__text">
                @if ($heading !== '')
                    <header class="block-image-text__head" data-reveal="head">
                        <p class="section-head__number" aria-hidden="true">{{ isset($sectionNumber) ? $sectionNumber() : '' }}</p>
                        <h2 id="{{ $block['id'] }}-title" class="block-image-text__title">{{ $heading }}</h2>
                    </header>
                @endif

                @if ($html !== '')
                    <x-rich-text :html="$html" class="block-image-text__body" />
                @endif

                @if ($hasCta)
                    <div class="block-image-text__cta">
                        <x-ui.arrow-link :href="$cta['url']">{{ $cta['label'] }}</x-ui.arrow-link>
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif

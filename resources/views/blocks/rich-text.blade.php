{{--
    Rich-text block (App\Blocks\RichTextBlock): a passage of text — the body
    of most imported pages — on the light chapter's lavender.

    It renders `content_html`, the sanitised HTML Block::prepare() stored
    beside the Tiptap JSON, so nothing is parsed here beyond its h2s;
    <x-rich-text> puts in its images and css/site/block-rich-text.css sets
    its type. Its own headings (h2–h4) are the passage's, so it has no
    section heading.

    With three or more h2s, from 1024px the 12-column grid holds a contents
    rail in columns 1–3 („Cuprins”, a mono label over its hairline, then
    the h2s, each a link to its heading's id) and the text in 5–12, as the
    section head sets its title and intro apart. The rail stays in view
    while the text scrolls past and marks the section being read
    (js/public/rich-text-toc.ts). With fewer, or below 1024px, there is no
    rail: the text alone, centred in the content column. It keeps a
    reading width (46rem, about 70 characters) either way.
--}}
@php
    $html = trim((string) ($data['content_html'] ?? ''));

    // The h2s, with the ids RichText gave them on save.
    preg_match_all('~<h2 id="([^"]+)">(.*?)</h2>~s', $html, $headings, PREG_SET_ORDER);
    $toc = array_values(array_filter(array_map(fn (array $heading): array => [
        'id' => html_entity_decode($heading[1], ENT_QUOTES | ENT_HTML5),
        'text' => trim(html_entity_decode(strip_tags($heading[2]), ENT_QUOTES | ENT_HTML5)),
    ], $headings), fn (array $entry): bool => $entry['id'] !== '' && $entry['text'] !== ''));
    $rail = count($toc) >= 3;
@endphp

@if ($html !== '')
    <section id="{{ $block['id'] }}" @class([
        'chapter chapter--light block-rich-text',
        'block-rich-text--rail' => $rail,
        'block-rich-text--centred' => ! $rail,
    ]) @if ($rail) data-toc @endif>
        <div class="block-rich-text__grid">
            @if ($rail)
                <nav class="block-rich-text__rail" aria-labelledby="{{ $block['id'] }}-cuprins">
                    <div class="block-rich-text__toc" data-toc-pin>
                        <p id="{{ $block['id'] }}-cuprins" class="section-head__number block-rich-text__label">Cuprins</p>
                        <ol class="block-rich-text__links">
                            @foreach ($toc as $entry)
                                <li><a href="#{{ $entry['id'] }}" class="block-rich-text__link" data-toc-link>{{ $entry['text'] }}</a></li>
                            @endforeach
                        </ol>
                    </div>
                </nav>
            @endif

            <x-rich-text :html="$html" class="block-rich-text__body" :data-toc-text="$rail" />
        </div>
    </section>
@endif

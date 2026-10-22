{{--
    Rich-text block (App\Blocks\RichTextBlock): a passage of text — the body
    of most imported pages — on the light chapter's lavender, in one reading
    column at the left of the content column.

    It renders `content_html`, the sanitised HTML Block::prepare() stored
    beside the Tiptap JSON, so nothing is parsed here; <x-rich-text> puts in
    its images and css/site/block-rich-text.css sets its type. Its own
    headings (h2–h4) are the passage's, so it has no section heading.
--}}
@php
    $html = trim((string) ($data['content_html'] ?? ''));
@endphp

@if ($html !== '')
    <section id="{{ $block['id'] }}" class="chapter chapter--light block-rich-text">
        <div class="chapter__inner">
            <x-rich-text :html="$html" class="block-rich-text__body" />
        </div>
    </section>
@endif
